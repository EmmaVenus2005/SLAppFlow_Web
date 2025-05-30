<?php

// Function that returns the sender's display name
function AFGetSenderName() {
    global $sender_name;

    return $sender_name ?? null;
}