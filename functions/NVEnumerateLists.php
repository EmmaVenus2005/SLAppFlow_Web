<?php

/**
 * NVEnumerateLists
 * 
 * Retrieves all rows from the 'List' table for the given class, restricted to the
 * current AppID and UserID context. Refuses to execute if called from an unsafe 
 * (e.g., unsanitized front-end) context.
 * 
 * @param string $class   // Value of the 'Class' column to filter by (case-sensitive)
 * @return array|false    // Array of matching rows (associative), or false on error/unsafe call
 */
function NVEnumerateLists($class) {

    // Ensure required globals are set
    global $conn, $appid, $uuid;

    if (!isset($conn, $appid, $uuid)) {
        error_log("NVEnumerateLists: Required variables are not set.");
        return false;
    }

    // Not allowed when called from front-end without being sanitized
    if (AFIsUnsafe()) return false;

    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT * FROM `List` WHERE AppID = ? AND UserID = ? AND Class = ?");
    if (!$stmt) {
        error_log("NVEnumerateLists: Statement preparation failed: " . $conn->error);
        return false;
    }

    // Bind the parameters (AppID, UserID, Class)
    $stmt->bind_param("sss", $appid, $uuid, $class);

    // Execute the statement
    if (!$stmt->execute()) {
        error_log("NVEnumerateLists: Execution failed: " . $stmt->error);
        $stmt->close();
        return false;
    }

    // Fetch results as associative arrays
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    // Closing the statement
    $stmt->close();

    // Returns the corresponding rows, or empty array if no match found
    return $rows;
  
}