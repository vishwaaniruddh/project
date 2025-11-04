<?php
require_once __DIR__ . '/config/auth.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || 
          (isset($_POST['ajax']) && $_POST['ajax'] === '1');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAjax) {
    header('Content-Type: application/json');
    
    $response = [
        'debug' => true,
        'method' => $_SERVER['REQUEST_METHOD'],
        'ajax_detected' => $isAjax,
        'files_count' => count($_FILES),
        'files_keys' => array_keys($_FILES),
        'post_keys' => array_keys($_POST),
        'excel_file_exists' => isset($_FILES['excel_file']),
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'Not set'
    ];
    
    if (isset($_FILES['excel_file'])) {
        $file = $_FILES['excel_file'];
        $response['file_info'] = [
            'name' => $file['name'],
            'size' => $file['size'],
            'type' => $file['type'],
            'error' => $file['error'],
            'tmp_name' => $file['tmp_name']
        ];
        
        if ($file['error'] === UPLOAD_ERR_OK) {
            $response['success'] = true;
            $response['message'] = 'File uploaded successfully!';
        } else {
            $response['success'] = false;
            $response['message'] = 'File upload error: ' . $file['error'];
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'No file field found in upload';
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

// Show simple form for testing
?>
<!DOCTYPE html>
<html>
<head>
    <title>Minimal Bulk Upload Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        button { padding: 10px 20px; background: #007cba; color: white; border: none; cursor: pointer; }
        .result { margin-top: 20px; padding: 10px; background: #f5f5f5; border: 1px solid #ddd; white-space: pre-wrap; }
    </style>
</head>
<body>
    <h1>Minimal Bulk Upload Test</h1>
    
    <form id="uploadForm" enctype="multipart/form-data">
        <div class="form-group">
            <label for="excel_file">Select File:</label><br>
            <input type="file" id="excel_file" name="excel_file" accept=".csv,.xlsx,.xls">
        </div>
        <button type="submit">Test Upload</button>
    </form>
    
    <div id="result" class="result" style="display: none;"></div>

    <script>
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('excel_file');
            const resultDiv = document.getElementById('result');
            
            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Please select a file');
                return;
            }
            
            const file = fileInput.files[0];
            console.log('Selected file:', file);
            
            const formData = new FormData();
            formData.append('excel_file', file);
            formData.append('ajax', '1');
            
            console.log('FormData entries:');
            for (let pair of formData.entries()) {
                console.log(pair[0] + ':', pair[1]);
            }
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.text();
            })
            .then(text => {
                console.log('Raw response:', text);
                resultDiv.style.display = 'block';
                resultDiv.textContent = text;
                
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        resultDiv.style.backgroundColor = '#d4edda';
                        resultDiv.style.borderColor = '#c3e6cb';
                    } else {
                        resultDiv.style.backgroundColor = '#f8d7da';
                        resultDiv.style.borderColor = '#f5c6cb';
                    }
                } catch (e) {
                    resultDiv.style.backgroundColor = '#fff3cd';
                    resultDiv.style.borderColor = '#ffeaa7';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                resultDiv.style.display = 'block';
                resultDiv.style.backgroundColor = '#f8d7da';
                resultDiv.style.borderColor = '#f5c6cb';
                resultDiv.textContent = 'Error: ' + error.message;
            });
        });
    </script>
</body>
</html>