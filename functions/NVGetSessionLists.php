<?php

// Retrieve all lists for a session and class
function NVGetSessionLists($session, $listClass) 
{
    
    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid)) {
        error_log("NVGetSessionLists: Required variables are not set.");
        return false;
    }

    $stmt = $conn->prepare("SELECT Name FROM List WHERE AppID = ? AND UserID = ? AND SessionID = ? AND Class = ?");
    if (!$stmt) {
        error_log("NVGetSessionLists: Statement preparation failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("ssss", $appid, $uuid, $session, $listClass);

    if (!$stmt->execute()) {
        error_log("NVGetSessionLists: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $listNames = [];
    while ($row = $result->fetch_assoc()) {
        $listNames[] = $row['Name'];
    }

    $stmt->close();
    return $listNames;
    
}