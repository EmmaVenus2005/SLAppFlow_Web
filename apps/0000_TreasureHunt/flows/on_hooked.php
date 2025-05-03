<?php

/**
 * Contextual variables used during flow execution
 * -----------------------------------------------
 * These variables are automatically populated when a flow is triggered,
 * especially from a touch interaction in Second Life.
 *
 * $appid        string   Application identifier (unique per app instance)
 * $appmode      string   Application mode (optional, to distinguish multiple objects from a same app)
 * $uuid         string   UUID of the avatar who owns the object (object owner)
 * $name         string   Display name of the object owner (avatar name)
 * $objid        string   UUID of the object that triggered the flow
 * $objregion    string   In which region the object is located
 * $objx[y,z]    float    Position x, y and z of the object
 * $objrx[y,z,w] float    Rotation quaternion components of the object
 * 
 * Specific variables for on_hooked event :
 * 
 * $session      string   Not relevant in this case
 * 
 */

// Only relevant when a egg is hooked
if ($appmode === "Egg")
{

    // Header of the dialog
    $dialog = "\n🥚🌷 Easter Egg Hunt 🌷🥚\n\n";
    $dialog .= "Please enter the name of this egg (default is Unnamed) :\n";

    // Checks if data already exist
    $entryExists = NVGetSessionList($objregion, "EasterEgg", $objid);

    // If didn't exist, the egg has been rezzed
    if ($entryExists == "")
    {

        // Opening the textbox		
        $answer = SLTextBox($objid, $uuid, $dialog);

        // Creating additional elements for the list
        $elements = [
            'addedOn' => date('c'),  // Format ISO 8601, ex: 2025-04-13T18:45:00+02:00
            'posX'    => $objx,
            'posY'    => $objy,
            'posZ'    => $objz,
            'name'    => $answer ?? 'Unnamed'
        ];

        // The egg registers itself in the database
        NVSetSessionList($objregion, "EasterEgg", $objid, json_encode($elements));

        // Console output to ensure the egg has been added
        SLOwnerSay($objid, "The egg has been added to the game !");

    } 
    
}