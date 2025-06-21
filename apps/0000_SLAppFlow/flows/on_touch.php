<?php

/**
 * Contextual functions used during flow execution
 * -----------------------------------------------
 * 
 * - AFGetAppID()               → Application identifier (unique per app instance)
 * - AFGetOwnerID()             → UUID of the avatar who owns the object
 * - AFGetOwnerName()           → Display name of the object owner
 * - AFGetFlowAppMode()         → Application mode (to distinguish objects of the same app)
 * - AFGetFlowObjectID()        → UUID of the object that triggered the flow
 * - AFGetFlowObjectName()      → Display name of the object that triggered the flow
 * - AFGetFlowGatewayVersion()  → Version of the gateway (as a float)
 * - AFGetFlowObjectPosition()  → Position (vector) of the object in the region
 * - AFGetFlowObjectRotation()  → Rotation (quaternion) of the object in the region
 * - AFGetFlowRegionPosition()  → Position (vector) of the region in the world
 * - AFGetFlowRegionName()      → Name of the region in the world
 * 
 * Touch-specific additional parameters are accessible using:
 *
 * - AFGetFlowSession()         → UUID of the avatar who touched the object
 * - AFGetFlowParameter(index)  → Indexed array of touch data:
 *      [0] = toucherName          (string)
 *      [1] = toucherOwner UUID    (string)
 *      [2] = toucherPos           (vector as string)
 *      [3] = toucherRot           (rotation as string)
 *      [4] = toucherType          (integer)
 *      [5] = surfaceST            (vector as string)
 *      [6] = surfaceUV            (vector as string)
 *      [7] = touchedFace          (integer)
 *      [8] = touchNormal          (vector as string)
 *      [9] = touchBinormal        (vector as string)
 *      [10] = touchPos            (vector as string)
 * 
 */

// Flow control variable
$flowStep = "MAIN";

