<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SiteSurvey.php';
require_once __DIR__ . '/../models/SiteDelegation.php';

// Require vendor authentication
Auth::requireVendor();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $vendorId = Auth::getVendorId();
    $siteId = $_POST['site_id'] ?? null;
    $delegationId = $_POST['delegation_id'] ?? null;
    
    if (!$siteId || !$delegationId) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Verify delegation belongs to vendor
    $delegationModel = new SiteDelegation();
    $delegation = $delegationModel->find($delegationId);
    
    if (!$delegation || $delegation['vendor_id'] != $vendorId) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }
    
    // Handle multiple file uploads for different photo types
    $photoFields = [
        'floor_height_photo' => 'floor_height_photos',
        'ceiling_photo' => 'ceiling_photos',
        'analytic_photos' => 'analytic_photos',
        'existing_poe_photos' => 'existing_poe_photos',
        'space_new_rack_photo' => 'space_new_rack_photos',
        'new_poe_photos' => 'new_poe_photos',
        'rrl_photos' => 'rrl_photos',
        'kptl_photos' => 'kptl_photos'
    ];
    
    $uploadedPhotos = [];
    $uploadDir = __DIR__ . '/../assets/uploads/surveys/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    foreach ($photoFields as $inputName => $dbField) {
        $uploadedPhotos[$dbField] = [];
        
        if (isset($_FILES[$inputName]) && !empty($_FILES[$inputName]['name'])) {
            // Handle multiple files
            if (is_array($_FILES[$inputName]['name'])) {
                foreach ($_FILES[$inputName]['name'] as $key => $filename) {
                    if ($_FILES[$inputName]['error'][$key] === UPLOAD_ERR_OK && !empty($filename)) {
                        $uploadedFile = handleFileUpload($_FILES[$inputName], $key, $uploadDir);
                        if ($uploadedFile) {
                            $uploadedPhotos[$dbField][] = $uploadedFile;
                        }
                    }
                }
            } else {
                // Handle single file
                if ($_FILES[$inputName]['error'] === UPLOAD_ERR_OK) {
                    $uploadedFile = handleSingleFileUpload($_FILES[$inputName], $uploadDir);
                    if ($uploadedFile) {
                        $uploadedPhotos[$dbField][] = $uploadedFile;
                    }
                }
            }
        }
        
        // Convert array to JSON string for database storage
        $uploadedPhotos[$dbField] = !empty($uploadedPhotos[$dbField]) ? json_encode($uploadedPhotos[$dbField]) : null;
    }
    
    // Prepare survey data
    $surveyData = [
        'site_id' => $siteId,
        'vendor_id' => $vendorId,
        'delegation_id' => $delegationId,
        'survey_status' => 'completed',
        'submitted_date' => date('Y-m-d H:i:s'),
        
        // Check-in/Check-out
        'checkin_datetime' => $_POST['checkin_datetime'] ?? null,
        'checkout_datetime' => $_POST['checkout_datetime'] ?? null,
        'working_hours' => $_POST['working_hours'] ?? null,
        
        // Site Information
        'store_model' => $_POST['store_model'] ?? null,
        
        // Floor and Ceiling
        'floor_height' => $_POST['floor_height'] ?? null,
        'floor_height_photos' => $uploadedPhotos['floor_height_photos'],
        'ceiling_type' => $_POST['ceiling_type'] ?? null,
        'ceiling_photos' => $uploadedPhotos['ceiling_photos'],
        
        // Camera Assessment
        'total_cameras' => $_POST['total_cameras'] ?? null,
        'analytic_cameras' => $_POST['analytic_cameras'] ?? null,
        'analytic_photos' => $uploadedPhotos['analytic_photos'],
        
        // POE Rack Assessment
        'existing_poe_rack' => $_POST['existing_poe_rack'] ?? null,
        'existing_poe_photos' => $uploadedPhotos['existing_poe_photos'],
        'space_new_rack' => $_POST['space_new_rack'] ?? null,
        'space_new_rack_photos' => $uploadedPhotos['space_new_rack_photos'],
        'new_poe_rack' => $_POST['new_poe_rack'] ?? null,
        'new_poe_photos' => $uploadedPhotos['new_poe_photos'],
        
        // Zone Assessment
        'zones_recommended' => $_POST['zones_recommended'] ?? null,
        
        // Material Status
        'rrl_delivery_status' => $_POST['rrl_delivery_status'] ?? null,
        'rrl_photos' => $uploadedPhotos['rrl_photos'],
        'kptl_space' => $_POST['kptl_space'] ?? null,
        'kptl_photos' => $uploadedPhotos['kptl_photos'],
        
        // Technical Assessment
        'site_accessibility' => $_POST['site_accessibility'] ?? null,
        'power_availability' => $_POST['power_availability'] ?? null,
        'network_connectivity' => $_POST['network_connectivity'] ?? null,
        'space_adequacy' => $_POST['space_adequacy'] ?? null,
        
        // Survey Findings
        'technical_remarks' => $_POST['technical_remarks'] ?? null,
        'challenges_identified' => $_POST['challenges_identified'] ?? null,
        'recommendations' => $_POST['recommendations'] ?? null,
        'estimated_completion_days' => $_POST['estimated_completion_days'] ?? null
    ];
    
    // Create survey
    $surveyModel = new SiteSurvey();
    $result = $surveyModel->createComprehensive($surveyData);
    
    if ($result) {
        // Update the sites table to reflect survey submission
        require_once __DIR__ . '/../models/Site.php';
        $siteModel = new Site();
        $siteModel->updateSurveyStatus($siteId, true, date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => true, 
            'message' => 'Site feasibility survey submitted successfully!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to submit survey. Please try again.'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Survey submission error: ' . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while submitting the survey.'
    ]);
}

function handleFileUpload($fileArray, $index, $uploadDir) {
    $filename = $fileArray['name'][$index];
    $tmpName = $fileArray['tmp_name'][$index];
    $fileSize = $fileArray['size'][$index];
    
    $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return false;
    }
    
    if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
        return false;
    }
    
    $newFilename = uniqid() . '_' . time() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFilename;
    
    if (move_uploaded_file($tmpName, $uploadPath)) {
        return 'assets/uploads/surveys/' . $newFilename;
    }
    
    return false;
}

function handleSingleFileUpload($file, $uploadDir) {
    $filename = $file['name'];
    $tmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    
    $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($fileExtension, $allowedExtensions)) {
        return false;
    }
    
    if ($fileSize > 5 * 1024 * 1024) { // 5MB limit
        return false;
    }
    
    $newFilename = uniqid() . '_' . time() . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFilename;
    
    if (move_uploaded_file($tmpName, $uploadPath)) {
        return 'assets/uploads/surveys/' . $newFilename;
    }
    
    return false;
}
?>