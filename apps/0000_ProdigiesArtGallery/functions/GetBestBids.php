<?php

/**
 * Returns the latest N (and thus highest N) bids for the current auction session of a given UNICAT,
 * formatted as [timestamp, json_string], just like GETBESTBIDS.
 * @param string $unicat   UNICAT number (e.g. "UNICAT234")
 * @param int    $number   Number of top bids to return (default 3)
 * @return array           Array of [timestamp, json_string], oldest to newest among the best
 */
function GetBestBids($unicat, $number = 3)
{
    
    // Get the key for the latest/current auction session
    $key = GetCurrentKey($unicat);
    if ($key === null) return [];

    // Fetch all bids (list of timestamps)
    $bidsList = NVGetSessionLists($key, "Bid");
    if (!$bidsList || count($bidsList) === 0) return [];

    // Sort by time ascending (oldest first)
    usort($bidsList, function($a, $b) {
        return strtotime($a) <=> strtotime($b);
    });

    // Take the last N bids (highest)
    $bestBids = array_slice($bidsList, -$number);

    // Formats the N best bids as [timestamp, json_string]
    $result = [];
    foreach ($bestBids as $timestamp) {
        $json = NVGetSessionList($key, "Bid", $timestamp);
        $result[] = [$timestamp, $json];
    }

    // Return the result
    return $result;

}