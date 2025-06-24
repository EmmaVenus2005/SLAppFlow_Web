<?php

// wccall.php
//
// Creates the context for a function to be run (E.g. NVGetParam()).
//
// It is called from the app page when a function is called (back-end functions provided by SLAppFlow)

// Setting the session token length and bits per character for higher security
ini_set('session.sid_length', 64); // 64 characters
ini_set('session.sid_bits_per_character', 6); // base64 (highest entropy per char)

// PHP session (sould already exist from main file)
session_start();

// If $uuid and $name are not set, someone tries to access to the page without using main menu
// In this case ALWAYS abort the script for obvious security reason
if (!isset($_SESSION['uuid']) || !isset($_SESSION['name'])) { exit; }

// If the app is not set in the context, functions cannot be called
if (!isset($_SESSION['app'])) { exit; }

// Read POST JSON input
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

// Getting the function name and parameters from the input
$function = $data['Function'] ?? null;
$params = $data['Params'] ?? [];

// Get the directory path containing the PHP functions and classes
$functionsDir = $_SESSION['config']['dirs']['funcdir'];

// Sanitize function name to avoid directory traversal, etc.
if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $function)) {
    echo json_encode(['Success' => 'False', 'Return' => null, 'Message' => 'Illegal function name.']);
    exit;
}

// Check if the function file exists (avoids calling functions outside the functions directory)
if (!file_exists($functionsDir . '/' . $function . '.php')) {
    echo json_encode(['Success' => 'False', 'Return' => null, 'Message' => 'Function file not found.']);
    exit;
}

// Including all PHP files from the functions directory
foreach (glob($functionsDir . '/*.php') as $filename) {
    require_once $filename;
}

// Database connection details
$servername = $_SESSION['config']['appflowdb']['servername'];
$username = $_SESSION['config']['appflowdb']['username'];
$password = $_SESSION['config']['appflowdb']['password'];
$dbname = $_SESSION['config']['appflowdb']['dbname'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  
    echo $conn->connect_error;
    exit;

}

// Unsetting DB variables once connection done
unset($servername, $username, $password, $dbname);

// Creating "session" context as it is for an API flow
$config = $_SESSION['config'];
$appid = $_SESSION['app'];
$uuid = $_SESSION['uuid'];
$name = $_SESSION['name'];
$session = "";

// Setting a variable to know the call comes from front-end
$isFrontendCall = true;

// Call the function with the parameters (try)
try {

    // Call the function with the parameters
    $result = call_user_func_array($function, $params);
    echo json_encode(['Success' => 'True', 'Return' => $result, 'Message' => 'Function executed']);

// If the function does not exist or fails, catch the exception
} catch (Exception $e) {
    echo json_encode(['Success' => 'False', 'Return' => null, 'Message' => $e->getMessage()]);
}