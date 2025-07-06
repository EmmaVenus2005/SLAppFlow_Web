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
 * Pay-specific additional parameters are accessible using:
 *
 * - AFGetFlowSession()         → UUID of the avatar who paid the object
 * - AFGetFlowParameter(index)  → Indexed array of payment data:
 *      [0] = payerName            (string)
 *      [1] = amount               (string)
 *      [2] = internalCount        (integer)
 * 
 */

// Registers the bidding, 3 possible options :
// - "FIRSTBID|<painting name> : First who bid on that painting
// - "REJECT|<painting name>|<min amount>" : The paid amount is not higher than the previous best bid
// - "REFUNDPREV|<painting name>|<previous bidder>|<previous best bid> : The previous best bidder got bid over
$response = AFSendFlowMessage(AFGetAppID(), "Global", "BID|" . AFGetFlowObjectID() . "|" . AFGetFlowSession() . "|" . AFGetFlowParameter(0) . "|" . AFGetFlowParameter(1));

// Notification and refund if needed
$commandParts = explode('|', $response);
$action = $commandParts[0];

switch ($action) 
{

    case 'FIRSTBID':

        // Example painting name: Painting_UNITEST0002_Forest Serenity
        $paintingFullName = $commandParts[1];

        // Extract the human-friendly painting name after the second underscore
        $nameParts = explode('_', $paintingFullName, 3);
        $friendlyName = isset($nameParts[2]) ? $nameParts[2] : $paintingFullName;

        // Build a friendly message for the bidder
        $bidderId = AFGetFlowSession();
        $message = "Congratulations! You have placed the first bid on the painting: \"$friendlyName\". Good luck in the auction!";

        // Send the notification to the bidder
        SLInstantMessage(AFGetFlowObjectID(), $bidderId, $message);
        
        // Nothing more to do
        break;

    case 'REJECT':

        // Example: Painting_UNITEST0002_Forest Serenity
        $paintingFullName = $commandParts[1];
        $minAmount = $commandParts[2];

        // Extract the human-friendly painting name after the second underscore
        // Splits into: [Painting, UNITEST0002, "Forest Serenity"]
        $nameParts = explode('_', $paintingFullName, 3);
        $friendlyName = isset($nameParts[2]) ? $nameParts[2] : $paintingFullName;

        // Refund unsuccessful bidder
        $bidderId = AFGetFlowSession();
        $amount = AFGetFlowParameter(1);
        SLPay(AFGetFlowObjectID(), $bidderId, $amount);

        // Build a friendly rejection message for the bidder
        $bidderId = AFGetFlowSession();
        $message = "Sorry! Your bid was not high enough for the painting: \"$friendlyName\". The minimum required bid is L$" . intval($minAmount) . ". Your money has been refunded.";

        // Send the notification to the bidder
        SLInstantMessage(AFGetFlowObjectID(), $bidderId, $message);
        
        // Nothing more to do
        break;

    case 'REFUNDPREV':
       
        // Example: Painting_UNITEST0002_Forest Serenity|<previous_bidder_uuid>
        $paintingFullName = $commandParts[1];
        $previousBidderUuid = $commandParts[2];

        // Extract the human-friendly painting name after the second underscore
        $nameParts = explode('_', $paintingFullName, 3);
        $friendlyName = isset($nameParts[2]) ? $nameParts[2] : $paintingFullName;

        // The amount to refund to the previous top bidder (should be stored/known)
        $previousAmount = $commandParts[3];
        SLPay(AFGetFlowObjectID(), $previousBidderUuid, $previousAmount);

        // Notify the new top bidder (new leader)
        $newTopBidder = AFGetFlowSession(); // This should be the UUID of the new best bidder (the payer in this flow)
        $leaderMessage = "Congratulations! You are now the top bidder for \"$friendlyName\". Good luck in the auction!";
        SLInstantMessage(AFGetFlowObjectID(), $newTopBidder, $leaderMessage);
        
        // Notify the previous top bidder (refund)
        $refundMessage = "Your bid for the painting \"$friendlyName\" has been surpassed by " . AFGetFlowParameter(0) . ". Your money has been refunded.\nFeel free to place a higher bid at the board : http://maps.secondlife.com/secondlife/HomeOfTheProdigies/108/14/35";
        SLInstantMessage(AFGetFlowObjectID(), $previousBidderUuid, $refundMessage);

        // Nothing more to do
        break;

    case 'LOWERSTARTPRICE':

        // Start price, minimum to bid
        $startPrice = $commandParts[2];

        // Example: Painting_UNITEST0002_Forest Serenity|<previous_bidder_uuid>
        $paintingFullName = $commandParts[1];

        // Extract the human-friendly painting name after the second underscore
        $nameParts = explode('_', $paintingFullName, 3);
        $friendlyName = isset($nameParts[2]) ? $nameParts[2] : $paintingFullName;

        // First, refunds the payer
        SLPay(AFGetFlowObjectID(), AFGetFlowSession(), AFGetFlowParameter(1));

        // Sending the message
        $message = "Thanks for your bid on " . $friendlyName . ". However, the start price is " . $startPrice . "L$. Your money has been refunded.";
        SLInstantMessage(AFGetFlowObjectID(), AFGetFlowSession(), $message);

        // Nothing more to do
        break;

    default:

        // Unknown action
        // Your code here...
        break;

}