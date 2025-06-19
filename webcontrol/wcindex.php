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

// Reading the config file that contains confidential data
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

// Directory where apps are located
$appsDir = $homeDir . '/apps';

// Creating array that will contain the available apps list
$apps = [];

// Checking for apps folder
if (is_dir($appsDir)) {
    foreach (scandir($appsDir) as $dir) {
        if ($dir !== '.' && $dir !== '..' && is_dir("$appsDir/$dir/web")) {
            $apps[$dir] = $dir;
        }
    }
}

// If this point of the code is reached, it means the user is authenticated
include "webcontrol/wchome.php";