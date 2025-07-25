<?php

// Sends payment options to the board owner based on the minimum bid of a UNICAT
function SetPaymentOptions($unicat, $boardID)
{

    // Only relevant for the Global user
    if (AFGetOwnerID() !== "Global") return false;
    
    // Determine the minimum bid for the current painting
    $minBid = GetMinimumBid($unicat);

    // If no valid minimum bid, do nothing
    if ($minBid === null || $minBid <= 0) return;

    // Payment options:
    $b1 = $minBid;
    $b2 = ceil(max($b1 * 1.25, $b1 + 10) / 10) * 10;
    $b3 = ceil(max($b1 * 2.00, $b2 + 10) / 10) * 10;
    $b4 = ceil(max($b1 * 2.50, $b3 + 10) / 10) * 10;

    // Retrieve the board owner to send the command
    $ownerID = NVGetList("BoardOwner", $boardID);
    if (!$ownerID) return false;

    // Compose the message
    $msg = "SETPAYMENT|{$boardID}|{$b1}|{$b1}|{$b2}|{$b3}|{$b4}";

    // Send the payment configuration
    AFSendFlowMessage(AFGetAppID(), $ownerID, $msg);

    // Success
    return true;

}