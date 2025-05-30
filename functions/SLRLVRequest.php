<?php

function SLRLVRequest($object, $rlvCommands) 
{
    
    // Global database connection
    global $conn;

    // Return false if $rlvCommands is empty or not an array
    if (empty($rlvCommands) || !is_array($rlvCommands)) {
        return false;
    }

    // Remove "@" from each command if present
    $rlvCommands = array_map(function ($entry) {
        return ltrim($entry, '@');
    }, $rlvCommands);

    // Retrieve FlowURL and FlowToken via NVGetSessionValue
    $flowURL = NVGetSessionValue($object, 'FlowURL');
    if (empty($flowURL)) {
        error_log("SLRLVRequest: Failed to retrieve FlowURL for object [$object].");
        return null;
    }

    $flowToken = NVGetSessionValue($object, 'FlowToken');
    if (empty($flowToken)) {
        error_log("SLRLVRequest: Failed to retrieve FlowToken for object [$object].");
        return null;
    }

    $results = [];

    // Loop through each RLV command individually
    foreach ($rlvCommands as $key => $command) {

        // Prepare the request
        $postData = 'rlv_request|' . $flowToken . '|' . $command;

        // Initialize cURL
        $ch = curl_init($flowURL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/plain; charset=UTF-8'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        // Execute request
        $response = curl_exec($ch);

        if ($response === false) {
            error_log("SLRLVRequest: cURL error on command [$command]: " . curl_error($ch));
            $results[$key] = null;
            curl_close($ch);
            continue;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            error_log("SLRLVRequest: HTTP error $httpCode on command [$command]");
            $results[$key] = null;
            continue;
        }

        // Success: store response
        $results[$key] = $response;
    }

    return $results;
    
}