<?php

/**
 * Returns the current owner (uuid + name) of a given UNICAT painting
 * from tracking entries stored under the "NewOwner" session list.
 *
 * @param string $unicatNumber  UNICAT number without suffix (e.g. "UNICAT123")
 * @return array|null           ['uuid' => string|null, 'name' => string|null] or null if unavailable
 */
function GetCurrentOwner($unicatNumber)
{
    
    // Fetch all timestamps for "NewOwner"
    $entries = NVGetSessionLists($unicatNumber, "NewOwner");
    
    // No tracking yet
    if (!$entries || count($entries) === 0) return null;

    // Sort ascending by timestamp so the last is the most recent
    usort($entries, fn($a, $b) => strtotime($a) <=> strtotime($b));
    $lastTimestamp = end($entries);

    // Load most recent tracking payload (JSON string)
    $json = NVGetSessionList($unicatNumber, "NewOwner", $lastTimestamp);
    if (!$json) {
        return null;
    }

    $data = json_decode($json, true);
    if (!is_array($data)) {
        return null;
    }

    // OwnerID/OwnerName
    $uuid = $data['OwnerID'] ?? null;
    $name = $data['OwnerName'] ?? null;

    // Normalize SL names: drop the trailing " Resident" if present
    if (is_string($name) && substr($name, -9) === ' Resident') {
        $name = substr($name, 0, -9);
    }
    
    // Returns the current owner name and UUID
    return ['uuid' => $uuid, 'name' => $name];

}