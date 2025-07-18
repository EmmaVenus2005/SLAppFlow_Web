<?php

// Function that returns the name of the object that triggered the flow
function AFGetFlowObjectName()
{

    // HTTP headers are used to get the object name
    global $headers;

    if (!isset($headers['X-SecondLife-Object-Name'])) {
        return null;
    }

    return $headers['X-SecondLife-Object-Name'];
    
}