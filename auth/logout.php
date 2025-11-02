<?php
require_once '../config/auth.php';
require_once '../includes/logger.php';

// Log the logout action
if (Auth::isLoggedIn()) {
    $user = Auth::getCurrentUser();
    Logger::logUserLogout($user['username']);
}

// Perform logout
Auth::logout();
?>