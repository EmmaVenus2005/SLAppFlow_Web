<?php

function AFSendFlowMessage($toApp, $toRecipient, $message) {
    global $config, $appid, $uuid, $name, $homeDir;

    if (!isset($toApp, $toRecipient)) {
        error_log("AFSendFlowMessage: Invalid parameters.");
        return false;
    }

    $recipientName = ""; // Optionally resolve name

    $context = [
        'config'       => $config,
        'homeDir'      => $homeDir,
        'appid'        => $toApp,
        'uuid'         => $toRecipient,
        'name'         => $recipientName,
        'session'      => "",
        'sender_appid' => $appid,
        'sender_uuid'  => $uuid,
        'sender_name'  => $name,
        'message'      => $message,
    ];

    $jsonContext = json_encode($context);

    // Command to run
    $cmd = ['php', $homeDir . '/api/send_flow_message.php'];

    $descriptors = [
        0 => ['pipe', 'r'],  // STDIN
        1 => ['pipe', 'w'],  // STDOUT
        2 => ['pipe', 'w'],  // STDERR
    ];

    $process = proc_open($cmd, $descriptors, $pipes);

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

    // Optionally: log stderr
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    proc_close($process);

    if ($stderr) {
        error_log("AFSendFlowMessage stderr: $stderr");
    }

    return $stdout;
    
}