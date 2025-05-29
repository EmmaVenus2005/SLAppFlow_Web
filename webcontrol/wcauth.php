<?php

// wcauth.php
// This script is responsible for authenticating the user based on
// posted 'username' and 'password'

// PHP session (sould already exist from main file)
session_start();

// Database connection details
$servername = $_SESSION['config']['webcontroldb']['servername'];
$username = $_SESSION['config']['webcontroldb']['username'];
$password = $_SESSION['config']['webcontroldb']['password'];
$dbname = $_SESSION['config']['webcontroldb']['dbname'];

// Create connection
$wcconn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($wcconn->connect_error) {
    die("Connection failed: " . $wcconn->connect_error);
}

// Unsetting DB variables once connection done
unset($servername, $username, $password, $dbname);

// Retrieve username and password sent via POST
$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Basic security check: Make sure both fields are filled
if (empty($username) || empty($password)) {
    die("Missing credentials.");
}

// Compute SHA-256 hash (hexadecimal, 64 characters, no dashes)
$passhash = hash('sha256', $password);

// Prepared SQL statement to avoid SQL injection
$stmt = $wcconn->prepare("SELECT UUID, Username FROM User WHERE Username = ? AND PassHash = ?");
$stmt->bind_param("ss", $username, $passhash);
$stmt->execute();
$result = $stmt->get_result();

// Unset the variables for security reasons
unset($username, $password, $passhash);

// If a matching user is found, store UUID and Username in the session
if ($result->num_rows === 1) 
{
    
    $row = $result->fetch_assoc();
    $_SESSION['uuid'] = $row['UUID'];
    $_SESSION['name'] = $row['Username'];

} else {    // Wrong username or password
    
    // This error message will be displayed after reloading the login page
    $login_error = "Invalid username or password.";

    // Delay to slow down brute-force attacks
    sleep(2);

}

// Close statement and connection
$stmt->close();
$wcconn->close();