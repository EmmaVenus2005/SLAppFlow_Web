<?php

// Function that allows to update a list
function NVSetList($listClass, $listName, $listElements) 
{
    
    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;
    
    if (!isset($conn, $appid, $uuid)) {
        error_log("NVSetList: Required variables are not set.");
        return false;
    }

    // Get the owner name using the dedicated function
    $name = AFGetOwnerName();

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