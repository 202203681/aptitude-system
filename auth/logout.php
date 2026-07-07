<?php
include '../config/db.php';

if (isset($_SESSION['user_id'])) {
    // Log the logout action
    logSystemAction($conn, $_SESSION['user_id'], 'logout', null);
    
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }
}

// Destroy the session
session_destroy();

// Redirect to landing page (not login page)
header("Location: ../index.php");
exit();
?>