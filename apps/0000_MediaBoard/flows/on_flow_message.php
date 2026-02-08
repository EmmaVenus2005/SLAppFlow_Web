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

// Lists the images from the board
// CALLED FROM THE FRONT-END MEDIA
// Sent to the owner of the board
// E. g. "LIST_IMAGES|<boardID>"
if ($messageParts[0] === "LIST_BOARD_IMAGES" && AFGetSenderID() === "Media")
{

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

    // Checks if the boardID exists, and belongs to the sender
    // ...

}

// Gets the image
// CALLED FROM THE FRONT-END MEDIA
// Sent to the owner of the board
// E. g. "GETIMAGE|<imageID>"
if ($messageParts[0] === "GET_BOARD_IMAGE" && AFGetSenderID() === "Media")
{

    // Since called from media, we have to sanitize the thread
    AFSetSafe();

    // Checks if the image is displayed on this board
    // ...

    // Gets the image from File Service
    $imageData = FSDownload($imageID);

    // Returns the image data directly
    return base64_encode($imageData);
    
}

// Message sent from WebControl to list the projects from the recipient the sender is allowed to access
// The sender has a list of users who trust him in its own scope, but doesn't know which projects
if ($messageParts[0] === "LIST_PROJECTS")
{
    
    // ... your code here ..



}

// List events

// List artists

// List venues

// List boards

// Get event info

// Get artist info

// Get venue info

// Get board info