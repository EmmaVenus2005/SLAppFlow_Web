<?php

// Function that returns the Owner Name
function AFGetOwnerName()
{

    // Global variable that gets the owner Name from request headers
    // or in case of webcontrol, the connected user
    global $name;

    return $name;

}