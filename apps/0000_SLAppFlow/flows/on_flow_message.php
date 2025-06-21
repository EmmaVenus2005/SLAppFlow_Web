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

// If the message is not from the same app, ignore it
// (If you handle messages from other apps, you can remove this check)
// BE CAREFUL: You don't control the sender app, carefully implement the logic to avoid security issues 
if (AFGetSenderAppID() !== AFGetAppID()) { return; }

// If the message is not from the same owner, ignore it
// (If you handle messages from other owners, you can remove this check)
//if (AFGetSenderID() !== AFGetOwnerID()) { return; }

// ... your code here ...

// Separate the message into parts
$messageParts = explode("|", $message);

// Check if the user already has an account
if ($messageParts[0] === "CHECKACCOUNT") {
    
    return NVGetValue("Login") ? true : false;

// Registers the user
} elseif ($messageParts[0] === "CREATEACCOUNT")  {
   
    NVSetValue("Login", $messageParts[1]);
    NVSetValue("Password", $messageParts[2]);

    return true;

// Resets the user's password
} elseif ($messageParts[0] === "RESETPASSWORD") {
    
    NVSetValue("Password", $messageParts[1]);

    return true;

}

// Always return explicitely, because if not, PHP returns 1
return;