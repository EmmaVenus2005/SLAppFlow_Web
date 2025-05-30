<?php

// Retrieve all parameter rows matching a specific key and current AppID
function NVEnumerateValues($key) {
    global $conn, $appid;

    if (!isset($conn, $appid)) {
        error_log("NVEnumerateValues: Required variables are not set.");
        return false;
    }

    $stmt = $conn->prepare("SELECT * FROM Parameter WHERE AppID = ? AND `Key` = ?");
    if (!$stmt) {
        error_log("NVEnumerateValues: Statement preparation failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("ss", $appid, $key);

    if (!$stmt->execute()) {
        error_log("NVEnumerateValues: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $rows = [];

    // Fetch each row as an associative array
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();

    // Will be an empty array if no match found
    return $rows;  
    
}