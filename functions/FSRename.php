<?php

// Renames a file for the current user and app (identified by its UUID). Applies same filename sanitization as FSUpload.
function FSRename($id, $newName)
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

    // Sanitize new filename (allow spaces but not at start/end, same as FSUpload)
    $newName = preg_replace('/[^A-Za-z0-9 ._-]/', '_', $newName);
    $newName = trim($newName);

    // The new name is not empty or too long
    if (empty($newName) || strlen($newName) > 32) return false;

    // Find the current file by UUID
    $pattern = $appDir . "/" . $id . "_*";
    $matches = glob($pattern);

    // If no file is found, return false
    if (!$matches || count($matches) === 0) return false;

    // There should be only one file per UUID
    $oldFile = $matches[0];

    // Build the new file path
    $newFile = $appDir . "/" . $id . "_" . $newName;

    // Rename the file
    if (!rename($oldFile, $newFile)) return false;

    // Return true if renaming succeeded
    return true;

}