// Main loop
while ($flowStep != "EXIT")
{
    
    if ($flowStep === "MAIN")
    {

        //SLRegionSayTo(AFGetFlowObjectID(), AFGetFlowSession(), 0, "Coming soon !");
        SLOwnerSay(AFGetFlowObjectID(), AFGetFlowParameter(0) . " touched the kiosk");

        // Main menu dialog
        $dialog = "\nSLAppFlow kiosk \n\n";
        $dialog .= "Please find below the options :\n\n";
        $dialog .= "[Info] Learn more about SLAppFlow.\n\n";
        $dialog .= "[Register] Create an account related to your avatar.\n\n";
        $dialog .= "[Reset password] You are already registered but lost your password.\n\n";
        $dialog .= "\n This is still work in progress, will be available soon !";
        
        // Options for dialog (all users)
        $options = ["Info", "Register", "Reset password"];

        // Checking if the user is the owner
        if (AFGetOwnerID() === AFGetFlowSession())
        {

            // Adding the Admin option
            //$options[] = "Admin";

        }

        // Send dialog to the avatar
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], $options, false, false);

        // Reads the answer
        switch ($answer) 
        {
        
            case "Info"          :   $flowStep = "MAIN/INFO"; break;
            case "Register"      :   $flowStep = "MAIN/REGISTER"; break;
            case "Reset password":   $flowStep = "MAIN/RESETPASSWORD"; break;
            
            // If no managed answer found, exits the flow
            default : $flowStep = "EXIT";
        
        }

    } elseif ($flowStep === "MAIN/REGISTER")
    {

        // Check if the user already has an account
        $exists = AFSendFlowMessage(AFGetAppID(), AFGetFlowSession(), "CHECKACCOUNT");

        if ($exists) {

            // Dialog for registration
            $dialog = "\nSLAppFlow kiosk \n\n";
            $dialog .= "You already have an account associated with your avatar. ";
            $dialog .= "If you have forgotten your password, please use the reset option.";

            // No options in this case
            $options = [];

        } else {

            // Dialog for registration
            $dialog = "\nSLAppFlow kiosk \n\n";
            $dialog .= "You will be prompted to create a password. ";
            $dialog .= "Keep in mind to use a strong password, and do not use your Second Life password ";
            $dialog .= "or a password you already use for other services.\n\n";

            // Options for dialog (all users)
            $options = ["Next"];

        }

        // Send dialog to the avatar
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], $options, false, true);

        // If not BACK, timeout or HTTP error...
		if ($answer != "BACK" && $answer != NULL)
		{
            
            // Answer can only be "Next" here
            $flowStep = "MAIN/REGISTER~PASS1";
        
        }

    } elseif ($flowStep === "MAIN/REGISTER~PASS1")
    {

        // Prompt for password (first time)
        $answer = SLTextBox(AFGetFlowObjectID(), AFGetFlowSession(), "Please enter your password for SLAppFlow :");

        // If the password is not null (timeout)
        if ($answer !== null) 
        { 
            
            // Store the password in a variable
            $password = $answer;

            // Proceed to the next step
            $flowStep = "MAIN/REGISTER~PASS2"; 
        
        }

    } elseif ($flowStep === "MAIN/REGISTER~PASS2")
    {

        // Prompt for password (second time)
        $answer = SLTextBox(AFGetFlowObjectID(), AFGetFlowSession(), "Please REPEAT your password :");

        // If the password is not null (timeout)
        if ($answer !== null) 
        { 
            
            // Store the second password in a variable
            $password2 = $answer;

            // Proceed to the next step
            $flowStep = "MAIN/REGISTER~PASSCHECK"; 
        
        }

    } elseif ($flowStep === "MAIN/REGISTER~PASSCHECK")
    {

        // Check if the two passwords match
        if ($password === $password2)
        {

            // Hash the password using a secure hashing algorithm
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Removes the ending Resident from the username
            $username = AFGetFlowParameter(0);
            if (substr($username, -9) === " Resident") {
                $username = substr($username, 0, -9);
            }

            // Create the account
            AFSendFlowMessage(AFGetAppID(), AFGetFlowSession(), "CREATEACCOUNT|" . $username . "|" . $passwordHash);

            // If the account was created successfully
            $dialog = "\nSLAppFlow kiosk\n\n";
            $dialog .= "Your account has been successfully created !\n\n";
            $dialog .= "Link : https://wwwtest.slappflow.net\n";
            $dialog .= "Your username : " . $username . "\n\n";  
            $dialog .= "Welcome and have fun !";
                
        } else {

            // If passwords do not match
            $dialog = "\nSLAppFlow kiosk\n\n";
            $dialog .= "The passwords you entered do not match. Please try again.";
            
        }

        // No options in this case
        $options = [];

        // Sending the dialog to the avatar
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], $options, false, true);

    } elseif ($flowStep === "MAIN/INFO")
    {

        // Information dialog content
        $dialog  = "\nSLAppFlow kiosk\n\n";
        $dialog .= "SLAppFlow is an external flow processor for compatible Second Life apps. ";
        $dialog .= "There is an external website where the apps can provide external control.\n\n";
        $dialog .= "https://github.com/EmmaVenus2005/SLAppFlow_Web\n\n";
        $dialog .= "Designed and coded by EmmaVenus2005. Let me know if you need any further information.";

        // Options for dialog (none required here)
        $options = [];

        // Sending dialog to the avatar
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], $options, false, true);

    } elseif ($flowStep === "MAIN/RESETPASSWORD")
    {

        // Check if the user already has an account
        $exists = AFSendFlowMessage(AFGetAppID(), AFGetFlowSession(), "CHECKACCOUNT");

        if (!$exists)
        {

            // User does not have an account yet
            $dialog = "\nSLAppFlow kiosk \n\n";
            $dialog .= "You don't have an account yet. Please register first.";
            
            // No options needed
            $options = [];

            // Send dialog to avatar
            $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], $options, false, true);

        }
        else
        {

            // Proceed to first password prompt
            $flowStep = "MAIN/RESETPASSWORD~PASS1";

        }

    } elseif ($flowStep === "MAIN/RESETPASSWORD~PASS1")
    {

        // Prompt for new password (first time)
        $answer = SLTextBox(AFGetFlowObjectID(), AFGetFlowSession(), "Enter your NEW password for SLAppFlow :");

        if ($answer !== null)
        {

            // Store the new password
            $newPassword = $answer;

            // Proceed to confirmation step
            $flowStep = "MAIN/RESETPASSWORD~PASS2";

        }

    } elseif ($flowStep === "MAIN/RESETPASSWORD~PASS2")
    {

        // Prompt for new password confirmation
        $answer = SLTextBox(AFGetFlowObjectID(), AFGetFlowSession(), "Please CONFIRM your new password :");

        if ($answer !== null)
        {

            // Store the confirmation password
            $newPassword2 = $answer;

            // Proceed to password check step
            $flowStep = "MAIN/RESETPASSWORD~PASSCHECK";

        }

    } elseif ($flowStep === "MAIN/RESETPASSWORD~PASSCHECK")
    {

        // Check if passwords match
        if ($newPassword === $newPassword2)
        {

            // Hash the new password securely
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

            // Send flow message to reset the password
            AFSendFlowMessage(AFGetAppID(), AFGetFlowSession(), "RESETPASSWORD|" . $passwordHash);

            // Success dialog
            $dialog = "\nSLAppFlow kiosk\n\n";
            $dialog .= "Your password has been successfully reset !";

        }
        else
        {

            // Password mismatch dialog
            $dialog = "\nSLAppFlow kiosk\n\n";
            $dialog .= "Passwords do not match, please try again.";

        }

        // No further options
        $options = [];

        // Send dialog
        $answer = SLDialog(AFGetFlowObjectID(), AFGetFlowSession(), $dialog, "", [], $options, false, true);

    }



    // Manage BACK or null responses (timeout, errors, etc.)
    if (!isset($answer) || $answer === null) {
        $flowStep = "EXIT";
    } elseif ($answer === "BACK") {
        $flowStep = AFStepBack($flowStep);
    }

}