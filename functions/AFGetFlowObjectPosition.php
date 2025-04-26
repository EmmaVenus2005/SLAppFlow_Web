<?php

// Function that returns the position of the object in the world
function AFGetFlowObjectPosition()
{

    // HTTP headers are used to get the object position
    global $headers;

    if (!isset($headers['X-SecondLife-Local-Position'])) {
        return null;
    }

    if (preg_match('/\(([-\d\.]+),\s*([-\d\.]+),\s*([-\d\.]+)\)/', $headers['X-SecondLife-Local-Position'], $matches)) {
        return [
            'x' => (float)$matches[1],
            'y' => (float)$matches[2],
            'z' => (float)$matches[3]
        ];
    }

    return null;

}