<?php

// wcfunctions.php
//
// This file returns the JavaScript code to access the PHP functions through AJAX.
// 
// Called by the app web page, to load the JavaScript that will declare the funcitons

// Setting the session token length and bits per character for higher security
ini_set('session.sid_length', 64); // 64 characters
ini_set('session.sid_bits_per_character', 6); // base64 (highest entropy per char)

// PHP session (sould already exist from main file)
session_start();

// If $uuid and $name are not set, someone tries to access to the page without using main menu
// In this case ALWAYS abort the script for obvious security reason
if (!isset($_SESSION['uuid']) || !isset($_SESSION['name'])) { exit(); }

// Get the directory path containing the functions we use as commands
$functions_dir = __DIR__ . '/../functions';
$function_data = [];

// Include all PHP files in the functions directory
foreach (glob("$functions_dir/*.php") as $file) {
    include_once $file;
}

// Get all user-defined functions
$all_functions = get_defined_functions()['user'];

// Loop through all user-defined functions
foreach ($all_functions as $fn) 
{

    // Get the function's reflection
    $ref = new ReflectionFunction($fn);

    // Store the function name and number of parameters
    $function_data[] = [
        'name' => $ref->getName(),
        'params' => $ref->getNumberOfParameters()
    ];

}

// Set the content type to JSON and output the function data
header('Content-Type: application/json');
echo json_encode($function_data);