<?php

// wcapp.php
//
// Creates the context for the app to be run.
//
// It is called from wchome.php when the user selects an app from the menu
// app is given as GET parameter 'app'.

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
    //sErrDbConn($conn->connect_error);
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

// The selected app is stored in the session
$_SESSION['app'] = $_GET['app'];

// Get the app directory
$appDir = realpath(__DIR__ . "/../apps/$appid/");

// Including app-specific functions
if (is_dir("$appDir/functions/")) {
    // Scan the directory for PHP files
    foreach (glob("$appDir/functions/*.php") as $filename) {
        // Include each PHP file
        require_once $filename;
    }
}

// Including the requested app
//include "$appDir/web/index.php";
echo file_get_contents("$appDir/web/index.html");

// Close the database connection
$conn->close();