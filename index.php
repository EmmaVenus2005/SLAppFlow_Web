<?php

// Using this variable to define paths from here
$homeDir = __DIR__;

// Reading the config file that contains confidential data
$config = parse_ini_file($homeDir . '/config.ini', true);

// Get the host from the server variables
$host = $_SERVER['HTTP_HOST'];

// Split the host into parts (subdomain, domain, etc.)
$parts = explode('.', $host);

// Check if there is a subdomain (usually more than two parts)
if (count($parts) > 2) 
{

    // Corresponding of the instance's API subdomain
    if ($config['subdomains']['api'] === $parts[0]) { include "api/apiindex.php"; }
    if ($config['subdomains']['www'] === $parts[0]) { echo "Web"; }

} else {
    // returns 404


}