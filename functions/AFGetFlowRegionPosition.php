<?php

// Function that returns the location of the region in the world as a string (x_y)
function AFGetFlowRegionPosition()
{

    // HTTP headers are used to get the region position
    global $headers;

    if (!isset($headers['X-SecondLife-Region'])) {
        return null;
    }

    if (preg_match('/\((\d+),\s*(\d+)\)/', $headers['X-SecondLife-Region'], $matches)) {
        return $matches[1] . '_' . $matches[2];
    }

    return null;
    
}