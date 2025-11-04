<?php
// Check if user is already logged in
require_once '../config/auth.php';
require_once '../config/constants.php';

if (Auth::isLoggedIn()) {
    $user = Auth::getCurrentUser();
    if ($user['role'] === VENDOR_ROLE) {
        // User is already logged in as vendor, show vendor dashboard
        require_once '../includes/vendor_layout.php';
        // Add vendor dashboard content here
        exit();
    } else {
        // User is logged in but not as vendor, redirect to login
        header('Location: ../auth/login.php');
        exit();
    }
} else {
    // User not logged in, redirect to login
    header('Location: ../auth/login.php');
    exit();
}
?>