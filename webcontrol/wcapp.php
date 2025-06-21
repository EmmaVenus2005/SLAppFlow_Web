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

// TO IMPLEMENT : Check if the app is valid, using backend list

// The selected app is stored in the session
$_SESSION['app'] = $_GET['app'];

// Get the app directory
$appDir = realpath(__DIR__ . "/../apps/" . $_SESSION['app'] . "/");

// Displaying the requested app
echo file_get_contents("$appDir/web/index.html");