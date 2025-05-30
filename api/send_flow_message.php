<?php

// This is called by the function AFSendFlowMessage(),
// and creates a new context where the recipient is the owner,
// to have always the same point of view from the flows

// Block web access: only allow CLI execution
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

// --- Input validation ---
if ($argc >= 2 && file_exists($argv[1])) {
    $contextData = file_get_contents($argv[1]);
} else {
    // Read from STDIN
    $contextData = stream_get_contents(STDIN);
}

$context = json_decode($contextData, true);

// --- Extract context into local variables ---
extract($context);

// --- Ensure required keys exist ---
$required = ['config', 'appid', 'uuid', 'name', 'session', 'sender_appid', 'sender_uuid', 'sender_name', 'message', 'homeDir'];
foreach ($required as $key) {
    if (!isset($context[$key])) {
        fwrite(STDERR, "Missing context key: $key\n");
        exit(1);
    }
}

// Database connection details
$servername = $config['appflowdb']['servername'];
$username = $config['appflowdb']['username'];
$password = $config['appflowdb']['password'];
$dbname = $config['appflowdb']['dbname'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    //ErrDbConn($conn->connect_error);
}

// --- Include all utility functions ---
$functionsDir = $homeDir . '/functions/';
if (is_dir($functionsDir)) {
    foreach (glob($functionsDir . '*.php') as $filename) {
        require_once $filename;
    }
}

// --- Execute the flow ---
$flowPath = $homeDir . '/apps/' . $appid . '/flows/on_message.php';

$reply = null;

if (file_exists($flowPath)) {
    
    include $flowPath;

} else {
    fwrite(STDERR, "Flow file not found at $flowPath\n");
    exit(1);
}

// --- Output result ---
echo $reply;

exit(0);
