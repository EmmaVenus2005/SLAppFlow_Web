<?php

// Function to set a value in the Parameter table based on $appid, $uuid, and $valueName
function NVSetValue($valueName, $value) {
    global $conn, $appid, $uuid, $name, $session;
    
    if (!isset($conn, $appid, $uuid, $name, $session)) {
        error_log("NVSetValue: Required variables are not set.");
        return false;
    }
    
    $stmt = $conn->prepare("INSERT INTO Parameter (AppID, UserID, UserName, SessionID, `Key`, `Value`)
                            VALUES (?, ?, ?, 'DefaultSession', ?, ?)
                            ON DUPLICATE KEY UPDATE `Value` = VALUES(`Value`)");
    if (!$stmt) {
        error_log("NVSetValue: Statement preparation failed: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("sssss", $appid, $uuid, $name, $valueName, $value);
    
    if (!$stmt->execute()) {
        error_log("NVSetValue: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }
    
    $stmt->close();
    return true;
}