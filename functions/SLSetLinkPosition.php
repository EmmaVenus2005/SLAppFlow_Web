<?php

/**
 * Moves a linked prim in Second Life via the gateway.
 *
 * @param mixed $object   Identifier of the session/object in your system.
 * @param int   $linknum  Link number (1=root, 2+ for children).
 * @param float $x        New local X position.
 * @param float $y        New local Y position.
 * @param float $z        New local Z position.
 * @return bool           True on success, false on failure.
 */
function SLSetLinkPosition($object, $linknum, $x, $y, $z)
{

    // Doesn't work in unsafe mode
    if (AFIsUnsafe()) {
        error_log("SLSetLinkPosition: Can't be used in unsafe mode.");
        return false;
    }

    // Retrieve the gateway URL and Token for this object
    $flowURL = NVGetSessionValue($object, 'FlowURL');
    if (empty($flowURL)) {
        error_log("SLSetLinkPosition: Failed to retrieve FlowURL.");
        return false;
    }
    $flowToken = NVGetSessionValue($object, 'FlowToken');
    if (empty($flowToken)) {
        error_log("SLSetLinkPosition: Failed to retrieve FlowToken.");
        return false;
    }

    // Prepare the command for the gateway (fields separated by '|')
    $command = 'set_child_pos|' . $flowToken . '|' . intval($linknum) . '|' . floatval($x) . '|' . floatval($y) . '|' . floatval($z);

    // Set up the cURL request
    $ch = curl_init($flowURL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $command);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: text/plain; charset=UTF-8'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // Execute the HTTP request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Check the HTTP status code
    if ($httpCode !== 200) {
        error_log("SLSetLinkPosition: HTTP $httpCode, response: " . $response);
        return false;
    }

    // Success
    return true;

}
