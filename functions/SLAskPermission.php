<?php

// Function to ask for a specific permission for a particular object
// (debit, attach, take_controls, trigger_animation, change_links, teleport)
function SLAskPermission($object, $permission) {
    
    // Globals
    global $conn, $appid, $uuid, $name;

    // Retrieve FlowURL and FlowToken via NVGetValue
    $flowURL = NVGetSessionValue($object, 'FlowURL');
    if (empty($flowURL)) {
        error_log("SLAskPermission: Failed to retrieve FlowURL.");
        return null;
    }

    $flowToken = NVGetSessionValue($object, 'FlowToken');
    if (empty($flowToken)) {
        error_log("SLAskPermission: Failed to retrieve FlowToken.");
        return null;
    }

    // Prepare the ask_permission command
    // Format: ask_permission|<token>|<permission>
    $command = 'ask_permission|' . $flowToken . '|' . $permission;

    // Send HTTPS POST request
    $ch = curl_init($flowURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $command);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/plain; charset=UTF-8'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    $response = curl_exec($ch);

    if ($response === false) {
        error_log("SLAskPermission: cURL error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("SLAskPermission: HTTP error code: $httpCode");
        return null;
    }

    // Parse the JSON response (example: {"debit":1,"attach":0,...})
    $json = json_decode($response, true);
    if (!is_array($json)) {
        error_log("SLAskPermission: Invalid JSON response: " . $response);
        return null;
    }

    // Return TRUE if the requested permission is present and true (1 or "true")
    if (
        isset($json[$permission]) &&
        ($json[$permission] === true || $json[$permission] === 1 || $json[$permission] === "true")
    ) {
        return true;
    }

    // Permission not granted
    return false;

}