<?php

/**
 * Contextual functions used during flow execution
 * -----------------------------------------------
 * 
 * - AFGetAppID()               → Application identifier (unique per app instance)
 * - AFGetOwnerID()             → UUID of the recipient avatar
 * - AFGetOwnerName()           → Display name of recipient avatar
 * - AFIsUnsafe()               → Returns true when the call is coming from WebControl (front-end)
 *
 * Message-specific additional parameters are accessible using:
 *
 * - AFGetSenderAppID()         → Application identifier of the sender (if applicable)
 * - AFGetSenderID()            → UUID of the avatar who sent the message
 * - AFGetSenderName()          → Display name of the avatar who sent the message
 * - AFGetMessage()             → The message content sent by the sender
 * 
 */

// When true, the call comes from WebControl and can be forged by the user.
// Front-end commands may be allowed, but:
// ONLY ALLOW ACTIONS THAT ARE SAFE BY DESIGN.
//if (AFIsUnsafe()) { return; }

// If the message is not from the same app, ignore it
// (If you handle messages from other apps, you can remove this check)
// BE CAREFUL: You don't control the sender app, carefully implement the logic to avoid security issues 
if (AFGetSenderAppID() !== AFGetAppID()) { return; }

// If the message is not from the same owner, ignore it
// (If you handle messages from other owners, you can remove this check)
//if (AFGetSenderID() !== AFGetOwnerID()) { return; }

// ... your code here ...

// Separating the message parts
$messageParts = explode("|", AFGetMessage());

// Lists the images from the board
// CALLED FROM THE FRONT-END MEDIA
// Sent to the owner of the board
// E. g. "LIST_IMAGES|<boardID>"
if ($messageParts[0] === "LIST_BOARD_IMAGES" && AFGetSenderID() === "Media")
{

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

    // Checks if the boardID exists, and belongs to the sender
    // ...

}

// Gets the image
// CALLED FROM THE FRONT-END MEDIA
// Sent to the owner of the board
// E. g. "GETIMAGE|<imageID>"
if ($messageParts[0] === "GET_BOARD_IMAGE" && AFGetSenderID() === "Media")
{

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

    // Checks if the image is displayed on this board
    // ...

    // Gets the image from File Service
    $imageData = FSDownload($imageID);

    // Returns the image data directly
    return base64_encode($imageData);
    
}

// Message sent from WebControl by me to create a project
// E.g. "ADD_PROJECT|<project_name>"
if ($messageParts[0] === "ADD_PROJECT" && AFGetSenderID() === AFGetOwnerID())
{

    // Sanitize thread to allow backend write
    AFSetSafe();

    // Project name (allow '|' by rejoining)
    $projectName = trim(implode("|", array_slice($messageParts, 1)));
    if ($projectName === "") {
        return;
    }

    // Create project using existing function
    $projectId = ProjectCreate($projectName);
    if (!$projectId) {
        return;
    }

    // Return minimal payload to front-end
    return [
        "project_id" => $projectId,
        "name" => $projectName
    ];

}

// Message sent from WebControl to list the projects from myself and those I am trusted
if ($messageParts[0] === "LIST_PROJECTS" && AFGetSenderID() === AFGetOwnerID())
{
    
    // Since called from WebControl, we have to sanitize the thread
    AFSetSafe();

    // Gets my projects
    $myProjects = ProjectsList();

    // Gets the list of trusters
    $trusters = json_decode(NVGetList("Trusters", "List"), true) ?: [];

    // Calls LIST_TRUSTER_PROJECTS for each truster and merges the results
    $trustedProjects = [];
    foreach ($trusters as $truster) {
        $response = AFSendFlowMessage(AFGetAppID(), $truster, "LIST_TRUSTER_PROJECTS");
  
        // LIST_TRUSTER_PROJECTS returns an array, but it's serialized, so must be decoded before merging
        if (is_string($response)) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $response = $decoded;
            }
        }

        if (is_array($response)) {
            $trustedProjects = array_merge($trustedProjects, $response);
        }

    }

    // Returns the combined list of projects
    return array_merge($myProjects, $trustedProjects);

}

