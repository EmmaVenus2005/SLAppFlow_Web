<?php

/**
 * ProjectCreate
 *
 * Creates a new project:
 * - Class: "Project"
 * - Name:  <project_uuid>
 * - Elements JSON: {"name": "<project_name>"}
 *
 * @param string $projectName
 * @return string|false   Project UUID or false on failure
 */
function ProjectCreate($projectName)
{

    if (!is_string($projectName) || trim($projectName) === "") {
        return false;
    }

    // Generate a unique project ID
    $projectId = AFGenerateUUID();

    $payload = [
        "name" => trim($projectName),
        "owner_name" => AFGetOwnerName()
    ];

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if (!$json) return false;

    if (!NVSetList("Project", $projectId, $json)) {
        return false;
    }

    return $projectId;

}