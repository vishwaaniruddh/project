<?php
require_once 'config/database.php';
require_once 'models/Site.php';

echo "<h2>Bulk Upload Process Test</h2>";

// Include the functions from bulk_upload.php
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

function validateAndProcessRow($row, $rowNumber) {
    $errors = [];
    
    // Expected columns: Site ID, Store ID, Location, Country, State, City, Branch, Customer, Bank, PO Number, PO Date, Remarks
    $expectedColumns = 12;
    
    // Pad row with empty strings if it has fewer columns
    while (count($row) < $expectedColumns) {
        $row[] = '';
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

// Test the CSV processing
echo "<h3>Testing CSV Processing:</h3>";

$csvFile = 'test_sites_with_existing_data.csv';
if (file_exists($csvFile)) {
    echo "✅ CSV file found: $csvFile<br>";
    
    $data = [];
    if (($handle = fopen($csvFile, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Skip header row
        echo "Header: " . implode(', ', $header) . "<br><br>";
        
        $rowNumber = 1;
        while (($row = fgetcsv($handle)) !== FALSE) {
            $rowNumber++;
            echo "<strong>Processing Row $rowNumber:</strong><br>";
            echo "Raw data: " . implode(' | ', $row) . "<br>";
            
            $result = validateAndProcessRow($row, $rowNumber);
            
            if (!empty($result['errors'])) {
                echo "❌ Validation errors:<br>";
                foreach ($result['errors'] as $error) {
                    echo "  - $error<br>";
                }
            } else {
                echo "✅ Validation passed<br>";
                echo "Processed data:<br>";
                foreach ($result['data'] as $key => $value) {
                    if (!empty($value)) {
                        echo "  - $key: $value<br>";
                    }
                }
            }
            echo "<br>";
        }
        fclose($handle);
    }
} else {
    echo "❌ CSV file not found: $csvFile<br>";
}

echo "<h3>Server Configuration:</h3>";
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "<br>";
echo "post_max_size: " . ini_get('post_max_size') . "<br>";

$maxSize = min(
    parseSize(ini_get('upload_max_filesize')),
    parseSize(ini_get('post_max_size')),
    10 * 1024 * 1024
);
echo "Effective max file size: " . formatFileSize($maxSize) . "<br>";
?>