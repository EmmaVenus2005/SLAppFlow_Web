<?php

// Returns the original filename for a file owned by the current user and app, identified by its UUID.
function FSGetName($id)
{

    // Environment variables
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

    // Find the file by UUID
    $pattern = $appDir . "/" . $id . "_*";
    $matches = glob($pattern);

    // If no file is found, return false
    if (!$matches || count($matches) === 0) return false;

    // There should be only one file per UUID
    $basename = basename($matches[0]);
    $parts = explode("_", $basename, 2);

    // Return the filename part (after the UUID and underscore)
    if (count($parts) < 2) return false;

    // Returns the original filename
    return $parts[1];

}