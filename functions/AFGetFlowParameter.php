<?php

// Function that returns a flow parameter
function AFGetFlowParameter($param)
{

    // Using global array to access the message parts
    global $msgParts;

    // Parameters are stored starting from index 4 in $msgParts
    // 0 => AppID
    // 1 => ReqType
    // 2 => FlowName (only relevant if ReqType = 'start_flow')
    // 3 => Session
    // 4 and above => Actual flow parameters

    // Index 4, we have the first parameter
    $index = 4 + (int)$param;

    return isset($msgParts[$index]) ? $msgParts[$index] : null;

}