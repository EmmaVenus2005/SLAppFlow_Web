<?php

// wcmedia.php
//
// Creates webpages for inworld media (E. g. boards).
//
// Called directly from the inworld media. Can include the public commande
// but is the "Media" user.

// Setting the session token length and bits per character for higher security
ini_set('session.sid_length', 64); // 64 characters
ini_set('session.sid_bits_per_character', 6); // base64 (highest entropy per char)

// Starting a new PHP session
session_start();

// Getting the app id that has been sent by GET
$app = $_GET['app'] ?? null;

// If the app is not set, exit
if ($app === null) { exit; }

// Avoids path traversal and other security issues (accepts alphanumeric and underscore characters only)
$app = preg_replace('/[^a-zA-Z0-9_]/', '', $app);

// Getting the media file to display (if not set, defaults to "index")
$file = $_GET['file'] ?? "index";

// Keep only alphanumeric characters (no underscore, no dash, nothing else)
$file = preg_replace('/[^a-zA-Z0-9]/', '', $file);

// 'config' directory, containing sensitive data, outside of the web root
$configDir = dirname(dirname(__DIR__)) . '/config';

// Reading the config file that contains confidential data
$config = parse_ini_file($configDir . '/config.ini', true);

// Checking if app it exists and have media
if (!file_exists($config['dirs']['appsdir'] . "/" . $app . "/media/" . $file . ".html")) { exit; }

// Setting config file to be used afterwards by the media
$_SESSION['config'] = $config;

// Explicitly set user ID and name to the "Media" user
$_SESSION['uuid'] = "Media";
$_SESSION['name'] = "Media";

// Displays the requested media file
echo file_get_contents($config['dirs']['appsdir'] . "/" . $app . "/media/" . $file . ".html");