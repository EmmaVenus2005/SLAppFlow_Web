<?php

// wcindex.php
//
// This is called when opening the webpage. It will display the navigation bar, authenticate the user
// with its key (given in URL using id GET property).
// Once authenticated, sets 'uuid' and 'name' as PHP session variables, used to know if the user is 
// well authenticated.
// If an app is selected, will include 'webcontrol/wcapp.php', and give the appid as GET parameter.

// Setting the session token length and bits per character for higher security
ini_set('session.sid_length', 64); // 64 characters
ini_set('session.sid_bits_per_character', 6); // base64 (highest entropy per char)

// Starting a new PHP session
session_start();

// Setting config file to be used by the webcontrol
$_SESSION['config'] = $config;

// If the username and password have been sent via POST, it means the user is trying to authenticate
if (isset($_POST['username']) && isset($_POST['password']))
{ 

    // Includes the authentication script 
    // (is actually setting the session variables if the authentication is successful)
    include "webcontrol/wcauth.php";

}

// If the user is not yet authenticated
if (!isset($_SESSION['uuid']) || !isset($_SESSION['name'])) 
{ 

    // Includes the authentication page 
    include "webcontrol/wclogin.php";

    // Nothing further to do, the authentication script will handle the rest
    exit(); 

}

// Creating array that will contain the available apps list
$apps = [];

// Directory where apps are located
$appsDir = $config['dirs']['appsdir'];

// Get the directory path containing the PHP functions
$functionsDir = $config['dirs']['funcdir'];

// Including all PHP files from the functions directory
foreach (glob($functionsDir . '/*.php') as $filename) {
    require_once $filename;
}

// Elements of the context
$uuid = $_SESSION['uuid'];
$name = $_SESSION['name'];

// Database connection details
$servername = $config['appflowdb']['servername'];
$username = $config['appflowdb']['username'];
$password = $config['appflowdb']['password'];
$dbname = $config['appflowdb']['dbname'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Unsetting DB variables once connection done
unset($servername, $username, $password, $dbname);

// Function to isolate the "include.php" file
function isolated_include($file) { return include $file; }

// Checking for apps folder
if (is_dir($appsDir)) 
{

    // Loops through each app
    foreach (scandir($appsDir) as $dir) 
    {

        // Looking for the "include.php" file
        if ($dir !== '.' && $dir !== '..' && file_exists("$appsDir/$dir/web/include.php")) 
        {

            // Sets the current app (only app changes, we have everything else)
            $appid = $dir;

            // Executes "include.php"
            if (isolated_include("$appsDir/$dir/web/include.php"))
            {

                // Adds the app
                $apps[$dir] = $dir;

            }   

        }

    }

}

// List used by wcapp.php to ensure it's an authorized app
$_SESSION['apps'] = $apps;

// If this point of the code is reached, it means the user is authenticated
include "webcontrol/wchome.php";