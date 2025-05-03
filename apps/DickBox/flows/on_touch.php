<?php

/**
 * Contextual variables used during flow execution
 * -----------------------------------------------
 * These variables are automatically populated when a flow is triggered,
 * especially from a touch interaction in Second Life.
 *
 * $appid       string   Application identifier (unique per app instance)
 * $uuid        string   UUID of the avatar who owns the object (object owner)
 * $name        string   Display name of the object owner (avatar name)
 * $objid       string   UUID of the object that triggered the flow
 * $session     string   UUID of the avatar who initiated the interaction (toucher)
 * 
 * $flowParams  array    Additional parameters from the touch interaction, in order:
 *                      [0] = toucherName          (string)
 *                      [1] = toucherOwner UUID    (string)
 *                      [2] = toucherPos           (vector as string)
 *                      [3] = toucherRot           (rotation as string)
 *                      [4] = toucherType          (integer)
 *                      [5] = surfaceST            (vector as string)
 *                      [6] = surfaceUV            (vector as string)
 *                      [7] = touchedFace          (integer)
 *                      [8] = touchNormal          (vector as string)
 *                      [9] = touchBinormal        (vector as string)
 *                      [10] = touchPos            (vector as string)
 */

$dresses = SLRLVRequest(AFGetFlowObjectID(), ["getinvworn:~wearings/Dresses=#"]);
SLOwnerSay(AFGetFlowObjectID(), $dresses[0]); 

