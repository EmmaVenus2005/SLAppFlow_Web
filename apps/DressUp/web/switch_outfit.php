<?php

// PHP session (sould already exist from main file)
session_start();

// If $uuid and $name are not set, someone tries to access to the app without using main menu
// In this case ALWAYS abort the script for obvious security reason
if (!isset($_SESSION['uuid']) || !isset($_SESSION['name'])) { exit(); }

// Get the directory path containing the PHP functions and classes
$functionsDir = realpath(__DIR__ . '/../../../functions/');

// Including all PHP files
if ($functionsDir !== false && is_dir($functionsDir)) {
    foreach (glob($functionsDir . '/*.php') as $filename) {
        require_once $filename;
    }
}

// Creating an instance of NonVolatile class
$nv = new NonVolatile();
$nv->setApp('DressUp');
$nv->setUser($_SESSION['uuid'], $_SESSION['name']);

// If outfit switch has been triggered
echo 'Switching to outfit : ' . $_GET['outfit'];