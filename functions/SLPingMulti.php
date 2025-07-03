<?php

function SLPingMulti(array $objectIds): array {
    $results = [];
    $multiHandle = curl_multi_init();
    $handles = [];

    foreach ($objectIds as $objid) {
        
        // Get FlowURL and FlowToken
        $flowURL = NVGetSessionValue($objid, 'FlowURL');
        $flowToken = NVGetSessionValue($objid, 'FlowToken');

        // Skip if not set
        if (empty($flowURL) || empty($flowToken)) {
            $results[$objid] = false;
            continue;
        }

        // Build ping payload
        $command = "ping|" . $flowToken;

        // Initialize cURL
        $ch = curl_init($flowURL);

        // Preparing the headers
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $command);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain; charset=UTF-8']);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        // Add to multi handle
        curl_multi_add_handle($multiHandle, $ch);
        $handles[(int)$ch] = ['handle' => $ch, 'objid' => $objid];

    }

    // Executes all pings in parallel
    $running = null;
    do {
        curl_multi_exec($multiHandle, $running);
        curl_multi_select($multiHandle); // wait for activity
    } while ($running > 0);

    // Collect results
    foreach ($handles as $h) {
        $httpCode = curl_getinfo($h['handle'], CURLINFO_HTTP_CODE);
        $results[$h['objid']] = ($httpCode === 200);
        curl_multi_remove_handle($multiHandle, $h['handle']);
        curl_close($h['handle']);
    }

    curl_multi_close($multiHandle);
    return $results;

}