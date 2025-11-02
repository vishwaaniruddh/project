<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/SitesController.php';
require_once __DIR__ . '/../../includes/master_functions.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

header('Content-Type: application/json');

// Log the request for debugging
error_log("Bulk upload request received");
error_log("Files received: " . print_r($_FILES, true));

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
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