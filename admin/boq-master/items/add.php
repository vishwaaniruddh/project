<?php
/**
 * AJAX endpoint to add an item to a BOQ master
 * 
 * Requirements: 2.2, 2.3, 2.4
 */

require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../models/BoqMasterItem.php';
require_once __DIR__ . '/../../../models/BoqMaster.php';

// Set JSON response header
header('Content-Type: application/json');

// Require admin authentication
if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $boqMasterItemModel = new BoqMasterItem();
    $boqMasterModel = new BoqMaster();
    
    // Get and validate input
    $boqMasterId = (int)($_POST['boq_master_id'] ?? 0);
    $boqItemId = (int)($_POST['boq_item_id'] ?? 0);
    $quantity = floatval($_POST['default_quantity'] ?? 1);
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Validate BOQ master exists
    if (!$boqMasterId) {
        echo json_encode(['success' => false, 'message' => 'BOQ Master ID is required']);
        exit;
    }
    
    $boqMaster = $boqMasterModel->find($boqMasterId);
    if (!$boqMaster) {
        echo json_encode(['success' => false, 'message' => 'BOQ Master not found']);
        exit;
    }
    
    // Prepare data for validation
    $data = [
        'boq_master_id' => $boqMasterId,
        'boq_item_id' => $boqItemId,
        'default_quantity' => $quantity,
        'remarks' => $remarks,
        'status' => 'active'
    ];
    
    // Validate data
    $errors = $boqMasterItemModel->validateData($data);
    
    if (!empty($errors)) {
        // Return first error message
        $firstError = reset($errors);
        echo json_encode(['success' => false, 'message' => $firstError, 'errors' => $errors]);
        exit;
    }
    
    // Create the association
    $newId = $boqMasterItemModel->create($data);
    
    if ($newId) {
        // Get the newly created item with details
        $newItem = $boqMasterItemModel->getWithItemDetails($newId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Item added to BOQ successfully',
            'data' => $newItem
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add item to BOQ']);
    }
    
} catch (Exception $e) {
    error_log('BOQ Item Add Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while adding the item'
    ]);
}
