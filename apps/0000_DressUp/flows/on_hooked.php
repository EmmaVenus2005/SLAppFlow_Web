<?php

/**
 * Contextual variables used during flow execution
 * -----------------------------------------------
 * These functiuons are automatically populated when a flow is triggered :
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
 * Specific functions for on_hooked event :
 * 
 * - AFGetFlowSession()   Not relevant in this case
 * 
 */

// Declare the HUD as last active object
// Will be used in case of triggering the 'Main" function from 
// on_message event, which doesn't know the object ID
NVSetValue("LastActiveHUD", AFGetFlowObjectID());

// Fetch the folders
FetchFolders(AFGetFlowObjectID());