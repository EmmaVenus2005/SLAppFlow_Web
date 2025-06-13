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

// Constants depending of the game (Egg Hunt, Treasure Hunt, etc.)
$gameName = "Treasure Hunt";
$treasureArticle = "a";
$treasureNameSingular = "treasure";
$treasureNamePlurial = "treasures";
$titleSurroundingLeft = "✨";
$titleSurroundingRight = "✨";

// Main loop
while ($flowStep != "EXIT")
{
    
    if ($flowStep === "MAIN")
    {
        
        // If the touched object is a treasure
        if ($appmode === "Treasure")
        {

            // Gets the list of found treasures by toucher avatar from the database
            $foundTreasures = NVGetSessionLists(AFGetFlowSession() . "@" . AFGetFlowRegionPosition(), "FoundTreasure");

            // If the treasure was already found
            if (in_array(AFGetFlowObjectID(), $foundTreasures)) 
            {
            
                // Treasure already found message
                $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " " . $titleSurroundingRight . "\n\n";
                $dialog .= "You already found this $treasureNameSingular, good luck to find them all !";

            // This avatar didn't find that treasure so far
            } else {

                // Creating additional elements for the list
                $elements = [
                    'hunterName' => AFGetFlowParam()[0]  // Name of the hunter
                ];

                // Declares the avatar as a hunter in the database
                NVSetSessionList(AFGetFlowRegionPosition(), "Hunter", AFGetFlowSession(), json_encode($elements));

                // Creating additional elements for the list
                $elements = [
                    'foundOn' => date('c')  // Format ISO 8601, ex: 2025-04-13T18:45:00+02:00
                ];

                // Adds the treasure to the found ones by that avatar
                NVSetSessionList(AFGetFlowSession() . "@" . AFGetFlowRegionPosition(), "FoundTreasure", AFGetFlowObjectID(), json_encode($elements));

                // Retrieve the treasure metadata from the database (for debug only)
                $treasureMeta = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "Treasure", AFGetFlowObjectID()), true);
                $treasureName = $treasureMeta['name'] ?? ("Treasure #" . substr(AFGetFlowObjectID(), 0, 8));

                // Debug
                SLRegionSayTo(AFGetFlowObjectID(), AFGetOwnerID(), 0, AFGetFlowParam()[0] . " found $treasureArticle $treasureNameSingular : $treasureName");

                // Explicit UUID for notifications, I will have to introdice an admin feature to add ppl to notif list
                SLRegionSayTo(AFGetFlowObjectID(), "ab866cf8-abbb-4e31-a109-72c75839dbf9", 0, AFGetFlowParam()[0] . " found $treasureArticle $treasureNameSingular : $treasureName");
                
                // New treasure found message
                $dialog .= "\n" . $titleSurroundingLeft . " " . $gameName . " " . $titleSurroundingRight . "\n\n";
                $dialog .= "Congratulations, you found $treasureArticle new $treasureNameSingular !\n";

                // Total number of treasures on the region
                $totalTreasures = count(NVGetSessionLists(AFGetFlowRegionPosition(), "Treasure"));

                // List of treasures found by this avatar
                $foundTreasures = NVGetSessionLists(AFGetFlowSession() . "@" . AFGetFlowRegionPosition(), "FoundTreasure");

                // Compute how many are left
                $treasuresLeft = $totalTreasures - count($foundTreasures);

                // Message
                $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " " . $titleSurroundingRight . "\n\n";
                $dialog .= "Congratulations, you found a new $treasureNameSingular !\n";

                if ($treasuresLeft === 0) {
                    $dialog .= "You found all the $treasureNamePlurial !\n";
                } elseif ($treasuresLeft === 1) {
                    $dialog .= "Just 1 $treasureNameSingular left to find, keep going !\n";
                } else {
                    $dialog .= "Only $treasuresLeft $treasureNamePlurial left to find !\n";
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

            //SLOwnerSay(AFGetFlowObjectID(), AFGetFlowParam()[0] . " touched the board !");

            // Treasure Hunt welcome message
            $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " " . $titleSurroundingRight . "\n\n";
            $dialog .= "Your mission is to discover all the hidden $treasureNamePlurial scattered across this SIM. ";
            $dialog .= "They could be anywhere — behind rocks, inside caves, or even under water... Stay sharp and explore every corner!\n\n";
            $dialog .= "Walk around and click on the $treasureNamePlurial to collect them.";
            $dialog .= "You can check your progress anytime by touching this board.\n\n";
            $dialog .= "A grand reward awaits the most dedicated treasure hunters.\n\n";
            $dialog .= "Good luck and have fun!\n";

            // Options for dialog (all users)
            $options = ["Rules", "My Score", "Best Hunters"];

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
                case "Admin"        :   $flowStep = "MAIN/ADMIN"; break;
                
                // If no managed answer found, exits the flow
                default : $flowStep = "EXIT";
            
            }

        }

    // My Score from board menu selected
    } else if ($flowStep === "MAIN/SCORE")
    {

        // Dialog title
        $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / My Score " . $titleSurroundingRight . "\n\n";
        
        // Getting the list of found treasures
        $foundTreasuresList = NVGetSessionLists(AFGetFlowSession() . "@" . AFGetFlowRegionPosition(), "FoundTreasure");
        
        // Will contain the date of the first treasure found
        $firstFoundDate = null;

        // Looping through all found treasures to find the first date
        foreach ($foundTreasuresList as $currentTreasure)
        {

            // Getting the database elements for the current treasure
            $currentTreasureData = NVGetSessionList(AFGetFlowSession() . "@" . AFGetFlowRegionPosition(), "FoundTreasure", $currentTreasure);
            
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

            $dialog .= "You found your first $treasureNameSingular on $firstFoundDateFormatted.\n";
            $dialog .= "You found all $totalTreasures $treasureNameSingular.\n\n";
            $dialog .= "Congratulations !";

        } else {

            // Text depending of the number of found treasures
            switch($foundTreasures)
            {

                case 0:
                    $dialog .= "You didn't find any $treasureNameSingular so far.\n\n";
                    $dialog .= "Good luck, and have fun !";
                    break;

                case 1:
                    $dialog .= "You found your first $treasureNameSingular on $firstFoundDateFormatted.\n";
                    $dialog .= "You found 1 $treasureNameSingular so far, and there are " . $totalTreasures - $foundTreasures . " more to find.\n\n";
                    $dialog .= "Good luck, and have fun !";
                    break;

                default:
                    $dialog .= "You found your first $treasureNameSingular on $firstFoundDateFormatted.\n";
                    $dialog .= "You found $foundTreasures $treasureNamePlurial so far, and there are " . $totalTreasures - $foundTreasures . " more to find.\n\n";
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
        $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / Rules " . $titleSurroundingRight . "\n\n";
        $dialog .= "The timer starts after you found the first $treasureNameSingular, and ends when you found the last one. ";
        $dialog .= "The less time it took you to find them all, the better your score will be.\n\n";
        $dialog .= "Don't check private places, there is no $treasureNameSingular there !";

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
        $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / Best Hunters " . $titleSurroundingRight . "\n\n";

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
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], [], false, true);

        // Exit if no BACK or null
        if ($answer != "BACK" && $answer != NULL) {
            $flowStep = "EXIT";
        }


    // Displays admin options
    } else if ($flowStep === "MAIN/ADMIN")
    {

        // Dialog title and options
        $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / Admin " . $titleSurroundingRight . "\n\n";
        $dialog .= "[Rename] : Change the name of one of the $treasureNamePlurial from this game\n";
        $dialog .= "[Ping] : Check if there are deleted $treasureNamePlurial in the game (allows you to remove them)\n";
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

    // Displays a paginated list of treasures for renaming
    } else if ($flowStep === "MAIN/ADMIN/RENAME")
    {

        // Header of the dialog
        $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / Admin / Rename <<PAGE>> " . $titleSurroundingRight . "\n\n";
        $dialog .= "Choose the $treasureNameSingular you want to rename :\n";

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
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", $choices, $options, true, true);

        // Exit if valid response (other than BACK or timeout)
        if ($answer != "BACK" && $answer != NULL)
        {
            
            // Header of the dialog
            $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / Admin / Rename " . $titleSurroundingRight . "\n\n";
            $dialog .= "Please enter the new name of this $treasureNameSingular (default is Unnamed) :\n";

            // Opening the textbox		
            $newName = SLTextBox(AFGetFlowObjectID(), AFGetFlowSession(), $dialog);

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
        $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / Admin / Ping <<PAGE>> " . $titleSurroundingRight . "\n\n";
        $dialog .= "Legend:\n■ Responding\n□ No response\n\nSelect $treasureArticle $treasureNameSingular to check its status :\n";

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
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", $choices, $options, true, true);

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
                    $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / Admin / Ping " . $titleSurroundingRight . "\n\n";
                    $dialog .= "This $treasureNameSingular is still responding to HTTP ping and cannot be deleted. ";
                    $dialog .= "If you want to remove it from the game, delete or derezz it inworld first.";

                    $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], [], false, true);

                // The selected treasure doesn't respond to ping
                } else {

                    // Unreachable → offer deletion
                    $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / Admin / Ping " . $titleSurroundingRight . "\n\n";
                    $dialog .= "This $treasureNameSingular does not respond anymore. It was likely deleted or derezzed.\n\n";
                    $dialog .= "If you delete it:\n";
                    $dialog .= "- It will be removed from the list of $treasureNamePlurial.\n";
                    $dialog .= "- Hunters who only found this $treasureNameSingular will be removed as well.\n\n";
                    $dialog .= "Are you sure you want to delete it ?";

                    $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], ["Delete"], false, true);

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
        $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / Admin / Eliminate <<PAGE>> " . $titleSurroundingRight . "\n\n";
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
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", $choices, $options, true, true);

        if ($answer != "BACK" && $answer != NULL) {

            $hunterIndex = intval($answer) - 1;
            $hunterUUID = $hunterSessions[$hunterIndex] ?? null;

            if ($hunterUUID !== null) {

                $hunterData = json_decode(NVGetSessionList(AFGetFlowRegionPosition(), "Hunter", $hunterUUID), true);
                $hunterName = $hunterData['hunterName'] ?? $hunterUUID;

                // Confirmation dialog
                $dialog = "\n" . $titleSurroundingLeft . " " . $gameName . " / Admin / Eliminate " . $titleSurroundingRight . "\n\n";
                $dialog .= "Are you sure you want to eliminate ";
                $dialog .= "$hunterName from the hunt ?\n\n";
                $dialog .= "This action is irreversible !";

                $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], ["Eliminate"], false, true);

                if ($answer === "Eliminate") {

                    // 1. Remove the hunter from the Hunter list
                    NVDelSessionList(AFGetFlowRegionPosition(), "Hunter", $hunterUUID);

                    // 2. Remove all lists associated with this hunter session
                    NVDelSessionLists($hunterUUID . "@" . AFGetFlowRegionPosition(), "FoundTreasure");

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