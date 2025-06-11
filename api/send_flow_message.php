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

// Read from STDIN
$contextData = stream_get_contents(STDIN);

// Decode the JSON context data
$context = json_decode($contextData, true);

// Extract context into local variables
extract($context);

// Ensure required keys exist
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
    fwrite(STDERR, "Database connection failed: " . $conn->connect_error . "\n");
    exit(1);
}

// Include all utility functions
$functionsDir = $homeDir . '/functions/';
if (is_dir($functionsDir)) {
    foreach (glob($functionsDir . '*.php') as $filename) {
        require_once $filename;
    }
}

// Execute the flow
$flowPath = $homeDir . '/apps/' . $appid . '/flows/on_flow_message.php';

// Initialize reply variable
//$reply = null;

// Check if the flow file exists
if (!file_exists($flowPath)) 
{
    
    // Close the database connection
    $conn->close();

    // Flow file not found
    fwrite(STDERR, "Flow file not found at $flowPath\n");
    exit(1);

}

// Execute the flow and get the return value
try {

    $reply = include $flowPath;

} finally {
    
    // Close the database connection in all cases
    $conn->close();

}

// Output result
echo is_array($reply) ? json_encode($reply) : (string)$reply;

// Exit with success
exit(0);