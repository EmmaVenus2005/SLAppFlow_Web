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
if (!isset($_SESSION['uuid']) || !isset($_SESSION['name'])) { exit(); }

// Get the directory path containing the PHP functions and classes
$functionsDir = realpath(__DIR__ . '/../functions/');

// Including all PHP files
if ($functionsDir !== false && is_dir($functionsDir)) {
    foreach (glob($functionsDir . '/*.php') as $filename) {
        require_once $filename;
    }
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
$appid = $_GET['app'];
$uuid = $_SESSION['uuid'];
$name = $_SESSION['name'];
$session = "";

// Calling the function sent by GET parameter 'function'
$function = isset($_GET['function']) ? $_GET['function'] : '';

// Trying to call the function with the given parameters
if (function_exists($function)) 
{

    // Get the parameters from the GET request (param1, param2, etc.)
    $params = [];
    foreach ($_GET as $key => $value) {
        if (strpos($key, 'param') === 0) {
            $params[] = $value;
        }
    } 

    // Call the function with the parameters (try)
    try {

        // Call the function with the parameters
        $result = call_user_func_array($function, $params);
        echo json_encode(['Status' => 'OK', 'Return' => $result, 'Message' => 'Function executed']);
    
    // If the function does not exist or fails, catch the exception
    } catch (Exception $e) {
        echo json_encode(['Status' => 'ERROR', 'Return' => null, 'Message' => $e->getMessage()]);
    }

}