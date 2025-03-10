<?php

// Function to get the total of paiments made by a specific user ($session)
function EleCheckPayments() {
    global $conn, $appid, $uuid, $name, $session;
    
    if (!isset($conn, $appid, $uuid, $name, $session)) {
        error_log("EleCheckPayments: Required variables are not set.");
        return false;
    }
    
    $stmt = $conn->prepare("SELECT `Elements` FROM List 
        WHERE AppID = ? 
          AND UserID = ? 
          AND Class = 'PurchaseInfo'
          AND SUBSTRING_INDEX(`Elements`, '|', 1) = ?");

    if (!$stmt) {
        error_log("EleCheckPayments: Statement preparation failed: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("sss", $appid, $uuid, $session);
    
    if (!$stmt->execute()) {
        error_log("EleCheckPayments: Execution failed: " . $stmt->error);
        $stmt->close();
        return null;
    }
    
    // Getting the result of the request
    $result = $stmt->get_result();

    // Will be the total for this "session" (avatar that paid)
    $value = 0;

    while ($row = $result->fetch_assoc()) 
    {
    
        // Gets the different parts :
        // [0] : UUID of the avatar
        // [1] : Username of the avatar
        // [2] : Sum he paid
        $parts = explode("|", $row['Elements']);    
        
        // Adding to the sum
        $value = $value + (integer)$parts[2];

    }

    // Closing connection
    $stmt->close();
    
    if ($result) {
        return $value;
    } else {
        // Value not found
        return null;
    }

}

?>