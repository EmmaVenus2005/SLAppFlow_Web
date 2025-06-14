<?php

/**
 * Contextual functions used during flow execution
 * -----------------------------------------------
 * 
 * - AFGetAppID()               → Application identifier (unique per app instance)
 * - AFGetFlowAppMode()         → Application mode (to distinguish objects of the same app)
 * - AFGetOwnerID()             → UUID of the avatar who owns the object
 * - AFGetOwnerName()           → Display name of the object owner
 * - AFGetFlowObjectID()        → UUID of the object that triggered the flow
 * - AFGetFlowObjectName()      → Display name of the object that triggered the flow
 * - AFGetFlowObjectPosition()  → Position (vector) of the object in the region
 * - AFGetFlowObjectRotation()  → Rotation (quaternion) of the object in the region
 * - AFGetFlowRegionPosition()  → Position (vector) of the region in the world
 * - AFGetFlowRegionName()      → Name of the region in the world
 * 
 * Touch-specific additional parameters are accessible using:
 *
 * - AFGetFlowSession()         → UUID of the avatar who touched the object
 * - AFGetFlowParam(index)      → Indexed array of touch data:
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
 * 
 */

// $flowTokens = NVEnumerateValues("FlowToken");

// foreach ($flowTokens as $token) {
//     // If the token is not empty, it means it has been set by the flow
//     if ($token !== "") {
//         // Display the token value in the owner's chat
//         SLOwnerSay(AFGetFlowObjectID(), "Token: " . $token["Value"]);
//     }
// }


//SLOwnerSay(AFGetFlowObjectID(), RLVGetCommandInfo("@clear")); 

$resp = AFSendFlowMessage(AFGetAppID(), AFGetOwnerID(), "test message from on_touch.php");
SLOwnerSay(AFGetFlowObjectID(), $resp); 

//SLOwnerSay(AFGetFlowObjectID(), AFGetAppID()); 

// $dresses = SLRLVRequest(AFGetFlowObjectID(), ["getinvworn:~wearings/Dresses=#"]);
// SLOwnerSay(AFGetFlowObjectID(), $dresses[0]); 


