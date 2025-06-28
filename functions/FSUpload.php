<?php

// Function to upload a file to the filesystem (owner and app specific)
function FSUpload($name, $data)
{

    // Environmement variables
    global $config;

    // Getting the path to the files directory
    $filesPath = $config['dirs']['filesdir'];

    // Getting the owner ID and app ID
    $owner = AFGetOwnerID();
    $app = AFGetAppID();

    // If owner or app is not set, return false
    if (empty($owner) || empty($app)) { return false; }

    // Checks if there is already a directory for the current owner
    if (!is_dir($filesPath . "/" . $owner)) 
    {
     
        // If not, create it
        mkdir($filesPath . "/" . $owner, 0755, true);
    
    }

    // Checks if the app subdirectory exists
    if (!is_dir($filesPath . "/" . $owner . "/" . $app)) 
    {
    
        // If not, create it
        mkdir($filesPath . "/" . $owner . "/" . $app, 0755, true);
    
    }

    // Generating an UUID for the file
    $uuid = AFGenerateUUID();
    
    // Sanitize filename to prevent directory traversal and unsafe chars, allow spaces (but not at the start or end)
    $name = preg_replace('/[^A-Za-z0-9 ._-]/', '_', $name);
    $name = trim($name);

    // Checks if the name is not empty or too long
    if (empty($name) || strlen($name) > 32) { return false; }

    // Creating the filename
    $filename = $filesPath . "/" . $owner . "/" . $app . "/" . $uuid . "_" . $name;

    // Writing the file to the filesystem
    if (file_put_contents($filename, $data) === false) { return false; }

    // If the file was written successfully, we return the UUID
    return $uuid;

}