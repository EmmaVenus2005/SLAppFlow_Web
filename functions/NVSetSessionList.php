<?php

// Set a session-specific list value
function NVSetSessionList($session, $listClass, $listName, $listElements) 
{
    
    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid)) {
        error_log("NVSetSessionList: Required variables are not set.");
        return false;
    }

    // Not allowed when called from front-end without being sanitized
    if (AFIsUnsafe()) return false;

    // Get the owner name using the dedicated function
    $name = AFGetOwnerName();

    $stmt = $conn->prepare("INSERT INTO List (Timestamp, AppID, UserID, UserName, SessionID, Class, Name, Elements) 
                            VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE Elements = VALUES(Elements)");
    $stmt->bind_param("sssssss", $appid, $uuid, $name, $session, $listClass, $listName, $listElements);

    if (!$stmt->execute()) {
        error_log("NVSetSessionList: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true;
    
}