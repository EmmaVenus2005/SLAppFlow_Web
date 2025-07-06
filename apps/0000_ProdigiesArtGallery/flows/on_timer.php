<?php

/**
 * Contextual functions used during flow execution
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
 * Timer specific additional parameters are accessible using:
 *
 * - AFGetFlowSession()         → Timer key (unique identifier for the timer)
 * 
 */

//SLOwnerSay(AFGetFlowObjectID(), "on_timer.php: Flow triggered by timer with key " . AFGetFlowSession());
if (AFGetFlowAppMode() === "AuctionBoard" && AFGetFlowSession() === "lock_screen")
{

    // Sets the BID button behind the screen (hidden)
    SLSetLinkPosition(AFGetFlowObjectID(), 2, -2.353508,-0.107327,-1.615417);
   
    // Updates the URL of the board with the new token
    $texture = AFSendFlowMessage(AFGetAppID(), "Global", "LOCKSCREEN|" . AFGetFlowObjectID());

    // Applies the received texture to the board
    SLApplyTexture(AFGetFlowObjectID(), $texture);

    // Successfully updated the URL
    return true;

}