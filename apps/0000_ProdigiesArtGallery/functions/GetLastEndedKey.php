<?php

/**
 * Returns the key for the most recent ended auction session for a given UNICAT.
 * Example: "UNICAT123", "UNICAT123@2", "UNICAT123@3", ...
 * Returns null if no ended session exists.
 *
 * - If only the first auction ended, returns the base key (no suffix).
 * - If multiple auctions, returns the latest ended one (with suffix).
 */
function GetLastEndedKey($unicat)
{
    
    // If there is NO ended auction at all, return null
    if (NVGetList("EndedAuction", $unicat) === null) {
        return null;
    }

    // Get the reauction count (number of ended auctions beyond the first)
    $count = NVGetList("ReauctionCount", $unicat);

    // If there was only one ended session (no reauction count), return base key
    if (!$count || $count < 2) {
        return $unicat;
    }

    // Otherwise, try to find the latest ended session with suffix
    $lastSuffix = $count - 1;
    $lastKey = $unicat . '@' . $lastSuffix;

    // If the key doesn't exist, iterate backward to find an existing EndedAuction key
    while ($lastSuffix > 1 && NVGetList("EndedAuction", $lastKey) === null) {
        $lastSuffix--;
        $lastKey = $unicat . '@' . $lastSuffix;
    }

    // Final verification: does this ended key exist?
    if (NVGetList("EndedAuction", $lastKey) !== null) {
        return $lastKey;
    }
    // As a fallback, if the base key exists, return it
    if (NVGetList("EndedAuction", $unicat) !== null) {
        return $unicat;
    }

    // No ended session found (should never reach here)
    return null;

}