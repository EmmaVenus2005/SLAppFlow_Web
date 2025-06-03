<?php

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