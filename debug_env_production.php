<?php
// Debug environment detection in production
echo "=== Environment Debug ===\n";

// Simulate production environment
$_SERVER['HTTP_HOST'] = 'project.sarsspl.com';

echo "Server Info:\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'NOT SET') . "\n";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'NOT SET') . "\n";

// Test domain detection
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
echo "Host variable: '$host'\n";
echo "Contains sarsspl.com: " . (strpos($host, 'sarsspl.com') !== false ? 'YES' : 'NO') . "\n";

// Test environment functions
try {
    require_once 'config/constants.php';
    
    echo "\nFunction Tests:\n";
    echo "getEnvironment(): " . (function_exists('getEnvironment') ? getEnvironment() : 'FUNCTION NOT FOUND') . "\n";
    echo "isProduction(): " . (function_exists('isProduction') ? (isProduction() ? 'YES' : 'NO') : 'FUNCTION NOT FOUND') . "\n";
    echo "isDevelopment(): " . (function_exists('isDevelopment') ? (isDevelopment() ? 'YES' : 'NO') : 'FUNCTION NOT FOUND') . "\n";
    
    echo "\nConstants:\n";
    echo "APP_ENV defined: " . (defined('APP_ENV') ? 'YES (' . APP_ENV . ')' : 'NO') . "\n";
    echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>