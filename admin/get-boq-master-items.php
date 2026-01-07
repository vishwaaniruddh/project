<?php
/**
 * API endpoint to get items for a specific BOQ Master
 * Returns JSON response with items associated with the selected BOQ Master
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/BoqMasterItem.php';

header('Content-Type: application/json');

try {
    $boqMasterId = $_GET['boq_master_id'] ?? null;
    
    if (!$boqMasterId || !is_numeric($boqMasterId)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid BOQ Master ID',
            'items' => []
        ]);
        exit;
    }
    
    $boqMasterItemModel = new BoqMasterItem();
    $items = $boqMasterItemModel->getActiveByBoqMaster($boqMasterId);
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching items: ' . $e->getMessage(),
        'items' => []
    ]);
}
?>
