<?php

// Checks if the given user is admin of the art gallery
function IsAdmin($user)
{

    // Only relevant for the Global user
    if (AFGetOwnerID() !== "Global") return false;

    // Checks if the sender is an owner
    $admins = NVGetLists("Admin");

    // Initial value
    $isAdmin = false;

    // Looping through all admins
    foreach ($admins as $admin)
    {

        // Current sender is found
        if ($user === $admin)
        {

            // Sets the user as admin
            $isAdmin = true;

            // No need to check further
            break;

        }

    }

    // Returns true or false
    return $isAdmin;

}