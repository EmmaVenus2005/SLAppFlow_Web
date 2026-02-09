<?php

/**
 * CheckTrusted
 *
 * Returns true if the sender is in my Trusters list.
 *
 * @param string $senderId
 * @return bool
 */
function CheckTrusted($senderId)
{
    
if (!is_string($senderId) || $senderId === "") {
        return false;
    }

    $json = NVGetList("Trusters", "List");
    if (!is_string($json) || $json === "") {
        return false;
    }

    $trusters = json_decode($json, true);
    if (!is_array($trusters)) {
        return false;
    }

    return in_array($senderId, $trusters, true);

}