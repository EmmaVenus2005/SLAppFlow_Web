<?php

/**
 * Checks if a given UNICAT painting is still active based on its end_date.
 * 
 * This function performs the following steps:
 * ---------------------------------------------------
 * 1. Loads the auction data stored in the "ActiveAuction" class.
 * 2. If no data is found, the item is not in the active list → returns false.
 * 3. If there's no "end_date" specified, the item is considered permanently active → returns true.
 * 4. If the end_date has passed:
 *    - The entry is moved from "ActiveAuction" to "EndedAuction"
 *    - The "status" field in the "Information" list is updated to "Ended"
 *    - The function returns false to indicate the item is no longer active.
 * 5. Otherwise (if end_date is still in the future), returns true.
 *
 * This function is meant to be called each time an auction or painting is accessed,
 * to ensure consistent cleanup without requiring a background process.
 *
 * @param string $unicat The identifier of the UNICAT painting (e.g., "UNICAT234")
 * @return bool True if the painting is still active, false otherwise.
 */
function IsUNICATActive($unicat)
{
    
    // Step 1: Retrieve the auction data from the active list
    $activeData = NVGetList("ActiveAuction", $unicat);

    // Scenario A: This painting is not in the "ActiveAuction" list
    if (!$activeData) {
        // Not found in active auctions → considered inactive
        return false;
    }

    // Decode the auction JSON data into an array
    $active = json_decode($activeData, true);

    // Scenario B: No end_date is specified in the auction data
    if (!isset($active['end_date'])) {
        // Considered always active → return true
        return true;
    }

    // Step 2: Compare end_date with current time
    if (strtotime($active['end_date']) < time()) {

        // Scenario C: The auction has ended — we need to archive it

        // Checks if it's the first auction
        $alreadyEnded = NVGetList("EndedAuction", $unicat);

        if ($alreadyEnded === null) {
            
            // First auction : legacy behaviour 
            NVSetList("EndedAuction", $unicat, $activeData);
            
        } else {
            
            // Reauction count (2 by default)
            $count = NVGetList("ReauctionCount", $unicat);
            if (!$count) { $count = 2; }

            // Creates the suffix for reauction count
            $suffix = '@' . $count;

            // Writes the data into EndedAuction class
            NVSetList("EndedAuction", $unicat . $suffix, $activeData);

            // Stores the count if there's more reauction
            NVSetList("ReauctionCount", $unicat, $count + 1);

        }
        
        // Remove from "ActiveAuction" list
        NVDelList("ActiveAuction", $unicat);

        // Retrieve painting metadata from the "Information" list
        $info = NVGetList("Information", $unicat);
        if ($info) 
        {

            $infoArray = json_decode($info, true);

            // Update the painting's status to "Ended"
            $infoArray['status'] = "Ended";

            // Save the updated info back to the database
            NVSetList("Information", $unicat, json_encode($infoArray, JSON_UNESCAPED_UNICODE));

        }

        // Return false because the auction is no longer active
        return false;

    }

    // Scenario D: The end_date is in the future → still active
    return true;

}