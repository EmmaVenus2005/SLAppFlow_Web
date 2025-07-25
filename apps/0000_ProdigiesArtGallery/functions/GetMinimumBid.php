<?php

// Gets the minimum bid price (start price if no existing bids, and highest bid + 1 if any)
function GetMinimumBid($unicat)
{
    
    // Step 1: Check if the auction is still active
    if (!IsUNICATActive($unicat)) {
        return null; // or return false;
    }

    // Step 2: Determine the correct key (could be UNICAT123 or UNICAT123@2, etc.)
    $bidKey = GetCurrentKey($unicat);
    if ($bidKey === null) {
        return null;
    }

    // Step 3: Get existing bids for this painting
    $bidsList = NVGetSessionLists($bidKey, "Bid");

    // Step 4: No bids yet → return start_price
    if (!$bidsList || count($bidsList) === 0) {
        $auctionMeta = NVGetList("ActiveAuction", $unicat);
        if ($auctionMeta === null) return null;
        $data = json_decode($auctionMeta, true);
        return (float)($data['start_price'] ?? 0);
    }

    // Step 5: Sort bids by timestamp ascending
    usort($bidsList, function ($a, $b) {
        return strtotime($a) <=> strtotime($b);
    });

    // Get the last bid details
    $lastBidKey = end($bidsList);
    $lastBidJson = NVGetSessionList($bidKey, "Bid", $lastBidKey);
    $lastBidData = json_decode($lastBidJson, true);
    if (!$lastBidData || !isset($lastBidData['amount'])) return null;

    // Return one L$ above the last amount
    return (float)$lastBidData['amount'] + 1;

}