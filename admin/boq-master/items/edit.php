<?php
/**
 * AJAX endpoint to edit a BOQ master item association
 * Updates quantity and remarks for an existing association
 * 
 * Requirements: 2.5
 */

require_once __DIR__ . '/../../../config/auth.php';
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
    $boqMasterItemModel = new BoqMasterItem();
    
    // Get and validate input
    $id = (int)($_POST['id'] ?? 0);
    $quantity = floatval($_POST['default_quantity'] ?? 0);
    $remarks = trim($_POST['remarks'] ?? '');
    
    // Validate ID
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Item ID is required']);
        exit;
    }
    
    // Check if item exists
    $existingItem = $boqMasterItemModel->find($id);
    if (!$existingItem) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
    
    // Validate quantity
    if ($quantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be greater than 0']);
        exit;
    }
    
    // Prepare update data
    $updateData = [
        'default_quantity' => $quantity,
        'remarks' => $remarks
    ];
    
    // Update the item
    $updated = $boqMasterItemModel->update($id, $updateData);
    
    if ($updated) {
        // Get the updated item with details
        $updatedItem = $boqMasterItemModel->getWithItemDetails($id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Item updated successfully',
            'data' => $updatedItem
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update item']);
    }
    
} catch (Exception $e) {
    error_log('BOQ Item Edit Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating the item'
    ]);
}
