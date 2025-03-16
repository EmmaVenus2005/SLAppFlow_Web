<?php

    // Ensure necessary variables are available
    if (!isset($appid, $objid, $uuid, $name, $conn, $session)) {
        error_log("Required variables are not set.");
        exit();
    }

    // $flowParams will be the RLV commands
    // $session is commandID@senderUUID

    // Getting the command and sender id
    $commandID = explode("@", $session)[0];
    $senderID = explode("@", $session)[1];

    // ICE command ^^
    //SLOwnerSay("@clear");


    // Channel on which RLV commands are sent (used for acknoldedgement sending)
    $RLV_CHANNEL = -1812221819;

    // Creating the arrays with commands to actually execute and other to ignore
    // Each command must be in one of these arrays (only one by command)
    $toExecute = [];
    $toIgnore = [];

    // Creating an instance of the RLV Helper
    $rlvHelper = new RLVCommandHelper();

    // Looping through commands
    foreach($flowParams as $currentCommand)
    {

        // This becomes true if the command is allowed
        $isAllowed = false;

        // Getting the infos from the command
        $cmdInfo = $rlvHelper->GetCommandInfo($currentCommand);

        // Check ! commands

        // Allowing 'get' commands
        $cmdInfo[0]['CommandType'] === "Get" ? $isAllowed = true : null;

        // TO IMPLEMENT : Logic to allow things


                
        // DEBUG
        $isAllowed ? SLOwnerSay($objid, "Allowed command " . $currentCommand) : null;
        //SLOwnerSay("Command : " . $currentCommand);

        // If the command has been allowed, goes in toExecute[], otherwise in toIgnore[]
        $isAllowed ? $toExecute[] = $currentCommand : $toIgnore[] = $currentCommand;

    }

    // Actually sending the command to the viewer
    SLRLVCommand($objid, $toExecute);

    // This string will be sent using SLRegionSayTo, to acknowledge the command (ok or ko)
    $regionSayString = "";

    // Starting with OK
    foreach ($toExecute as $current)
    {

        // Adding the acknowledgement string
        $regionSayString .= $commandID . "," . $senderID . "," . $current . ",ok|";

    }

    // Starting with OK
    foreach ($toIgnore as $current)
    {

        $regionSayString .= $commandID . "," . $senderID . "," . $current . ",ko|";

    }

    // Sending actual acknowledgement
    SLRegionSayTo($objid, $regionSayString, $RLV_CHANNEL, $senderID);

    // DEBUG
    //SLOwnerSay(str_replace('|', "\n", $regionSayString));

    // Destroys the instance and closes DB connection
    unset($rlvHelper);
    
?>