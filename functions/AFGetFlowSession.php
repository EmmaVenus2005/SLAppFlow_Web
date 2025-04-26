<?php

// Function that returns the flow session
function AFGetFlowSession()
{
    
    // Using global array to access the message parts
    global $msgParts;

    // Parameters are stored starting from index 0 in $msgParts
    // 0 => AppID
    // 1 => ReqType
    // 2 => FlowName (only relevant if ReqType = 'start_flow')
    // 3 => Session

    // Session is always at index 3
    return isset($msgParts[3]) ? $msgParts[3] : null;

}