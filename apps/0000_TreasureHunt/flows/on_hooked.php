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

// Only relevant when a egg is hooked
if ($appmode === "Treasure")
{

    // Header of the dialog
    $dialog = "\n✨ Treasure Hunt ✨\n\n";
    $dialog .= "Please enter the name of this treasure (default is Unnamed) :\n";

    // Checks if data already exist
    $entryExists = NVGetSessionList(AFGetFlowRegionPosition(), "Treasure", AFGetFlowObjectID());

    // If didn't exist, the egg has been rezzed
    if ($entryExists == "")
    {

        // Opening the textbox		
        $answer = SLTextBox($objid, $uuid, $dialog);

        // Creating additional elements for the list
        $elements = [
            'addedOn' => date('c'),  // Format ISO 8601, ex: 2025-04-13T18:45:00+02:00
            'posX'    => AFGetFlowObjectPosition()['x'],
            'posY'    => AFGetFlowObjectPosition()['y'],
            'posZ'    => AFGetFlowObjectPosition()['z'],
            'name'    => $answer ?? 'Unnamed'
        ];

        // The egg registers itself in the database
        NVSetSessionList(AFGetFlowRegionPosition(), "Treasure", AFGetFlowObjectID(), json_encode($elements));

        // Console output to ensure the egg has been added
        SLOwnerSay(AFGetFlowObjectID(), "The treasure has been added to the game !");

    } 
    
}