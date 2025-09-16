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

            // Adding dialog for trusted or "owners"
            $dialog = "Emma's OpenCollar [0.90]\n\n";
            $dialog .= "[DressUp] : Open DressUp app to manage clothings\n";
            $dialog .= "[Leash] : Use the leash\n";
            $dialog .= "[Unleash] : Release her\n";

            // Adding options for trusted or "owners"
            $options = ["DressUp", "Leash", "Unleash"];
            
            // Only added if the dialog session is the wearer
            if ($isWearer)
            {

                // Adding Config choice
                $dialog .= "[Config] : Configure the collar\n\n";
                $options[] = "Config";

            }

            // Sending the dialog to the avatar
            $answer = SLDialog($p_obj_id, $p_session, $dialog, "", [], $options, false, false);
            
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

                } elseif ($answer === "Leash")
                {

                    // Activates the leash (script EOC_Leash in the collar)
                    // leash;<leasher>;<colour>;<length>;<texture>
                    SLMessageLinked($p_obj_id, -4, 20000, "leash;" . $p_session . ";<1.0, 0.75, 0.80>;3.0;Chain", 0);

                } elseif ($answer === "Unleash")
                {

                    // Disables the leash (script EOC_Leash in the collar)
                    SLMessageLinked($p_obj_id, -4, 20000, "unleash", 0);

                }
                
            }

        }

        // Managing the 'BACK' option and when a dialug returns null (timeout or HTTP error)
        if ($answer === null) {	$flowStep = "EXIT";	}
        elseif ($answer === "BACK")	{ $flowStep = AFStepBack($flowStep); }

    }  

}