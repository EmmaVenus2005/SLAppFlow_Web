<?php

// Function to retrieve a value from the Parameter table based on $appid, $uuid, and $valueName
function NVGetValue($valueName) {
    global $conn, $appid, $uuid, $name;
    
    if (!isset($conn, $appid, $uuid, $name)) {
        error_log("NVGetValue: Required variables are not set.");
        return false;
    }
    
    $stmt = $conn->prepare("SELECT `Value` FROM Parameter WHERE AppID = ? AND UserID = ? AND `Key` = ? LIMIT 1");
    if (!$stmt) {
        error_log("NVGetValue: Statement preparation failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("sss", $appid, $uuid, $valueName);
    
    if (!$stmt->execute()) {
        error_log("NVGetValue: Execution failed: " . $stmt->error);
        $stmt->close();
        return null;
    }
    
    $stmt->bind_result($value);
    $result = $stmt->fetch();
    $stmt->close();
    
    if ($result) {
        return $value;
    } else {
        // Value not found
        return null;
    }
}