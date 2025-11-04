<?php
/**
 * PHP Compatibility Check Script
 * Checks if the current PHP version and environment is compatible with the application
 */

echo "🔍 PHP Compatibility Check for Site Installation Management System\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Check PHP Version
$phpVersion = PHP_VERSION;
$requiredVersion = '7.4.0';

echo "Current PHP Version: $phpVersion\n";
echo "Required PHP Version: $requiredVersion or higher\n";

if (version_compare($phpVersion, $requiredVersion, '>=')) {
    echo "✅ PHP Version: COMPATIBLE\n";
} else {
    echo "❌ PHP Version: INCOMPATIBLE - Please upgrade PHP\n";
}

echo "\n";

// Check Required Extensions
$requiredExtensions = [
    'pdo',
    'pdo_mysql',
    'mysqli',
    'mbstring',
    'json',
    'fileinfo',
    'gd',
    'curl'
];

echo "Checking PHP Extensions:\n";
$missingExtensions = [];

foreach ($requiredExtensions as $extension) {
    if (extension_loaded($extension)) {
        echo "✅ $extension: Available\n";
    } else {
        echo "❌ $extension: Missing\n";
        $missingExtensions[] = $extension;
    }
}

echo "\n";

// Check Directory Permissions
echo "Checking Directory Permissions:\n";
$directories = [
    'uploads' => 'uploads/',
    'logs' => 'auth/logs/',
    'assets' => 'assets/'
];

foreach ($directories as $name => $path) {
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "✅ $name directory: Writable\n";
        } else {
            echo "⚠️ $name directory: Not writable\n";
        }
    } else {
        echo "⚠️ $name directory: Does not exist\n";
    }
}

echo "\n";

// Check Database Connection
echo "Checking Database Connection:\n";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    if ($db) {
        echo "✅ Database: Connected successfully\n";
    } else {
        echo "❌ Database: Connection failed\n";
    }
} catch (Exception $e) {
    echo "❌ Database: " . $e->getMessage() . "\n";
}

echo "\n";

// Summary
echo "=" . str_repeat("=", 70) . "\n";
echo "COMPATIBILITY SUMMARY\n";
echo "=" . str_repeat("=", 70) . "\n";

$compatible = version_compare($phpVersion, $requiredVersion, '>=') && empty($missingExtensions);

if ($compatible) {
    echo "🎉 SYSTEM COMPATIBLE: Your server meets all requirements!\n";
    echo "✅ You can deploy the application safely.\n";
} else {
    echo "⚠️ COMPATIBILITY ISSUES FOUND:\n";
    
    if (!version_compare($phpVersion, $requiredVersion, '>=')) {
        echo "- Upgrade PHP to version $requiredVersion or higher\n";
    }
    
    if (!empty($missingExtensions)) {
        echo "- Install missing PHP extensions: " . implode(', ', $missingExtensions) . "\n";
    }
}

echo "\n";
echo "For cloud deployment, ensure your hosting provider supports:\n";
echo "- PHP 7.4+ (PHP 8.1+ recommended)\n";
echo "- MySQL 5.7+ or MariaDB 10.3+\n";
echo "- All required PHP extensions\n";
echo "- Write permissions for upload directories\n";

?>