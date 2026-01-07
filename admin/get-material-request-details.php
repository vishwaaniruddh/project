<?php
/**
 * API endpoint to get material request details
 * Returns JSON response with request details including items
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/MaterialRequest.php';

header('Content-Type: application/json');

try {
    $requestId = $_GET['id'] ?? null;
    
    if (!$requestId || !is_numeric($requestId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request ID'
        ]);
        exit;
    }
    
    $materialRequestModel = new MaterialRequest();
    $request = $materialRequestModel->findWithDetails($requestId);
    
    if (!$request) {
        echo json_encode([
            'success' => false,
            'message' => 'Request not found'
        ]);
        exit;
    }
    
    // Parse items JSON and fetch item names from boq_items table
    $request['items_data'] = [];
    if (!empty($request['items'])) {
        $items = json_decode($request['items'], true);
        if (is_array($items)) {
            // Get database connection to fetch item names
            $db = Database::getInstance()->getConnection();
            
            foreach ($items as &$item) {
                // If item_name is missing or empty, fetch from boq_items table
                if (empty($item['item_name']) && !empty($item['boq_item_id'])) {
                    $stmt = $db->prepare("SELECT item_name, item_code, unit FROM boq_items WHERE id = ?");
                    $stmt->execute([$item['boq_item_id']]);
                    $boqItem = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($boqItem) {
                        $item['item_name'] = $boqItem['item_name'];
                        if (empty($item['item_code'])) {
                            $item['item_code'] = $boqItem['item_code'];
                        }
                        if (empty($item['unit'])) {
                            $item['unit'] = $boqItem['unit'];
                        }
                    }
                }
            }
            
            $request['items_data'] = $items;
        }
    }
    
    echo json_encode([
        'success' => true,
        'request' => $request
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching request: ' . $e->getMessage()
    ]);
}
?>
