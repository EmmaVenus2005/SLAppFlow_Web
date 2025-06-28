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
    $params = "?token=" . $token . "&app=" . AFGetAppID() . "&page=" . $current;

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
        $currentPage = NVGettList("CurrentPage", $boardID) ?? 1;

        // Sending the current page
        return $currentPage;

    }

}

// Always return explicitely, because if not, PHP returns 1
return;