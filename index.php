<?php

// Using this variable to define paths from here
$homeDir = __DIR__;

// 'config' directory, containing sensitive data, outside of the web root
$configDir = dirname($homeDir) . '/config';

// 'log' directory, containing log files, outside of the web root
$logDir = dirname($homeDir) . '/log';

// Reading the config file that contains confidential data
$config = parse_ini_file($configDir . '/config.ini', true);

// Get the host from the server variables
$host = $_SERVER['HTTP_HOST'];

// Split the host into parts (subdomain, domain, etc.)
$parts = explode('.', $host);

// Check if there is a subdomain (usually more than two parts)
if (count($parts) > 2) 
{

    // Corresponding of the instance's API subdomain
    if ($config['subdomains']['api'] === $parts[0]) { include "api/apiindex.php"; }
    if ($config['subdomains']['www'] === $parts[0]) { include "webcontrol/wcindex.php"; }

} else {
    // returns 404


}