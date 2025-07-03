<?php

// Template JSON structure for applying a texture or media to a prim in Second Life

// Template array representing one or more visual instructions
// $templateArray = [
//     [
//         "link" => 0,            // Link number of the prim (0 = current)
//         "face" => 0,            // Face number (0–7)
//         "type" => "media",      // "texture" or "media"
//         "scale" => [1.0, 1.0],  // Repeat on U and V axes
//         "offset" => [0.0, 0.0], // Offset on U and V axes
//         "rotation" => 0.0,      // Rotation in radians

//         // Texture definition (if type == texture)
//         "texture" => [
//             "value" => "MyTexture",    // Name or UUID
//             "source" => "inventory"    // "inventory" or "uuid"
//         ],

//         // Media definition (if type == media)
//         "media" => [
//             "url" => "https://example.com",
//             "width" => 1024,
//             "height" => 768,
//             "auto_play" => true,
//             "auto_scale" => false,
//             "whitelist" => [
//                 "https://example.com",
//                 "https://cdn.example.com"
//             ],
//             "interact" => "owner", // "none", "owner", "group", "anyone"
//             "control" => "owner"   // "none", "owner", "group", "anyone"
//         ]
//     ]
// ];

// Function to send a visual configuration to a Second Life object
// using the "apply_texture" command and a JSON payload
function SLApplyTexture($object, $json)
{

    // Globals
    global $appid, $uuid;

    // Retrieve FlowURL and FlowToken via NVGetValue
    $flowURL = NVGetSessionValue($object, 'FlowURL');
    if (empty($flowURL)) {
        error_log("SLApplyTexture: Failed to retrieve FlowURL.");
        return false;
    }

    $flowToken = NVGetSessionValue($object, 'FlowToken');
    if (empty($flowToken)) {
        error_log("SLApplyTexture: Failed to retrieve FlowToken.");
        return false;
    }

    // Encode only if input is an array (not already JSON)
    $jsonData = is_string($json) ? $json : json_encode($json, JSON_UNESCAPED_SLASHES);
    if ($jsonData === false) {
        error_log("SLApplyTexture: Failed to encode JSON.");
        return false;
    }

    // Prepare the apply_texture command
    // Format: apply_texture|<token>|<json>
    $command = 'apply_texture|' . $flowToken . '|' . $jsonData;

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
        error_log("SLApplyTexture: cURL error: " . curl_error($ch));
        curl_close($ch);
        return false;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("SLApplyTexture: HTTP error code: $httpCode");
        return false;
    }

    // Successfully applied texture or media
    return true;

}