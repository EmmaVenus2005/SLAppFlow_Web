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
 * 
 */

// Flow control variable
$flowStep = "MAIN";

// Main loop
while ($flowStep != "EXIT")
{
    
    if ($flowStep === "MAIN")
    {
        
        // If the touched object is an egg
        if (AFGetFlowAppMode() === "Egg")
        {

            // Gets the list of found eggs by toucher avatar from the database
            $foundEggs = NVGetSessionLists(AFGetFlowSession() . "@" . AFGetFlowRegionPosition(), "FoundEgg");

            // If the egg was already found
            if (in_array(AFGetFlowObjectID(), $foundEggs)) 
            {
            
                // Egg already found message
                $dialog = "\n🥚🌷 Easter Egg Hunt 🌷🥚\n\n";
                $dialog .= "You already found this egg, good luck to find them all !";

            // This avatar didn't find that egg so far
            } else {

                // Creating additional elements for the list
                $elements = [
                    'hunterName' => AFGetFlowParameter(0)  // Name of the hunter
                ];

                // Declares the avatar as a hunter in the database
                NVSetSessionList(AFGetFlowRegionPosition(), "EggHunter", AFGetFlowSession(), json_encode($elements));

                // Creating additional elements for the list
                $elements = [
                    'foundOn' => date('c')  // Format ISO 8601, ex: 2025-04-13T18:45:00+02:00
                ];

                // Adds the egg to the found ones by that avatar
                NVSetSessionList(AFGetFlowSession() . "@" . AFGetFlowRegionPosition(), "FoundEgg", AFGetFlowObjectID(), json_encode($elements));

                // Retrieve egg metadata from the database (for debug only)
                $eggMeta = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "EasterEgg", AFGetFlowObjectID()), true);
                $eggName = $eggMeta['name'] ?? ("Egg #" . substr(AFGetFlowObjectID(), 0, 8));

                // Debug
                SLRegionSayTo(AFGetFlowObjectID(), AFGetOwnerID(), 0, AFGetFlowParameter(0) . " found an egg : " . $eggName);

                // Explicit UUID for notifications, I will have to introdice an admin feature to add ppl to notif list
                SLRegionSayTo(AFGetFlowObjectID(), "ab866cf8-abbb-4e31-a109-72c75839dbf9", 0, AFGetFlowParameter(0) . " found an egg : " . $eggName);
                
                // New egg found message
                $dialog = "\n🥚🌷 Easter Egg Hunt 🌷🥚\n\n";
                $dialog .= "Congratulations, you found a new egg !\n";

                // Total number of eggs on the region
                $totalEggs = count(NVGetSessionLists(AFGetFlowRegionPosition(), "EasterEgg"));

                // List of eggs found by this avatar
                $foundEggs = NVGetSessionLists(AFGetFlowSession() . "@" . AFGetFlowRegionPosition(), "FoundEgg");

                // Compute how many are left
                $eggsLeft = $totalEggs - count($foundEggs);

                if ($eggsLeft === 0) {
                    $dialog .= "You found all the eggs !\n";
                } elseif ($eggsLeft === 1) {
                    $dialog .= "Just 1 egg left to find, keep going !\n";
                } else {
                    $dialog .= "Only $eggsLeft eggs left to find !\n";
                }

            }

            // Options for dialog (only 'Close' needed)
            $options = ["Close"];

            // Send dialog to the avatar
            $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], $options, false, false);

            // Nothing more to do
            $flowStep = "EXIT";
             
        // Touching the board
        } else {    

            //SLOwnerSay(AFGetFlowObjectID(), AFGetFlowParameter(0) . " touched the board !");

            // Egg Hunt welcome message
            $dialog = "\n🥚🌷 Easter Egg Hunt 🌷🥚\n\n";
            $dialog .= "Welcome to the Easter Egg Hunt, the most egg-citing challenge of the season!\n\n";
            $dialog .= "Your mission is to hunt down all the eggs hidden across The Dawn Star. ";
            $dialog .= "They're scattered everywhere — behind trees, under benches... Keep your eyes peeled !\n\n";
            $dialog .= "Walk around and click on the eggs to collect them.\n";
            $dialog .= "You can check how many eggs you still need by touching this board anytime.\n\n";
            $dialog .= "Prizes await the best egg-hunters.\n\n";
            $dialog .= "Have fun !\n";

            // Options for dialog (all users)
            $options = ["Rules", "My Score", "Best Hunters", "Prizes"];

            // Checking if the user is the owner
            if (AFGetOwnerID() === AFGetFlowSession())
            {

                // Adding the Admin option
                $options[] = "Admin";

            }

            // Send dialog to the avatar
            $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], $options, false, false);

            // Reads the answer
            switch ($answer) 
            {
            
                case "My Score"     :   $flowStep = "MAIN/SCORE"; break;
                case "Rules"        :   $flowStep = "MAIN/RULES"; break;
                case "Best Hunters" :   $flowStep = "MAIN/BESTHUNTERS"; break;
                case "Prizes"       :   $flowStep = "MAIN/PRIZES"; break;
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
        $foundEggsList = NVGetSessionLists(AFGetFlowSession() . "@" . AFGetFlowRegionPosition(), "FoundEgg");
        
        // Will contain the date of the first egg
        $firstFoundDate = null;

        // Looping through all found eggs to find the first date
        foreach ($foundEggsList as $currentEgg)
        {

            // Getting the database elements for the current egg
            $currentEggDate = NVGetSessionList(AFGetFlowSession() . "@" . AFGetFlowRegionPosition(), "FoundEgg", $currentEgg);
            
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
        $firstFoundDateFormatted = $firstFoundDate ? (new DateTime($firstFoundDate))->format('Y-m-d H:i') : null;

        // Counting the found eggs
        $foundEggs = count($foundEggsList);

        // Checking how many eggs are on the SIM
        $totalEggs = count(NVGetSessionLists(AFGetFlowRegionPosition(), "EasterEgg"));

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
                    $dialog .= "You found 1 egg so far, and there are " . ($totalEggs - $foundEggs) . " more to find.\n\n";
                    $dialog .= "Good luck, and have fun !";
                    break;

                default:
                    $dialog .= "You found your first egg on " . $firstFoundDateFormatted . "\n";
                    $dialog .= "You found " . $foundEggs . " eggs so far, and there are " . ($totalEggs - $foundEggs) . " more to find.\n\n";
                    $dialog .= "Good luck, and have fun !";

            }

        }
        
        // Send dialog to the avatar
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], [], false, true);

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
        $dialog .= "The Great Easter Egg Hunt is open now and ends at 2.15 pm SLT on Sunday 5th April.\n\n";
        $dialog .= "Your timer starts when you find your first egg and ends when you find your last. ";
        $dialog .= "The faster you find them all, the better your score.\n\n";
        $dialog .= "If nobody finds all eggs, the winner is the player who found the most in the shortest time.\n\n";
        $dialog .= "Do not check the 3 private residences on the north side - no eggs there. ";
        $dialog .= "But you may find some in or around the Telescopium Observatory!";

        // Send dialog to the avatar
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], [], false, true);

        // If not BACK, timeout or HTTP error...
		if ($answer != "BACK" && $answer != NULL)
		{
        
            $flowStep = "EXIT";
        
        }

    // Displays the prizes
    } else if ($flowStep === "MAIN/PRIZES")
    {

        // Dialog
        $dialog = "\n🥚🌷 Easter Egg Hunt / Prizes 🌷🥚\n\n";
        $dialog .= "1st prize: L$ 1000\n";
        $dialog .= "2nd prize: L$ 500\n";
        $dialog .= "3rd prize: L$ 250";

        // Send dialog to the avatar
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], [], false, true);

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
        $totalEggs = count(NVGetSessionLists(AFGetFlowRegionPosition(), "EasterEgg"));

        // Get the list of all players who found at least one egg
        $hunterSessions = NVGetSessionLists(AFGetFlowRegionPosition(), "EggHunter");

        // Will hold the stats for each hunter
        $hunterStats = [];

        // Loop through each hunter
        foreach ($hunterSessions as $hunter) {

            // Get display name of the hunter
            $hunterData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "EggHunter", $hunter), true);
            $hunterName = $hunterData['hunterName'] ?? $hunter;

            // Get the list of eggs found by the hunter
            $eggs = NVGetSessionLists($hunter . "@" . AFGetFlowRegionPosition(), "FoundEgg");

            // Initialize stats
            $count = count($eggs);
            $first = null;
            $last = null;

            // Loop through all found eggs to get first and last timestamps
            foreach ($eggs as $egg) 
            {

                // Get the found date for the current egg
                $eggData = json_decode(NVGetSessionList($hunter . "@" . AFGetFlowRegionPosition(), "FoundEgg", $egg), true);

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
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], [], false, true);

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
        $dialog .= "[Eliminate] : Eliminates a player from the game\n";

        // Available options
        $options = ["Rename", "Ping", "Eliminate"];

        // Show dialog without paging
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], $options, false, true);

        // Handle valid user choices only
        if ($answer != "BACK" && $answer != NULL) 
        {

            if ($answer === "Rename") { $flowStep = "MAIN/ADMIN/RENAME"; } 
            else if ($answer === "Ping") { $flowStep = "MAIN/ADMIN/PING"; }
            else if ($answer === "Eliminate") { $flowStep = "MAIN/ADMIN/ELIMINATE"; }

        }

    // Displays a paginated list of eggs for renaming
    } else if ($flowStep === "MAIN/ADMIN/RENAME")
    {

        // Header of the dialog
        $dialog = "\n🥚🌷 Easter Egg Hunt / Admin / Rename <<PAGE>> 🌷🥚\n\n";
        $dialog .= "Choose the egg you want to rename :\n";

        // Get the list of all eggs on the region
        $eggList = NVGetSessionLists(AFGetFlowRegionPosition(), "EasterEgg");

        // Initialize arrays for choices and options
        $choices = [];
        $options = [];

        // Loop through eggs to build the selection list
        foreach ($eggList as $i => $eggId) 
        {

            // Retrieve the egg metadata
            $eggData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "EasterEgg", $eggId), true);

            // Extract the name or use a fallback if not set
            $eggName = $eggData['name'] ?? ("Egg #" . substr($eggId, 0, 8));

            // Add the formatted name to the list
            $choices[] = ($i + 1) . " - " . $eggName;
            $options[] = (string)($i + 1);
            
        }

        // Send the dialog to the user with paging and BACK support
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", $choices, $options, true, true);

        // Exit if valid response (other than BACK or timeout)
        if ($answer != "BACK" && $answer != NULL)
        {
            
            // Header of the dialog
            $dialog = "\n🥚🌷 Easter Egg Hunt / Admin / Rename 🌷🥚\n\n";
            $dialog .= "Please enter the new name of this egg (default is Unnamed) :\n";

            // Opening the textbox		
            $newName = SLTextBox(AFGetFlowObjectID(), AFGetFlowSession(), $dialog);

            // Exit if valid response (other than BACK or timeout)
            if ($newName != "BACK" && $newName != NULL) 
            {

                // Retrieve the selected egg ID from the list
                $eggIndex = intval($answer) - 1;
                $eggId = $eggList[$eggIndex] ?? null;

                if ($eggId !== null)
                {
                    
                    // Retrieve the existing metadata for the egg
                    $eggData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "EasterEgg", $eggId), true);

                    // Update the name field with the new value
                    $eggData['name'] = $newName;

                    // Save the updated metadata back to the database
                    NVSetSessionList(AFGetFlowRegionPosition(), "EasterEgg", $eggId, json_encode($eggData));

                }

            }

        }

    // Admin feature to ping all the eggs
    } else if ($flowStep === "MAIN/ADMIN/PING") {

        // Dialog header
        $dialog = "\n🥚🌷 Easter Egg Hunt / Admin / Ping <<PAGE>> 🌷🥚\n\n";
        $dialog .= "Legend:\n■ Responding\n□ No response\n\nSelect an egg to check its status:\n";

        // Get all eggs
        $eggList = NVGetSessionLists(AFGetFlowRegionPosition(), "EasterEgg");
        $pingResults = SLPingMulti($eggList);

        // Build choices
        $choices = [];
        $options = [];

        // Looping through the eggs
        foreach ($eggList as $i => $eggId) 
        {
            
            $eggData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "EasterEgg", $eggId), true);
            $eggName = $eggData['name'] ?? ("Egg #" . substr($eggId, 0, 8));
            $status = !empty($pingResults[$eggId]) ? "■" : "□";
            $choices[] = "$status " . ($i + 1) . " - " . $eggName;
            $options[] = (string)($i + 1);

        }

        // Show dialog with paging
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", $choices, $options, true, true);

        // If valid response (other than BACK or timeout)
        if ($answer != "BACK" && $answer != NULL) {

            // Determine selected egg
            $eggIndex = intval($answer) - 1;
            $eggId = $eggList[$eggIndex] ?? null;

            // If the egg exists (should ever happen)
            if ($eggId !== null) 
            {

                // True if responds to ping, false if not
                $isOnline = $pingResults[$eggId] ?? false;

                // The selected egg is still online
                if ($isOnline) 
                {

                    // Responding → cannot delete
                    $dialog = "\n🥚🌷 Easter Egg Hunt / Admin / Ping 🌷🥚\n\n";
                    $dialog .= "This egg is still responding to HTTP ping and cannot be deleted. ";
                    $dialog .= "If you want to remove it from the game, delete or derezz it inworld first.";

                    $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], [], false, true);

                // The selected egg doesn't respond to ping
                } else {

                    // Unreachable → offer deletion
                    $dialog = "\n🥚🌷 Easter Egg Hunt / Admin / Ping 🌷🥚\n\n";
                    $dialog .= "This egg does not respond anymore. It was likely deleted or derezzed.\n\n";
                    $dialog .= "If you delete it:\n";
                    $dialog .= "- It will be removed from the list of eggs.\n";
                    $dialog .= "- Hunters who only found this egg will be removed as well.\n\n";
                    $dialog .= "Are you sure you want to delete it ?";

                    $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], ["Delete"], false, true);

                    if ($answer === "Delete") 
                    {

                        // 1. Remove from EasterEgg list
                        NVDelSessionList(AFGetFlowRegionPosition(), "EasterEgg", $eggId);

                        // 2. Remove from FoundEgg of all hunters
                        $hunters = NVGetSessionLists(AFGetFlowRegionPosition(), "EggHunter");

                        foreach ($hunters as $hunterSession) {
                            $foundEggs = NVGetSessionLists($hunterSession . "@" . AFGetFlowRegionPosition(), "FoundEgg");

                            if (in_array($eggId, $foundEggs)) {
                                NVDelSessionList($hunterSession . "@" . AFGetFlowRegionPosition(), "FoundEgg", $eggId);
                            }

                            // Remove hunter if no eggs left (the first egg being the start of his hunt)
                            $stillFound = NVGetSessionLists($hunterSession . "@" . AFGetFlowRegionPosition(), "FoundEgg");
                            if (count($stillFound) === 0) {
                                NVDelSessionList(AFGetFlowRegionPosition(), "EggHunter", $hunterSession);
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
        $dialog = "\n🥚🌷 Easter Egg Hunt / Admin / Eliminate <<PAGE>> 🌷🥚\n\n";
        $dialog .= "Choose a player to eliminate from the hunt:\n";

        // Get all hunter sessions
        $hunterSessions = NVGetSessionLists(AFGetFlowRegionPosition(), "EggHunter");

        // Build display list
        $choices = [];
        $options = [];

        foreach ($hunterSessions as $i => $hunterUUID) {
            $hunterData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "EggHunter", $hunterUUID), true);
            $hunterName = $hunterData['hunterName'] ?? $hunterUUID;
            $choices[] = ($i + 1) . " - " . $hunterName;
            $options[] = (string)($i + 1);
        }

        // Show list with paging
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", $choices, $options, true, true);

        if ($answer != "BACK" && $answer != NULL) {

            $hunterIndex = intval($answer) - 1;
            $hunterUUID = $hunterSessions[$hunterIndex] ?? null;

            if ($hunterUUID !== null) {

                $hunterData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "EggHunter", $hunterUUID), true);
                $hunterName = $hunterData['hunterName'] ?? $hunterUUID;

                // Confirmation dialog
                $dialog = "\n🥚🌷 Easter Egg Hunt / Admin / Eliminate 🌷🥚\n\n";
                $dialog .= "Are you sure you want to eliminate \n";
                $dialog .= $hunterName . " from the hunt?\n\n";
                $dialog .= "This action is irreversible !";

                $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], ["Eliminate"], false, true);

                if ($answer === "Eliminate") {

                    // 1. Remove the hunter from the EggHunter list
                    NVDelSessionList(AFGetFlowRegionPosition(), "EggHunter", $hunterUUID);

                    // 2. Remove all lists associated with this hunter session
                    NVDelSessionLists($hunterUUID . "@" . AFGetFlowRegionPosition(), "FoundEgg");

                    // Optional feedback
                    //SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), "\n" . $hunterName . " has been eliminated from the hunt.", "", [], [], false, true);

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