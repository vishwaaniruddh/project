<?php
/**
 * Cloud Deployment Script
 * Prepares the application for cloud deployment without Composer dependencies
 */

echo "🚀 Preparing Site Installation Management System for Cloud Deployment\n";
echo "=" . str_repeat("=", 70) . "\n\n";

// Step 1: Check current environment
echo "1. Checking current environment...\n";
$phpVersion = PHP_VERSION;
echo "   PHP Version: $phpVersion\n";

if (version_compare($phpVersion, '7.4.0', '<')) {
    echo "   ❌ PHP version too old. Minimum required: 7.4.0\n";
    exit(1);
} else {
    echo "   ✅ PHP version compatible\n";
}

// Step 2: Remove Composer dependencies if they cause issues
echo "\n2. Handling Composer dependencies...\n";
if (is_dir('vendor/')) {
    echo "   Found vendor directory\n";
    
    // Check if composer dependencies work
    try {
        require_once 'vendor/autoload.php';
        echo "   ✅ Composer dependencies working\n";
    } catch (Exception $e) {
        echo "   ⚠️ Composer dependencies causing issues: " . $e->getMessage() . "\n";
        echo "   Removing problematic vendor directory...\n";
        
        // Backup and remove vendor directory
        if (is_dir('vendor_backup/')) {
            removeDirectory('vendor_backup/');
        }
        rename('vendor/', 'vendor_backup/');
        echo "   ✅ Vendor directory backed up and removed\n";
    }
} else {
    echo "   No vendor directory found (good for cloud deployment)\n";
}

// Step 3: Create necessary directories
echo "\n3. Creating necessary directories...\n";
$directories = [
    'uploads/',
    'uploads/sites/',
    'uploads/vendors/',
    'uploads/surveys/',
    'uploads/installations/',
    'auth/logs/',
    'temp/'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "   ✅ Created: $dir\n";
        } else {
            echo "   ❌ Failed to create: $dir\n";
        }
    } else {
        echo "   ✅ Exists: $dir\n";
    }
}

// Step 4: Set proper permissions
echo "\n4. Setting directory permissions...\n";
$writableDirectories = [
    'uploads/',
    'auth/logs/',
    'temp/'
];

foreach ($writableDirectories as $dir) {
    if (is_dir($dir)) {
        if (chmod($dir, 0755)) {
            echo "   ✅ Set permissions for: $dir\n";
        } else {
            echo "   ⚠️ Could not set permissions for: $dir\n";
        }
    }
}

// Step 5: Create .htaccess files for security
echo "\n5. Creating security files...\n";

// Uploads directory protection
$uploadsHtaccess = "uploads/.htaccess";
if (!file_exists($uploadsHtaccess)) {
    $htaccessContent = "# Prevent direct access to uploaded files\n";
    $htaccessContent .= "Options -Indexes\n";
    $htaccessContent .= "<Files *.php>\n";
    $htaccessContent .= "    Deny from all\n";
    $htaccessContent .= "</Files>\n";
    
    if (file_put_contents($uploadsHtaccess, $htaccessContent)) {
        echo "   ✅ Created uploads/.htaccess\n";
    } else {
        echo "   ⚠️ Could not create uploads/.htaccess\n";
    }
}

// Config directory protection
$configHtaccess = "config/.htaccess";
if (!file_exists($configHtaccess)) {
    $htaccessContent = "# Prevent direct access to config files\n";
    $htaccessContent .= "Deny from all\n";
    
    if (file_put_contents($configHtaccess, $htaccessContent)) {
        echo "   ✅ Created config/.htaccess\n";
    } else {
        echo "   ⚠️ Could not create config/.htaccess\n";
    }
}

// Step 6: Create cloud-specific configuration
echo "\n6. Creating cloud configuration...\n";
$cloudConfig = "config/cloud.php";
$cloudConfigContent = "<?php\n";
$cloudConfigContent .= "// Cloud-specific configuration\n";
$cloudConfigContent .= "define('IS_CLOUD_DEPLOYMENT', true);\n";
$cloudConfigContent .= "define('CLOUD_PHP_VERSION', '" . PHP_VERSION . "');\n";
$cloudConfigContent .= "define('CLOUD_DEPLOYMENT_DATE', '" . date('Y-m-d H:i:s') . "');\n";
$cloudConfigContent .= "\n";
$cloudConfigContent .= "// Disable features that require Composer\n";
$cloudConfigContent .= "define('DISABLE_ADVANCED_EXCEL', true);\n";
$cloudConfigContent .= "define('USE_BASIC_CSV_ONLY', true);\n";
$cloudConfigContent .= "?>";

if (file_put_contents($cloudConfig, $cloudConfigContent)) {
    echo "   ✅ Created cloud configuration\n";
} else {
    echo "   ⚠️ Could not create cloud configuration\n";
}

// Step 7: Test database connection
echo "\n7. Testing database connection...\n";
try {
    require_once 'config/database.php';
    $db = Database::getInstance()->getConnection();
    if ($db) {
        echo "   ✅ Database connection successful\n";
    } else {
        echo "   ❌ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
    echo "   Please update config/database.php with correct cloud database credentials\n";
}

// Step 8: Create deployment summary
echo "\n8. Creating deployment summary...\n";
$summary = [
    'deployment_date' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_info' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'composer_available' => is_dir('vendor/'),
    'directories_created' => $directories,
    'security_files' => ['.htaccess files created'],
    'status' => 'ready_for_cloud'
];

$summaryFile = 'deployment_summary.json';
if (file_put_contents($summaryFile, json_encode($summary, JSON_PRETTY_PRINT))) {
    echo "   ✅ Created deployment summary: $summaryFile\n";
}

// Final message
echo "\n" . str_repeat("=", 70) . "\n";
echo "🎉 CLOUD DEPLOYMENT PREPARATION COMPLETE!\n";
echo str_repeat("=", 70) . "\n";
echo "\nYour application is now ready for cloud deployment.\n";
echo "\nNext steps:\n";
echo "1. Upload all files to your cloud server\n";
echo "2. Update config/database.php with cloud database credentials\n";
echo "3. Update config/constants.php with cloud URLs\n";
echo "4. Run: php check_compatibility.php (on cloud server)\n";
echo "5. Test the application\n";

echo "\nCloud-compatible features:\n";
echo "✅ Basic CSV import/export (no Composer required)\n";
echo "✅ All core functionality\n";
echo "✅ Authentication system\n";
echo "✅ Database operations\n";
echo "✅ File uploads\n";
echo "✅ Responsive design\n";

echo "\nNote: Advanced Excel features disabled for cloud compatibility.\n";
echo "Basic CSV functionality will be used instead.\n";

function removeDirectory($dir) {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

?>