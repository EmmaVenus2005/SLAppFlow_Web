<?php

    // Written by EmmaVenus2005 on 2025-01-02

    // RLV specifications : https://wiki.secondlife.com/wiki/LSL_Protocol/RestrainedLoveAPI
    
    // Database structure that stores commands :
    // ---------------------------------------

    // Table: FunctionalCategory
    // Stores functional categories of RLV commands
    // Columns:
    // ID: Unique identifier for the category (Primary Key, Auto Increment)
    // Name: Name of the functional category (e.g., "Movement", "Camera and view", Unique)

    // Table: FunctionalCategoryDesc
    // Stores multilingual descriptions for functional categories
    // Columns:
    // ID: Identifier for the functional category (Foreign Key to FunctionalCategory.ID)
    // Language: Language code (e.g., "en", "fr", Part of Primary Key)
    // Description: Description of the functional category in the specified language

    // Table: CommandType
    // Stores types of RLV commands (e.g., Restriction, Exception)
    // Columns:
    // ID: Unique identifier for the type (Primary Key, Auto Increment)
    // Name: Name of the command type (e.g., "Restriction", "Action", Unique)

    // Table: CommandTypeDesc
    // Stores multilingual descriptions for command types
    // Columns:
    // ID: Identifier for the command type (Foreign Key to CommandType.ID)
    // Language: Language code (e.g., "en", "fr", Part of Primary Key)
    // Description: Description of the command type in the specified language

    // Table: Command
    // Stores RLV commands and their associations with categories and types
    // Columns:
    // Command: Unique name of the command (Primary Key, e.g., "@detach")
    // FunctionalCategoryID: Identifier for the functional category (Foreign Key to FunctionalCategory.ID)
    // CommandTypeID: Identifier for the command type (Foreign Key to CommandType.ID)

    // Table: CommandDesc
    // Stores multilingual descriptions for RLV commands
    // Columns:
    // Command: Name of the command (Primary Key, Foreign Key to Command.Command)
    // Language: Language code (e.g., "en", "fr", Part of Primary Key)
    // Description: Description of the command in the specified language


    // How filter works :
    // ----------------

    // Requirement : We need to be able to know if a commandes has type Get, Restriction, Exception or Action, in order
    // to let the user set its own limits. We can't use y/n or add/rem to distinguish Restriction or Exception,
    // since internally they are the same (see wiki link : For your information section). Instead, I use a database
    // storing filters, that allow to parse the command and return the category (and allows user-specific allow/deny per command or category).

    // Any command starts with @command (or some other command name, but always with @ at the front).
    // Example: @sit, @stand, etc.

    // Some commands have options after them, like @command:option. In this case, the filter will have to distinguish it.
    // If the filter is "@commad:*=force", means that the command has to have a non-null option (*). We could have done
    // "@command?:*=force". In this case, the command would match wether there is or not an option (leading ? makes the : optional).
    // However, remember that * means something non-null. In this case, IF there is an option, THEN it MUST be NON-NULL.
    // We could have used ?* to say, NULL OR NON-NULL. For example : "@command?:?*=force". The ? leading * means it is optional.
    // We could have done "@command?:=force", meaning that a : could be there or not, but must NOT contain any value.
    // "@command:=force" means that it would only match with an empty option. We can also make the value optional with ?= :
    // "@command?:?*?=force". In this case, there could be no =, but if there is any, value MUST be "force".

    // If there is an = possible values are listed and separated by / (the OR operator).
    // Example: =y/n/add/rem means that one of those four values (y, n, add, rem) is expected.
    // The # symbol corresponds to an integer value. We could use * to consider whatever as matching.

    class RLVCommandHelper
    {

        // THIS MIGHT BE COPIED IN CLASSES THAT NEED THE GLOBAL CONTEXT
        // Context global variable that might be used in the class
        // global $conn, $appid, $uuid, $name, $session;

        // Connector to RLV Relay DB
        private $rlvconn;

        // Constructor
        // Initializes DB access
        public function __construct() 
        {

            // Reading the instance config file for DB info
            global $config;

            // Database connection details
            $servername = $config['rlvrelaydb']['servername'];
            $username = $config['rlvrelaydb']['username'];
            $password = $config['rlvrelaydb']['password'];
            $dbname = $config['rlvrelaydb']['dbname'];

            // Create connection
            $this->rlvconn = new mysqli($servername, $username, $password, $dbname);

            // Check connection
            if ($this->rlvconn->connect_error) 
            {
            
                error_log("Connection failed: " . $this->rlvconn->connect_error);
                die("Database connection failed: " . $this->rlvconn->connect_error);
            
            }

        }

        // Destructor
        public function __destruct() 
        {
            
            // Closing DB connection when the instance is destroyed
            if ($this->rlvconn) { $this->rlvconn->close(); }
        
        }

        function GetCommandInfo($command)
        {

            $stmt = $this->rlvconn->prepare("CALL find_matching(?)");
	
            if (!$stmt) {
                error_log("RLVCommandHelper: Statement preparation failed: " . $this->rlvconn->error);
                return false;
                }
                
            $stmt->bind_param("s", $command);

            if (!$stmt->execute()) {
                error_log("RLVCommandHelper: Execution failed: " . $stmt->error);
                $stmt->close();
                    return false;    
                }
                
            $result = $stmt->get_result();

            $results = [];

            // Collect the data as an associative array
            while ($row = $result->fetch_assoc()) {
                $results[] = [
                    "Filter" => $row['Filter'],
                    "CategoryShortName" => $row['CategoryShortName'],
                    "FunctionalCategory" => $row['FunctionalCategory'],
                    "CommandType" => $row['CommandType'],
                    "Description" => $row['Description']
                ];
            }

            // Closing the cursor
            $stmt->close();

            // Return null if no data found
            return !empty($results) ? $results : null;  

        }

    }

?>