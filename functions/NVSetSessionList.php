<?php

// Set a session-specific list value
function NVSetSessionList($session, $listClass, $listName, $listElements) {
    global $conn, $appid, $uuid, $name;

    if (!isset($conn, $appid, $uuid, $session)) {
        error_log("NVSetSessionList: Required variables are not set.");
        return false;
    }

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