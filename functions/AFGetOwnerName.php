<?php

// Function that returns the Owner Name
function AFGetOwnerName()
{

    // Global variable that gets the owner Name from request headers
    // or in case of webcontrol, the connected user
    global $name;

    // If the name is set in the global context
    if (isset($name) && !empty($name)) 
    {
        
        // Return the name
        return $name;

    }
    
    // In case of AFSendFlowMessage(), the name is not set
    // (looking for the name in the database)

    // Enumerates the webhooks to find the owner name
    $webHooks = NVEnumerateValues('FlowURL');
    
    // Sorting them by Timestamp, to have the most recent first
    usort($webHooks, function ($a, $b) {
        return $b['Timestamp'] <=> $a['Timestamp'];
    });

    // Gets the UserName from the first webhook
    return $webHooks[0]['UserName'] ?? '';

}