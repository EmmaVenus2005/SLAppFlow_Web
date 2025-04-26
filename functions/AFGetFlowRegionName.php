<?php

// Function that returns name of the region
function AFGetFlowRegionName()
{

    // HTTP headers are used to get the region name
    global $headers;

    if (!isset($headers['X-SecondLife-Region'])) {
        return null;
    }

    if (preg_match('/^(.+?)\\s*\\(/', $headers['X-SecondLife-Region'], $matches)) {
        return trim($matches[1]);
    }

    return null;
    
}