<?php
session_start();

// Unset all session variables
$_SESSION = array();

// If you want to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
//session_destroy();

// Clear the session variables used for authentication
unset($_SESSION['uuid'], $_SESSION['name']);

// Clear the 'app' session variable if it exists,
// avoiding further public command calls
unset($_SESSION['app']);

// API response: return JSON
header('Content-Type: application/json');
echo json_encode(['status' => 'ok', 'message' => 'Logged out']);

exit();