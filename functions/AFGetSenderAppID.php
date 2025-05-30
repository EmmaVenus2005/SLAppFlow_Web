<?php

// Returns the application identifier of the sender (only for on_message flow)
function AFGetSenderAppID()
{
    global $sender_appid;
    return $sender_appid;
}