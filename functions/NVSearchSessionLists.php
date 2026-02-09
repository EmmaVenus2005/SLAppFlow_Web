<?php

/**
 * NVSearchSessionLists
 *
 * Search session-scoped lists by SQL LIKE pattern on Name.
 *
 * @param string $session
 * @param string $class
 * @param string $nameLike   SQL LIKE pattern (recommended: "prefix%")
 * @param int    $limit      Safety limit (default 200)
 * @return array|false       Array of rows (Name, Elements) or false
 */
function NVSearchSessionLists($session, $class, $nameLike, $limit = 200)
{

    // Ensure that the required global variables are set
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid)) {
        error_log("NVSearchSessionLists: Required variables are not set.");
        return false;
    }

    // Not allowed when called from front-end without being sanitized
    if (AFIsUnsafe()) return false;

    // Avoids full table scans which could be triggered by empty or wildcard-only patterns
    if ($nameLike === "" || $nameLike === "%") return false;

    // Enforce a reasonable limit to prevent abuse
    $limit = max(1, min((int)$limit, 500));

    $sql = "
        SELECT Name, Elements
        FROM `List`
        WHERE AppID = ?
          AND UserID = ?
          AND SessionID = ?
          AND Class = ?
          AND Name LIKE ?
        ORDER BY Name
        LIMIT $limit
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("NVSearchSessionLists: Statement preparation failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("sssss", $appid, $uuid, $session, $class, $nameLike);

    if (!$stmt->execute()) {
        error_log("NVSearchSessionLists: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    $stmt->close();
    return $rows;

}