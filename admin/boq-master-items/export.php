<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/BoqMaster.php';
require_once __DIR__ . '/../../models/BoqMasterItem.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$boqMasterModel = new BoqMaster();
$boqMasterItemModel = new BoqMasterItem();

$boqId = (int)($_GET['boq_id'] ?? 0);

// Validate BOQ ID
if (!$boqId) {
    header('Location: ../boq-master/index.php?error=invalid_id');
    exit;
}

// Get BOQ master details
$boqMaster = $boqMasterModel->find($boqId);
if (!$boqMaster) {
    header('Location: ../boq-master/index.php?error=not_found');
    exit;
}

// Get BOQ master items
$items = $boqMasterItemModel->getByBoqMaster($boqId);

// Set headers for CSV download
$filename = 'boq_items_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $boqMaster['boq_name']) . '_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write BOQ Master information header
fputcsv($output, ['BOQ Master Export']);
fputcsv($output, ['BOQ Name:', $boqMaster['boq_name']]);
fputcsv($output, ['BOQ ID:', $boqMaster['boq_id']]);
fputcsv($output, ['Status:', ucfirst($boqMaster['status'])]);
fputcsv($output, ['Serial Number Required:', $boqMaster['is_serial_number_required'] ? 'Yes' : 'No']);
fputcsv($output, ['Export Date:', date('Y-m-d H:i:s')]);
fputcsv($output, ['Total Items:', count($items)]);
fputcsv($output, []); // Empty row

// Write CSV headers
$headers = [
    'Item Name',
    'Item Code',
    'Category',
    'Unit',
    'Quantity',
    'Sort Order',
    'Serial Number Required',
    'Status',
    'Description'
];
fputcsv($output, $headers);

// Write data rows
foreach ($items as $item) {
    $row = [
        $item['item_name'],
        $item['item_code'],
        $item['category'],
        $item['unit'],
        number_format($item['default_quantity'], 2),
        $item['sort_order'],
        $item['need_serial_number'] ? 'Yes' : 'No',
        ucfirst($item['status']),
        $item['item_description'] ?? ''
    ];
    fputcsv($output, $row);
}

// Close output stream
fclose($output);
exit;
?>