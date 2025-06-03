<?php

// Function to delete a specific list based on $listClass and $listName
function NVDelList($listClass, $listName) {
    global $conn, $appid, $uuid, $name, $session;

    if (!isset($conn, $appid, $uuid, $name, $session)) {
        error_log("NVDelList: Required variables are not set.");
        return false;
    }

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