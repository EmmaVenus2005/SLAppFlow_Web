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

// Nothing to do when the board comes online
if (AFGetFlowAppMode() === "AuctionBoard") 
{

    // Saves the board owner, so the message to reset the lock screen timer can
    // be sent when the front-end uses the selection arrows
    AFSendFlowMessage(AFGetAppID(), "Global", "BOARDOWNER|" . AFGetFlowObjectID() . "|" . AFGetOwnerID());

    // Updates the URL of the board with the new token
    //$texture = AFSendFlowMessage(AFGetAppID(), "Global", "UPDATEURL|" . AFGetFlowObjectID());

    // Applies the received texture to the board
    //SLApplyTexture(AFGetFlowObjectID(), $texture);

    // Successfully updated the URL
    return true; 

}

// If it's a painting, checking if the owner changed

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