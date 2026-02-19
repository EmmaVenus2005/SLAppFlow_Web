<?php

// Function to delete a specific list based on $listClass and $listName
function NVDelList($listClass, $listName) 
{
    
    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid)) {
        error_log("NVDelList: Required variables are not set.");
        return false;
    }

    // Not allowed when called from front-end without being sanitized
    if (AFIsUnsafe()) return false;

    $stmt = $conn->prepare("DELETE FROM List WHERE AppID = ? AND UserID = ? AND Class = ? AND Name = ?");
    if (!$stmt) {
        error_log("NVDelList: Statement preparation failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("ssss", $appid, $uuid, $listClass, $listName);

    if (!$stmt->execute()) {
        error_log("NVDelList: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true;
    
}