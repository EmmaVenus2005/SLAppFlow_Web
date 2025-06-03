<?php

// Function that allows to update a list
function NVSetList($listClass, $listName, $listElements) {
    global $conn, $appid, $uuid, $name, $session;
    
    if (!isset($conn, $appid, $uuid, $name, $session)) {
        error_log("NVSetList: Required variables are not set.");
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO List (Timestamp, AppID, UserID, UserName, SessionID, Class, Name, Elements) 
                        VALUES (NOW(), ?, ?, ?, 'DefaultSession', ?, ?, ?)
                        ON DUPLICATE KEY UPDATE Elements = VALUES(Elements)");
    $stmt->bind_param("ssssss", $appid, $uuid, $name, $listClass, $listName, $listElements);

    if (!$stmt->execute()) {
        error_log("NVSetList: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $stmt->close();
    return true;

}