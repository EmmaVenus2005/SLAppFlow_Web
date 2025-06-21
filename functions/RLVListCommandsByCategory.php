<?php

function RLVListCommandsByCategory($shortname)
{

    // Environement variables for database connection
    global $config;

    // Open database connection
    $conn = new mysqli(
        $config['rlvrelaydb']['servername'],
        $config['rlvrelaydb']['username'],
        $config['rlvrelaydb']['password'],
        $config['rlvrelaydb']['dbname']
    );

    // Handle connection error
    if ($conn->connect_error) {
        error_log("RLVGetListByCategory: DB connection failed: " . $conn->connect_error);
        return json_encode([]);
    }

    // Prepare SQL to retrieve commands belonging to the specified category shortname
    $sql = "
        SELECT 
            c.Command AS Filter,
            ct.Name AS CommandType,
            cd.Description
        FROM Command c
        INNER JOIN FunctionalCategory fc ON c.FunctionalCategoryID = fc.ID
        LEFT JOIN CommandType ct ON c.CommandTypeID = ct.ID
        LEFT JOIN CommandDesc cd ON c.ID = cd.CommandID AND cd.Language = 'en'
        WHERE fc.ShortName = ?
          AND c.IsObsolete = 0
        ORDER BY c.Command ASC
    ";

    // Prepare and execute the statement
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("RLVGetListByCategory: Statement preparation failed: " . $conn->error);
        $conn->close();
        return json_encode([]);
    }

    // Bind the parameter and execute
    $stmt->bind_param("s", $shortname);
    if (!$stmt->execute()) {
        error_log("RLVGetListByCategory: Execution failed: " . $stmt->error);
        $stmt->close();
        $conn->close();
        return json_encode([]);
    }

    // Fetch the results
    $result = $stmt->get_result();
    $commands = [];

    while ($row = $result->fetch_assoc()) {
        $commands[] = [
            "Filter"      => $row["Filter"],
            "CommandType" => $row["CommandType"],
            "Description" => $row["Description"] ?? ''
        ];
    }

    // Cleanup: free result set, close statement and connection
    $stmt->close();
    $conn->close();

    // Return the commands as a JSON string
    return json_encode($commands);

}