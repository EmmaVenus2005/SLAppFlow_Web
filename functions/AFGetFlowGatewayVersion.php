<?php

// Function that returns the gateway version as a float, or 0 if not castable
function AFGetFlowGatewayVersion()
{

    // HTTP headers are used to get the gateway version
    global $headers;

    if (!isset($headers['X-AFGatewayVersion'])) {
        return 0;
    }

    $value = $headers['X-AFGatewayVersion'];

    // Use is_numeric to check if value can be safely casted to float
    if (is_numeric($value)) {
        return (float)$value;
    }

    return 0;
    
}