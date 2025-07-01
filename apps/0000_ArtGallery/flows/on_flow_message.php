<?php

/**
 * Contextual functions used during flow execution
 * -----------------------------------------------
 * 
 * - AFGetAppID()               → Application identifier (unique per app instance)
 * - AFGetOwnerID()             → UUID of the recipient avatar
 * - AFGetOwnerName()           → Display name of recipient avatar
 * - AFIsUnsafe()               → Returns true when the call is coming from WebControl (front-end)
 *
 * Message-specific additional parameters are accessible using:
 *
 * - AFGetSenderAppID()         → Application identifier of the sender (if applicable)
 * - AFGetSenderID()            → UUID of the avatar who sent the message
 * - AFGetSenderName()          → Display name of the avatar who sent the message
 * - AFGetMessage()             → The message content sent by the sender
 * 
 */

// When true, the call comes from WebControl and can be forged by the user.
// Front-end commands may be allowed, but:
// ONLY ALLOW ACTIONS THAT ARE SAFE BY DESIGN.
//if (AFIsUnsafe()) { return; }

// If the message is not from the same app, ignore it
// (If you handle messages from other apps, you can remove this check)
// BE CAREFUL: You don't control the sender app, carefully implement the logic to avoid security issues 
if (AFGetSenderAppID() !== AFGetAppID()) { return; }

// If the message is not from the same owner, ignore it
// (If you handle messages from other owners, you can remove this check)
//if (AFGetSenderID() !== AFGetOwnerID()) { return; }

// ... your code here ...

// Messages are sent to the "Global" user, which is not an actual avatar
// but a common user for the ArtGallery app.
if (AFGetOwnerID() !== "Global") {
    return; 
}

// Separating the message parts
$messageParts = explode("|", AFGetMessage());

// Refreshes the token and sends the new URL from the board
// E. g. "UPDATEURL|<boardID>
if ($messageParts[0] === "UPDATEURL" && AFIsUnsafe() !== true)
{

    // Generating a new UUID as a token
    $token = GenerateUUID();

    // Saves the new token
    NVSetList("BoardToken", $token, $messageParts[1]);

    // Gets the current page from the board
    $current = NVGetList("CurrentPage", $messageParts[1]) ?? 1;

    // New URL to apply
    $url = "https://wwwtest.slappflow.net/webcontrol/wcmedia.php";

    // Params including the new token
    $params = "?token=" . $token . "&app=" . AFGetAppID();

    // Creating the message to send
    $message = "URL\\1\\3\\{$url}" . $params;

    // Reuturns the message to be sent
    return $message;

}

// When a piece of art is rezzed, checks if the owner changed
// E. g. "REZZED|Painting_UNICAT0000000001_Astrid|{ JSON data }"
if ($messageParts[0] === "REZZED" && AFIsUnsafe() !== true) 
{
    
    // Recovering the last known owner from the database 
    $lastOwnerID = NVGetSessionValue($messageParts[1], "LastOwnerID");

    // We check if the last owner ID is different from the last known one
    if ($lastOwnerID !== AFGetSenderID()) 
    {

        // Sets the new owner ID in the database
        NVSetSessionValue($messageParts[1], "LastOwnerID", AFGetSenderID());
        
        // Stores the last transaction in the list, with the JSON as additional data
        NVSetSessionList($messageParts[1], "NewOwner", date('c'), $messageParts[2]);

    }

}

// When the page from a board changes, updates the current page in the database
// CALLED FROM THE FRONT-END MEDIA
// E. g. "PAGECHANGE|<token>|<pageNumber>"
if ($messageParts[0] ===  "PAGECHANGE" && AFGetSenderID() === "Media")
{

    // Gets the board using the token
    $boardID = NVGetList("BoardToken", $messageParts[1]);

    // If the board exists, updates the current page
    if ($boardID !== null)
    {     

        // Updates the current page in the database
        NVSetList("CurrentPage", $boardID, $messageParts[2]);

        // Successfully updated the page
        return true;

    }

}

