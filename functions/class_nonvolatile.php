<?php

class NonVolatile 
{

    /** @var mysqli|null Database connection */
    private ?mysqli $conn = null;
    
    /** @var string Application ID */
    private string $appid;
    
    /** @var string User ID */
    private string $uuid;
    
    /** @var string User Name */
    private string $name;
    
    /** @var string Session ID */
    private string $session = 'DefaultSession';

    /**
     * Constructor to open the database connection.
     */
    public function __construct() 
    {

        // Reading the config file that contains confidential data
        $config = parse_ini_file(__DIR__ . '/../config.ini', true);

        // Database connection details
        $servername = $config['appflowdb']['servername'];
        $username = $config['appflowdb']['username'];
        $password = $config['appflowdb']['password'];
        $dbname = $config['appflowdb']['dbname'];

        // Actual DB connection
        $this->conn = new mysqli($servername, $username, $password, $dbname);
        
        if ($this->conn->connect_error) 
        {
            
            error_log("Database connection failed: " . $this->conn->connect_error);
 
        }

    }

    /**
     * Destructor to close the database connection.
     */
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
    }

    /**
     * Set the application ID.
     * 
     * @param string $appid Application ID
     */
    public function setApp(string $appid): void {
        $this->appid = $appid;
    }

    /**
     * Set user details.
     * 
     * @param string $uuid User ID
     * @param string $name User Name
     */
    public function setUser(string $uuid, string $name): void {
        $this->uuid = $uuid;
        $this->name = $name;
    }

    /**
     * Retrieve a value from the Parameter table.
     */
    public function getValue(string $valueName): ?string {
        $stmt = $this->conn->prepare("SELECT `Value` FROM Parameter WHERE AppID = ? AND UserID = ? AND `Key` = ? LIMIT 1");
        $stmt->bind_param("sss", $this->appid, $this->uuid, $valueName);
        $stmt->execute();
        $stmt->bind_result($value);
        $stmt->fetch();
        $stmt->close();
        return $value ?? null;
    }

    /**
     * Set a value in the Parameter table.
     */
    public function setValue(string $valueName, string $value): bool {
        $stmt = $this->conn->prepare("INSERT INTO Parameter (AppID, UserID, UserName, SessionID, `Key`, `Value`) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE `Value` = VALUES(`Value`)");
        $stmt->bind_param("ssssss", $this->appid, $this->uuid, $this->name, $this->session, $valueName, $value);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Retrieve a specific list from the List table.
     */
    public function getList(string $listClass, string $listName): ?string {
        $stmt = $this->conn->prepare("SELECT Elements FROM List WHERE AppID = ? AND UserID = ? AND Class = ? AND Name = ?");
        $stmt->bind_param("ssss", $this->appid, $this->uuid, $listClass, $listName);
        $stmt->execute();
        $stmt->bind_result($value);
        $stmt->fetch();
        $stmt->close();
        return $value ?? null;
    }

    /**
     * Retrieve all lists of a given class.
     */
    public function getLists(string $listClass): ?array {
        $stmt = $this->conn->prepare("SELECT Name FROM List WHERE AppID = ? AND UserID = ? AND Class = ?");
        $stmt->bind_param("sss", $this->appid, $this->uuid, $listClass);
        $stmt->execute();
        $result = $stmt->get_result();
        $lists = [];
        while ($row = $result->fetch_assoc()) {
            $lists[] = $row['Name'];
        }
        $stmt->close();
        return $lists ?: null;
    }

    /**
     * Create or update a list in the List table.
     */
    public function setList(string $listClass, string $listName, string $listElements): bool {
        $stmt = $this->conn->prepare("INSERT INTO List (AppID, UserID, UserName, SessionID, Class, Name, Elements) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE Elements = VALUES(Elements)");
        $stmt->bind_param("sssssss", $this->appid, $this->uuid, $this->name, $this->session, $listClass, $listName, $listElements);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Delete a specific list.
     */
    public function deleteList(string $listClass, string $listName): bool {
        $stmt = $this->conn->prepare("DELETE FROM List WHERE AppID = ? AND UserID = ? AND Class = ? AND Name = ?");
        $stmt->bind_param("ssss", $this->appid, $this->uuid, $listClass, $listName);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Delete all lists of a given class.
     */
    public function deleteLists(string $listClass): bool {
        $stmt = $this->conn->prepare("DELETE FROM List WHERE AppID = ? AND UserID = ? AND Class = ?");
        $stmt->bind_param("sss", $this->appid, $this->uuid, $listClass);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Search for a list entry where Elements contains a specific element.
     * 
     * @param string $listClass The list class
     * @param string $listName The list name
     * @param string $element The element to search for in the Elements column
     * @param string $delimiter The delimiter used in the Elements column (default: '|')
     * @return array|null An array of matching UserID and UserName or null if no results
     */
    public function searchList(string $listClass, string $listName, string $element, string $delimiter = '|'): ?array {
        if (!$this->conn) {
            error_log("searchList: No database connection.");
            return null;
        }

        $regexPattern = "(^|" . preg_quote($delimiter) . ")" . preg_quote($element) . "(" . preg_quote($delimiter) . "|$)";
        
        $stmt = $this->conn->prepare(
            "SELECT UserID, UserName FROM List WHERE AppID = ? AND SessionID = ? AND Class = ? AND Name = ? AND Elements REGEXP ?");
        if (!$stmt) {
            error_log("searchList: Statement preparation failed: " . $this->conn->error);
            return null;
        }
        
        $stmt->bind_param("sssss", $this->appid, $this->session, $listClass, $listName, $regexPattern);
        
        if (!$stmt->execute()) {
            error_log("searchList: Execution failed: " . $stmt->error);
            $stmt->close();
            return null;
        }
        
        $result = $stmt->get_result();
        $entries = [];
        while ($row = $result->fetch_assoc()) {
            $entries[] = ['id' => $row['UserID'], 'name' => $row['UserName']];
        }
        
        $stmt->close();
        return $entries ?: null;
    }

}

?>