<?php

// Function to set a value in the Parameter table based on $appid, $uuid, and $valueName
function NVSetValue($valueName, $value) 
{
    
    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;
    
    if (!isset($conn, $appid, $uuid)) {
        error_log("NVSetValue: Required variables are not set.");
        return false;
    }

    // Get the owner name using the dedicated function
    $name = AFGetOwnerName();
    
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