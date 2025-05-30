<?php

// Returns the UUID of the avatar who sent the message
function AFGetSenderID()
{
    global $sender_uuid;
    return $sender_uuid;
}