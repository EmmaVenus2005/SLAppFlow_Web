<?php

function AFIsUnsafe(): bool 
{
    
    // This variable is set to true when the call comes from WebControl (frontend).
    global $isFrontendCall;

    // Return true if the call is considered unsafe (i.e., from frontend), false otherwise.
    return $isFrontendCall ?? false;

}