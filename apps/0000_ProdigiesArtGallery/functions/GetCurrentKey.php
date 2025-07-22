<?php

/**
 * Returns the current session key to use for bids or auction tracking
 * depending on whether it's the first auction or a reauction.
 *
 * If there's an active auction:
 * - Returns $unicat (no suffix) if no previous ended auction exists.
 * - Returns $unicat@N where N is ReauctionCount if at least one auction ended.
 * If no auction is active, returns null.
 */
function GetCurrentKey($unicat)
{
    
    // If there's no active auction, there's no current key to use
    if (NVGetList("ActiveAuction", $unicat) === null) {
        return null;
    }

    // If this is the first auction (no previous ended one), return the base key
    if (NVGetList("EndedAuction", $unicat) === null) {
        return $unicat;
    }

    // Otherwise, this is a reauction → use suffix @N
    $count = NVGetList("ReauctionCount", $unicat);

    // If no count exists, assume we're at the 2nd auction (1st reauction)
    if (!is_numeric($count)) {
        $count = 2;
    }

    // Returns UNICAT@N
    return $unicat . '@' . $count;

}
