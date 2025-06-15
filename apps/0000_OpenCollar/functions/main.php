<?php

function Main($p_obj_id, $p_session, $p_toucher_name)
{

    // Navigation variables
    $flowStep = "MAIN";

    while ($flowStep != "EXIT")
    {

        // Initial step
        if ($flowStep == "MAIN")
        {

            // Checking if the session is from wearer
            $isWearer = $p_session === AFGetOwnerID();

            // Adding dialog for everyone
            $dialog = "Emma's OpenCollar [0.90]\n\n";
            $dialog .= "[DressUp] : Open DressUp app to manage clothings\n\n";
           
            // Adding options for everyone
            $options = ["DressUp"];
            
            // Only added if the dialog session is the wearer
            if ($isWearer)
            {

                // Adding Config choice
                $dialog .= "[Config] : Configure the collar\n\n";
                $options[] = "Config";

            }

            // Sending the dialog to the avatar
            $answer = SLDialog($p_obj_id, $p_session, $dialog, "", [], $options, false, true);
            
            // If not BACK, timeout or HTTP error...
            if ($answer != "BACK" && $answer != NULL)
            {

                // If a forbidden category has been clicked, opens same dialog again
                if ($answer === "DressUp")
                {

                    // Opens DressUp app
                    AFSendFlowMessage("0000_DressUp", AFGetOwnerID(), "OPEN|" . $p_session . "|" . $p_toucher_name);

                    // Exits the collar dialog
                    $flowStep = "EXIT";

                }

            }

        }

        // Managing the 'BACK' option and when a dialug returns null (timeout or HTTP error)
        if ($answer === null) {	$flowStep = "EXIT";	}
        elseif ($answer === "BACK")	{ $flowStep = AFStepBack($flowStep); }

    }  

}