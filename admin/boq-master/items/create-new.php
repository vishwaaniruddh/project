<?php
/**
 * AJAX endpoint to create a new BOQ item and optionally add it to a BOQ master
 * 
 * Requirements: 5.1, 5.2, 5.3
 */

require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../models/BoqItem.php';
require_once __DIR__ . '/../../../models/BoqMasterItem.php';

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
    $boqItemModel = new BoqItem();
    
    // Get and validate input
    $itemName = trim($_POST['item_name'] ?? '');
    $itemCode = trim($_POST['item_code'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $needSerialNumber = isset($_POST['need_serial_number']) && $_POST['need_serial_number'] == '1';
    
    // Optional: BOQ master association
    $boqMasterId = (int)($_POST['boq_master_id'] ?? 0);
    $defaultQuantity = floatval($_POST['default_quantity'] ?? 1);
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Prepare item data
    $itemData = [
        'item_name' => $itemName,
        'item_code' => $itemCode,
        'unit' => $unit,
        'status' => 'active'
    ];
    
    if (!empty($category)) {
        $itemData['category'] = $category;
    }
    if (!empty($description)) {
        $itemData['description'] = $description;
    }
    $itemData['need_serial_number'] = $needSerialNumber ? 1 : 0;
    
    // Validate item data
    $errors = $boqItemModel->validateItemData($itemData);
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors),
            'errors' => $errors
        ]);
        exit;
    }
    
    // Create the new BOQ item
    $newItemId = $boqItemModel->create($itemData);
    
    if (!$newItemId) {
        echo json_encode(['success' => false, 'message' => 'Failed to create item']);
        exit;
    }
    
    // Get the newly created item
    $newItem = $boqItemModel->find($newItemId);
    
    $response = [
        'success' => true,
        'message' => 'Item created successfully',
        'item' => $newItem
    ];
    
    // If BOQ master ID is provided, also add the item to the BOQ
    if ($boqMasterId > 0) {
        $boqMasterItemModel = new BoqMasterItem();
        
        $associationData = [
            'boq_master_id' => $boqMasterId,
            'boq_item_id' => $newItemId,
            'default_quantity' => $defaultQuantity,
            'remarks' => $remarks,
            'status' => 'active'
        ];
        
        $associationId = $boqMasterItemModel->create($associationData);
        
        if ($associationId) {
            $response['message'] = 'Item created and added to BOQ successfully';
            $response['association_id'] = $associationId;
        } else {
            $response['message'] = 'Item created but failed to add to BOQ';
            $response['warning'] = 'The item was created but could not be associated with the BOQ';
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log('BOQ Item Create Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating the item'
    ]);
}
