<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/SitesController.php';
require_once __DIR__ . '/../../includes/master_functions.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

// Handle AJAX requests (return JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    
    // Log the request for debugging
    error_log("Bulk upload AJAX request received");
    error_log("Files received: " . print_r($_FILES, true));

    try {
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error occurred');
        }
        
        $file = $_FILES['excel_file'];
        $allowedTypes = [
            'application/vnd.ms-excel', 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'application/csv'
        ];
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['xlsx', 'xls', 'csv'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Please upload an Excel file (.xlsx, .xls) or CSV file (.csv)');
        }
        
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 10MB');
        }
        
        // Load PhpSpreadsheet library (you'll need to install this via Composer)
        // For now, we'll use a simple CSV-like approach
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . 'sites_' . time() . '_' . $file['name'];
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        // Process the Excel file
        $results = processSitesExcel($uploadPath);
        
        // Clean up uploaded file
        unlink($uploadPath);
        
        if ($results['success']) {
            echo json_encode([
                'success' => true,
                'message' => "Successfully processed {$results['processed']} sites. {$results['created']} created, {$results['updated']} updated.",
                'details' => $results
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Bulk upload completed with errors',
                'errors' => $results['errors']
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

// Handle regular form submission (non-AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error occurred');
        }
        
        $file = $_FILES['excel_file'];
        $allowedTypes = [
            'application/vnd.ms-excel', 
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'application/csv'
        ];
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExtensions = ['xlsx', 'xls', 'csv'];
        
        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new Exception('Invalid file type. Please upload an Excel file (.xlsx, .xls) or CSV file (.csv)');
        }
        
        // Check file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum size is 10MB');
        }
        
        $uploadDir = __DIR__ . '/../../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $uploadPath = $uploadDir . 'sites_' . time() . '_' . $file['name'];
        
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to save uploaded file');
        }
        
        // Process the Excel file
        $results = processSitesExcel($uploadPath);
        
        // Clean up uploaded file
        unlink($uploadPath);
        
        $success = $results['success'];
        $message = $results['success'] ? 
            "Successfully processed {$results['processed']} sites. {$results['created']} created, {$results['updated']} updated." :
            'Bulk upload completed with errors';
        $uploadResult = $results;
        
    } catch (Exception $e) {
        $success = false;
        $message = $e->getMessage();
        $uploadResult = null;
    }
}

// Show UI for GET requests or after form submission
$title = 'Bulk Upload Sites';
ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Bulk Upload Sites</h1>
        <p class="mt-2 text-sm text-gray-700">Upload multiple sites using Excel or CSV file</p>
    </div>
    <div class="flex space-x-2">
        <a href="download_template.php" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
            Download Template
        </a>
        <a href="index.php" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
            </svg>
            Back to Sites
        </a>
    </div>
</div>

<?php if (isset($success)): ?>
    <?php if ($success): ?>
        <!-- Success Message -->
        <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800"><?php echo htmlspecialchars($message); ?></h3>
                    <?php if ($uploadResult): ?>
                        <div class="mt-2 text-sm text-green-700">
                            <p>Upload Summary:</p>
                            <ul class="list-disc list-inside mt-1">
                                <li>Total processed: <?php echo $uploadResult['processed'] ?? 0; ?> rows</li>
                                <li>Successfully created: <?php echo $uploadResult['created'] ?? 0; ?> sites</li>
                                <li>Successfully updated: <?php echo $uploadResult['updated'] ?? 0; ?> sites</li>
                                <?php if (isset($uploadResult['errors']) && count($uploadResult['errors']) > 0): ?>
                                    <li class="text-red-600">Errors: <?php echo count($uploadResult['errors']); ?> rows</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Error Message -->
        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Upload Failed</h3>
                    <p class="mt-1 text-sm text-red-700"><?php echo htmlspecialchars($message); ?></p>
                    <?php if ($uploadResult && isset($uploadResult['errors']) && count($uploadResult['errors']) > 0): ?>
                        <div class="mt-2">
                            <p class="text-sm font-medium text-red-800">Error Details:</p>
                            <ul class="mt-1 text-sm text-red-700 list-disc list-inside">
                                <?php foreach (array_slice($uploadResult['errors'], 0, 10) as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                                <?php if (count($uploadResult['errors']) > 10): ?>
                                    <li>... and <?php echo count($uploadResult['errors']) - 10; ?> more errors</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Instructions Card -->
<div class="card mb-6">
    <div class="card-body">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload Instructions</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium text-gray-900 mb-2">File Requirements</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>• Supported formats: Excel (.xlsx, .xls) or CSV (.csv)</li>
                    <li>• Maximum file size: 10MB</li>
                    <li>• First row should contain column headers</li>
                    <li>• Required columns: Site ID, Location</li>
                </ul>
            </div>
            
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Column Order</h4>
                <ul class="text-sm text-gray-600 space-y-1">
                    <li>1. Site ID (Required)</li>
                    <li>2. Store ID</li>
                    <li>3. Location (Required)</li>
                    <li>4. Country</li>
                    <li>5. State</li>
                    <li>6. City</li>
                    <li>7. Branch</li>
                    <li>8. Customer</li>
                    <li>9. Bank</li>
                    <li>10. PO Number</li>
                    <li>11. PO Date (YYYY-MM-DD)</li>
                    <li>12. Remarks</li>
                </ul>
            </div>
        </div>
        
        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h5 class="text-sm font-medium text-blue-800">Important Notes</h5>
                    <p class="text-sm text-blue-700 mt-1">
                        • Download the template file to ensure correct format<br>
                        • Master data (Country, State, City, Customer, Bank) must exist in the system<br>
                        • Existing sites will be updated, new sites will be created<br>
                        • Invalid data will be reported in the results
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Form -->
<div class="card">
    <div class="card-body">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload File</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="excel_file" class="form-label">Select Excel or CSV File *</label>
                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md hover:border-gray-400 transition-colors">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-gray-600">
                            <label for="excel_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                <span>Upload a file</span>
                                <input id="excel_file" name="excel_file" type="file" class="sr-only" accept=".xlsx,.xls,.csv" required>
                            </label>
                            <p class="pl-1">or drag and drop</p>
                        </div>
                        <p class="text-xs text-gray-500">Excel (.xlsx, .xls) or CSV files up to 10MB</p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 mt-6">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                    Upload Sites
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// File upload handling
document.getElementById('excel_file').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        updateFileDisplay(file);
    }
});

