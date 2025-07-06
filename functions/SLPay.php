<?php 

// Function allowing to send a payment through an object
function SLPay($object, $recipient, $amount) 
{

    // NEVER EXECUTE IN UNSAFE
    if (AFIsUnsafe()) {
        error_log("SLPay: Attempting to emit payment in unsafe mode.");
        return false;
    }

    // Retrieve FlowURL and FlowToken for the object
    $flowURL = NVGetSessionValue($object, 'FlowURL');
    if (empty($flowURL)) {
        error_log("SLPay: Failed to retrieve FlowURL.");
        return false;
    }

    $flowToken = NVGetSessionValue($object, 'FlowToken');
    if (empty($flowToken)) {
        error_log("SLPay: Failed to retrieve FlowToken.");
        return false;
    }

    // Strictly check amount is a positive integer (for client-side logic)
    if (!is_numeric($amount) || (int)$amount <= 0 || (string)(int)$amount !== (string)$amount) {
        error_log("SLPay: Invalid amount value '$amount'. Must be a positive integer.");
        return false;
    }

    // Prepare the command in the format expected by your gateway
    // Format: pay|<flowToken>|<recipient_uuid>|<amount>
    $command = 'pay|' . $flowToken . '|' . $recipient . '|' . $amount;

    // Prepare cURL
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

    // Execute the request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        error_log("SLPay: cURL error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }

    // Closing cURL
    curl_close($ch);

    // Success
    if ($httpCode === 200) {
        return true;
    }

    // Log and interpret HTTP response codes, if not success
    if ($httpCode === 401) {
        error_log("SLPay: Gateway does not have debit permission.");
    } elseif ($httpCode === 402) {
        error_log("SLPay: Insufficient funds in object local balance.");
    } elseif ($httpCode === 400) {
        error_log("SLPay: Bad request: " . $response);
    } else {
        error_log("SLPay: Unexpected HTTP code: $httpCode, response: $response");
    }

    // Returns false if somethign went wrong
    return false;

}