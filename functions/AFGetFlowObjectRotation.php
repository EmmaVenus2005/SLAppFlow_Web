<?php

// Function that returns the position of the object in the world
function AFGetFlowObjectRotation()
{

    // HTTP headers are used to get the object rotation
    global $headers;

    if (!isset($headers['X-SecondLife-Local-Rotation'])) {
        return null;
    }

    if (preg_match('/\(([-\d\.]+),\s*([-\d\.]+),\s*([-\d\.]+),\s*([-\d\.]+)\)/', $headers['X-SecondLife-Local-Rotation'], $matches)) {
        return [
            'rx' => (float)$matches[1],
            'ry' => (float)$matches[2],
            'rz' => (float)$matches[3],
            'rw' => (float)$matches[4]
        ];
    }

    return null;

}