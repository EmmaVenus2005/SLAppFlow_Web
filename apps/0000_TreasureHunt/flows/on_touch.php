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
 * 
 */


// App constants allowing to customize the game (Egg Hunt, Treasure Hunt, etc.)
$appConstants["GameName"] = "Treasure Hunt";
$appConstants["TreasureArticle"] = "a";
$appConstants["TreasureNameSingular"] = "treasure";
$appConstants["TreasureNamePlurial"] = "treasures";
$appConstants["TitleSurroundingLeft"] = "✨";
$appConstants["TitleSurroundingRight"] = "✨";

// If the touched object is a treasure
if (AFGetFlowAppMode() === "Treasure") { MainTreasure($appConstants, AFGetFlowObjectID(), AFGetFlowSession()); }
else { MainBoard($appConstants, AFGetFlowObjectID(), AFGetFlowSession()); }