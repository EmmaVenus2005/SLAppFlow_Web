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

    // If the owner touches the board, offers some setting up options
    if (AFGetFlowSession() === AFGetOwnerID())
    {

        // Navigation variables
        $exit = false;

        // Loops until Done is selected, or timeout
        while ($exit === false)
        {

            // Gets the current style of the board (if already set)
            $style = AFSendFlowMessage(AFGetAppID(), "Global", "GETSTYLE|" . AFGetFlowObjectID());

            // Adding dialog for everyone
            $dialog = "\nThe Prodigies Art Gallery\n\n";
            $dialog .= "Current style : " . $style . "\n\n";
            $dialog .= "[Money] : Authorize the money transactions\n";
            $dialog .= "[Style] : Set-up the painting style\n";
            $dialog .= "[Done] : Unlocks the board\n";

            // Adding options for everyone
            $options = ["Money", "Style", "Done"];
            
            // Sending the dialog to the avatar
            $answer = SLDialog(AFGetFlowObjectID(), AFGetOwnerID(), $dialog, "", [], $options, false, false);

            if ($answer === "Money")
            {

                // Asking for "debit" permission (needed for the Auction Board)
                SLAskPermission(AFGetFlowObjectID(), "debit");

            } elseif ($answer === "Style")
            {

                // Header of the dialog
                $dialog = "\nThe Prodigies Art Gallery\n\n";
                $dialog .= "Please enter the painting style";

                // Opening the textbox		
                $answer = SLTextBox(AFGetFlowObjectID(), AFGetOwnerID(), $dialog);

                // If the owner gave an answer
                if ($answer !== null)
                {

                    // Sets the style in the "Global" user
                    AFSendFlowMessage(AFGetAppID(), "Global", "SETSTYLE|" . AFGetFlowObjectID() . "|" . $answer);

                }

            } else { $exit = true; }

        }

    }

    // Sets the BID button position in front of the screen (visible)
    SLSetLinkPosition(AFGetFlowObjectID(), 2, -1.682045,0.002674,-1.159348);
    
    // Updates the URL of the board with the new token
    $texture = AFSendFlowMessage(AFGetAppID(), "Global", "UPDATEURL|" . AFGetFlowObjectID());

    // Applies the received texture to the board
    SLApplyTexture(AFGetFlowObjectID(), $texture);

    // Successfully updated the URL
    return true;
   
}