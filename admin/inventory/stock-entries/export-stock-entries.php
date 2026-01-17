<?php
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../models/Inventory.php';
require_once __DIR__ . '/../../../models/BoqItem.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

// Increase memory and execution time for export
ini_set('memory_limit', '256M');
set_time_limit(300);

$inventoryModel = new Inventory();
$boqModel = new BoqItem();

// Handle filters (same as index page)
$search = $_GET['search'] ?? '';
$boqItemId = $_GET['boq_item_id'] ?? null;
$location = $_GET['location'] ?? '';

// Build WHERE clause
$whereClause = '';
$params = [];
$conditions = [];

if ($boqItemId) {
    $conditions[] = "ist.boq_item_id = ?";
    $params[] = $boqItemId;
}

if (!empty($search)) {
    $conditions[] = "(bi.item_name LIKE ? OR bi.item_code LIKE ? OR ist.batch_number LIKE ? OR ist.serial_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($location)) {
    $conditions[] = "ist.location_type = ?";
    $params[] = $location;
}

if (!empty($conditions)) {
    $whereClause = "WHERE " . implode(" AND ", $conditions);
}

$db = Database::getInstance()->getConnection();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=stock_entries_' . date('Y-m-d_His') . '.csv');

// Create output stream
$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 support
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add CSV headers
fputcsv($output, [
    'Stock ID',
    'Item Name',
    'Item Code',
    'Category',
    'Batch Number',
    'Serial Number',
    'Unit Cost',
    'Unit',
    'Location Type',
    'Location Name',
    'Item Status',
    'Quality Status',
    'Supplier Name',
    'Purchase Date',
    'Expiry Date',
    'Warranty Period (months)',
    'Purchase Order Number',
    'Invoice Number',
    'Notes',
    'Created At',
    'Updated At',
    'Dispatched At',
    'Delivered At'
]);

// Process data in chunks to avoid memory issues
$chunkSize = 500;
$offset = 0;
$hasMoreData = true;

while ($hasMoreData) {
    // Get chunk of data
    $sql = "SELECT ist.*, bi.item_name, bi.item_code, bi.unit, bi.category
            FROM inventory_stock ist
            JOIN boq_items bi ON CAST(ist.boq_item_id AS CHAR) = CAST(bi.id AS CHAR)
            $whereClause
            ORDER BY bi.item_name, ist.batch_number, ist.serial_number
            LIMIT $chunkSize OFFSET $offset";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $stockEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check if we have more data
    $hasMoreData = count($stockEntries) >= $chunkSize;
    
    // Write chunk to CSV
    foreach ($stockEntries as $entry) {
        fputcsv($output, [
            $entry['id'],
            $entry['item_name'],
            $entry['item_code'],
            $entry['category'],
            $entry['batch_number'] ?? '',
            $entry['serial_number'] ?? '',
            $entry['unit_cost'],
            $entry['unit'],
            $entry['location_type'],
            $entry['location_name'] ?? '',
            $entry['item_status'],
            $entry['quality_status'],
            $entry['supplier_name'] ?? '',
            $entry['purchase_date'] ? date('Y-m-d', strtotime($entry['purchase_date'])) : '',
            $entry['expiry_date'] ? date('Y-m-d', strtotime($entry['expiry_date'])) : '',
            $entry['warranty_period'] ?? '',
            $entry['purchase_order_number'] ?? '',
            $entry['invoice_number'] ?? '',
            $entry['notes'] ?? '',
            $entry['created_at'] ? date('Y-m-d H:i:s', strtotime($entry['created_at'])) : '',
            $entry['updated_at'] ? date('Y-m-d H:i:s', strtotime($entry['updated_at'])) : '',
            $entry['dispatched_at'] ? date('Y-m-d H:i:s', strtotime($entry['dispatched_at'])) : '',
            $entry['delivered_at'] ? date('Y-m-d H:i:s', strtotime($entry['delivered_at'])) : ''
        ]);
    }
    
    // Free memory
    unset($stockEntries);
    
    $offset += $chunkSize;
}

fclose($output);
exit;
?>
