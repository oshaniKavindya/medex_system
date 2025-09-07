<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    
    // Log the logout
    logAction($user_id, 'User Logout', 'User logged out from IP: ' . $_SERVER['REMOTE_ADDR']);
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Start a new session for the success message
    session_start();
    $_SESSION['success_message'] = 'You have been logged out successfully.';
}

// Redirect to login page
header('Location: login.php');
exit();
?>