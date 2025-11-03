<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/MaterialRequest.php';
require_once __DIR__ . '/../../models/Inventory.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $materialRequestModel = new MaterialRequest();
    $inventoryModel = new Inventory();
    $currentUser = Auth::getCurrentUser();
    
    // Validate required fields
    $requiredFields = ['material_request_id', 'contact_person_name', 'contact_person_phone', 'dispatch_date', 'delivery_address'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Field '$field' is required");
        }
    }
    
    $materialRequestId = intval($_POST['material_request_id']);
    
    // Get material request details
    $materialRequest = $materialRequestModel->findWithDetails($materialRequestId);
    if (!$materialRequest || $materialRequest['status'] !== 'approved') {
        throw new Exception('Material request not found or not approved');
    }
    
    // Validate items
    if (empty($_POST['items']) || !is_array($_POST['items'])) {
        throw new Exception('No items to dispatch');
    }
    
    // Parse requested items from material request to validate stock
    $requestedItems = json_decode($materialRequest['items'], true) ?: [];
    $stockAvailability = $inventoryModel->checkStockAvailabilityForItems($requestedItems);
    
    // Check if any items are out of stock
    $stockIssues = [];
    foreach ($stockAvailability as $boqItemId => $stock) {
        if (!$stock['is_sufficient']) {
            $stockIssues[] = $stock['item_name'] . ' (Available: ' . $stock['available_quantity'] . ', Required: ' . $stock['requested_quantity'] . ')';
        }
    }
    
    if (!empty($stockIssues)) {
        throw new Exception('Insufficient stock for items: ' . implode(', ', $stockIssues));
    }
    
    // Generate dispatch number
    $dispatchNumber = $inventoryModel->generateDispatchNumber();
    
    // Prepare dispatch data
    $dispatchData = [
        'dispatch_number' => $dispatchNumber,
        'dispatch_date' => $_POST['dispatch_date'],
        'material_request_id' => $materialRequestId,
        'site_id' => $materialRequest['site_id'],
        'vendor_id' => $materialRequest['vendor_id'],
        'contact_person_name' => $_POST['contact_person_name'],
        'contact_person_phone' => $_POST['contact_person_phone'],
        'delivery_address' => $_POST['delivery_address'],
        'courier_name' => $_POST['courier_name'] ?? null,
        'tracking_number' => $_POST['pod_number'] ?? null,
        'expected_delivery_date' => $_POST['expected_delivery_date'] ?? null,
        'dispatch_status' => 'dispatched',
        'dispatched_by' => $currentUser['id'],
        'delivery_remarks' => $_POST['dispatch_remarks'] ?? null
    ];
    
    // Create dispatch record
    $dispatchId = $inventoryModel->createDispatch($dispatchData);
    
    if (!$dispatchId) {
        throw new Exception('Failed to create dispatch record');
    }
    
    // Process dispatch items
    $dispatchItems = [];
    $totalItems = 0;
    $totalValue = 0;
    
    foreach ($_POST['items'] as $itemData) {
        if (empty($itemData['boq_item_id']) || empty($itemData['dispatch_quantity'])) {
            continue;
        }
        
        $boqItemId = intval($itemData['boq_item_id']);
        $dispatchQuantity = floatval($itemData['dispatch_quantity']);
        $recordType = $itemData['record_type'] ?? 'cumulative';
        
        if ($dispatchQuantity <= 0) {
            continue;
        }
        
        // Get unit cost from inventory stock (average cost)
        $stockInfo = $inventoryModel->getStockOverview('', '', false);
        $unitCost = 0;
        foreach ($stockInfo as $stock) {
            if ($stock['boq_item_id'] == $boqItemId) {
                $unitCost = $stock['unit_cost'] ?? 0;
                break;
            }
        }
        
        // Handle individual records
        $individualRecords = [];
        if (!empty($itemData['individual']) && is_array($itemData['individual'])) {
            foreach ($itemData['individual'] as $individual) {
                $individualRecords[] = [
                    'serial_number' => $individual['serial_number'] ?? null,
                    'batch_number' => $individual['batch_number'] ?? null,
                    'quantity' => floatval($individual['quantity'] ?? 1)
                ];
            }
        }
        
        $dispatchItems[] = [
            'boq_item_id' => $boqItemId,
            'quantity_dispatched' => $dispatchQuantity,
            'unit_cost' => $unitCost,
            'batch_number' => $itemData['batch_number'] ?? null,
            'individual_records' => !empty($individualRecords) ? json_encode($individualRecords) : null,
            'item_condition' => 'new',
            'remarks' => $itemData['dispatch_notes'] ?? null
        ];
        
        $totalItems++;
        $totalValue += $dispatchQuantity * $unitCost;
    }
    
    if (empty($dispatchItems)) {
        throw new Exception('No valid items to dispatch');
    }
    
    // Add items to dispatch
    $result = $inventoryModel->addDispatchItems($dispatchId, $dispatchItems);
    
    if (!$result) {
        throw new Exception('Failed to add items to dispatch');
    }
    
    // Update dispatch totals (will be handled by addDispatchItems method)
    
    // Update material request status to dispatched
    $materialRequestModel->updateStatus(
        $materialRequestId, 
        'dispatched', 
        $currentUser['id'], 
        date('Y-m-d H:i:s')
    );
    
    // Create tracking entries for dispatched items
    foreach ($dispatchItems as $item) {
        if (!empty($item['individual_records'])) {
            // Create separate tracking entries for each individual record
            $individualRecords = json_decode($item['individual_records'], true);
            foreach ($individualRecords as $record) {
                $inventoryModel->createTrackingEntry([
                    'boq_item_id' => $item['boq_item_id'],
                    'serial_number' => $record['serial_number'] ?? null,
                    'batch_number' => $record['batch_number'] ?? $item['batch_number'],
                    'quantity' => $record['quantity'],
                    'current_location_type' => 'in_transit',
                    'current_location_name' => 'In Transit to ' . $materialRequest['site_code'],
                    'site_id' => $materialRequest['site_id'],
                    'vendor_id' => $materialRequest['vendor_id'],
                    'dispatch_id' => $dispatchId,
                    'status' => 'dispatched',
                    'movement_remarks' => 'Dispatched via ' . ($dispatchData['courier_name'] ?: 'courier')
                ]);
            }
        } else {
            // Create single tracking entry for cumulative record
            $inventoryModel->createTrackingEntry([
                'boq_item_id' => $item['boq_item_id'],
                'batch_number' => $item['batch_number'],
                'quantity' => $item['quantity_dispatched'],
                'current_location_type' => 'in_transit',
                'current_location_name' => 'In Transit to ' . $materialRequest['site_code'],
                'site_id' => $materialRequest['site_id'],
                'vendor_id' => $materialRequest['vendor_id'],
                'dispatch_id' => $dispatchId,
                'status' => 'dispatched',
                'movement_remarks' => 'Dispatched via ' . ($dispatchData['courier_name'] ?: 'courier')
            ]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Material dispatch processed successfully',
        'dispatch_id' => $dispatchId,
        'dispatch_number' => $dispatchNumber
    ]);
    
} catch (Exception $e) {
    error_log('Material dispatch processing error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}


?>