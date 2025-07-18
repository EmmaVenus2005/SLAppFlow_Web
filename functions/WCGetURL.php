<?php

// Gets the base URL for WebCOntrol
// Useful to have the instance-specific URL when going from test to prodution
function WCGetURL()
{

    // Needs the global config file
    global $config;

    // Returns the URL
    return "https://" . $config['subdomains']['www'] . ".slappflow.net";

}