<?php

// Retrieve a session-specific parameter value
function NVGetSessionValue($session, $valueName) 
{
    
    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid)) {
        error_log("NVGetSessionValue: Required variables are not set.");
        return false;
    }

    $stmt = $conn->prepare("SELECT `Value` FROM Parameter WHERE AppID = ? AND UserID = ? AND SessionID = ? AND `Key` = ? LIMIT 1");
    if (!$stmt) {
        error_log("NVGetSessionValue: Statement preparation failed: " . $conn->error);
        return null;
    }

    $stmt->bind_param("ssss", $appid, $uuid, $session, $valueName);

    if (!$stmt->execute()) {
        error_log("NVGetSessionValue: Execution failed: " . $stmt->error);
        $stmt->close();
        return null;
    }

    $stmt->bind_result($value);
    $result = $stmt->fetch();
    $stmt->close();

    return $result ? $value : null;
    
}