<?php

/**
 * IsTrustedForProject
 *
 * Returns true if $userId is allowed to access $projectId in THIS owner's storage.
 * Authorization rule:
 * - owner always allowed
 * - otherwise must be present in NV list "Trusted" for that project
 */
function IsTrustedForProject($projectId, $userId)
{
 
    if (!is_string($projectId) || $projectId === "") return false;
    if (!is_string($userId) || $userId === "") return false;

    // Owner always allowed
    if ($userId === AFGetOwnerID()) return true;

    $trustedJson = NVGetList("Trusted", $projectId);
    $trustedList = json_decode($trustedJson, true) ?: [];

    return (is_array($trustedList) && in_array($userId, $trustedList, true));

}