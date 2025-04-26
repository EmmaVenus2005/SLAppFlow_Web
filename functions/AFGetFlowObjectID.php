<?php

// Function that returns the UUID of the object that triggered the flow
function AFGetFlowObjectID()
{

    // Global variable to access the object ID
    global $objid;

    return $objid;
    
}
