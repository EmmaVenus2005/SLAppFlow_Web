<?php

// Delete all session-specific list values
function NVDelSessionLists($session, $listClass) 
{

    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    $stmt = $conn->prepare("DELETE FROM List WHERE AppID = ? AND UserID = ? AND SessionID = ? AND Class = ?");
    $stmt->bind_param("ssss", $appid, $uuid, $session, $listClass);

    $stmt->execute();
    $stmt->close();
    return true;
    
}