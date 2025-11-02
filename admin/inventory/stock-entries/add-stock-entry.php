<?php
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../models/Inventory.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $inventoryModel = new Inventory();
    
    // Validate required fields
    $requiredFields = ['boq_item_id', 'current_stock', 'unit_cost'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    // Prepare stock entry data
    $stockData = [
        'boq_item_id' => intval($_POST['boq_item_id']),
        'current_stock' => floatval($_POST['current_stock']),
        'unit_cost' => floatval($_POST['unit_cost']),
        'batch_number' => $_POST['batch_number'] ?? null,
        'serial_number' => $_POST['serial_number'] ?? null,
        'location_type' => $_POST['location_type'] ?? 'warehouse',
        'location_name' => $_POST['location_name'] ?? null,
        'supplier_name' => $_POST['supplier_name'] ?? null,
        'purchase_date' => $_POST['purchase_date'] ?? null,
        'expiry_date' => $_POST['expiry_date'] ?? null,
        'quality_status' => $_POST['quality_status'] ?? 'good',
        'warranty_period' => $_POST['warranty_period'] ?? null,
        'notes' => $_POST['notes'] ?? null
    ];
    
    // Validate values
    if ($stockData['current_stock'] <= 0) {
        throw new Exception('Quantity must be greater than 0');
    }
    
    if ($stockData['unit_cost'] < 0) {
        throw new Exception('Unit cost cannot be negative');
    }
    
    // Check if serial number already exists for this BOQ item
    if (!empty($stockData['serial_number'])) {
        $db = Database::getInstance()->getConnection();
        $checkSql = "SELECT id FROM inventory_stock WHERE boq_item_id = ? AND serial_number = ?";
        $checkStmt = $db->prepare($checkSql);
        $checkStmt->execute([$stockData['boq_item_id'], $stockData['serial_number']]);
        
        if ($checkStmt->fetch()) {
            throw new Exception('Serial number already exists for this item');
        }
    }
    
    // Add the stock entry
    $entryId = $inventoryModel->addIndividualStockEntry($stockData);
    
    if (!$entryId) {
        throw new Exception('Failed to add stock entry');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Stock entry added successfully',
        'entry_id' => $entryId
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>