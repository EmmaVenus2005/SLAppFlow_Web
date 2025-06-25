<?php

// This function unsets $isFrontendCall when called. The variable is set to 'true' when a function is
// called from WebControl front-end (wccall.php), to identify when a message can't be trusted. In the
// 'on_flow_message.php' event, once ensured the request is safe, if I want to cascade another message
// that I don't want to get called directly from front-end, I need to ensure to call this function,
// to pass the AFIsUnsafe() check.
//
// It would be useless to call this from front-end in order to hack it, since anyway each function call
// is a separate thread, would be set 'true' again on next call.
function AFSetSafe()
{

    // The variable that is 'true' when the command is called from WebControl
    global $isFrontendCall;

    // Unsetting it, so next flow message will be considered as safe
    // BE CAREFUL THOUGH TO MAKE ALL THE CHECKS
    if (isset($isFrontendCall)) { unset($isFrontendCall); }

}