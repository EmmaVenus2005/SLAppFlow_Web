<?php

// Function to delete all lists of a given class
function NVDelLists($listClass) 
{
    
    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuide)) {
        error_log("NVDelLists: Required variables are not set.");
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM List WHERE AppID = ? AND UserID = ? AND Class = ?");
    if (!$stmt) {
        error_log("NVDelLists: Statement preparation failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("sss", $appid, $uuid, $listClass);

    if (!$stmt->execute()) {
        error_log("NVDelLists: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true;

}