// Drag and drop handling
const dropZone = document.querySelector('.border-dashed');
dropZone.addEventListener('dragover', function(e) {
    e.preventDefault();
    this.classList.add('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('dragleave', function(e) {
    e.preventDefault();
    this.classList.remove('border-blue-500', 'bg-blue-50');
});

dropZone.addEventListener('drop', function(e) {
    e.preventDefault();
    this.classList.remove('border-blue-500', 'bg-blue-50');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        document.getElementById('excel_file').files = files;
        updateFileDisplay(files[0]);
    }
});

function updateFileDisplay(file) {
    const dropZone = document.querySelector('.border-dashed .space-y-1');
    dropZone.innerHTML = `
        <svg class="mx-auto h-12 w-12 text-green-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
        </svg>
        <div class="text-sm text-gray-900">
            <span class="font-medium">${file.name}</span>
        </div>
        <p class="text-xs text-gray-500">${formatFileSize(file.size)} • ${file.type || 'Unknown type'}</p>
    `;
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/admin_layout.php';
?>

<?php
function processSitesExcel($filePath) {
    $results = [
        'success' => true,
        'processed' => 0,
        'created' => 0,
        'updated' => 0,
        'errors' => []
    ];
    
    try {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        if ($extension === 'csv') {
            $data = readCSVFile($filePath);
        } else {
            // Use PhpSpreadsheet for Excel files
            $data = readExcelFile($filePath);
        }
        
        $siteController = new SitesController();
        $rowNumber = 1;
        
        foreach ($data as $row) {
            $rowNumber++;
            
            try {
                // Skip empty rows
                if (empty(array_filter($row))) {
                    continue;
                }
                
                // Validate and process row
                $siteData = validateAndProcessRow($row, $rowNumber);
                
                if (!empty($siteData['errors'])) {
                    $results['errors'] = array_merge($results['errors'], $siteData['errors']);
                    continue;
                }
                
                // Check if site exists
                $siteModel = new Site();
                $existingSite = $siteModel->findBySiteId($siteData['data']['site_id']);
                
                if ($existingSite) {
                    // Update existing site
                    $success = $siteModel->update($existingSite['id'], $siteData['data']);
                    if ($success) {
                        $results['updated']++;
                    } else {
                        $results['errors'][] = "Row {$rowNumber}: Failed to update site {$siteData['data']['site_id']}";
                    }
                } else {
                    // Create new site
                    $siteId = $siteModel->create($siteData['data']);
                    if ($siteId) {
                        $results['created']++;
                    } else {
                        $results['errors'][] = "Row {$rowNumber}: Failed to create site {$siteData['data']['site_id']}";
                    }
                }
                
                $results['processed']++;
                
            } catch (Exception $e) {
                $results['errors'][] = "Row {$rowNumber}: " . $e->getMessage();
            }
        }
        
        if (!empty($results['errors'])) {
            $results['success'] = false;
        }
        
    } catch (Exception $e) {
        $results['success'] = false;
        $results['errors'][] = $e->getMessage();
    }
    
    return $results;
}

function readCSVFile($filePath) {
    $data = [];
    if (($handle = fopen($filePath, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Skip header row
        while (($row = fgetcsv($handle)) !== FALSE) {
            $data[] = $row;
        }
        fclose($handle);
    }
    return $data;
}

function readExcelFile($filePath) {
    try {
        $spreadsheet = IOFactory::load($filePath);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = [];
        
        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        // Skip header row (start from row 2)
        for ($row = 2; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cell = $worksheet->getCell($col . $row);
                $value = $cell->getValue();
                
                // Handle date cells
                if (Date::isDateTime($cell)) {
                    $dateValue = Date::excelToDateTimeObject($value);
                    $value = $dateValue->format('Y-m-d');
                }
                
                $rowData[] = (string)$value;
            }
            
            // Skip completely empty rows
            if (!empty(array_filter($rowData))) {
                $data[] = $rowData;
            }
        }
        
        return $data;
        
    } catch (Exception $e) {
        throw new Exception('Error reading Excel file: ' . $e->getMessage());
    }
}

function validateAndProcessRow($row, $rowNumber) {
    $errors = [];
    
    // Expected columns: Site ID, Store ID, Location, Country, State, City, Branch, Customer, Bank, PO Number, PO Date, Remarks
    $expectedColumns = 12;
    
    // Pad row with empty strings if it has fewer columns
    while (count($row) < $expectedColumns) {
        $row[] = '';
    }
    
    if (count($row) < 3) { // At least Site ID, Location, and one location field
        $errors[] = "Row {$rowNumber}: Insufficient data columns";
        return ['errors' => $errors];
    }
    
    $siteData = [
        'site_id' => trim($row[0] ?? ''),
        'store_id' => trim($row[1] ?? ''),
        'location' => trim($row[2] ?? ''),
        'country' => trim($row[3] ?? ''),
        'state' => trim($row[4] ?? ''),
        'city' => trim($row[5] ?? ''),
        'branch' => trim($row[6] ?? ''),
        'customer' => trim($row[7] ?? ''),
        'bank' => trim($row[8] ?? ''),
        'po_number' => trim($row[9] ?? ''),
        'po_date' => trim($row[10] ?? ''),
        'remarks' => trim($row[11] ?? ''),
        'created_by' => 'bulk_upload'
    ];
    
    // Validate required fields
    if (empty($siteData['site_id'])) {
        $errors[] = "Row {$rowNumber}: Site ID is required";
    }
    
    if (empty($siteData['location'])) {
        $errors[] = "Row {$rowNumber}: Location is required";
    }
    
    // Validate and convert location data to foreign keys
    if (!empty($siteData['country'])) {
        $countryId = findMasterIdByName('countries', $siteData['country']);
        if ($countryId) {
            $siteData['country_id'] = $countryId;
        } else {
            $errors[] = "Row {$rowNumber}: Country '{$siteData['country']}' not found in master data";
        }
    }
    
    if (!empty($siteData['state']) && isset($siteData['country_id'])) {
        $stateId = findStateIdByName($siteData['state'], $siteData['country_id']);
        if ($stateId) {
            $siteData['state_id'] = $stateId;
        } else {
            $errors[] = "Row {$rowNumber}: State '{$siteData['state']}' not found for country '{$siteData['country']}'";
        }
    }
    
    if (!empty($siteData['city']) && isset($siteData['state_id'])) {
        $cityId = findCityIdByName($siteData['city'], $siteData['state_id']);
        if ($cityId) {
            $siteData['city_id'] = $cityId;
        } else {
            $errors[] = "Row {$rowNumber}: City '{$siteData['city']}' not found for state '{$siteData['state']}'";
        }
    }
    
    // Validate and convert customer data
    if (!empty($siteData['customer'])) {
        $customerId = findMasterIdByName('customers', $siteData['customer']);
        if ($customerId) {
            $siteData['customer_id'] = $customerId;
        } else {
            $errors[] = "Row {$rowNumber}: Customer '{$siteData['customer']}' not found in master data";
        }
    }
    
    // Validate and convert bank data
    if (!empty($siteData['bank'])) {
        $bankId = findMasterIdByName('banks', $siteData['bank']);
        if ($bankId) {
            $siteData['bank_id'] = $bankId;
        } else {
            $errors[] = "Row {$rowNumber}: Bank '{$siteData['bank']}' not found in master data";
        }
    }
    
    // Validate PO date format
    if (!empty($siteData['po_date'])) {
        $dateFormats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];
        $validDate = false;
        
        foreach ($dateFormats as $format) {
            $date = DateTime::createFromFormat($format, $siteData['po_date']);
            if ($date && $date->format($format) === $siteData['po_date']) {
                $siteData['po_date'] = $date->format('Y-m-d');
                $validDate = true;
                break;
            }
        }
        
        if (!$validDate) {
            $errors[] = "Row {$rowNumber}: Invalid PO date format '{$siteData['po_date']}'. Use YYYY-MM-DD, DD/MM/YYYY, or similar formats";
        }
    }
    
    return [
        'data' => $siteData,
        'errors' => $errors
    ];
}

function findMasterIdByName($table, $name) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM {$table} WHERE name = ? AND status = 'active' LIMIT 1");
        $stmt->execute([trim($name)]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        error_log("Error finding master ID for {$table}: " . $e->getMessage());
        return null;
    }
}

function findStateIdByName($stateName, $countryId) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM states WHERE name = ? AND country_id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([trim($stateName), $countryId]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        error_log("Error finding state ID: " . $e->getMessage());
        return null;
    }
}

function findCityIdByName($cityName, $stateId) {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id FROM cities WHERE name = ? AND state_id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([trim($cityName), $stateId]);
        $result = $stmt->fetch();
        return $result ? $result['id'] : null;
    } catch (Exception $e) {
        error_log("Error finding city ID: " . $e->getMessage());
        return null;
    }
}
?>