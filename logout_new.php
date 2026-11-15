<?php
/**
 * StudyLance User Logout
 * Securely logs out user and destroys session
 */

require_once 'config.php';

// Log logout activity if user is logged in
if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $user_email = $_SESSION['email'];
    
    error_log("User logged out: {$user_email} (ID: {$user_id}) from IP: " . $_SERVER['REMOTE_ADDR']);
}

// Destroy all session data
$_SESSION = array();

// Delete session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    unset($_COOKIE['remember_token']);
}

// Redirect to login with success message
redirect('login_new.php', 'You have been logged out successfully.', 'success');
?>
