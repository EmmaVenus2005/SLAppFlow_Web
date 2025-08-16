<?php

// Function to safely get a file from /media or /web path
function WCGetInclude($file)
{
    
    // Required globals
    global $config, $uuid, $name, $appid;

    // Abort if essential globals are missing
    if (!isset($uuid, $name, $appid, $config['dirs']['appsdir'])) { return; }

    // Validate appid (prevent directory injection)
    if (!preg_match('/^[A-Za-z0-9._-]+$/', $appid)) { return; }

    // Reject empty or invalid file names
    if ($file === '' || preg_match('/[^\w.\-\/]/', $file)) { return; }

    // Explicitly block traversal sequences like ./ and ../
    if (preg_match('#(^|/)\.{1,2}(/|$)#', $file)) { return; }

    // Normalize multiple slashes
    $file = preg_replace('#/+#', '/', $file);

    // Decide base subdir depending on context
    $subdir = ($uuid === 'Media') ? 'media' : 'web';

    // Build base and target path
    $appsdir = rtrim($config['dirs']['appsdir'], '/').'/'.$appid.'/'.$subdir;
    $target  = $appsdir.'/'.$file;

    // Resolve base directory
    $appsdirReal = realpath($appsdir);
    if ($appsdirReal === false) { return; }

    // Resolve target file (must exist)
    $targetReal = realpath($target);
    if ($targetReal === false || !is_file($targetReal)) { return; }

    // Ensure target is inside the base directory (protects against symlinks)
    if (strpos($targetReal, $appsdirReal . DIRECTORY_SEPARATOR) !== 0 && $targetReal !== $appsdirReal) { return; }

    // Ensure readability
    if (!is_readable($targetReal)) { return; }

    // Return file contents (or nothing if failed)
    $content = file_get_contents($targetReal);
    if ($content === false) { return; }

    // Return the content of the file
    return $content;

}