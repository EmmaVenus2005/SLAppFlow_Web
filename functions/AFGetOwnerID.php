<?php

// Function that returns the Owner UUID
function AFGetOwnerID()
{

    // Global variable that gets the owner UUID from request headers
    // or in case of webcontrol, the connected user
    global $uuid;

    return $uuid;

}