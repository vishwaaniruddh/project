<?php
/**
 * Production Debug Script
 * Upload this to your production server and run it to debug the url() function issue
 */

echo "=== PRODUCTION DEBUG ===\n\n";

// Test 1: Basic PHP info
echo "1. PHP Version: " . PHP_VERSION . "\n";
echo "   Server: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "   Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "\n\n";

// Test 2: Check if files exist
echo "2. File Existence Check:\n";
$files = [
    'config/constants.php',
    'config/auth.php',
    'includes/admin_layout.php'
];

foreach ($files as $file) {
    echo "   $file: " . (file_exists($file) ? '✓ EXISTS' : '✗ NOT FOUND') . "\n";
}
echo "\n";

// Test 3: Try to load constants.php
echo "3. Loading constants.php:\n";
try {
    if (file_exists('config/constants.php')) {
        require_once 'config/constants.php';
        echo "   ✓ constants.php loaded\n";
        
        // Check if functions are defined
        echo "   url() function: " . (function_exists('url') ? '✓ DEFINED' : '✗ NOT DEFINED') . "\n";
        echo "   BASE_URL constant: " . (defined('BASE_URL') ? '✓ DEFINED (' . BASE_URL . ')' : '✗ NOT DEFINED') . "\n";
        
        if (function_exists('url')) {
            echo "   Test url('/test'): " . url('/test') . "\n";
        }
    } else {
        echo "   ✗ constants.php file not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error loading constants.php: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "   ✗ Fatal error loading constants.php: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 4: Try to load auth.php
echo "4. Loading auth.php:\n";
try {
    if (file_exists('config/auth.php')) {
        require_once 'config/auth.php';
        echo "   ✓ auth.php loaded\n";
        echo "   url() function after auth: " . (function_exists('url') ? '✓ AVAILABLE' : '✗ NOT AVAILABLE') . "\n";
    } else {
        echo "   ✗ auth.php file not found\n";
    }
} catch (Exception $e) {
    echo "   ✗ Error loading auth.php: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "   ✗ Fatal error loading auth.php: " . $e->getMessage() . "\n";
}
echo "\n";

// Test 5: Environment detection
echo "5. Environment Detection:\n";
if (function_exists('getEnvironment')) {
    echo "   Environment: " . getEnvironment() . "\n";
} else {
    echo "   ✗ getEnvironment() function not available\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "Upload this file to your production server and run it to see what's happening.\n";
?>