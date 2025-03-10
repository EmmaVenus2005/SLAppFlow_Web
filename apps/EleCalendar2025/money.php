<?php

// Ensure necessary variables are available
if (!isset($appid, $uuid, $name, $conn, $session)) {
    error_log("Required variables are not set.");
    exit();
}

// Price of the calendar
$price = 300;

// Navigation variables
$flowStep = "MAIN";

while ($flowStep != "EXIT")
{

	// Initial step
	if ($flowStep == "MAIN")
	{

        // Awaiting for SLS script to send payment to database
        sleep(2);

        // Checking what the donator already donated
        $payments = EleCheckPayments();

        // Paid minimum price
        if ($payments >= $price)
        {
        
            // Giving the calendar and special edition poster
		    SLGiveInventory($session, "Ele Gee 2025 Calendar");
            SLGiveInventory($session, "Special edition poster");

        }
        
        // Avoids loop
        $flowStep = "EXIT";

    }

	// Managing the 'BACK' option and when a dialug returns null (timeout or HTTP error)
	if ($answer === null) {	$flowStep = "EXIT";	}
	elseif ($answer === "BACK")	{ $flowStep = AFStepBack($flowStep); }

}

exit();

?>

