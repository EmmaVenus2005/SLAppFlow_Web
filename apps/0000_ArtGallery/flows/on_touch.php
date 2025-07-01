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
 * Touch-specific additional parameters are accessible using:
 *
 * - AFGetFlowSession()         → UUID of the avatar who touched the object
 * - AFGetFlowParameter(index)  → Indexed array of touch data:
 *      [0] = toucherName          (string)
 *      [1] = toucherOwner UUID    (string)
 *      [2] = toucherPos           (vector as string)
 *      [3] = toucherRot           (rotation as string)
 *      [4] = toucherType          (integer)
 *      [5] = surfaceST            (vector as string)
 *      [6] = surfaceUV            (vector as string)
 *      [7] = touchedFace          (integer)
 *      [8] = touchNormal          (vector as string)
 *      [9] = touchBinormal        (vector as string)
 *      [10] = touchPos            (vector as string)
 *      [11] = touchedLink         (integer)  (Gateway =<0.952)
 * 
 */

if (AFGetFlowAppMode() === "AuctionBoard")
{

    //Test
    //SLOwnerSay(AFGetFlowObjectID(), "The board has been touched at link number : " . AFGetFlowParameter(11));


    SLAddTimer(AFGetFlowObjectID(), "demo_2min", time() + 120);
    SLAddTimer(AFGetFlowObjectID(), "demo_3min", time() + 180);

    SLAddTimer(AFGetFlowObjectID(), "demo_2min", time() + 120);
   
    // Updates the URL of the board with the new token
    $message = AFSendFlowMessage(AFGetAppID(), "Global", "UPDATEURL|" . AFGetFlowObjectID());

    // Sending the message to the linked prims
    SLMessageLinked(AFGetFlowObjectID(), -1, 500, $message, "key");

    // Successfully updated the URL
    return true; 
   
}