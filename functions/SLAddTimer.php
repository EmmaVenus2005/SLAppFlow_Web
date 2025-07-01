<?php

function SLAddTimer($object, $timerKey, $timestamp) 
{
    
    // Globals
    global $conn, $appid, $uuid, $name;

    // Retrieve FlowURL and FlowToken via NVGetSessionValue (mêmes helpers que SLAskPermission)
    $flowURL = NVGetSessionValue($object, 'FlowURL');
    if (empty($flowURL)) {
        error_log("SLAddTimer: Failed to retrieve FlowURL.");
        return false;
    }

    $flowToken = NVGetSessionValue($object, 'FlowToken');
    if (empty($flowToken)) {
        error_log("SLAddTimer: Failed to retrieve FlowToken.");
        return false;
    }

    // Prepare the add_timer command
    // Format: add_timer|<token>|<timerKey>|<timestamp>
    $command = 'add_timer|' . $flowToken . '|' . $timerKey . '|' . intval($timestamp);

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
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);

    if ($response === false) {
        error_log("SLAddTimer: cURL error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("SLAddTimer: HTTP error code: $httpCode | Response: $response");
        return false;
    }

    // Timer has been successfully added or updated
    return true;

}