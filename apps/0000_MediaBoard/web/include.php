<?php

// Include file, must return true if the app can be seen in the menu
// Use usual public commands to determine whether it should appear or not

// Checks if the user is an admin of the art gallery
//return AFSendFlowMessage(AFGetAppID(), "Global", "ISADMIN");
return true;