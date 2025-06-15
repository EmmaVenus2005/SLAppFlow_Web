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
//if (AFGetSenderAppID() !== AFGetAppID()) { return; }

// If the message is not from the same owner, ignore it
// (If you handle messages from other owners, you can remove this check)
if (AFGetSenderID() !== AFGetOwnerID()) { return; }

// ... your code here ...

// Add here all the apps that are allowed to send messages to this app
const allowedApps = [
  "0000_DressUp",
  "0000_OpenCollar"
  // ...
];

// Ignore messages from disallowed apps
if (!in_array(AFGetSenderAppID(), allowedApps)) {
    return; 
}

// Get the last active HUD from the database
$lastHUD = NVGetValue("LastActiveHUD");

// Gets the message sent by the sender
$message = AFGetMessage();

// Debugging: Log the message received
//SLOwnerSay($lastHUD, $message);

// Used to check the first part of the message (SAY|, OPEN|, etc.)
$messageParts = explode("|", $message);

if ($messageParts[0] === "SAY") {
    
  // Debugging: Log the message received
  SLOwnerSay($lastHUD, $messageParts[1]);	

} elseif ($messageParts[0] === "OPEN")  {
   
  // Opens DressUp app
  Main($lastHUD, $messageParts[1], $messageParts[2]);

}

// Always return explicitely, because if not, PHP returns 1
return;