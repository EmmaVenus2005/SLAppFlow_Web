<?php

// Downloads the content of a file belonging to the current user and app.
function FSDownload($id)
{
    
    // Environement variables
    global $config;

    // Get the current owner ID and app ID
    $owner = AFGetOwnerID();
    $app = AFGetAppID();

    // If owner or app is not set, return false
    if (empty($owner) || empty($app)) return false;

    // Build the path to the app's directory
    $appDir = $config['dirs']['filesdir'] . "/" . $owner . "/" . $app;

    // Security: UUID format validation (standard UUID, 36 chars)
    if (!preg_match('/^[a-f0-9-]{36}$/i', $id)) return false;

    // Find file(s) matching this UUID in the app directory
    $pattern = $appDir . '/' . $id . '_*';
    $matches = glob($pattern);

    // If no file is found for this UUID, return false
    if (!$matches || count($matches) === 0) return false;

    // There should only be one file per UUID (by design)
    $filePath = $matches[0];

    // Read and return the file content (text or binary)
    $data = file_get_contents($filePath);

    // If reading the file failed, return false
    if ($data === false) return false;

    // Returns the file content
    return $data;

}