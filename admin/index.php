<?php
require_once '../config/auth.php';

// Check if user is logged in and is admin
if (Auth::isLoggedIn() && Auth::isAdmin()) {
    // Redirect to dashboard
    header('Location: dashboard.php');
    exit();
} else {
    // Redirect to login
    header('Location: login.php');
    exit();
}
?>