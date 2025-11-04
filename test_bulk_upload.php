<?php
// Simple test script for bulk upload functionality
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Site.php';

echo "<h2>Bulk Upload Test</h2>";

// Test file upload simulation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_file'])) {
    echo "<h3>File Upload Test Results:</h3>";
    
    $file = $_FILES['test_file'];
    
    echo "File name: " . $file['name'] . "<br>";
    echo "File size: " . $file['size'] . " bytes<br>";
    echo "File type: " . $file['type'] . "<br>";
    echo "Upload error: " . $file['error'] . "<br>";
    
    if ($file['error'] === UPLOAD_ERR_OK) {
        echo "✅ File uploaded successfully<br>";
        
        // Test CSV reading
        if (($handle = fopen($file['tmp_name'], "r")) !== FALSE) {
            echo "<h4>CSV Content:</h4>";
            $rowNum = 0;
            while (($row = fgetcsv($handle)) !== FALSE) {
                $rowNum++;
                echo "Row {$rowNum}: " . implode(', ', $row) . "<br>";
            }
            fclose($handle);
        }
    } else {
        echo "❌ File upload failed with error code: " . $file['error'] . "<br>";
    }
}

// Test database connection
echo "<h3>Database Connection Test:</h3>";
try {
    $db = Database::getInstance()->getConnection();
    echo "✅ Database connection successful<br>";
    
    // Test Site model
    $siteModel = new Site();
    echo "✅ Site model loaded successfully<br>";
    
    // Test finding a site
    $testSite = $siteModel->findBySiteId('TEST001');
    if ($testSite) {
        echo "✅ Found existing site with ID TEST001<br>";
    } else {
        echo "ℹ️ No site found with ID TEST001 (this is normal for first test)<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// Test helper functions
echo "<h3>Helper Functions Test:</h3>";

function parseSize($size) {
    $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
    $size = preg_replace('/[^0-9\.]/', '', $size);
    if ($unit) {
        return round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
    } else {
        return round($size);
    }
}

function formatFileSize($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}

$testSize = parseSize('2M');
echo "parseSize('2M') = " . $testSize . " bytes<br>";
echo "formatFileSize(2097152) = " . formatFileSize(2097152) . "<br>";

echo "<h3>PHP Configuration:</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";

$maxSize = min(
    parseSize(ini_get('upload_max_filesize')),
    parseSize(ini_get('post_max_size')),
    10 * 1024 * 1024
);
echo "Calculated max file size: " . formatFileSize($maxSize) . "<br>";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Bulk Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-form { margin-top: 20px; padding: 20px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="test-form">
        <h3>Test File Upload</h3>
        <form method="POST" enctype="multipart/form-data">
            <p>
                <label for="test_file">Select CSV file:</label><br>
                <input type="file" name="test_file" id="test_file" accept=".csv">
            </p>
            <p>
                <button type="submit">Test Upload</button>
            </p>
        </form>
    </div>
</body>
</html>