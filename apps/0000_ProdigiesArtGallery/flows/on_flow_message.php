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
    //SLOwnerSay($messageParts[1], "Timer reset for the lock screen");

    // Successfully reset the lock screen timer
    return true; 

}

// Messages are sent to the "Global" user, which is not an actual avatar
// but a common user for the ArtGallery app.
if (AFGetOwnerID() !== "Global") {
    return; 
}

// Saves the board owner in the "Global" user context
// Called from on_hooked event
// E. g. "BOARDOWNER|<boardID>|<ownerID>"
if ($messageParts[0] === "BOARDOWNER" && AFIsUnsafe() !== true)
{

    // Saves the board owner in the database
    NVSetList("BoardOwner", $messageParts[1], $messageParts[2]);

    // Successfully saved the board owner
    return true;

}

// Saves the board painting style in the "Global" user context
// Called from on_touch, when the owner touches it
// E. g. "SETSTYLE|<boardID>|<style>"
if ($messageParts[0] === "SETSTYLE" && AFIsUnsafe() !== true)
{

    // Saves the style in the database
    NVSetList("BoardStyle", $messageParts[1], $messageParts[2]);

    // Successfully saved the board style
    return true;

}

// Returns the board painting style from the "Global" user context
// Called from on_touch, when the owner touches it
// E. g. "GETSTYLE|<boardID>"
if ($messageParts[0] === "GETSTYLE" && AFIsUnsafe() !== true)
{

    // Returns the style in the database
    return NVGetList("BoardStyle", $messageParts[1]);

}