// Message sent from LIST_PROJECTS in the context of a user who trusts me
if ($messageParts[0] === "LIST_TRUSTER_PROJECTS" && AFIsUnsafe() !== true)
{

    // My projects
    $myProjects = ProjectsList(); // expected: [ ["project_id"=>..., "name"=>...], ... ]
    if (!is_array($myProjects)) {
        return [];
    }

    // Filter projects where senderId is trusted
    $allowed = [];

    foreach ($myProjects as $p) {
        $projectId = is_array($p) ? ($p["project_id"] ?? "") : "";
        if (!is_string($projectId) || $projectId === "") continue;

        $trustedJson = NVGetList("Trusted", $projectId);
        $trustedList = json_decode($trustedJson, true) ?: [];

        if (is_array($trustedList) && in_array(AFGetSenderID(), $trustedList, true)) {
            $allowed[] = $p; // keep original structure (project_id + name)
        }
    }

    return $allowed;

}

// Message sent from WebControl by me to add a trusted user
// E. g. "ADD_TRUSTED|<trusted_user_id>|<project_id>"
if ($messageParts[0] === "ADD_TRUSTED" && AFGetSenderID() === AFGetOwnerID())
{

    // Since called from WebControl, we have to sanitize the thread
    AFSetSafe();

    // Gets the current list of trusted users for this project
    $trusted = json_decode(NVGetList("Trusted", $messageParts[2]), true) ?: [];

    // Only keep unique trusted users, avoid adding duplicates
    $trusted = array_values(array_unique(array_merge($trusted, [$messageParts[1]])));

    // Saves the updated list of trusted users for this project
    NVSetList("Trusted", $messageParts[2], json_encode($trusted));

    // Sends a message to the trusted user to add me as a truster
    AFSendFlowMessage(AFGetAppID(), $messageParts[1], "ADD_TRUSTER|".AFGetOwnerID());

    return;

}

// Message sent from ADD_TRUSTED in the context of a user who trusts me
// (To update my Trusters table with the new truster user)
// (Used to list the projects of the trusters in LIST_PROJECTS)
// E. g. "ADD_TRUSTER|<truster_user_id>"
if ($messageParts[0] === "ADD_TRUSTER" && AFIsUnsafe() !== true)
{

    // Reads the current list of trusters
    $trusters = json_decode(NVGetList("Trusters", "List"), true) ?: [];

    // Only keep unique trusters, avoid adding duplicates
    $trusters = array_values(array_unique(array_merge($trusters, [$messageParts[1]])));

    // Saves the updated list of trusters
    NVSetList("Trusters", "List", json_encode($trusters));

    return;

}

