<?php

function MainTreasure($p_appConstants, $p_obj_id, $p_toucher)
{

    // Flow control variable
    $flowStep = "MAIN";

    // Main loop
    while ($flowStep != "EXIT")
    {
        
        if ($flowStep === "MAIN")
        {

            // Gets the list of found treasures by toucher avatar from the database
            $foundTreasures = NVGetSessionLists($p_toucher . "@" . AFGetFlowRegionPosition(), "FoundTreasure");

            // If the treasure was already found
            if (in_array($p_obj_id, $foundTreasures)) 
            {
            
                // Treasure already found message
                $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
                $dialog .= "You already found this " . $p_appConstants["TreasureNameSingular"] . ", good luck to find them all !";

            // This avatar didn't find that treasure so far
            } else {

                // Creating additional elements for the list
                $elements = [
                    'hunterName' => AFGetFlowParam()[0]  // Name of the hunter
                ];

                // Declares the avatar as a hunter in the database
                NVSetSessionList(AFGetFlowRegionPosition(), "Hunter", $p_toucher, json_encode($elements));

                // Creating additional elements for the list
                $elements = [
                    'foundOn' => date('c')  // Format ISO 8601, ex: 2025-04-13T18:45:00+02:00
                ];

                // Adds the treasure to the found ones by that avatar
                NVSetSessionList($p_toucher . "@" . AFGetFlowRegionPosition(), "FoundTreasure", $p_obj_id, json_encode($elements));

                // Retrieve the treasure metadata from the database (for debug only)
                $treasureMeta = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "Treasure", $p_obj_id), true);
                $treasureName = $treasureMeta['name'] ?? ("Treasure #" . substr($p_obj_id, 0, 8));

                // Debug
                SLRegionSayTo($p_obj_id, AFGetOwnerID(), 0, AFGetFlowParam()[0] . " found " . $p_appConstants["TreasureArticle"] . " " . $p_appConstants["TreasureNameSingular"] . " : " . $treasureName);

                // Explicit UUID for notifications, I will have to introdice an admin feature to add ppl to notif list
                SLRegionSayTo($p_obj_id, "ab866cf8-abbb-4e31-a109-72c75839dbf9", 0, AFGetFlowParam()[0] . " found " . $p_appConstants["TreasureArticle"] . " " . $p_appConstants["TreasureNameSingular"] . " : " . $treasureName);
                
                // New treasure found message
                $dialog .= "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
                $dialog .= "Congratulations, you found " . $p_appConstants["TreasureArticle"] . " new " . $p_appConstants["TreasureNameSingular"] . " !\n";

                // Total number of treasures on the region
                $totalTreasures = count(NVGetSessionLists(AFGetFlowRegionPosition(), "Treasure"));

                // List of treasures found by this avatar
                $foundTreasures = NVGetSessionLists($p_toucher . "@" . AFGetFlowRegionPosition(), "FoundTreasure");

                // Compute how many are left
                $treasuresLeft = $totalTreasures - count($foundTreasures);

                // Message
                $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
                $dialog .= "Congratulations, you found a new " . $p_appConstants["TreasureNameSingular"] . " !\n";

                if ($treasuresLeft === 0) {
                    $dialog .= "You found all the " . $p_appConstants["TreasureNamePlurial"] . " !\n";
                } elseif ($treasuresLeft === 1) {
                    $dialog .= "Just 1 " . $p_appConstants["TreasureNameSingular"] . " left to find, keep going !\n";
                } else {
                    $dialog .= "Only $treasuresLeft " . $p_appConstants["TreasureNamePlurial"] . " left to find !\n";
                }

            }

            // Options for dialog (only 'Close' needed)
            $options = ["Close"];

            // Send dialog to the avatar
            $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", [], $options, false, false);

            // Nothing more to do
            $flowStep = "EXIT";
        
        }

        // Managing the 'BACK' option and when a dialug returns null (timeout or HTTP error)
        if ($answer === null) {	$flowStep = "EXIT";	}
        elseif ($answer === "BACK")	{ $flowStep = AFStepBack($flowStep); }

    }

}