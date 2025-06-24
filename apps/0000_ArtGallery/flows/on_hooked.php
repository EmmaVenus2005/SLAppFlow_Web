<?php

/**
 * Contextual variables used during flow execution
 * -----------------------------------------------
 *
 * - AFGetAppID()               → Application identifier (unique per app instance)
 * - AFGetOwnerID()             → UUID of the avatar who owns the object
 * - AFGetOwnerName()           → Display name of the object owner
 * - AFGetFlowAppMode()         → Application mode (to distinguish objects of the same app)
 * - AFGetFlowObjectID()        → UUID of the object that triggered the flow
 * - AFGetFlowObjectName()      → Display name of the object that triggered the flow
 * - AFGetFlowGatewayVersion()  → Version of the gateway (as a float)
 * - AFGetFlowObjectPosition()  → Position (vector) of the object in the region
 * - AFGetFlowObjectRotation()  → Rotation (quaternion) of the object in the region
 * - AFGetFlowRegionPosition()  → Position (vector) of the region in the world
 * - AFGetFlowRegionName()      → Name of the region in the world
 * 
 * Specific functions for on_hooked event :
 * 
 * - AFGetFlowSession()   Not relevant in this case
 * 
 */

// Creating a JSON with all the information
$data = [
    "OwnerID"               => AFGetOwnerID(),
    "OwnerName"             => AFGetOwnerName(),
    "FlowObjectID"          => AFGetFlowObjectID(),
    "FlowObjectName"        => AFGetFlowObjectName(),
    "FlowGatewayVersion"    => AFGetFlowGatewayVersion(),
    "FlowObjectPosition"    => AFGetFlowObjectPosition(),
    "FlowObjectRotation"    => AFGetFlowObjectRotation(),
    "FlowRegionPosition"    => AFGetFlowRegionPosition(),
    "FlowRegionName"        => AFGetFlowRegionName()
    ];

// Sending the message to the "Global" user
AFSendFlowMessage(AFGetAppID(), "Global", "REZZED|" . AFGetFlowAppMode() . "|" . json_encode($data));