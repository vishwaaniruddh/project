<?php
require_once __DIR__ . '/../../config/auth.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="sites_upload_template.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

// Create CSV output
$output = fopen('php://output', 'w');

// CSV Headers - Updated with new fields and removed bank
$headers = [
    'Site ID',
    'Store ID', 
    'Location',
    'Country',
    'State',
    'City',
    'Zone',
    'Pincode',
    'Branch',
    'Customer',
    'Contact Person Name',
    'Contact Person Number',
    'Contact Person Email',
    'PO Number',
    'PO Date',
    'Remarks'
];

// Write headers
fputcsv($output, $headers);

// Write sample data row
$sampleData = [
    'SITE001',
    'STORE001',
    'Main Street, Commercial Complex',
    'India',
    'Maharashtra',
    'Mumbai',
    'West Zone',
    '400001',
    'Andheri Branch',
    'Croma',
    'John Doe',
    '+91-9876543210',
    'john.doe@example.com',
    'PO2024001',
    '2024-01-15',
    'Sample site for installation'
];

fputcsv($output, $sampleData);

// Close output
fclose($output);
exit;
?>