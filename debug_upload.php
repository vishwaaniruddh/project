<?php
// Debug script to check file upload issues
echo "<h2>File Upload Debug Information</h2>";

echo "<h3>PHP Configuration:</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "<br>";
echo "max_execution_time: " . ini_get('max_execution_time') . "<br>";
echo "memory_limit: " . ini_get('memory_limit') . "<br>";

echo "<h3>Upload Directory Check:</h3>";
$uploadDir = __DIR__ . '/uploads/';
echo "Upload directory: " . $uploadDir . "<br>";
echo "Directory exists: " . (is_dir($uploadDir) ? 'Yes' : 'No') . "<br>";
echo "Directory writable: " . (is_writable($uploadDir) ? 'Yes' : 'No') . "<br>";

if (!is_dir($uploadDir)) {
    if (mkdir($uploadDir, 0755, true)) {
        echo "Created upload directory successfully<br>";
    } else {
        echo "Failed to create upload directory<br>";
    }
}

echo "<h3>POST Data:</h3>";
echo "POST data received: " . (empty($_POST) ? 'No' : 'Yes') . "<br>";
echo "FILES data received: " . (empty($_FILES) ? 'No' : 'Yes') . "<br>";

if (!empty($_FILES)) {
    echo "<h4>Files Information:</h4>";
    foreach ($_FILES as $key => $file) {
        echo "Field name: $key<br>";
        echo "Original name: " . ($file['name'] ?? 'Not set') . "<br>";
        echo "Type: " . ($file['type'] ?? 'Not set') . "<br>";
        echo "Size: " . ($file['size'] ?? 'Not set') . " bytes<br>";
        echo "Error code: " . ($file['error'] ?? 'Not set') . "<br>";
        echo "Error meaning: " . getUploadErrorMessage($file['error'] ?? -1) . "<br>";
        echo "Temp name: " . ($file['tmp_name'] ?? 'Not set') . "<br>";
        echo "Temp file exists: " . (isset($file['tmp_name']) && file_exists($file['tmp_name']) ? 'Yes' : 'No') . "<br>";
        echo "<br>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>Processing Result:</h3>";
    
    if (!isset($_FILES['excel_file'])) {
        echo "❌ No file field 'excel_file' found in upload<br>";
    } else {
        $file = $_FILES['excel_file'];
        echo "✅ File field found<br>";
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo "❌ Upload error: " . getUploadErrorMessage($file['error']) . "<br>";
        } else {
            echo "✅ File uploaded successfully<br>";
            echo "File size: " . formatBytes($file['size']) . "<br>";
            echo "File type: " . $file['type'] . "<br>";
            
            // Check file extension
            $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExtensions = ['xlsx', 'xls', 'csv'];
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                echo "❌ Invalid file extension: $fileExtension<br>";
            } else {
                echo "✅ Valid file extension: $fileExtension<br>";
            }
            
            // Try to move file
            $uploadPath = $uploadDir . 'test_' . time() . '_' . $file['name'];
            if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
                echo "✅ File moved successfully to: $uploadPath<br>";
                echo "File exists after move: " . (file_exists($uploadPath) ? 'Yes' : 'No') . "<br>";
                
                // Clean up
                if (file_exists($uploadPath)) {
                    unlink($uploadPath);
                    echo "✅ Test file cleaned up<br>";
                }
            } else {
                echo "❌ Failed to move uploaded file<br>";
            }
        }
    }
}

function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_OK:
            return 'No error';
        case UPLOAD_ERR_INI_SIZE:
            return 'File exceeds upload_max_filesize directive';
        case UPLOAD_ERR_FORM_SIZE:
            return 'File exceeds MAX_FILE_SIZE directive';
        case UPLOAD_ERR_PARTIAL:
            return 'File was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'File upload stopped by extension';
        default:
            return 'Unknown error code: ' . $errorCode;
    }
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h2, h3, h4 { color: #333; }
        .form-container { margin-top: 20px; padding: 20px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <div class="form-container">
        <h3>Test File Upload</h3>
        <form method="POST" enctype="multipart/form-data">
            <p>
                <label for="excel_file">Select file:</label><br>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls,.csv">
            </p>
            <p>
                <button type="submit">Upload Test</button>
            </p>
        </form>
    </div>
</body>
</html>