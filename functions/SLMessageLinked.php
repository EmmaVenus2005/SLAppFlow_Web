<?php

// Function to send a linkset message through an object (warning, key must contain something)
function SLMessageLinked($object, $linkset, $num, $message, $key) 
{

    // Required globals
    global $conn, $appid, $uuid, $name;

	// Retrieve FlowURL and FlowToken via NVGetValue
    $flowURL = NVGetSessionValue($object, 'FlowURL');
	if (empty($flowURL)) {
		error_log("SLMessageLinked: Failed to retrieve FlowURL.");
		return null;
	}

	$flowToken = NVGetSessionValue($object, 'FlowToken');
	if (empty($flowToken)) {
		error_log("SLMessageLinked: Failed to retrieve FlowToken.");
		return null;
	}

	// Prepare the command
    $command = 'message_linked|' . $flowToken . "|" . $linkset . "|" . $num . "|" . $message . "|" . $key;
    
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

    if ($response === false) {
        // Error during communication
        error_log("SLMessageLinked: cURL error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        // Non-200 HTTP response; interrupt the session
        error_log("SLMessageLinked: HTTP error code: $httpCode");
        return null;
    }

	// If message has been sent successfully, returns true
    return true;

}