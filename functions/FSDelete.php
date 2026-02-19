<?php

// Deletes a file for the current user and app, identified by its UUID.
function FSDelete($id)
{

    // Environment variables
    global $config;

    // Not allowed when called from front-end without being sanitized
    if (AFIsUnsafe()) return false;

    // Get the current owner ID and app ID
    $owner = AFGetOwnerID();
    $app = AFGetAppID();

    // If owner or app is not set, return false
    if (empty($owner) || empty($app)) return false;

    // Build the path to the app's directory
    $appDir = $config['dirs']['filesdir'] . "/" . $owner . "/" . $app;

    // Security: UUID format validation (standard UUID, 36 chars)
    if (!preg_match('/^[a-f0-9-]{36}$/i', $id)) return false;

    // Find the current file by UUID
    $pattern = $appDir . "/" . $id . "_*";
    $matches = glob($pattern);

    // If no file is found, return false
    if (!$matches || count($matches) === 0) return false;

    // There should be only one file per UUID
    $fileToDelete = $matches[0];

    // Delete the file
    if (!unlink($fileToDelete)) return false;

    // Return true if deletion succeeded
    return true;

}