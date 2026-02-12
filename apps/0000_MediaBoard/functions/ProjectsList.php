<?php

/**
 * ProjectList
 *
 * Lists all projects as:
 * - { "project_id": "<uuid>", "name": "<project_name>" }
 *
 * Note:
 * - The project UUID is the NV Name (key), not stored inside the JSON.
 * - The UUID is injected into the returned structure.
 *
 * @return array
 */
function ProjectsList()
{
    
    $projectIds = NVGetLists("Project");
    if (!is_array($projectIds) || count($projectIds) === 0) {
        return [];
    }

    $projects = [];

    foreach ($projectIds as $projectId) {
        $json = NVGetList("Project", $projectId);
        if (!is_string($json) || $json === "") continue;

        $data = json_decode($json, true);
        if (!is_array($data)) continue;

        if (!isset($data["name"]) || !is_string($data["name"])) continue;

        $projects[] = [
            "project_id" => $projectId,
            "name" => $data["name"],
            "owner" => AFGetOwnerID(),
            "owner_name" => $data["owner_name"] ?? ""
        ];
    }

    return $projects;

}