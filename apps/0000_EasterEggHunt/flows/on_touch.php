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
            $foundEggs = NVGetSessionLists($session . "@" . $objregion, "FoundEgg");

            // If the egg was already found
            if (in_array($objid, $foundEggs)) 
            {
            
                // Egg already found message
                $dialog = "\n🥚🌷 Easter Egg Hunt 🌷🥚\n\n";
                $dialog .= "You already found this egg, good luck to find them all !";

            // This avatar didn't find that egg so far
            } else {

                // Creating additional elements for the list
                $elements = [
                    'hunterName' => $flowParams[0]  // Name of the hunter
                ];

                // Declares the avatar as a hunter in the database
                NVSetSessionList($objregion, "EggHunter", $session, json_encode($elements));

                // Creating additional elements for the list
                $elements = [
                    'foundOn' => date('c')  // Format ISO 8601, ex: 2025-04-13T18:45:00+02:00
                ];

                // Adds the egg to the found ones by that avatar
                NVSetSessionList($session . "@" . $objregion, "FoundEgg", $objid, json_encode($elements));

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

            // Options for dialog (all users)
            $options = ["Rules", "My Score", "Best Hunters"];

            // Checking if the user is the owner
            if ($uuid === $session)
            {

                // Adding the Admin option
                $options[] = "Admin";

            }

            // Send dialog to the avatar
            $answer = SLDialog($objid, $session, $dialog, "", [], $options, false, false);

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

        }

    // My Score from board menu selected
    } else if ($flowStep === "MAIN/SCORE")
    {

        // Dialog title
        $dialog = "\n🥚🌷 Easter Egg Hunt / My Score 🌷🥚\n\n";
        
        // Getting the list of found eggs
        $foundEggsList = NVGetSessionLists($session . "@" . $objregion, "FoundEgg");
        
        // Will contain the date of the first egg
        $firstFoundDate = null;

        // Looping through all found eggs to find the first date
        foreach ($foundEggsList as $currentEgg)
        {

            // Getting the database elements for the current egg
            $currentEggDate = NVGetSessionList($session . "@" . $objregion, "FoundEgg", $currentEgg);
            
            // Extracting the date of found for the current egg
            $foundOn = json_decode($currentEggDate, true)['foundOn'] ?? null;

            // If a foundOn parameter has been found (should not miss)
            if ($foundOn) 
            {
                
                // If first date never set or current egg found date older
                if ($firstFoundDate === null || strtotime($foundOn) < strtotime($firstFoundDate)) 
                {
                    
                    // Setting the older date as first
                    $firstFoundDate = $foundOn;

                }

            }

        }

        // Formatting the date in US format
        $firstFoundDateFormatted = (new DateTime($firstFoundDate))->format('Y-m-d H:i');

        // Counting the found eggs
        $foundEggs = count($foundEggsList);

        // Checking how many eggs are on the SIM
        $totalEggs = count(NVGetSessionLists($objregion, "EasterEgg"));

        // In case the owner didn't add any egg yet
        if ($totalEggs == 0)
        {

            $dialog .= "The game didn't start yet, stay tuned !";

        // All eggs have been found
        } elseif ($totalEggs - $foundEggs == 0)
        {

            $dialog .= "You found your first egg on " . $firstFoundDateFormatted . "\n";
            $dialog .= "You found all " . $totalEggs . " eggs.\n\n";
            $dialog .= "Congratulations !";

        } else {

            // Text depending of the number of found eggs
            switch($foundEggs)
            {

                case 0:
                    $dialog .= "You didn't find any egg so far.\n\n";
                    $dialog .= "Good luck, and have fun !";
                    break;

                case 1:
                    $dialog .= "You found your first egg on " . $firstFoundDateFormatted . "\n";
                    $dialog .= "You found 1 egg so far, and there are " . $totalEggs - $foundEggs . " more to find.\n\n";
                    $dialog .= "Good luck, and have fun !";
                    break;

                default:
                    $dialog .= "You found your first egg on " . $firstFoundDateFormatted . "\n";
                    $dialog .= "You found " . $foundEggs . " eggs so far, and there are " . $totalEggs - $foundEggs . " more to find.\n\n";
                    $dialog .= "Good luck, and have fun !";

            }

        }
        
        // Send dialog to the avatar
        $answer = SLDialog($objid, $session, $dialog, "", [], [], false, true);

        // If not BACK, timeout or HTTP error...
		if ($answer != "BACK" && $answer != NULL)
		{
        
            $flowStep = "EXIT";
        
        }

    // If the rules are selected
    } else if ($flowStep === "MAIN/RULES")
    {

        // Dialog
        $dialog = "\n🥚🌷 Easter Egg Hunt / Rules 🌷🥚\n\n";
        $dialog .= "The timer starts after you found the first egg, and ends when you found the last one. ";
        $dialog .= "The less time it took you to find them all, the better your score will be.\n\n";
        $dialog .= "Don't check private places, there are no eggs there !";

        // Send dialog to the avatar
        $answer = SLDialog($objid, $session, $dialog, "", [], [], false, true);

        // If not BACK, timeout or HTTP error...
		if ($answer != "BACK" && $answer != NULL)
		{
        
            $flowStep = "EXIT";
        
        }

    // Displays the best hunters
    } else if ($flowStep === "MAIN/BESTHUNTERS")
    {

        // Dialog title
        $dialog = "\n🥚🌷 Easter Egg Hunt / Best Hunters 🌷🥚\n\n";

        // Get the total number of eggs on the region
        $totalEggs = count(NVGetSessionLists($objregion, "EasterEgg"));

        // Get the list of all players who found at least one egg
        $hunterSessions = NVGetSessionLists($objregion, "EggHunter");

        // Will hold the stats for each hunter
        $hunterStats = [];

        // Loop through each hunter
        foreach ($hunterSessions as $hunter) {

            // Get display name of the hunter
            $hunterData = json_decode(NVGetSessionList($objregion, "EggHunter", $hunter), true);
            $hunterName = $hunterData['hunterName'] ?? $hunter;

            // Get the list of eggs found by the hunter
            $eggs = NVGetSessionLists($hunter . "@" . $objregion, "FoundEgg");

            // Initialize stats
            $count = count($eggs);
            $first = null;
            $last = null;

            // Loop through all found eggs to get first and last timestamps
            foreach ($eggs as $egg) 
            {

                // Get the found date for the current egg
                $eggData = json_decode(NVGetSessionList($hunter . "@" . $objregion, "FoundEgg", $egg), true);

                // Skip if no date
                if (!isset($eggData['foundOn'])) continue;

                // Parse date
                $t = strtotime($eggData['foundOn']);

                // Update first/last if needed
                if ($first === null || $t < $first) $first = $t;
                if ($last === null || $t > $last) $last = $t;

            }

            // Compute the duration between first and last egg
            $duration = ($last !== null && $first !== null) ? $last - $first : null;

            // Store hunter stats
            $hunterStats[] = [
                'name' => $hunterName,
                'count' => $count,
                'duration' => $duration
            ];

        }

        // Sort hunters :
        // 1. Those who found all eggs, by ascending duration
        // 2. Others by descending count, then ascending duration
        usort($hunterStats, function ($a, $b) use ($totalEggs) {
            if ($a['count'] === $b['count']) {
                return ($a['duration'] ?? PHP_INT_MAX) <=> ($b['duration'] ?? PHP_INT_MAX);
            }
            if ($a['count'] === $totalEggs) return -1;
            if ($b['count'] === $totalEggs) return 1;
            return $b['count'] <=> $a['count'];
        });

        // Build the leaderboard (only top 10)
        foreach (array_slice($hunterStats, 0, 10) as $h) {
            $dialog .= $h['name'] . " [" . $h['count'] . "/" . $totalEggs . "]";
            if ($h['duration'] !== null) {
                $dialog .= " " . gmdate("H:i:s", $h['duration']);
            }
            $dialog .= "\n";
        }

        // Show the leaderboard to the user
        $answer = SLDialog($objid, $session, $dialog, "", [], [], false, true);

        // Exit if no BACK or null
        if ($answer != "BACK" && $answer != NULL) {
            $flowStep = "EXIT";
        }


    // Displays admin options
    } else if ($flowStep === "MAIN/ADMIN")
    {

        // Dialog title and options inline
        $dialog  = "\n🥚🌷 Easter Egg Hunt / Admin 🌷🥚\n\n";
        $dialog .= "[Rename] : Change the name of one of the eggs from this game\n";
        $dialog .= "[Ping] : Check if there are deleted eggs in the game (allows you to remove them)\n";

        // Available options
        $options = ["Rename", "Ping"];

        // Show dialog without paging
        $answer = SLDialog($objid, $session, $dialog, "", [], $options, false, true);

        // Handle valid user choices only
        if ($answer != "BACK" && $answer != NULL) {

            if ($answer === "Rename") { $flowStep = "MAIN/ADMIN/RENAME"; } 
            else if ($answer === "Ping") { $flowStep = "MAIN/ADMIN/PING"; }
        }

    // Displays a paginated list of eggs for renaming
    } else if ($flowStep === "MAIN/ADMIN/RENAME")
    {

        // Header of the dialog
        $dialog = "\n🥚🌷 Easter Egg Hunt / Admin / Rename <<PAGE>> 🌷🥚\n\n";
        $dialog .= "Choose the egg you want to rename :\n";

        // Get the list of all eggs on the region
        $eggList = NVGetSessionLists($objregion, "EasterEgg");

        // Initialize arrays for choices and options
        $choices = [];
        $options = [];

        // Loop through eggs to build the selection list
        foreach ($eggList as $i => $eggId) 
        {

            // Retrieve the egg metadata
            $eggData = json_decode(NVGetSessionList($objregion, "EasterEgg", $eggId), true);

            // Extract the name or use a fallback if not set
            $eggName = $eggData['name'] ?? ("Egg #" . substr($eggId, 0, 8));

            // Add the formatted name to the list
            $choices[] = ($i + 1) . " - " . $eggName;
            $options[] = (string)($i + 1);
            
        }

        // Send the dialog to the user with paging and BACK support
        $answer = SLDialog($objid, $session, $dialog, "", $choices, $options, true, true);

        // Exit if valid response (other than BACK or timeout)
        if ($answer != "BACK" && $answer != NULL)
        {
            
            // Header of the dialog
            $dialog = "\n🥚🌷 Easter Egg Hunt / Admin / Rename 🌷🥚\n\n";
            $dialog .= "Please enter the new name of this egg (default is Unnamed) :\n";

            // Opening the textbox		
            $newName = SLTextBox($objid, $session, $dialog);

            // Exit if valid response (other than BACK or timeout)
            if ($newName != "BACK" && $newName != NULL) 
            {

                // Retrieve the selected egg ID from the list
                $eggIndex = intval($answer) - 1;
                $eggId = $eggList[$eggIndex] ?? null;

                if ($eggId !== null)
                {
                    
                    // Retrieve the existing metadata for the egg
                    $eggData = json_decode(NVGetSessionList($objregion, "EasterEgg", $eggId), true);

                    // Update the name field with the new value
                    $eggData['name'] = $newName;

                    // Save the updated metadata back to the database
                    NVSetSessionList($objregion, "EasterEgg", $eggId, json_encode($eggData));

                }

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