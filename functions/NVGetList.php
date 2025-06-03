<?php

// Function to retrieve elements from a list in the List table based on $appid, $uuid, $listClass and $listName
function NVGetList($listClass, $listName) {
    global $conn, $appid, $uuid, $name, $session;
    
    if (!isset($conn, $appid, $uuid, $name, $session)) {
        error_log("NVGetList: Required variables are not set.");
        return false;
    }
	
	$stmt = $conn->prepare("SELECT Elements FROM List WHERE AppID = ? AND UserID = ? AND Class = ? AND Name = ?");
	
	if (!$stmt) {
        error_log("NVGetList: Statement preparation failed: " . $conn->error);
        return false;
    	}
    	
	$stmt->bind_param("ssss", $appid, $uuid, $listClass, $listName);

	if (!$stmt->execute()) {
		error_log("NVGetList: Execution failed: " . $stmt->error);
		$stmt->close();
        	return false;    
    	}
    	
    	$stmt->bind_result($value);
	$result = $stmt->fetch();
	$stmt->close();
	    
	if ($result) {
		return $value; //explode($delimiter, $value);
	} else {
		// Value not found
		return null;
	}

}