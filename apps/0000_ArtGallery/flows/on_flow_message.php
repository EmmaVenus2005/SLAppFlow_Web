<?php

/**
 * Contextual functions used during flow execution
 * -----------------------------------------------
 * 
 * - AFGetAppID()               → Application identifier (unique per app instance)
 * - AFGetOwnerID()             → UUID of the recipient avatar
 * - AFGetOwnerName()           → Display name of recipient avatar
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
if (AFIsUnsafe()) { return; }

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

// Check the first part of the message (SAY|, OPEN|, etc.)
$messageParts = explode("|", AFGetMessage());

if ($messageParts[0] === "REZZED") {
    
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

// Always return explicitely, because if not, PHP returns 1
return;