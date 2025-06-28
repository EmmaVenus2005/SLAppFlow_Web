<?php

// Lists the files for the current user and app, with optional wildcard filtering on the filename.
function FSList($wildcard = "*")
{
    
    // Environment variables
    global $config;

    // Get the current owner ID and app ID
    $owner = AFGetOwnerID();
    $app = AFGetAppID();

    // If owner or app is not set, return an empty array
    if (empty($owner) || empty($app)) return [];

    // Build the path to the app's directory
    $appDir = $config['dirs']['filesdir'] . "/" . $owner . "/" . $app;

    // If directory does not exist, return an empty array
    if (!is_dir($appDir)) return [];

    // Build the search pattern: UUID_filename (wildcard applies to the filename part)
    $pattern = $appDir . "/*_" . $wildcard;

    // Get all matching files
    $files = glob($pattern);

    if (!$files) return [];

    // Extract file IDs (UUIDs) from filenames
    $ids = [];
    foreach ($files as $filepath) {
        $basename = basename($filepath);
        $parts = explode("_", $basename, 2); // split into [uuid, name]
        if (preg_match('/^[a-f0-9-]{36}$/i', $parts[0])) {
            $ids[] = $parts[0];
        }
    }

    // Return the list of UUIDs
    return $ids;

}