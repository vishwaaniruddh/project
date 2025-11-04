<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Site.php';
require_once __DIR__ . '/../../models/SiteSurvey.php';
require_once __DIR__ . '/../../models/MaterialRequest.php';
require_once __DIR__ . '/../../models/Installation.php';
require_once __DIR__ . '/../../models/Vendor.php';
require_once __DIR__ . '/../../models/User.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$reportType = $_POST['report_type'] ?? '';

if (!$reportType) {
    die('Invalid report type');
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $reportType . '_report_' . date('Y-m-d_H-i-s') . '.csv"');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

// Create output stream
$output = fopen('php://output', 'w');

try {
    switch ($reportType) {
        case 'sites':
            exportSitesReport($output);
            break;
        case 'surveys':
            exportSurveysReport($output);
            break;
        case 'materials':
            exportMaterialsReport($output);
            break;
        case 'installations':
            exportInstallationsReport($output);
            break;
        default:
            die('Unknown report type');
    }
} catch (Exception $e) {
    fputcsv($output, ['Error', $e->getMessage()]);
}

fclose($output);
exit;

function exportSitesReport($output) {
    $siteModel = new Site();
    
    // CSV Headers
    $headers = [
        'Site ID',
        'Location',
        'Address',
        'City',
        'State', 
        'Country',
        'Customer',
        'Bank',
        'Vendor',
        'Status',
        'Survey Status',
        'Created Date',
        'Updated Date'
    ];
    fputcsv($output, $headers);
    
    // Get all sites with details
    $result = $siteModel->getAllWithPagination(1, 10000); // Get all sites
    $sites = $result['sites'];
    
    foreach ($sites as $site) {
        $row = [
            $site['site_id'] ?? '',
            $site['location'] ?? '',
            $site['address'] ?? '',
            $site['city_name'] ?? '',
            $site['state_name'] ?? '',
            $site['country_name'] ?? '',
            $site['customer_name'] ?? '',
            $site['bank_name'] ?? '',
            $site['vendor_name'] ?? '',
            $site['status'] ?? '',
            $site['actual_survey_status'] ?? 'No Survey',
            $site['created_at'] ?? '',
            $site['updated_at'] ?? ''
        ];
        fputcsv($output, $row);
    }
}

function exportSurveysReport($output) {
    $surveyModel = new SiteSurvey();
    
    // CSV Headers
    $headers = [
        'Survey ID',
        'Site ID',
        'Site Location',
        'Vendor Name',
        'Survey Status',
        'Submitted Date',
        'Approved Date',
        'Approved By',
        'Survey Data',
        'Notes',
        'Created Date'
    ];
    fputcsv($output, $headers);
    
    // Get all surveys with details
    $surveys = $surveyModel->getAllWithDetails();
    
    foreach ($surveys as $survey) {
        // Parse survey data if it's JSON
        $surveyData = '';
        if ($survey['survey_data']) {
            $data = json_decode($survey['survey_data'], true);
            if ($data) {
                $surveyData = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
        }
        
        $row = [
            $survey['id'] ?? '',
            $survey['site_id'] ?? '',
            $survey['location'] ?? '',
            $survey['vendor_name'] ?? '',
            $survey['survey_status'] ?? '',
            $survey['submitted_date'] ?? '',
            $survey['approved_date'] ?? '',
            $survey['approved_by_name'] ?? '',
            $surveyData,
            $survey['notes'] ?? '',
            $survey['created_at'] ?? ''
        ];
        fputcsv($output, $row);
    }
}

function exportMaterialsReport($output) {
    $materialModel = new MaterialRequest();
    
    // CSV Headers
    $headers = [
        'Request ID',
        'Site ID',
        'Site Location',
        'Vendor Name',
        'Request Status',
        'Request Date',
        'Required Date',
        'Total Items',
        'Total Quantity',
        'Request Notes',
        'Dispatch Status',
        'Dispatch Date',
        'Courier Name',
        'Tracking Number',
        'Expected Delivery',
        'Actual Delivery',
        'Created Date'
    ];
    fputcsv($output, $headers);
    
    // Get all material requests with details
    $requests = $materialModel->getAllWithDetails();
    
    foreach ($requests as $request) {
        // Calculate totals from items JSON
        $totalItems = 0;
        $totalQuantity = 0;
        
        if ($request['items']) {
            $items = json_decode($request['items'], true);
            if ($items && is_array($items)) {
                $totalItems = count($items);
                foreach ($items as $item) {
                    if (isset($item['quantity']) && is_numeric($item['quantity'])) {
                        $totalQuantity += (int)$item['quantity'];
                    }
                }
            }
        }
        
        $row = [
            $request['id'] ?? '',
            $request['site_id'] ?? '',
            $request['location'] ?? '',
            $request['vendor_name'] ?? '',
            $request['status'] ?? '',
            $request['request_date'] ?? '',
            $request['required_date'] ?? '',
            $totalItems,
            $totalQuantity,
            $request['request_notes'] ?? '',
            $request['dispatch_status'] ?? '',
            $request['dispatch_date'] ?? '',
            $request['courier_name'] ?? '',
            $request['tracking_number'] ?? '',
            $request['expected_delivery_date'] ?? '',
            $request['actual_delivery_date'] ?? '',
            $request['created_date'] ?? ''
        ];
        fputcsv($output, $row);
    }
}

function exportInstallationsReport($output) {
    $installationModel = new Installation();
    
    // CSV Headers
    $headers = [
        'Installation ID',
        'Site ID',
        'Site Location',
        'Vendor Name',
        'Installation Status',
        'Progress Percentage',
        'Start Date',
        'Expected Completion',
        'Actual Completion',
        'Installation Notes',
        'Material Usage',
        'Files Uploaded',
        'Created Date',
        'Updated Date'
    ];
    fputcsv($output, $headers);
    
    // Get all installations with details
    $installations = $installationModel->getAllWithDetails();
    
    foreach ($installations as $installation) {
        // Count files if available
        $filesCount = 0;
        if ($installation['files']) {
            $files = json_decode($installation['files'], true);
            if ($files && is_array($files)) {
                $filesCount = count($files);
            }
        }
        
        // Format material usage
        $materialUsage = '';
        if ($installation['material_usage']) {
            $usage = json_decode($installation['material_usage'], true);
            if ($usage && is_array($usage)) {
                $materialUsage = count($usage) . ' items used';
            }
        }
        
        $row = [
            $installation['id'] ?? '',
            $installation['site_id'] ?? '',
            $installation['location'] ?? '',
            $installation['vendor_name'] ?? '',
            $installation['status'] ?? '',
            $installation['progress_percentage'] ?? '0',
            $installation['start_date'] ?? '',
            $installation['expected_completion_date'] ?? '',
            $installation['actual_completion_date'] ?? '',
            $installation['notes'] ?? '',
            $materialUsage,
            $filesCount . ' files',
            $installation['created_at'] ?? '',
            $installation['updated_at'] ?? ''
        ];
        fputcsv($output, $row);
    }
}
?>