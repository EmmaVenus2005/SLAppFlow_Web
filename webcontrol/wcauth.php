<?php

// wcauth.php
//
// This script is responsible for authenticating the user based on
// posted 'username' and 'password'.
//
// Included from wbindex.php when 'username' and 'password' are sent via POST.

// Database connection details
$servername = $_SESSION['config']['appflowdb']['servername'];
$username = $_SESSION['config']['appflowdb']['username'];
$password = $_SESSION['config']['appflowdb']['password'];
$dbname = $_SESSION['config']['appflowdb']['dbname'];

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) 
{
  
    die("Connection failed: " . $wcconn->connect_error);

}

// Retrieve username and password sent via POST
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Basic security check: Make sure both fields are filled
if (empty($username) || empty($password)) 
{

    die("Missing credentials.");

}

// Prepared SQL statement to retrieve the stored password hash for the given username
$stmt = $conn->prepare("
    SELECT pass.Value AS PassHash, login.UserID AS UUID, login.Value AS Username
    FROM Parameter login
    INNER JOIN Parameter pass
        ON login.UserID = pass.UserID
        AND pass.Key = 'Password'
    WHERE login.Key = 'Login'
      AND login.Value = ?
");

// Bind the username parameter
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Assume invalid credentials initially
$valid_credentials = false;

// If a matching user is found, verify the password hash
if ($result->num_rows === 1) {
    
    // Fetch the stored hash and user details
    $row = $result->fetch_assoc();

    // Verify provided password against the stored bcrypt hash
    if (password_verify($password, $row['PassHash'])) {
        
        // Password verified: store UUID and Username in the session
        $_SESSION['uuid'] = $row['UUID'];
        $_SESSION['name'] = $row['Username'];
        $valid_credentials = true;

    }

}

// Handle invalid credentials case
if (!$valid_credentials) 
{
    
    // Invalid username or password provided
    $login_error = "Invalid username or password.";

    // Delay to slow down brute-force attacks
    sleep(1);

}

// Close statement and connection
$stmt->close();
$conn->close();