<?php

/**
 * Contextual variables used during flow execution
 * -----------------------------------------------
 * 
 * These variables are automatically populated when a flow is triggered :
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
 * Specific variables for on_touch event :
 * 
 * $session      string   UUID of the avatar who initiated the interaction (toucher)
 * 
 * $flowParams   array    Additional parameters from the touch interaction, in order:
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

// Flow control variable
$flowStep = "MAIN";

// Main loop
while ($flowStep != "EXIT")
{
    
    if ($flowStep === "MAIN")
    {
        
        // If the touched object is an egg
        if ($appmode === "Egg")
        {

            // Gets the list of found eggs by toucher avatar from the database
            $foundEggs = NVGetSessionList($objregion, "FoundEggs", $session);

            // Separating egg UUIDs
            $foundEggsArray = explode("|", $foundEggs);

            // If the egg was already found
            if (in_array($objid, $foundEggsArray)) 
            {
            
                // Egg already found message
                $dialog = "\n🥚🌷 Easter Egg Hunt 🌷🥚\n\n";
                $dialog .= "You already found this egg, good luck to find them all !";

            // This avatar didn't find that egg so far
            } else {

                // The list is imploded again
                $foundEggs = implode("|", $foundEggsArray);

                // Adding the current egg to the found list
                $foundEggs .= "|" . $objid;

                // Writes the updated list to the server
                NVSetSessionList($objregion, "FoundEggs", $session, $foundEggs);

                // Debug
                SLOwnerSay($objid, $flowParams[0] . " found an egg !");

                // Egg already found message
                $dialog = "\n🥚🌷 Easter Egg Hunt 🌷🥚\n\n";
                $dialog .= "Congratulations, you found a new egg !\n";

            }

            // Options for dialog (only 'Close' needed)
            $options = ["Close"];

            // Send dialog to the avatar
            $answer = SLDialog($objid, $session, $dialog, "", [], $options, false, false);

            // Nothing more to do
            $flowStep = "EXIT";
            
            
        // Touching the board
        } else {    

            SLOwnerSay($objid, $flowParams[0] . " touched the board !");

            // Egg Hunt welcome message
            $dialog = "\n🥚🌷 Easter Egg Hunt 🌷🥚\n\n";
            $dialog .= "Welcome to the most egg-citing challenge of the season!\n\n";
            $dialog .= "Your mission is to hunt down all the eggs hidden across this SIM. ";
            $dialog .= "They're scattered everywhere — behind trees, under benches... Keep your eyes peeled !\n\n";
            $dialog .= "Walk around and click on the eggs to collect them.\n";
            $dialog .= "You can check how many eggs you still need by touching this board anytime.\n\n";
            $dialog .= "A sweet reward awaits the best egg-hunters.\n\n";
            $dialog .= "Have fun !\n";

            // Options for dialog (just OK for now)
            $options = ["Rules", "My Score", "Best Hunters", "Close"];

            // Send dialog to the avatar
            $answer = SLDialog($objid, $session, $dialog, "", [], $options, false, false);

            // If not BACK, timeout or HTTP error...
            if ($answer !== "BACK" && $answer !== null)
            {
            }

        }

    }

    // Manage BACK or null responses (timeout, errors, etc.)
    if (!isset($answer) || $answer === null) {
        $flowStep = "EXIT";
    } elseif ($answer === "BACK") {
        $flowStep = AFStepBack($flowStep);
    }

}

exit();