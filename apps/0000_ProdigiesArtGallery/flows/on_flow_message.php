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

// Separating the message parts
$messageParts = explode("|", AFGetMessage());

// This message is sent to the board owner, to reset the lock screen timer
// E. g. "RESETLOCK|<boardID>"
if ($messageParts[0] === "RESETLOCK" && AFIsUnsafe() !== true)
{

    // 5 minutes
    SLAddTimer($messageParts[1], "lock_screen", time() + 60 * 5); 

    // Debug
    SLOwnerSay($messageParts[1], "timer reset for the lock screen");

    // Successfully reset the lock screen timer
    return true; 

}

// Messages are sent to the "Global" user, which is not an actual avatar
// but a common user for the ArtGallery app.
if (AFGetOwnerID() !== "Global") {
    return; 
}

// Saves the board owner in the "Global" user context
// E. g. "BOARDOWNER|<boardID>|<ownerID>"
if ($messageParts[0] === "BOARDOWNER" && AFIsUnsafe() !== true)
{

    // Saves the board owner in the database
    NVSetList("BoardOwner", $messageParts[1], $messageParts[2]);

    // Successfully saved the board owner
    return true;

}

// Refreshes the token and sends the new URL from the board
// E. g. "UPDATEURL|<boardID>
if ($messageParts[0] === "UPDATEURL" && AFIsUnsafe() !== true)
{

    // Generating a new UUID as a token
    $token = GenerateUUID();

    // Saves the new token
    NVSetList("BoardToken", $token, $messageParts[1]);

    // JSON structure to apply the media texture to the prim
    $textureInfo = [[
        "link" => 1,                // Target prim (1 = specific prim, not root)
        "face" => 3,                // Target face on the prim
        "type" => "media",          // Type: media (web URL)
        "scale" => [0.750, 0.563],  // Default scale
        "offset" => [0.0, 0.0],     // Default offset
        "rotation" => 0.0,          // No rotation

        "media" => [
            "url" => "https://wwwtest.slappflow.net/webcontrol/wcmedia.php?token={$token}&app=" . AFGetAppID(),
            "width" => 1024,           // Optional: adjust if known
            "height" => 768,           // Optional: adjust if known
            "auto_play" => true,       // Media auto-start
            "auto_scale" => false,     // Disable auto-scale (respects PRIM_TEXTURE scaling)
            "whitelist" => [
                "https://wwwtest.slappflow.net"
            ],
            "interact" => "anyone",     // Who can click/interact
            "control" => "none"       // Who can control (navigate, reload)
        ]
    ]];

    // Returns the structure, so can be applied using SLApplyTexture()
    return $textureInfo;

}

// Refreshes the token and sends the new URL from the board
// E. g. "LOCKSCREEN|<boardID>
if ($messageParts[0] === "LOCKSCREEN" && AFIsUnsafe() !== true)
{

    // Texture of the lock screen
    $textureInfo = [[
        "link" => 1,              // Numéro du prim (1 = prim spécifique, 0 = root)
        "face" => 3,              // Face à texturer (0 = toutes, ou à préciser)
        "type" => "texture",      // Indique qu'on applique une texture
        "scale" => [1.0, 1.0],    // Remplit la face (ajuste si besoin)
        "offset" => [0.0, 0.0],   // Centré
        "rotation" => 0.0,        // Pas de rotation

        "texture" => [
            "value" => "Lock screen",  // Nom EXACT de la texture dans l’inventaire de l’objet
            "source" => "inventory"    // Indique que la source est l’inventaire (pas une UUID ou URL)
        ]
    ]];

    // IMPLEMENT HERE THE TOKEN REMOVAL

    // Returns the structure, so can be applied using SLApplyTexture()
    return $textureInfo;

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

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

    // Gets the board using the token
    $boardID = NVGetList("BoardToken", $messageParts[1]);

    // If the board exists, updates the current page
    if ($boardID === null) return null;

    // Sending a message to the board owner to reset the lock screen timer
    $ownerID = NVGetList("BoardOwner", $boardID);

    // Sending a message to the board owner to reset the lock screen timer
    AFSendFlowMessage(AFGetAppID(), $ownerID, "RESETLOCK|" . $boardID);

    // Updates the current page in the database
    NVSetList("CurrentPage", $boardID, $messageParts[2]);

    // Successfully updated the page
    return true;

}

// Gets the current page from the board
// CALLED FROM THE FRONT-END MEDIA
// E. g. "GETPAGE|<token>"
if ($messageParts[0] === "GETPAGE" && AFGetSenderID() === "Media")
{

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

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

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

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

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

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

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

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

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

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