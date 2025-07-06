<?php

// Returns infomation about a particular link of the object
function SLLinkInfo($object, $linknum) 
{

    // Retrieve FlowURL and FlowToken via NVGetValue
    $flowURL = NVGetSessionValue($object, 'FlowURL');
    if (empty($flowURL)) {
        error_log("SLLinkInfo: Failed to retrieve FlowURL.");
        return false;
    }

    $flowToken = NVGetSessionValue($object, 'FlowToken');
    if (empty($flowToken)) {
        error_log("SLLinkInfo: Failed to retrieve FlowToken.");
        return false;
    }

    // Prepare the link_info command
    $command = 'link_info|' . $flowToken . '|' . (int)$linknum;

    // Initialize cURL
    $ch = curl_init($flowURL);

    // Preparing the headers
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $command);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/plain; charset=UTF-8'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    // Executes the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Non-200 return status
    if ($httpCode !== 200 || empty($response)) {
        error_log("SLLinkInfo: HTTP $httpCode or empty response.");
        return false;
    }

    // Decode JSON into associative array
    $data = json_decode($response, true);

    // Not a valid JSON format
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("SLLinkInfo: JSON decode error - " . json_last_error_msg());
        return false;
    }

    // Returns the JSON payload
    return $data;

}