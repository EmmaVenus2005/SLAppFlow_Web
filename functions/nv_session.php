<?php

// Retrieve a session-specific parameter value
function NVGetSessionValue($session, $valueName) {
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid, $session)) {
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

// Set a session-specific parameter value
function NVSetSessionValue($session, $valueName, $value) {
    global $conn, $appid, $uuid, $name;

    if (!isset($conn, $appid, $uuid, $session)) {
        error_log("NVSetSessionValue: Required variables are not set.");
        return false;
    }

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

// Retrieve a session-specific list value
function NVGetSessionList($session, $listClass, $listName) {
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid, $session)) {
        error_log("NVGetSessionList: Required variables are not set.");
        return false;
    }

    $stmt = $conn->prepare("SELECT Elements FROM List WHERE AppID = ? AND UserID = ? AND SessionID = ? AND Class = ? AND Name = ?");
    if (!$stmt) {
        error_log("NVGetSessionList: Statement preparation failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("sssss", $appid, $uuid, $session, $listClass, $listName);

    if (!$stmt->execute()) {
        error_log("NVGetSessionList: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->bind_result($elements);
    $result = $stmt->fetch();
    $stmt->close();

    return $result ? $elements : null;
}

// Retrieve all lists for a session and class
function NVGetSessionLists($session, $listClass) {
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid, $session)) {
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

// Set a session-specific list value
function NVSetSessionList($session, $listClass, $listName, $listElements) {
    global $conn, $appid, $uuid, $name;

    if (!isset($conn, $appid, $uuid, $session)) {
        error_log("NVSetSessionList: Required variables are not set.");
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO List (Timestamp, AppID, UserID, UserName, SessionID, Class, Name, Elements) 
                            VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE Elements = VALUES(Elements)");
    $stmt->bind_param("sssssss", $appid, $uuid, $name, $session, $listClass, $listName, $listElements);

    if (!$stmt->execute()) {
        error_log("NVSetSessionList: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $stmt->close();
    return true;
}

// Delete a session-specific list value
function NVDelSessionList($session, $listClass, $listName) {
    global $conn, $appid, $uuid;

    $stmt = $conn->prepare("DELETE FROM List WHERE AppID = ? AND UserID = ? AND SessionID = ? AND Class = ? AND Name = ?");
    $stmt->bind_param("sssss", $appid, $uuid, $session, $listClass, $listName);

    $stmt->execute();
    $stmt->close();
    return true;
}

// Delete all session-specific list values
function NVDelSessionLists($session, $listClass) {
    global $conn, $appid, $uuid;

    $stmt = $conn->prepare("DELETE FROM List WHERE AppID = ? AND UserID = ? AND SessionID = ? AND Class = ?");
    $stmt->bind_param("ssss", $appid, $uuid, $session, $listClass);

    $stmt->execute();
    $stmt->close();
    return true;
}
