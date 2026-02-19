<?php

// Delete a session-specific list value
function NVDelSessionList($session, $listClass, $listName) 
{
    
    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid)) {
        error_log("NVDelSessionList: Required variables are not set.");
        return false;
    }

    // Not allowed when called from front-end without being sanitized
    if (AFIsUnsafe()) return false;

    $stmt = $conn->prepare("DELETE FROM List WHERE AppID = ? AND UserID = ? AND SessionID = ? AND Class = ? AND Name = ?");
    $stmt->bind_param("sssss", $appid, $uuid, $session, $listClass, $listName);

    $stmt->execute();
    $stmt->close();
    return true;

}