// Gets the current page from the board
// CALLED FROM THE FRONT-END MEDIA
// E. g. "GETPAGE|<token>"
if ($messageParts[0] === "GETPAGE" && AFGetSenderID() === "Media")
{

    // Gets the board using the token
    $boardID = NVGetList("BoardToken", $messageParts[1]);

    // If the board exists, returns the current page
    if ($boardID !== null)
    {

        // Gets the current page from the database
        $currentPage = NVGetList("CurrentPage", $boardID) ?? 1;

        // Sending the current page
        return $currentPage;

    }

}

// Gets the information about the painting
// CALLED FROM THE FRONT-END MEDIA
// E. g. "GETINFO|<token>|<paintingID>"
if ($messageParts[0] === "GETINFO" && AFGetSenderID() === "Media")
{

    // Gets the board using the token
    $boardID = NVGetList("BoardToken", $messageParts[1]);

    // If the board exists, returns the current page
    if ($boardID === null) return null;
    
    // Getting the ActiveAuction elements, if doesn't exist,
    // doesn't do further processing, since we don't want media
    // to get information about paintings that are not active
    $activeAuction = NVGetList("ActiveAuction", $messageParts[2]);

    // No active auction, no information to return
    if ($activeAuction === null) return null;

    // Getting the general information about the painting
    $generalInfo = NVGetList("Information", $messageParts[2]);

    // Decode each JSON (if not null)
    $activeAuctionArr = $activeAuction ? json_decode($activeAuction, true) : [];
    $generalInfoArr   = $generalInfo   ? json_decode($generalInfo, true)   : [];

    // Merging the JSON data
    $info = array_merge($activeAuctionArr, $generalInfoArr);

    // Returning the information as JSON
    return json_encode($info, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

}

// Gets the list of paintings from the board
// CALLED FROM THE FRONT-END MEDIA
// E. g. "LISTACTIVE|<token>"
if ($messageParts[0] === "LISTACTIVE" && AFGetSenderID() === "Media")
{

    // Gets the board using the token
    $boardID = NVGetList("BoardToken", $messageParts[1]);

    // If the board doesn't exist, returns null
    if ($boardID === null) return null;

    // Gets the list of paintings from the database
    $paintingsList = NVGetLists("ActiveAuction");

    // If there are no paintings, return an empty array
    if ($paintingsList === null) return [];

    // Returns the list of paintings as JSON
    return json_encode($paintingsList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

}

// Gets the image of the painting
// CALLED FROM THE FRONT-END MEDIA
// E. g. "GETIMAGE|<token>|<paintingID>"
if ($messageParts[0] === "GETIMAGE" && AFGetSenderID() === "Media")
{

    // Gets the board using the token
    $boardID = NVGetList("BoardToken", $messageParts[1]);

    // If the board doesn't exist, returns null
    if ($boardID === null) return null;

    // Looking for the image ID for the painting
    $imageID = NVGetList("Image", $messageParts[2]);

    // If the image ID doesn't exist, returns null
    if ($imageID === null) return null;

    // Gets the image from File Service
    $imageData = FSDownload($imageID);

    // Returns the image data directly
    return base64_encode($imageData); 
    
}

// Gets 3 best bidders
// CALLED FROM THE FRONT-END MEDIA
// E. g. "GETBESTBIDS|<token>|<paintingID>"
if ($messageParts[0] === "GETBESTBIDS" && AFGetSenderID() === "Media")
{

    // Gets the board using the token
    $boardID = NVGetList("BoardToken", $messageParts[1]);

    // If the board doesn't exist, returns null
    if ($boardID === null) return null;

    // Gets the list of buids for the painting
    $bidsList = NVGetSessionLists($messageParts[2], "Bid");

    // If no bid on this painting, returns an empty array
    if ($bidsList === null || count($bidsList) === 0) return [];

    // Sort bids by date ascending
    usort($bidsList, function($a, $b) {
        return strtotime($a) <=> strtotime($b);
    });

    // Keep only the latest 3 bids
    $lastBids = array_slice($bidsList, -3);

    // For each bid, recovering the bidder name
    foreach($lastBids as &$bid) 
    {
     
        // Gets the elements of each bid (including the name, UUID and price)
        $bidderDetails[] = [$bid, NVGetSessionList($messageParts[2], "Bid", $bid)];

    }

    // Returns the list of bidders
    return json_encode($bidderDetails, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

}

// Always return explicitely, because if not, PHP returns 1
return;