// Called from WebControl to set user preferences
// E. g. "SETPREFERENCES|<json>"
if ($messageParts[0] === "SETPREFERENCES" && AFGetSenderID() === AFGetOwnerID())
{
    
    // Admin verified; mark thread safe
    AFSetSafe();

    // Require JSON payload in $messageParts[1]
    if (!isset($messageParts[1])) return false;

    // Target user = sender by default
    $user = AFGetSenderID();

    // Decode incoming JSON as assoc array
    $incoming = json_decode($messageParts[1], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($incoming)) {
        return false;
    }

    // Load existing preferences for this user (if any)
    $existingJson = NVGetList("Preferences", $user);
    $existing = [];
    if ($existingJson !== null) {
        $existing = json_decode($existingJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($existing)) {
            // Malformed existing; ignore it
            $existing = [];
        }
    }

    // Start from existing
    $final = array_replace($existing, $incoming);

    // Persist as JSON (keep it human-readable for URLs/unicode)
    NVSetList("Preferences", $user, json_encode($final, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    // Aknowledge success
    return true;

}

// Called from WebControl to get user preferences
// E. g. "GETPREFERENCES"
// Returns the stored JSON for AFGetSenderID(), or "{}" if none.
if ($messageParts[0] === "GETPREFERENCES" && AFGetSenderID() === AFGetOwnerID())
{
    
    // Admin verified; mark thread safe
    AFSetSafe();

    // Target user = sender by default
    $user = AFGetSenderID();

    // Read stored JSON
    $prefsJson = NVGetList("Preferences", $user);

    // If none found, return empty object
    if ($prefsJson === null) {
        return "{}";
    }

    // Return stored JSON as-is
    return $prefsJson;

}

// Message sent from WebControl to create an artist
// E.g. "ADD_ARTIST|<project_uuid>|<json_info>"
if ($messageParts[0] === "ADD_ARTIST")
{

    // Called from WebControl → allow backend write
    AFSetSafe();

    // Args
    if (!isset($messageParts[1]) || !isset($messageParts[2])) return false;

    $projectId = trim($messageParts[1]);
    $jsonInfo  = $messageParts[2];

    if ($projectId === "" || $jsonInfo === "") return false;

    // Authorization for THIS project
    if (!IsTrustedForProject($projectId, AFGetSenderID())) return false;

    // Decode + validate payload
    $info = json_decode($jsonInfo, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($info)) return false;

    // Minimal required fields (keep it minimal so it's easy to extend later)
    $artistName = trim((string)($info["name"] ?? ""));
    if ($artistName === "") return false;

    // Create UUID
    $artistId = AFGenerateUUID();

    // Start from input JSON and enrich (extensible design)
    $final = $info;

    // Ensure canonical required fields (avoid storing weird whitespace)
    $final["name"] = $artistName;

    // Add server-controlled fields
    $final["created_at"] = time();
    $final["created_by"] = AFGetSenderID();

    // Store (session = projectId, class = Artist, name = artist UUID)
    NVSetSessionList(
        $projectId,
        "Artist",
        $artistId,
        json_encode($final, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    // Acknowledge success
    return true;

}

// List artists for a project (returns full JSON + injected artist_id key)
// E.g. "LIST_ARTISTS|<project_uuid>"
if ($messageParts[0] === "LIST_ARTISTS")
{

    // Called from WebControl → allow backend read
    AFSetSafe();

    // Args
    if (!isset($messageParts[1])) return [];

    $projectId = trim($messageParts[1]);
    if ($projectId === "") return [];

    // Authorization for THIS project
    if (!IsTrustedForProject($projectId, AFGetSenderID())) return [];

    // Enumerate all artists in this project session
    $artistIds = NVGetSessionLists($projectId, "Artist");
    if (!is_array($artistIds) || count($artistIds) === 0) return [];

    $out = [];

    foreach ($artistIds as $artistId) {

        if (!is_string($artistId) || $artistId === "") continue;

        $json = NVGetSessionList($projectId, "Artist", $artistId);
        if (!is_string($json) || $json === "") continue;

        $row = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($row)) continue;

        // Inject the key as artist_id (not stored redundantly in DB JSON)
        $row["artist_id"] = $artistId;

        // Return full row as-is (plus artist_id)
        $out[] = $row;
    }

    return $out;

}

// Message sent from WebControl to create a venue
// E.g. "ADD_VENUE|<project_uuid>|<json_info>"
if ($messageParts[0] === "ADD_VENUE")
{

    // Called from WebControl → allow backend write
    AFSetSafe();

    // Args
    if (!isset($messageParts[1]) || !isset($messageParts[2])) return false;

    $projectId = trim($messageParts[1]);
    $jsonInfo  = $messageParts[2];

    if ($projectId === "" || $jsonInfo === "") return false;

    // Authorization for THIS project
    if (!IsTrustedForProject($projectId, AFGetSenderID())) return false;

    // Decode + validate payload
    $info = json_decode($jsonInfo, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($info)) return false;

    // Minimal required fields (keep it minimal so it's easy to extend later)
    $venueName = trim((string)($info["name"] ?? ""));
    if ($venueName === "") return false;

    // Create UUID
    $venueId = AFGenerateUUID();

    // Start from input JSON and enrich (extensible design)
    $final = $info;

    // Ensure canonical required fields (avoid storing weird whitespace)
    $final["name"] = $venueName;

    // Add server-controlled fields
    $final["created_at"] = time();
    $final["created_by"] = AFGetSenderID();

    // Store (session = projectId, class = Venue, name = venue UUID)
    NVSetSessionList(
        $projectId,
        "Venue",
        $venueId,
        json_encode($final, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    // Acknowledge success
    return true;

}

// List venues for a project (returns full JSON + injected venue_id key)
// E.g. "LIST_VENUES|<project_uuid>"
if ($messageParts[0] === "LIST_VENUES")
{

    // Called from WebControl → allow backend read
    AFSetSafe();

    // Args
    if (!isset($messageParts[1])) return [];

    $projectId = trim($messageParts[1]);
    if ($projectId === "") return [];

    // Authorization for THIS project
    if (!IsTrustedForProject($projectId, AFGetSenderID())) return [];

    // Enumerate all venues in this project session
    $venueIds = NVGetSessionLists($projectId, "Venue");
    if (!is_array($venueIds) || count($venueIds) === 0) return [];

    $out = [];

    foreach ($venueIds as $venueId) {

        if (!is_string($venueId) || $venueId === "") continue;

        $json = NVGetSessionList($projectId, "Venue", $venueId);
        if (!is_string($json) || $json === "") continue;

        $row = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($row)) continue;

        // Inject the key as venue_id (not stored redundantly in DB JSON)
        $row["venue_id"] = $venueId;

        // Return full row as-is (plus venue_id)
        $out[] = $row;
    }

    return $out;

}

// Message sent from WebControl to create an event
// E.g. "ADD_EVENT|<project_uuid>|<json_info>|<base64_pic>"
if ($messageParts[0] === "ADD_EVENT")
{

    // Called from WebControl → allow backend write
    AFSetSafe();

    // Args
    if (!isset($messageParts[1]) || !isset($messageParts[2]) || !isset($messageParts[3])) return false;

    $projectId = trim($messageParts[1]);
    $jsonInfo  = $messageParts[2];
    $base64Pic = $messageParts[3]; // raw base64 (no data: prefix). Can be empty.

    if ($projectId === "" || $jsonInfo === "") return false;

    // Authorization for THIS project
    if (!IsTrustedForProject($projectId, AFGetSenderID())) return false;

    // Decode + validate payload
    $info = json_decode($jsonInfo, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($info)) return false;

    // Minimal required fields (keep it minimal so it's easy to extend later)
    $eventName = trim((string)($info["name"] ?? ""));
    $date = trim((string)($info["date"] ?? "")); // "YYYY-MM-DD"

    if ($eventName === "" || $date === "") return false;

    // Derive Event class from date: EventYYYYMM
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m)) return false;
    $class = "Event" . $m[1] . $m[2];

    // Create UUID
    $eventId = AFGenerateUUID();

    // Optional: store picture via FileService and keep file_id in event JSON
    $pictureFileId = "";
    if (is_string($base64Pic) && $base64Pic !== "") {

        $binaryData = base64_decode($base64Pic);

        // Minimal validation (same spirit as your UNICAT code)
        if ($binaryData && strlen($binaryData) >= 1000) {

            // Build filename (jpg/png detection by base64 prefix)
            if (str_starts_with($base64Pic, "/9j/")) {
                $filename = $class . ".jpg";
            } elseif (str_starts_with($base64Pic, "iVBOR")) {
                $filename = $class . ".png";
            }

            if ($filename !== "") {
                $fileId = FSUpload($filename, $binaryData);
                if ($fileId) $pictureFileId = $fileId;
            }
        }
    }

    // Start from input JSON and enrich (extensible design)
    $final = $info;

    // Ensure canonical required fields (avoid storing weird whitespace)
    $final["name"] = $eventName;
    $final["date"] = $date;

    // Add server-controlled fields
    $final["picture_id"] = $pictureFileId;   // empty string if none
    $final["created_at"] = time();
    $final["created_by"] = AFGetSenderID();

    // Store (session = projectId, class = EventYYYYMM, name = event UUID)
    NVSetSessionList(
        $projectId,
        $class,
        $eventId,
        json_encode($final, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    );

    return true;

}

// List events for a project month (returns full JSON + injected event_id key)
// E.g. "LIST_EVENTS|<project_uuid>|<year>|<month>"
if ($messageParts[0] === "LIST_EVENTS")
{

    // Called from WebControl → allow backend read
    AFSetSafe();

    // Args
    if (!isset($messageParts[1]) || !isset($messageParts[2]) || !isset($messageParts[3])) return [];

    $projectId = trim($messageParts[1]);
    $year      = trim($messageParts[2]); // "YYYY"
    $month     = trim($messageParts[3]); // "01".."12" (or "1".."12" tolerated below)

    if ($projectId === "" || $year === "" || $month === "") return [];

    // Authorization for THIS project
    if (!IsTrustedForProject($projectId, AFGetSenderID())) return [];

    // Normalize + validate year/month
    if (!preg_match('/^\d{4}$/', $year)) return [];
    if (!preg_match('/^\d{1,2}$/', $month)) return [];
    $month = str_pad($month, 2, "0", STR_PAD_LEFT);
    if ((int)$month < 1 || (int)$month > 12) return [];

    $class = "Event" . $year . $month;

    // Enumerate all events in this project session for that class
    $eventIds = NVGetSessionLists($projectId, $class);
    if (!is_array($eventIds) || count($eventIds) === 0) return [];

    $out = [];

    foreach ($eventIds as $eventId) {

        if (!is_string($eventId) || $eventId === "") continue;

        $json = NVGetSessionList($projectId, $class, $eventId);
        if (!is_string($json) || $json === "") continue;

        $row = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($row)) continue;

        // Inject the key as event_id (not stored redundantly in DB JSON)
        $row["event_id"] = $eventId;

        // Return full row as-is (plus event_id)
        $out[] = $row;
    }

    return $out;

}







// Always return explicitely, because if not, PHP returns 1
return;