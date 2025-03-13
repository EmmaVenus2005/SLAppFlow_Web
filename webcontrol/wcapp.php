<?php

// PHP session (sould already exist from main file)
session_start();

// If $uuid and $name are not set, someone tries to access to the app without using main menu
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
$servername = $config['appflowdb']['servername'];
$username = $config['appflowdb']['username'];
$password = $config['appflowdb']['password'];
$dbname = $config['appflowdb']['dbname'];

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
//$session = "";

// Creating an instance of NonVolatile class
$nv = new NonVolatile();
$nv->setApp('DressUp');
$nv->setUser($_SESSION['uuid'], $_SESSION['name']);

// INCLUDE APP FUNCTIONS


// APP TO INCLUDE HERE

// Test
echo $_GET['app'];

// Close the database connection
$conn->close();