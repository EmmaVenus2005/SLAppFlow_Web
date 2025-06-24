<?php

function AFSendFlowMessage($toApp, $toRecipient, $message) {
    
    // Ensure global variables are available
    global $config, $appid, $uuid, $name, $isFrontendCall;

    // Ensure required parameters are set
    if (!isset($toApp, $toRecipient)) {
        error_log("AFSendFlowMessage: Invalid parameters.");
        return false;
    }

    // Creates the context array
    $context = [
        'config'            => $config,
        'appid'             => $toApp,
        'uuid'              => $toRecipient,
        'name'              => "",
        'session'           => "",
        'sender_appid'      => $appid,
        'sender_uuid'       => $uuid,
        'sender_name'       => $name,
        'message'           => $message,
        'isFrontendCall'    => $isFrontendCall
    ];

    // Create a JSON representation of the context
    $jsonContext = json_encode($context);

    // Command to run
    $cmd = ['php', $config['dirs']['homedir'] . '/api/send_flow_message.php'];

    // Set the descriptors for the process
    $descriptors = [
        0 => ['pipe', 'r'],  // STDIN
        1 => ['pipe', 'w'],  // STDOUT
        2 => ['pipe', 'w'],  // STDERR
    ];

    // Start the process
    $process = proc_open($cmd, $descriptors, $pipes);

    // Check if the process was started successfully
    if (!is_resource($process)) {
        error_log("AFSendFlowMessage: Failed to start subprocess.");
        return false;
    }

    // Send context via STDIN
    fwrite($pipes[0], $jsonContext);
    fclose($pipes[0]);

    // Read output
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Read stderr
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    // Close the process
    proc_close($process);

    // Log the error output if any
    if ($stderr) {
        error_log("AFSendFlowMessage stderr: $stderr");
    }

    // Return the output from STDOUT
    return $stdout;
    
}