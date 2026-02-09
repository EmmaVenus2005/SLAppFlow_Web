<?php

/**
 * NVSearchLists
 *
 * Search lists by SQL LIKE pattern on Name.
 * Intended usage: prefix searches (e.g. "EA|EV_2026|%").
 *
 * @param string $class
 * @param string $nameLike   SQL LIKE pattern (recommended: "prefix%")
 * @param int    $limit      Safety limit (default 200)
 * @return array|false       Array of rows (Name, Elements, SessionID) or false
 */
function NVSearchLists($class, $nameLike, $limit = 200)
{
    
    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid)) {
        error_log("NVSearchLists: Required variables are not set.");
        return false;
    }

    // Not allowed when called from front-end without being sanitized
    if (AFIsUnsafe()) return false;

    // Avoids full table scans which could be triggered by empty or wildcard-only patterns
    if ($nameLike === "" || $nameLike === "%") return false;

    // Enforce a reasonable limit to prevent abuse
    $limit = max(1, min((int)$limit, 500));

    $sql = "
        SELECT SessionID, Name, Elements
        FROM `List`
        WHERE AppID = ?
          AND UserID = ?
          AND Class = ?
          AND Name LIKE ?
        ORDER BY Name
        LIMIT $limit
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("NVSearchLists: Statement preparation failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("ssss", $appid, $uuid, $class, $nameLike);

    if (!$stmt->execute()) {
        error_log("NVSearchLists: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();
    return $rows;

}