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

// Here the sender sets the owner as a trusted or owner (owner in BDSM terms, not the object). 
// In order to be able to list the avatars who trust me or own me, we add it to the trustee or owning list.

// Checking the first part of the message (ADDTRUSTED|, ADDOWNER|, etc.)
$message = AFGetMessage();

if ($message === "ADDTRUSTED") {

    // Creating a JSON object to store the sender's information
    $senderInfo = [
        "Name" => AFGetSenderName()
    ];
    
    // Add the sender to the trustee list
    NVSetList("TrustsMe", AFGetSenderID(), json_encode($senderInfo));

} elseif ($message === "ADDOWNER") {
    
    // Creating a JSON object to store the sender's information
    $senderInfo = [
        "Name" => AFGetSenderName()
    ];
    
    // Add the sender to the trustee list
    NVSetList("Owning", AFGetSenderID(), json_encode($senderInfo));

}

// Always return explicitely, because if not, PHP returns 1
return;