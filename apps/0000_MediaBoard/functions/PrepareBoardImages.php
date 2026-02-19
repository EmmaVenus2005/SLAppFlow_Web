<?php

/**
 * PrepareBoardImages
 *
 * Purpose:
 * - Generates the deterministic Board.slide node used by Media boards.
 * - Works with per-item validity windows (Option B):
 *     - from_ts  = start_ts - (days_ahead * 86400)
 *     - until_ts = start_ts + (show_mins_after_start * 60)
 *   so the board can "auto-hide" events X minutes after start, without needing
 *   a backend refresh at the exact time.
 *
 * Expected NV / DB structures (current system):
 *
 * 1) Board storage (NV "List"):
 *    - Class: "Board"
 *    - Name : <board_id>
 *    - JSON Elements example:
 *      {
 *        "last_hooked": 1770914697,
 *        "project_id": "fec9049a-fc43-45ee-a28a-d7ae04a69688",
 *        "config": {
 *          "all_artists": true,
 *          "artists": [],
 *          "all_venues": true,
 *          "venues": [],
 *          "days_ahead": 7,
 *          "show_mins_after_start": 0,
 *          "seconds_shown": 10
 *        },
 *        "slide": { ... }   // computed by this function
 *      }
 *
 * 2) Events storage (NV "SessionList"):
 *    - SessionID: <project_id>
 *    - Class    : "EventYYYYMM" derived from event date (YYYY-MM)
 *    - Name     : <event_id>
 *    - JSON Elements example:
 *      {
 *        "name": "Friday night party",
 *        "artist_id": "<uuid>",
 *        "venue_id": "<uuid>",
 *        "date": "2026-02-13",
 *        "start_time": "18:00",
 *        "end_time": "19:00",
 *        "picture_id": "<file_id from FSUpload or empty>",
 *        "created_at": 1770988925,
 *        "created_by": "<uuid>"
 *      }
 *
 * Output (stored in Board JSON under "slide"):
 *   {
 *     "prepared_at": <epoch>,
 *     "start_at": <epoch aligned>,
 *     "step_sec": <int>,
 *     "items": [
 *       { "image_id": "<file_id>", "from_ts": <epoch>, "until_ts": <epoch>, "start_ts": <epoch> },
 *       ...
 *     ]
 *   }
 *
 * Notes:
 * - items do NOT reveal project_id/event_id.
 * - start_ts is included ONLY to keep stable ordering and allow board-side debug
 *   if needed; it is not a sensitive identifier like event_id.
 *
 * Efficiency goals:
 * - Only scans the minimum month buckets needed for the configured horizon.
 * - No heavy processing; simple parsing + filtering + sorting.
 *
 * @param string $boardId
 * @return bool True on success, false on failure
 */
