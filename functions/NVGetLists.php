<?php

// Function to enumerate lists from List table based on $appid, $uuid, $listClass
function NVGetLists($listClass) {
    global $conn, $appid, $uuid, $name;
    
    if (!isset($conn, $appid, $uuid, $name)) {
        error_log("SetValue: Required variables are not set.");
        return false;
    }
	
	$stmt = $conn->prepare("SELECT Name FROM List WHERE AppID = ? AND UserID = ? AND Class = ?");
	
	if (!$stmt) {
        error_log("GetLists: Statement preparation failed: " . $conn->error);
        return false;
    	}
    	
	$stmt->bind_param("sss", $appid, $uuid, $listClass);

	if (!$stmt->execute()) {
		error_log("GetLists: Execution failed: " . $stmt->error);
		$stmt->close();
        	return false;    
    	}
    	
    	$result = $stmt->get_result();

        // Collect the list names in an array
        $listNames = [];
        while ($row = $result->fetch_assoc()) {
            $listNames[] = $row['Name'];
        }
    	
	$stmt->close();
	    
	if ($listNames) {
		return $listNames;
	} else {
		// Value not found
		return null;
	}

}