// Refreshes the token and sends the new URL from the board
// E. g. "UPDATEURL|<boardID>
if ($messageParts[0] === "UPDATEURL" && AFIsUnsafe() !== true)
{

    // Generating a new UUID as a token
    $token = AFGenerateUUID();

    // Saves the new token
    NVSetList("BoardToken", $token, $messageParts[1]);

    // Retrieving the board owner, in order to send him a message
    $ownerID = NVGetList("BoardOwner", $messageParts[1]);

    // Sending a message to the board owner to reset the lock screen timer
    AFSendFlowMessage(AFGetAppID(), $ownerID, "RESETLOCK|" . $messageParts[1]);

    // JSON structure to apply the media texture to the prim
    $textureInfo = [[
        "link" => 1,                // Target prim (1 = specific prim, not root)
        "face" => 3,                // Target face on the prim
        "type" => "media",          // Type: media (web URL)
        "scale" => [0.750, 0.563],  // Default scale
        "offset" => [0.0, -0.050],  // Default offset
        "rotation" => 0.0,          // No rotation

        "media" => [
            "url" => "https://wwwtest.slappflow.net/webcontrol/wcmedia.php?token={$token}&app=" . AFGetAppID(),
            "width" => 1024,           // Optional: adjust if known
            "height" => 768,           // Optional: adjust if known
            "auto_play" => 1,          // Media auto-start
            "auto_scale" => 0,         // Disable auto-scale (respects PRIM_TEXTURE scaling)
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

    // Removing all tokens from that board (cleaning)
    
    // Get all entries with Class = 'BoardToken'
    $rows = NVEnumerateLists('BoardToken');

    if ($rows !== false) 
    {

        // Looping through all rows
        foreach ($rows as $row) 
        {
            
            // If the token is for that board
            // 'Name' stores the token
            // 'Elements' corresponds to the board ID
            if ($row['Elements'] === $messageParts[1])
            {

                // Deletes the token
                NVDelList("BoardToken",  $row['Name']);

            }

        }

    }

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

    // Returns the structure, so can be applied using SLApplyTexture()
    return $textureInfo;

}

// When a piece of art is rezzed, checks if the owner changed
// E. g. "REZZED|Painting_UNICAT0000000001|{ JSON data }"
if ($messageParts[0] === "REZZED" && AFIsUnsafe() !== true) 
{

    // Gets the UNICAT number (without the leading Painting_ mention)
    $unicatNumber = explode("_", $messageParts[1])[1];
    
    // Recovering the last known owner from the database 
    $lastOwnerID = NVGetSessionValue($unicatNumber, "LastOwnerID");

    // We check if the last owner ID is different from the last known one
    if ($lastOwnerID !== AFGetSenderID()) 
    {

        // Sets the new owner ID in the database
        NVSetSessionValue($unicatNumber, "LastOwnerID", AFGetSenderID());
        
        // Stores the last transaction in the list, with the JSON as additional data
        NVSetSessionList($unicatNumber, "NewOwner", date('c'), $messageParts[2]);

    }

}

// Bids on the currently selected painting. Called from on_payment event
// E. g. "BID|<boardID>|<bidder uuid>|<bidder name>|<amount>
if ($messageParts[0] === "BID" && AFIsUnsafe() !== true)
{

    // Retrieving the board owner, in order to send him a message
    $ownerID = NVGetList("BoardOwner", $messageParts[1]);

    // Sending a message to the board owner to reset the lock screen timer
    AFSendFlowMessage(AFGetAppID(), $ownerID, "RESETLOCK|" . $messageParts[1]);
    
    // Get the current page related to the boardID
    $currentPage = NVGetList("CurrentPage", $messageParts[1]);

    // Get the list of existing bids for this painting
    $bidsList = NVGetSessionLists($currentPage, "Bid");

    // Removes the ending Resident from the bidder's name
    $username = $messageParts[3];
    if (substr($username, -9) === " Resident") {
        $username = substr($username, 0, -9);
    }

    // If there are no previous bids, accept this bid directly if at least the start price
    if (!$bidsList || count($bidsList) === 0) {
        
        // Reading the start-price, must be equal or higher
        $activeAuction = json_decode(NVGetList("ActiveAuction", $currentPage), true);
        $startPrice = $activeAuction['start_price'];

        // Returns LOWERSTARTPRICE|<painting name>|<start price>
        if ($messageParts[4] < $startPrice) return "LOWERSTARTPRICE|" . GetUNICATName($currentPage) . "|" . $startPrice;
        
        $bid = [
            "board"  => $messageParts[1],
            "uuid"   => $messageParts[2],
            "name"   => $username,
            "amount" => $messageParts[4]
        ];
        
        // Save the new bid to the database
        NVSetSessionList($currentPage, "Bid", date('Y-m-d H:i:s'), json_encode($bid));
        
        // Nothing to refund since it's the first bid
        return "FIRSTBID|" . GetUNICATName($currentPage);

    }

    // Sort all bids by their date (ascending order)
    usort($bidsList, function($a, $b) {
        return strtotime($a) <=> strtotime($b);
    });

    // Get the key (timestamp) of the last bid
    $lastBidKey = end($bidsList);

    // Retrieve the JSON-encoded last bid details from storage
    $lastBidJson = NVGetSessionList($currentPage, "Bid", $lastBidKey);
    $lastBidDetails = json_decode($lastBidJson, true);

    // If the new bid amount is not greater than the previous, reject the bid
    if ($lastBidDetails['amount'] >= $messageParts[4]) {
        return "REJECT|" . GetUNICATName($currentPage) . "|" . $lastBidDetails['amount'] + 1;
    }

    // Build the new bid data structure
    $bid = [
        "board"  => $messageParts[1],
        "uuid"   => $messageParts[2],
        "name"   => $username,
        "amount" => $messageParts[4]
    ];

    // Save the new bid to the database
    NVSetSessionList($currentPage, "Bid", date('Y-m-d H:i:s'), json_encode($bid));

    // Returns "REFUNDPREV|<painting name>|<previous bidder>|<previous best bid>"
    return "REFUNDPREV|" . GetUNICATName($currentPage) . "|" . $lastBidDetails['uuid'] . "|" . $lastBidDetails['amount'];

}

// When the page from a board changes, updates the current page in the database
// CALLED FROM THE FRONT-END MEDIA
// E. g. "PAGECHANGE|<token>|<pageNumber>"
if ($messageParts[0] === "PAGECHANGE" && AFGetSenderID() === "Media")
{

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

    // Gets the board using the token
    $boardID = NVGetList("BoardToken", $messageParts[1]);

    // If the board exists, updates the current page
    if ($boardID === null) return null;

    // Retrieving the board owner, in order to send him a message
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

    // Filter out expired paintings
    $filteredList = [];
    foreach ($paintingsList as $painting) {
        if (IsUNICATActive($painting)) {
            $filteredList[] = $painting;
        }
    }

    // Returns the list of paintings as JSON
    return json_encode($filteredList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

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

    // Creating a new empty array
    $bidderDetails = [];

    // For each bid, recovering the bidder name
    foreach($lastBids as &$bid) 
    {
     
        // Gets the elements of each bid (including the name, UUID and price)
        $bidderDetails[] = [$bid, NVGetSessionList($messageParts[2], "Bid", $bid)];

    }

    // Returns the list of bidders
    return json_encode($bidderDetails, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

}

// Checks if the user is an admin
// Called from WebControl authenticated user to "Global"
// E. g. "ISADMIN"
if ($messageParts[0] === "ISADMIN" && AFIsUnsafe() !== true)
{

    // Returns true or false weither the sender is admin
    return IsAdmin(AFGetSenderID());

}

// Gets the list of UNICATS
// Called by admin or trusted user ONLY through WebControl
// E. g. "LISTUNICATS"
if ($messageParts[0] === "LISTUNICATS" && IsAdmin(AFGetSenderID()))
{

    // At this point, we know we're safe
    AFSetSafe();

    // Gets all the UNICATS
    $unicats = NVGetLists("Information");

    // Preparing an empty array
    $output = [];
    
    // Looping through each UNICAT
    // UNICAT example string : "Painting_UNICAT1_Painting name"
    foreach ($unicats as $unicat)
    {

        // Check and update auction status first
        IsUNICATActive($unicat);

        // Getting the general information about the painting
        $jsonInfo = NVGetList("Information", $unicat);
        $info = json_decode($jsonInfo, true);

        // Merge the UNICAT number into the output array
        $output[] = array_merge(['number' => $unicat], $info);

    }

    // Return the result as JSON (echo or return depending on your structure)
    return json_encode($output);

}

// Gets the image of a given painting
// Called by admin or trusted user ONLY through WebControl
// E. g. "GETIMAGE|<unicat number>"
if ($messageParts[0] === "GETIMAGE" && IsAdmin(AFGetSenderID()))
{

    // Gets the image ID for the specified painting (unicat number)
    $imageID = NVGetList("Image", $messageParts[1]);

    // If the image ID doesn't exist, returns null
    if ($imageID === null) return null;

    // Gets the image data from File Service
    $imageData = FSDownload($imageID);

    // Returns the image data as base64
    return base64_encode($imageData);

}

// Adds a new UNICAT entry
// E.g. "ADDUNICAT|UNICAT234|My super test painting|Emma Gee-Venus|This is a test description."
if ($messageParts[0] === "ADDUNICAT" && IsAdmin(AFGetSenderID())) 
{

    // Decoding the incoming information
    $number       = $messageParts[1];
    $unicatName   = $messageParts[2];
    $creatorName  = $messageParts[3];
    $description  = $messageParts[4];

    // Create the JSON structure (status is forced to "Inactive")
    $data = [
        "name"        => $unicatName,
        "description" => $description,
        "creatorName" => $creatorName,
        "status"      => "Inactive"
    ];

    // Save into the Information list
    NVSetList("Information", $number, json_encode($data, JSON_UNESCAPED_UNICODE));

    // Successfully created
    return true;

}

// Stores a base64-encoded image for a UNICAT painting
// E.g. "SETIMAGE|UNICAT234|<base64 image string>"
if ($messageParts[0] === "SETIMAGE" && IsAdmin(AFGetSenderID())) 
{

    $number     = $messageParts[1];      // UNICAT number (e.g., UNICAT234)
    $base64     = $messageParts[2];      // Base64-encoded image

    // Decode the image
    $binaryData = base64_decode($base64);

    // Minimal validation
    if (!$binaryData || strlen($binaryData) < 1000) {
        return false;
    }

    // Build a unique file name (e.g. UNICAT234.jpg)
    if (str_starts_with($base64, '/9j/')) {
        $filename = $number . ".jpg";
    } elseif (str_starts_with($base64, 'iVBOR')) {
        $filename = $number . ".png";
    } else {
        return false; // unsupported format
    }

    // Upload the file to the File Service
    $fileID = FSUpload($filename, $binaryData);

    // If upload failed
    if (!$fileID) {
        return false;
    }

    // Associate the uploaded file ID to the UNICAT number
    NVSetList("Image", $number, $fileID);

    // File saved
    return true;
    
}

// Changes the description of a given UNICAT
// E.g. "SETDESCRIPTION|UNICAT234|New description for this painting"
if ($messageParts[0] === "SETDESCRIPTION" && IsAdmin(AFGetSenderID())) 
{

    // Reads the input parts
    $number      = $messageParts[1];
    $description = $messageParts[2];

    // Retrieve the current info for this UNICAT
    $json = NVGetList("Information", $number);
    if ($json === null) return false;

    // Decode the JSON and update the description
    $info = json_decode($json, true);
    $info['description'] = $description;

    // Save the updated info back to the database
    NVSetList("Information", $number, json_encode($info, JSON_UNESCAPED_UNICODE));

    // Successfully updated
    return true;

}

// Changes the category of a given UNICAT
// E.g. "SETCATEGORY|UNICAT234|Modernism"
if ($messageParts[0] === "SETCATEGORY" && IsAdmin(AFGetSenderID())) 
{
    
    // Extract the UNICAT number and new category
    $number   = $messageParts[1];
    $category = $messageParts[2];

    // Retrieve the current info for this UNICAT
    $json = NVGetList("Information", $number);
    if ($json === null) return false;

    // Decode the JSON and update the category
    $info = json_decode($json, true);
    $info['category'] = $category;

    // Save the updated info back to the database
    NVSetList("Information", $number, json_encode($info, JSON_UNESCAPED_UNICODE));

    // Successfully updated
    return true;

}

// Always return explicitely, because if not, PHP returns 1
return;