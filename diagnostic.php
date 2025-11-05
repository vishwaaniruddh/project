<?php
/**
 * Diagnostic script to verify URL function and environment detection
 * Run this on production to verify the fixes
 */

echo "=== DIAGNOSTIC REPORT ===\n\n";

// Test 1: Check if constants.php can be loaded
echo "1. Testing constants.php loading:\n";
if (!function_exists('getEnvironment')) {
    try {
        require_once __DIR__ . '/config/constants.php';
        echo "   ✓ constants.php loaded successfully\n";
    } catch (Exception $e) {
        echo "   ✗ Error loading constants.php: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    echo "   ✓ constants.php already loaded\n";
}

// Test 2: Check environment detection
echo "\n2. Environment Detection:\n";
echo "   Host: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "   Detected Environment: " . getEnvironment() . "\n";
echo "   BASE_URL: " . BASE_URL . "\n";

// Test 3: Check url() function
echo "\n3. URL Function Test:\n";
if (function_exists('url')) {
    echo "   ✓ url() function exists\n";
    echo "   url('/admin/dashboard.php'): " . url('/admin/dashboard.php') . "\n";
    echo "   url('/assets/css/admin.css'): " . url('/assets/css/admin.css') . "\n";
} else {
    echo "   ✗ url() function not found\n";
}

// Test 4: Check for double slashes
echo "\n4. Double Slash Test:\n";
$testUrl = url('/admin/dashboard.php');
if (strpos($testUrl, '//') !== false && strpos($testUrl, 'http://') !== 0 && strpos($testUrl, 'https://') !== 0) {
    echo "   ✗ Double slash detected in: $testUrl\n";
} else {
    echo "   ✓ No double slashes detected\n";
}

// Test 5: Environment indicator
echo "\n5. Environment Indicator Test:\n";
$env = getEnvironment();
$envColors = [
    'development' => 'bg-green-500 text-white',
    'testing' => 'bg-yellow-500 text-black',
    'production' => 'bg-red-500 text-white'
];
$envColor = $envColors[$env] ?? 'bg-gray-500 text-white';
echo "   Environment: $env\n";
echo "   CSS Classes: $envColor\n";
echo "   Display: " . strtoupper($env) . "\n";

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "If all tests show ✓, the fixes are working correctly.\n";
?>