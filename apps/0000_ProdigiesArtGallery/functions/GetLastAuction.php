<?php

/**
 * Returns the most recent auction JSON for a UNICAT.
 * - If active: returns NVGetList("ActiveAuction", $unicat)
 * - Else: returns NVGetList("EndedAuction", GetLastEndedKey($unicat))
 * - If none exists: returns null
 *
 * @param string $unicat
 * @return ?string  Raw JSON or null
 */
function GetLastAuction(string $unicat): ?string
{
    
    // Checks if the UNICAT has an active auction
    if (IsUNICATActive($unicat)) {
        $json = NVGetList("ActiveAuction", $unicat);
        return $json;
    }

    // If not active, get the last ended auction key
    $endedKey = GetLastEndedKey($unicat);
    if ($endedKey === null) {
        return null;
    }

    // Fetch the ended auction JSON using the last ended key
    $json = NVGetList("EndedAuction", $endedKey);
    return $json;

}