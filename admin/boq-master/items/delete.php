<?php
/**
 * AJAX endpoint to remove an item from a BOQ master
 * 
 * Requirements: 2.6
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
    
    // Get item ID from query string or POST
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    
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
    
    // Delete the item association
    $deleted = $boqMasterItemModel->delete($id);
    
    if ($deleted) {
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from BOQ successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
    }
    
} catch (Exception $e) {
    error_log('BOQ Item Delete Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while removing the item'
    ]);
}
