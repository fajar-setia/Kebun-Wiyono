<?php
// logout.php - Pastikan file ini ada dan benar
session_start();

// Debug: log logout action
error_log("User logging out. Session before destroy: " . print_r($_SESSION, true));

// Clear all session data
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

// Redirect to login page
header("Location: index.php");
exit();
?>