<?php
/**
 * AJAX endpoint to search BOQ items for association with a BOQ master
 * Excludes items already associated with the current BOQ
 * 
 * Requirements: 2.2
 */

require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../config/database.php';

// Set JSON response header
header('Content-Type: application/json');

// Require admin authentication
if (!Auth::isLoggedIn() || !Auth::isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Get parameters
    $boqId = (int)($_GET['boq_id'] ?? 0);
    $query = trim($_GET['q'] ?? '');
    $limit = (int)($_GET['limit'] ?? 20);
    
    // Validate BOQ ID
    if (!$boqId) {
        echo json_encode(['success' => false, 'message' => 'BOQ ID is required']);
        exit;
    }
    
    // Validate query
    if (strlen($query) < 2) {
        echo json_encode(['success' => true, 'items' => []]);
        exit;
    }
    
    // Search for items not already associated with this BOQ
    $searchTerm = "%{$query}%";
    
    $sql = "SELECT bi.id, bi.item_name, bi.item_code, bi.unit, bi.category, bi.description
            FROM boq_items bi
            WHERE bi.status = 'active'
            AND (bi.item_name LIKE ? OR bi.item_code LIKE ?)
            AND bi.id NOT IN (
                SELECT boq_item_id FROM boq_master_items 
                WHERE boq_master_id = ?
            )
            ORDER BY bi.item_name ASC
            LIMIT ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$searchTerm, $searchTerm, $boqId, $limit]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'count' => count($items)
    ]);
    
} catch (Exception $e) {
    error_log('BOQ Item Search Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while searching items'
    ]);
}
