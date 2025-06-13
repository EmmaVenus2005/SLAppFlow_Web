<?php

function MainBoard($p_appConstants, $p_obj_id, $p_toucher)
{

    // Flow control variable
    $flowStep = "MAIN";

    // Main loop
    while ($flowStep != "EXIT")
    {
        
        if ($flowStep === "MAIN")
        {

            // Treasure Hunt welcome message
            $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
            $dialog .= "Your mission is to discover all the hidden " . $p_appConstants["TreasureNamePlurial"] . " scattered across this SIM. ";
            $dialog .= "They could be anywhere — behind rocks, inside caves, or even under water... Stay sharp and explore every corner!\n\n";
            $dialog .= "Walk around and click on the " . $p_appConstants["TreasureNamePlurial"] . " to collect them.";
            $dialog .= "You can check your progress anytime by touching this board.\n\n";
            $dialog .= "A grand reward awaits the most dedicated treasure hunters.\n\n";
            $dialog .= "Good luck and have fun!\n";

            // Options for dialog (all users)
            $options = ["Rules", "My Score", "Best Hunters"];

            // Checking if the user is the owner
            if (AFGetOwnerID() === $p_toucher)
            {

                // Adding the Admin option
                $options[] = "Admin";

            }

            // Send dialog to the avatar
            $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", [], $options, false, false);

            // Reads the answer
            switch ($answer) 
            {
            
                case "My Score"     :   $flowStep = "MAIN/SCORE"; break;
                case "Rules"        :   $flowStep = "MAIN/RULES"; break;
                case "Best Hunters" :   $flowStep = "MAIN/BESTHUNTERS"; break;
                case "Admin"        :   $flowStep = "MAIN/ADMIN"; break;
                
                // If no managed answer found, exits the flow
                default : $flowStep = "EXIT";
            
            }

        // My Score from board menu selected
        } else if ($flowStep === "MAIN/SCORE")
        {

            // Dialog title
            $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / My Score " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
            
            // Getting the list of found treasures
            $foundTreasuresList = NVGetSessionLists($p_toucher . "@" . AFGetFlowRegionPosition(), "FoundTreasure");
            
            // Will contain the date of the first treasure found
            $firstFoundDate = null;

            // Looping through all found treasures to find the first date
            foreach ($foundTreasuresList as $currentTreasure)
            {

                // Getting the database elements for the current treasure
                $currentTreasureData = NVGetSessionList($p_toucher . "@" . AFGetFlowRegionPosition(), "FoundTreasure", $currentTreasure);
                
                // Extracting the date of found for the current treasure
                $foundOn = json_decode($currentTreasureData, true)['foundOn'] ?? null;

                // If a foundOn parameter has been found (should not miss)
                if ($foundOn) 
                {
                    
                    // If first date never set or current treasure found date older
                    if ($firstFoundDate === null || strtotime($foundOn) < strtotime($firstFoundDate)) 
                    {
                        
                        // Setting the older date as first
                        $firstFoundDate = $foundOn;

                    }

                }

            }

            // Formatting the date in US format
            $firstFoundDateFormatted = (new DateTime($firstFoundDate))->format('Y-m-d H:i');

            // Counting the found treasures
            $foundTreasures = count($foundTreasuresList);

            // Checking how many treasures are on the SIM
            $totalTreasures = count(NVGetSessionLists(AFGetFlowRegionPosition(), "Treasure"));

            // In case the owner didn't add any treasure yet
            if ($totalTreasures == 0)
            {

                $dialog .= "The game didn't start yet, stay tuned !";

            // All treasures have been found
            } elseif ($totalTreasures - $foundTreasures == 0)
            {

                $dialog .= "You found your first " . $p_appConstants["TreasureNameSingular"] . " on $firstFoundDateFormatted.\n";
                $dialog .= "You found all $totalTreasures " . $p_appConstants["TreasureNameSingular"] . ".\n\n";
                $dialog .= "Congratulations !";

            } else {

                // Text depending of the number of found treasures
                switch($foundTreasures)
                {

                    case 0:
                        $dialog .= "You didn't find any " . $p_appConstants["TreasureNameSingular"] . " so far.\n\n";
                        $dialog .= "Good luck, and have fun !";
                        break;

                    case 1:
                        $dialog .= "You found your first " . $p_appConstants["TreasureNameSingular"] . " on $firstFoundDateFormatted.\n";
                        $dialog .= "You found 1 " . $p_appConstants["TreasureNameSingular"] . " so far, and there are " . $totalTreasures - $foundTreasures . " more to find.\n\n";
                        $dialog .= "Good luck, and have fun !";
                        break;

                    default:
                        $dialog .= "You found your first " . $p_appConstants["TreasureNameSingular"] . " on $firstFoundDateFormatted.\n";
                        $dialog .= "You found $foundTreasures " . $p_appConstants["TreasureNamePlurial"] . " so far, and there are " . $totalTreasures - $foundTreasures . " more to find.\n\n";
                        $dialog .= "Good luck, and have fun !";

                }

            }
            
            // Send dialog to the avatar
            $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", [], [], false, true);

            // If not BACK, timeout or HTTP error...
            if ($answer != "BACK" && $answer != NULL)
            {
            
                $flowStep = "EXIT";
            
            }

        // If the rules are selected
        } else if ($flowStep === "MAIN/RULES")
        {

            // Dialog
            $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / Rules " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
            $dialog .= "The timer starts after you found the first " . $p_appConstants["TreasureNameSingular"] . ", and ends when you found the last one. ";
            $dialog .= "The less time it took you to find them all, the better your score will be.\n\n";
            $dialog .= "Don't check private places, there is no " . $p_appConstants["TreasureNameSingular"] . " there !";

            // Send dialog to the avatar
            $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", [], [], false, true);

            // If not BACK, timeout or HTTP error...
            if ($answer != "BACK" && $answer != NULL)
            {
            
                $flowStep = "EXIT";
            
            }

        // Displays the best hunters
        } else if ($flowStep === "MAIN/BESTHUNTERS")
        {

            // Dialog title
            $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / Best Hunters " . $p_appConstants["TitleSurroundingRight"] . "\n\n";

            // Get the total number of treasures on the region
            $totalTreasures = count(NVGetSessionLists(AFGetFlowRegionPosition(), "Treasure"));

            // Get the list of all players who found at least one treasure
            $hunterSessions = NVGetSessionLists(AFGetFlowRegionPosition(), "Hunter");

            // Will hold the stats for each hunter
            $hunterStats = [];

            // Loop through each hunter
            foreach ($hunterSessions as $hunter) {

                // Get display name of the hunter
                $hunterData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "Hunter", $hunter), true);
                $hunterName = $hunterData['hunterName'] ?? $hunter;

                // Get the list of treasures found by the hunter
                $treasures = NVGetSessionLists($hunter . "@" . AFGetFlowRegionPosition(), "FoundTreasure");

                // Initialize stats
                $count = count($treasures);
                $first = null;
                $last = null;

                // Loop through all found treasures to get first and last timestamps
                foreach ($treasures as $treasure) 
                {

                    // Get the found date for the current treasure
                    $treasureData = json_decode(NVGetSessionList($hunter . "@" . AFGetFlowRegionPosition(), "FoundTreasure", $treasure), true);

                    // Skip if no date
                    if (!isset($treasureData['foundOn'])) continue;

                    // Parse date
                    $t = strtotime($treasureData['foundOn']);

                    // Update first/last if needed
                    if ($first === null || $t < $first) $first = $t;
                    if ($last === null || $t > $last) $last = $t;

                }

                // Compute the duration between first and last treasure
                $duration = ($last !== null && $first !== null) ? $last - $first : null;

                // Store hunter stats
                $hunterStats[] = [
                    'name' => $hunterName,
                    'count' => $count,
                    'duration' => $duration
                ];

            }

            // Sort hunters :
            // 1. Those who found all treasures, by ascending duration
            // 2. Others by descending count, then ascending duration
            usort($hunterStats, function ($a, $b) use ($totalTreasures) {
                if ($a['count'] === $b['count']) {
                    return ($a['duration'] ?? PHP_INT_MAX) <=> ($b['duration'] ?? PHP_INT_MAX);
                }
                if ($a['count'] === $totalTreasures) return -1;
                if ($b['count'] === $totalTreasures) return 1;
                return $b['count'] <=> $a['count'];
            });

            // Build the leaderboard (only top 10)
            foreach (array_slice($hunterStats, 0, 10) as $h) {
                $dialog .= $h['name'] . " [" . $h['count'] . "/" . $totalTreasures . "]";
                if ($h['duration'] !== null) {
                    $dialog .= " " . gmdate("H:i:s", $h['duration']);
                }
                $dialog .= "\n";
            }

            // Show the leaderboard to the user
            $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", [], [], false, true);

            // Exit if no BACK or null
            if ($answer != "BACK" && $answer != NULL) {
                $flowStep = "EXIT";
            }


        // Displays admin options
        } else if ($flowStep === "MAIN/ADMIN")
        {

            // Dialog title and options
            $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / Admin " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
            $dialog .= "[Rename] : Change the name of one of the " . $p_appConstants["TreasureNamePlurial"] . " from this game\n";
            $dialog .= "[Ping] : Check if there are deleted " . $p_appConstants["TreasureNamePlurial"] . " in the game (allows you to remove them)\n";
            $dialog .= "[Eliminate] : Eliminates a player from the game\n";

            // Available options
            $options = ["Rename", "Ping", "Eliminate"];

            // Show dialog without paging
            $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", [], $options, false, true);

            // Handle valid user choices only
            if ($answer != "BACK" && $answer != NULL) 
            {

                if ($answer === "Rename") { $flowStep = "MAIN/ADMIN/RENAME"; } 
                else if ($answer === "Ping") { $flowStep = "MAIN/ADMIN/PING"; }
                else if ($answer === "Eliminate") { $flowStep = "MAIN/ADMIN/ELIMINATE"; }

            }

        // Displays a paginated list of treasures for renaming
        } else if ($flowStep === "MAIN/ADMIN/RENAME")
        {

            // Header of the dialog
            $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / Admin / Rename <<PAGE>> " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
            $dialog .= "Choose the " . $p_appConstants["TreasureNameSingular"] . " you want to rename :\n";

            // Get the list of all treasures on the region
            $treasuresList = NVGetSessionLists(AFGetFlowRegionPosition(), "Treasure");

            // Initialize arrays for choices and options
            $choices = [];
            $options = [];

            // Loop through treasures to build the selection list
            foreach ($treasuresList as $i => $treasureId) 
            {

                // Retrieve the treasure metadata
                $treasureData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "Treasure", $treasureId), true);

                // Extract the name or use a fallback if not set
                $treasureName = $treasureData['name'] ?? ("Treasure #" . substr($treasureId, 0, 8));

                // Add the formatted name to the list
                $choices[] = ($i + 1) . " - " . $treasureName;
                $options[] = (string)($i + 1);
                
            }

            // Send the dialog to the user with paging and BACK support
            $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", $choices, $options, true, true);

            // Exit if valid response (other than BACK or timeout)
            if ($answer != "BACK" && $answer != NULL)
            {
                
                // Header of the dialog
                $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / Admin / Rename " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
                $dialog .= "Please enter the new name of this " . $p_appConstants["TreasureNameSingular"] . " (default is Unnamed) :\n";

                // Opening the textbox		
                $newName = SLTextBox($p_obj_id, $p_toucher, $dialog);

                // Exit if valid response (other than BACK or timeout)
                if ($newName != "BACK" && $newName != NULL) 
                {

                    // Retrieve the selected treasure ID from the list
                    $treasureIndex = intval($answer) - 1;
                    $treasureId = $treasuresList[$treasureIndex] ?? null;

                    if ($treasureId !== null)
                    {
                        
                        // Retrieve the existing metadata for the treasure
                        $treasureData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "Treasure", $treasureId), true);

                        // Update the name field with the new value
                        $treasureData['name'] = $newName;

                        // Save the updated metadata back to the database
                        NVSetSessionList(AFGetFlowRegionPosition(), "Treasure", $treasureId, json_encode($treasureData));

                    }

                }

            }

        // Admin feature to ping all the treasures
        } else if ($flowStep === "MAIN/ADMIN/PING") {

            // Dialog header
            $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / Admin / Ping <<PAGE>> " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
            $dialog .= "Legend:\n■ Responding\n□ No response\n\nSelect " . $p_appConstants["TreasureArticle"] . " " . $p_appConstants["TreasureNameSingular"] . " to check its status :\n";

            // Get all treasures
            $treasuresList = NVGetSessionLists(AFGetFlowRegionPosition(), "Treasure");
            $pingResults = SLPingMulti($treasuresList);

            // Build choices
            $choices = [];
            $options = [];

            // Looping through the treasures
            foreach ($treasuresList as $i => $treasureId) 
            {
                
                $treasureData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "Treasure", $treasureId), true);
                $treasureName = $treasureData['name'] ?? ("Treasure #" . substr($treasureId, 0, 8));
                $status = !empty($pingResults[$treasureId]) ? "■" : "□";
                $choices[] = "$status " . ($i + 1) . " - " . $treasureName;
                $options[] = (string)($i + 1);

            }

            // Show dialog with paging
            $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", $choices, $options, true, true);

            // If valid response (other than BACK or timeout)
            if ($answer != "BACK" && $answer != NULL) {

                // Determine selected treasure
                $treasureIndex = intval($answer) - 1;
                $treasureId = $treasuresList[$treasureIndex] ?? null;

                // If the treasure exists (should ever happen)
                if ($treasureId !== null) 
                {

                    // True if responds to ping, false if not
                    $isOnline = $pingResults[$treasureId] ?? false;

                    // The selected treasure is still online
                    if ($isOnline) 
                    {

                        // Responding → cannot delete
                        $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / Admin / Ping " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
                        $dialog .= "This " . $p_appConstants["TreasureNameSingular"] . " is still responding to HTTP ping and cannot be deleted. ";
                        $dialog .= "If you want to remove it from the game, delete or derezz it inworld first.";

                        $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", [], [], false, true);

                    // The selected treasure doesn't respond to ping
                    } else {

                        // Unreachable → offer deletion
                        $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / Admin / Ping " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
                        $dialog .= "This " . $p_appConstants["TreasureNameSingular"] . " does not respond anymore. It was likely deleted or derezzed.\n\n";
                        $dialog .= "If you delete it:\n";
                        $dialog .= "- It will be removed from the list of " . $p_appConstants["TreasureNamePlurial"] . ".\n";
                        $dialog .= "- Hunters who only found this " . $p_appConstants["TreasureNameSingular"] . " will be removed as well.\n\n";
                        $dialog .= "Are you sure you want to delete it ?";

                        $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", [], ["Delete"], false, true);

                        if ($answer === "Delete") 
                        {

                            // 1. Remove from treasures list
                            NVDelSessionList(AFGetFlowRegionPosition(), "Treasure", $treasureId);

                            // 2. Remove from FoundTreasures of all hunters
                            $hunters = NVGetSessionLists(AFGetFlowRegionPosition(), "Hunter");

                            foreach ($hunters as $hunterSession) {
                                $foundTreasures = NVGetSessionLists($hunterSession . "@" . AFGetFlowRegionPosition(), "FoundTreasure");

                                if (in_array($treasureId, $foundTreasures)) {
                                    NVDelSessionList($hunterSession . "@" . AFGetFlowRegionPosition(), "FoundTreasure", $treasureId);
                                }

                                // Remove hunter if no treasures left (the first treasure being the start of his hunt)
                                $stillFound = NVGetSessionLists($hunterSession . "@" . AFGetFlowRegionPosition(), "FoundTreasure");
                                if (count($stillFound) === 0) {
                                    NVDelSessionList(AFGetFlowRegionPosition(), "Hunter", $hunterSession);
                                }

                            }

                        }

                    }

                }

            }

        // To eliminate a player from the game
        } else if ($flowStep === "MAIN/ADMIN/ELIMINATE") 
        {

            // Dialog header
            $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / Admin / Eliminate <<PAGE>> " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
            $dialog .= "Choose a player to eliminate from the hunt :\n";

            // Get all hunter sessions
            $hunterSessions = NVGetSessionLists(AFGetFlowRegionPosition(), "Hunter");

            // Build display list
            $choices = [];
            $options = [];

            foreach ($hunterSessions as $i => $hunterUUID) {
                $hunterData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "Hunter", $hunterUUID), true);
                $hunterName = $hunterData['hunterName'] ?? $hunterUUID;
                $choices[] = ($i + 1) . " - " . $hunterName;
                $options[] = (string)($i + 1);
            }

            // Show list with paging
            $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", $choices, $options, true, true);

            if ($answer != "BACK" && $answer != NULL) {

                $hunterIndex = intval($answer) - 1;
                $hunterUUID = $hunterSessions[$hunterIndex] ?? null;

                if ($hunterUUID !== null) {

                    $hunterData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "Hunter", $hunterUUID), true);
                    $hunterName = $hunterData['hunterName'] ?? $hunterUUID;

                    // Confirmation dialog
                    $dialog = "\n" . $p_appConstants["TitleSurroundingLeft"] . " " . $p_appConstants["GameName"] . " / Admin / Eliminate " . $p_appConstants["TitleSurroundingRight"] . "\n\n";
                    $dialog .= "Are you sure you want to eliminate ";
                    $dialog .= "$hunterName from the hunt ?\n\n";
                    $dialog .= "This action is irreversible !";

                    $answer = SLDialog($p_obj_id, $p_toucher, $dialog, "", [], ["Eliminate"], false, true);

                    if ($answer === "Eliminate") {

                        // 1. Remove the hunter from the Hunter list
                        NVDelSessionList(AFGetFlowRegionPosition(), "Hunter", $hunterUUID);

                        // 2. Remove all lists associated with this hunter session
                        NVDelSessionLists($hunterUUID . "@" . AFGetFlowRegionPosition(), "FoundTreasure");

                        // Optional feedback
                        //SLDialog($p_obj_id, $p_toucher, "\n" . $hunterName . " has been eliminated from the hunt.", "", [], [], false, true);

                    }

                }
                
            }

        }

        // Managing the 'BACK' option and when a dialug returns null (timeout or HTTP error)
        if ($answer === null) {	$flowStep = "EXIT";	}
        elseif ($answer === "BACK")	{ $flowStep = AFStepBack($flowStep); }
    
    }

}