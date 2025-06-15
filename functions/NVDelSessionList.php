<?php

// Delete a session-specific list value
function NVDelSessionList($session, $listClass, $listName) 
{
    
    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    $stmt = $conn->prepare("DELETE FROM List WHERE AppID = ? AND UserID = ? AND SessionID = ? AND Class = ? AND Name = ?");
    $stmt->bind_param("sssss", $appid, $uuid, $session, $listClass, $listName);

    $stmt->execute();
    $stmt->close();
    return true;

}