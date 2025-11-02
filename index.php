<?php


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once 'config/auth.php';
require_once 'config/constants.php';


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in
if (Auth::isLoggedIn()) {
    $user = Auth::getCurrentUser();
    
    // Redirect based on role
    if ($user['role'] === ADMIN_ROLE) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } elseif ($user['role'] === VENDOR_ROLE) {
        header('Location: ' . BASE_URL . '/vendor/');
    }
    exit();
} else {
    // Redirect to login page
    header('Location: ' . BASE_URL . '/auth/login.php');
    exit();
}
?>