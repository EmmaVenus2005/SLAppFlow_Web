<?php

function RLVListCategories() 
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

    // Error handling for database connection
    if ($conn->connect_error) {
        error_log("RLVListCategories: DB connection failed: " . $conn->connect_error);
        return json_encode([]);
    }

    // Prepare and execute the SQL query to fetch functional categories with descriptions
    $sql = "
        SELECT fc.ShortName, d.Name, d.Description
        FROM FunctionalCategory fc
        LEFT JOIN FunctionalCategoryDesc d ON fc.ID = d.ID AND d.Language = 'en'
    ";

    // Execute the query and fetch results
    $result = $conn->query($sql);
    $categories = [];
   
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                "ShortName"     => $row['ShortName'],
                "Name"          => $row['Name'],
                "Description"   => $row['Description'] ?? ''
            ];
        }
        $result->free();
    } else {
        error_log("RLVListCategories: Query failed: " . $conn->error);
    }

    // Cleanup: close connection and return JSON string
    $conn->close();
    return json_encode($categories);

}