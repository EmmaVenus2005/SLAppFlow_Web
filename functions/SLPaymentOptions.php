<?php

// Function that adjusts the payment options
// $default is the default amount in the custom amount field (-1 to hide it)
// $b1 to $b4 are the values of the buttons (-1 to hide)
function SLPaymentOptions($object, int $default, int $b1, int $b2, int $b3, int $b4) 
{
    
    // Retrieve FlowURL and FlowToken for the object
    $flowURL = NVGetSessionValue($object, 'FlowURL');
    $flowToken = NVGetSessionValue($object, 'FlowToken');

    // Checks if the object exists for the current owner
    if (empty($flowURL) || empty($flowToken)) {
        error_log("SLPaymentOptions: Missing FlowURL or FlowToken for object $object.");
        return false;
    }

    // Compose the request string
    $command = 'payment_options|' . $flowToken . '|' . $default . '|' . $b1 . '|' . $b2 . '|' . $b3 . '|' . $b4;

    // Send the command via HTTP POST
    $ch = curl_init($flowURL);
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
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Closing cURL
    curl_close($ch);

    // Return true if HTTP 200 OK
    return $httpCode === 200;

}