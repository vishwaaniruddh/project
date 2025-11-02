<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/MaterialRequest.php';

// Require vendor authentication
Auth::requireRole(VENDOR_ROLE);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $currentUser = Auth::getCurrentUser();
    $vendorId = $currentUser['vendor_id'];
    
    // Validate required fields
    if (empty($_POST['dispatch_id']) || empty($_POST['request_id'])) {
        throw new Exception('Dispatch ID and Request ID are required');
    }
    
    $dispatchId = intval($_POST['dispatch_id']);
    $requestId = intval($_POST['request_id']);
    
    $inventoryModel = new Inventory();
    $materialRequestModel = new MaterialRequest();
    
    // Get dispatch details
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM inventory_dispatches WHERE id = ? AND vendor_id = ?");
    $stmt->execute([$dispatchId, $vendorId]);
    $dispatch = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dispatch) {
        throw new Exception('Dispatch not found or access denied');
    }
    
    // Verify the dispatch is in 'dispatched' status
    if ($dispatch['dispatch_status'] !== 'dispatched') {
        throw new Exception('This request is not available for acceptance');
    }
    
    // Get dispatch items to create confirmations
    $dispatchItems = $inventoryModel->getDispatchItems($dispatchId);
    
    if (empty($dispatchItems)) {
        throw new Exception('No items found in this dispatch');
    }
    
    // Create item confirmations for all dispatched items
    $itemConfirmations = [];
    foreach ($dispatchItems as $item) {
        $itemConfirmations[] = [
            'boq_item_id' => $item['boq_item_id'],
            'received_quantity' => $item['quantity_dispatched'],
            'condition' => 'good', // Default to good condition
            'notes' => 'Accepted via bulk request acceptance'
        ];
    }
    
    // Prepare delivery confirmation data
    $deliveryData = [
        'delivery_date' => date('Y-m-d'),
        'delivery_time' => date('H:i:s'),
        'received_by' => $currentUser['username'],
        'received_by_phone' => $currentUser['phone'] ?? '',
        'delivery_address' => $dispatch['delivery_address'],
        'delivery_notes' => 'Request accepted by vendor on ' . date('Y-m-d H:i:s'),
        'lr_copy_path' => null,
        'additional_documents' => null,
        'item_confirmations' => json_encode($itemConfirmations),
        'confirmed_by' => $currentUser['id'],
        'confirmation_date' => date('Y-m-d H:i:s')
    ];
    
    // Update dispatch status to 'delivered'
    $result = $inventoryModel->confirmDelivery($dispatchId, $deliveryData);
    
    if (!$result) {
        throw new Exception('Failed to confirm delivery');
    }
    
    // Update material request status to 'delivered'
    $materialRequestModel->updateStatus(
        $requestId,
        'delivered',
        $currentUser['id'],
        date('Y-m-d H:i:s')
    );
    
    // Create tracking entries for delivered items
    foreach ($dispatchItems as $item) {
        // Check if there are individual records
        $individualRecords = json_decode($item['serial_numbers'] ?? '[]', true);
        
        if (!empty($individualRecords)) {
            // Create separate tracking entries for each individual record
            foreach ($individualRecords as $record) {
                $inventoryModel->createTrackingEntry([
                    'boq_item_id' => $item['boq_item_id'],
                    'serial_number' => $record['serial_number'] ?? null,
                    'batch_number' => $record['batch_number'] ?? $item['batch_number'],
                    'quantity' => $record['quantity'] ?? 1,
                    'current_location_type' => 'vendor_site',
                    'current_location_name' => 'Vendor Site - Site ID ' . $dispatch['site_id'],
                    'site_id' => $dispatch['site_id'],
                    'vendor_id' => $vendorId,
                    'dispatch_id' => $dispatchId,
                    'status' => 'delivered',
                    'movement_remarks' => 'Delivered and accepted by vendor'
                ]);
            }
        } else {
            // Create single tracking entry for cumulative record
            $inventoryModel->createTrackingEntry([
                'boq_item_id' => $item['boq_item_id'],
                'batch_number' => $item['batch_number'],
                'quantity' => $item['quantity_dispatched'],
                'current_location_type' => 'vendor_site',
                'current_location_name' => 'Vendor Site - Site ID ' . $dispatch['site_id'],
                'site_id' => $dispatch['site_id'],
                'vendor_id' => $vendorId,
                'dispatch_id' => $dispatchId,
                'status' => 'delivered',
                'movement_remarks' => 'Delivered and accepted by vendor'
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Request accepted successfully',
        'request_id' => $requestId,
        'dispatch_id' => $dispatchId
    ]);
    
} catch (Exception $e) {
    error_log('Request acceptance error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>