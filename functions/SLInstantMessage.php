<?php

// Function to send an instant message
function SLInstantMessage($object, $recipient, $message) 
{
    
    // Retrieve FlowURL and FlowToken via NVGetSessionValue
    $flowURL = NVGetSessionValue($object, 'FlowURL');
    if (empty($flowURL)) {
        error_log("SLInstantMessage: Failed to retrieve FlowURL.");
        return null;
    }

    $flowToken = NVGetSessionValue($object, 'FlowToken');
    if (empty($flowToken)) {
        error_log("SLInstantMessage: Failed to retrieve FlowToken.");
        return null;
    }

    // Prepare the command
    // Format: instant_message|<FlowToken>|<recipient UUID>|<message>
    $command = 'instant_message|' . $flowToken . '|' . $recipient . '|' . $message;

    // Initialize cURL
    $ch = curl_init($flowURL);

    // Set cURL options
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $command);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/plain; charset=UTF-8'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);

    // Execute the request
    $response = curl_exec($ch);

    // Error during communication
    if ($response === false) {
        error_log("SLInstantMessage: cURL error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    // Getting the result and closing the cURL
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Non-200 HTTP response
    if ($httpCode !== 200) {
        error_log("SLInstantMessage: HTTP error code: $httpCode");
        return null;
    }

    // If the message was sent successfully, return true
    return true;

}