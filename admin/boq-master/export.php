<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/BoqMaster.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

try {
    $boqMasterModel = new BoqMaster();
    
    // Get all BOQ masters with item counts
    $result = $boqMasterModel->getAllWithItemCount(1, 1000); // Get up to 1000 records
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="boq_masters_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create file pointer
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'BOQ ID',
        'BOQ Name', 
        'Description',
        'Item Count',
        'Serial Number Required',
        'Status',
        'Created Date',
        'Created By'
    ]);
    
    // Add data rows
    foreach ($result['records'] as $boqMaster) {
        fputcsv($output, [
            $boqMaster['boq_id'],
            $boqMaster['boq_name'],
            $boqMaster['description'] ?? '',
            $boqMaster['item_count'],
            $boqMaster['is_serial_number_required'] ? 'Yes' : 'No',
            ucfirst($boqMaster['status']),
            date('Y-m-d H:i:s', strtotime($boqMaster['created_at'])),
            $boqMaster['created_by_name'] ?? ''
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    error_log("Error exporting BOQ masters: " . $e->getMessage());
    
    // Redirect back with error message
    header('Location: index.php?error=' . urlencode('Failed to export BOQ masters'));
    exit;
}
?>