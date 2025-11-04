<?php
// Simple test to verify login pages are accessible
echo "<h1>Login Pages Test</h1>";

$pages = [
    'Admin Login' => 'admin/login.php',
    'Vendor Login' => 'vendor/login.php',
    'Auth Login' => 'auth/login.php'
];

foreach ($pages as $name => $path) {
    echo "<h2>Testing $name</h2>";
    
    if (file_exists($path)) {
        echo "<p style='color: green;'>✓ File exists: $path</p>";
        
        // Check if file is readable
        if (is_readable($path)) {
            echo "<p style='color: green;'>✓ File is readable</p>";
            
            // Check for basic PHP syntax
            $content = file_get_contents($path);
            if (strpos($content, '<?php') !== false) {
                echo "<p style='color: green;'>✓ Contains PHP code</p>";
            } else {
                echo "<p style='color: orange;'>⚠ No PHP opening tag found</p>";
            }
            
            // Check for required variables
            if (strpos($content, '$error') !== false && strpos($content, '$success') !== false) {
                echo "<p style='color: green;'>✓ Contains required variables</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Missing some required variables</p>";
            }
            
        } else {
            echo "<p style='color: red;'>✗ File is not readable</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ File does not exist: $path</p>";
    }
    
    echo "<hr>";
}

echo "<h2>Dependencies Check</h2>";

$dependencies = [
    'config/constants.php',
    'config/auth.php',
    'config/database.php',
    'includes/error_handler.php',
    'includes/logger.php',
    'includes/jwt_helper.php'
];

foreach ($dependencies as $dep) {
    if (file_exists($dep)) {
        echo "<p style='color: green;'>✓ $dep exists</p>";
    } else {
        echo "<p style='color: red;'>✗ $dep missing</p>";
    }
}

echo "<h2>Test Complete</h2>";
echo "<p>You can now test the unified login page:</p>";
echo "<ul>";
echo "<li><a href='auth/login.php' target='_blank'>Main Login (Handles both Admin & Vendor)</a></li>";
echo "<li><a href='admin/' target='_blank'>Admin Portal (redirects to login)</a></li>";
echo "<li><a href='vendor/' target='_blank'>Vendor Portal (redirects to login)</a></li>";
echo "</ul>";
echo "<p><strong>Note:</strong> Now using a single login form with 2-column design that handles both admin and vendor authentication based on user credentials.</p>";
?>