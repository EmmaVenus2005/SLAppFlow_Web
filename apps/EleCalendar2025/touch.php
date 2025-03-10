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

        // Checking what the donator already donated
        $payments = EleCheckPayments();

		$dialog = "\nEle's Calendar\n\n";
		$dialog .= "Ele Gee Charity Photo Calendar 2025, with all proceeds to Breast Cancer Now charity in the UK.\n";
		$dialog .= "This “Special Edition” version is only available here at POC, and includes a free limited edition poster signed by Ele.\n\n";
        $dialog .= "https://breastcancernow.org/\n\n";
        
        // If the user did not yet (fully) pay the price
        if ($payments < $price)
        {

            // Adding the footer indication about what you already paid (if revelant)
            $payments != 0 ? $dialog .= "You already paid " . (string)$payments . " L$. " : null;

            // Fixed text
            $dialog .= "The calendar is for you if you make a donation of at least 300 L$.\n";
            $dialog .= "To proceed with donation, right-click on this poster and choose pay.\n\n";

            // Options in this case
            $options = ["OK"];

        // User already paid the price
        } else
        {

            $dialog .= "You already paid " . (string)$payments . " L$. ";
            $dialog .= "You may get a redelivery if needed.\n\n";

            // Options in this case
            $options = ["Redel. Cal.", "Redel. Poster"];

        }

        // Footer to thank donators
        $dialog .= "Thank you so much for your support and enjoy the calendar xx\n";

		// Sending the dialog to the avatar
		$answer = SLDialog($session, $dialog, $options);
		
		switch ($answer) {
		    
            case "Redel. Cal.": 	
                
                SLGiveInventory($session, "Ele Gee 2025 Calendar"); 
                break;
		    
                case "Redel. Poster":	
                    
                SLGiveInventory($session, "Special edition poster");; 
                break;
		
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