function PrepareBoardImages(string $boardId): bool
{
    // -------------------------------------------------------------------------
    // STEP 1) Load and validate Board JSON
    // -------------------------------------------------------------------------
    $boardJson = NVGetList("Board", $boardId);
    if (!is_string($boardJson) || $boardJson === "") return false;

    $board = json_decode($boardJson, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($board)) return false;

    // Board must be linked to a project (source of events)
    $projectId = trim((string)($board["project_id"] ?? ""));
    if ($projectId === "") return false;

    // -------------------------------------------------------------------------
    // STEP 2) Read config with defaults (node may be absent in early tests)
    // -------------------------------------------------------------------------
    $cfg = (isset($board["config"]) && is_array($board["config"])) ? $board["config"] : [];

    $allArtists = (bool)($cfg["all_artists"] ?? true);
    $artists    = (is_array($cfg["artists"] ?? null)) ? $cfg["artists"] : [];

    $allVenues  = (bool)($cfg["all_venues"] ?? true);
    $venues     = (is_array($cfg["venues"] ?? null)) ? $cfg["venues"] : [];

    $daysAhead  = (int)($cfg["days_ahead"] ?? 7);
    if ($daysAhead < 0) $daysAhead = 0;

    $minsAfterStart = (int)($cfg["show_mins_after_start"] ?? 0);
    if ($minsAfterStart < 0) $minsAfterStart = 0;

    $stepSec = (int)($cfg["seconds_shown"] ?? 10);
    if ($stepSec < 1) $stepSec = 10;

    // Normalize artist/venue allowlists into fast lookup maps (when enabled)
    $artistAllow = [];
    if (!$allArtists) {
        foreach ($artists as $a) {
            if (!is_string($a)) continue;
            $a = trim($a);
            if ($a !== "") $artistAllow[$a] = true;
        }
    }

    $venueAllow = [];
    if (!$allVenues) {
        foreach ($venues as $v) {
            if (!is_string($v)) continue;
            $v = trim($v);
            if ($v !== "") $venueAllow[$v] = true;
        }
    }

    // -------------------------------------------------------------------------
    // STEP 3) Determine scan horizon (months to read)
    // -------------------------------------------------------------------------
    // We only need to fetch events whose start_ts is within a reasonable horizon.
    // The board decides display using from_ts/until_ts, but we still must avoid
    // reading too many month buckets.
    //
    // Horizon choice:
    // - Include events up to now + daysAhead days (+ 1 day safety margin).
    //   This keeps data small and still practical with periodic refresh.
    $now = time();
    $horizonToTs = $now + ($daysAhead * 86400) + 86400; // +1 day safety

    // Determine the list of months between "now" and "horizonToTs" inclusive.
    // We use UTC to keep it deterministic.
    $dtStart = new DateTimeImmutable("@$now");
    $dtStart = $dtStart->setTimezone(new DateTimeZone("UTC"));
    $dtEnd = new DateTimeImmutable("@$horizonToTs");
    $dtEnd = $dtEnd->setTimezone(new DateTimeZone("UTC"));

    // Move dtStart to first day of its month at 00:00
    $dtCursor = $dtStart->setDate((int)$dtStart->format("Y"), (int)$dtStart->format("m"), 1)
                        ->setTime(0, 0, 0);

    // Move dtEnd to first day of its month at 00:00 (inclusive end condition)
    $dtEndMonth = $dtEnd->setDate((int)$dtEnd->format("Y"), (int)$dtEnd->format("m"), 1)
                        ->setTime(0, 0, 0);

    // -------------------------------------------------------------------------
    // STEP 4) Enumerate candidate events from NV buckets (EventYYYYMM)
    // -------------------------------------------------------------------------
    $items = [];

    while ($dtCursor <= $dtEndMonth) {

        $yyyymm = $dtCursor->format("Ym");        // e.g. "202602"
        $class  = "Event" . $yyyymm;              // e.g. "Event202602"

        // List IDs in this bucket
        $eventIds = NVGetSessionLists($projectId, $class);
        if (is_array($eventIds) && count($eventIds) > 0) {

            foreach ($eventIds as $eventId) {

                if (!is_string($eventId) || $eventId === "") continue;

                $evJson = NVGetSessionList($projectId, $class, $eventId);
                if (!is_string($evJson) || $evJson === "") continue;

                $ev = json_decode($evJson, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($ev)) continue;

                // Must have a picture_id to be used in slide
                $imageId = trim((string)($ev["picture_id"] ?? ""));
                if ($imageId === "") continue;

                // Filter by artists/venues (if configured)
                $artistId = trim((string)($ev["artist_id"] ?? ""));
                if (!$allArtists && $artistId !== "" && !isset($artistAllow[$artistId])) {
                    continue;
                }

                $venueId = trim((string)($ev["venue_id"] ?? ""));
                if (!$allVenues && $venueId !== "" && !isset($venueAllow[$venueId])) {
                    continue;
                }

                // Parse event start timestamp (UTC)
                $date = trim((string)($ev["date"] ?? ""));         // "YYYY-MM-DD"
                if ($date === "") continue;

                $startTime = trim((string)($ev["start_time"] ?? "")); // "HH:MM" (or empty)
                if ($startTime === "") $startTime = "00:00";

                // Minimal validation: avoid weird formats
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;
                if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $startTime)) continue;

                // Use DateTimeImmutable for deterministic UTC parsing
                $dtStr = $date . " " . substr($startTime, 0, 5) . ":00";
                $dtEv = DateTimeImmutable::createFromFormat("Y-m-d H:i:s", $dtStr, new DateTimeZone("UTC"));
                if (!$dtEv) continue;

                $startTs = (int)$dtEv->getTimestamp();

                // Apply horizon filter (avoid building huge lists)
                // Keep only events that start before our horizon.
                if ($startTs > $horizonToTs) continue;

                // Build per-item validity window:
                // - from_ts: event becomes eligible N days before start
                // - until_ts: event remains eligible X minutes after start
                $fromTs  = $startTs - ($daysAhead * 86400);
                $untilTs = $startTs + ($minsAfterStart * 60);

                $items[] = [
                    "image_id" => $imageId,
                    "from_ts"  => $fromTs,
                    "until_ts" => $untilTs,
                    "start_ts" => $startTs, // ordering only, not an ID
                ];
            }
        }

        // Next month
        $dtCursor = $dtCursor->modify("+1 month");
    }

    // -------------------------------------------------------------------------
    // STEP 5) Sort deterministically (start_ts ASC, tie-breaker image_id ASC)
    // -------------------------------------------------------------------------
    usort($items, function ($a, $b) {
        $sa = (int)($a["start_ts"] ?? 0);
        $sb = (int)($b["start_ts"] ?? 0);
        if ($sa !== $sb) return $sa <=> $sb;

        $ia = (string)($a["image_id"] ?? "");
        $ib = (string)($b["image_id"] ?? "");
        return strcmp($ia, $ib);
    });

    // -------------------------------------------------------------------------
    // STEP 6) Build slide node (minimal payload for the board)
    // -------------------------------------------------------------------------
    $preparedAt = time();

    // start_at: anchor for sync (aligned to step_sec so index math is stable)
    $startAt = $preparedAt - ($preparedAt % $stepSec);

    // Remove any fields you do not want to expose. We keep start_ts for ordering/debug.
    $slide = [
        "prepared_at" => $preparedAt,
        "start_at"    => $startAt,
        "step_sec"    => $stepSec,
        "items"       => $items,
    ];

    // -------------------------------------------------------------------------
    // STEP 7) Persist slide into Board JSON
    // -------------------------------------------------------------------------
    $board["slide"] = $slide;

    NVSetList("Board", $boardId, json_encode($board, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return true;
}