<?php

// Set a session-specific parameter value
function NVSetSessionValue($session, $valueName, $value) 
{

    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid)) {
        error_log("NVSetSessionValue: Required variables are not set.");
        return false;
    }

    // Not allowed when called from front-end without being sanitized
    if (AFIsUnsafe()) return false;

    // Get the owner name using the dedicated function
    $name = AFGetOwnerName();

    $stmt = $conn->prepare("INSERT INTO Parameter (AppID, UserID, UserName, SessionID, `Key`, `Value`) 
                            VALUES (?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE `Value` = VALUES(`Value`)");
    if (!$stmt) {
        error_log("NVSetSessionValue: Statement preparation failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("ssssss", $appid, $uuid, $name, $session, $valueName, $value);

    if (!$stmt->execute()) {
        error_log("NVSetSessionValue: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true;
    
}