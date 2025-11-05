<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Site.php';
require_once __DIR__ . '/../../models/SiteDelegation.php';

// Set JSON header
header('Content-Type: application/json');

// Require vendor authentication
try {
    Auth::requireVendor();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$siteId = $_GET['site_id'] ?? null;
if (!$siteId) {
    echo json_encode(['success' => false, 'message' => 'Site ID is required']);
    exit;
}

$vendorId = Auth::getVendorId();
$siteModel = new Site();
$delegationModel = new SiteDelegation();

try {
    // Get site details with relations
    $site = $siteModel->findWithRelations($siteId);
    if (!$site) {
        echo json_encode(['success' => false, 'message' => 'Site not found']);
        exit;
    }
    
    // Verify this site is delegated to current vendor
    $delegation = $delegationModel->getActiveDelegation($siteId);
    if (!$delegation || $delegation['vendor_id'] != $vendorId) {
        echo json_encode(['success' => false, 'message' => 'Access denied - site not assigned to you']);
        exit;
    }
    
    // Combine site and delegation data
    $siteData = array_merge($site, [
        'delegation_id' => $delegation['id'],
        'delegation_date' => $delegation['delegation_date'],
        'delegation_status' => $delegation['status'],
        'notes' => $delegation['notes']
    ]);
    
    echo json_encode([
        'success' => true,
        'site' => $siteData
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching site details: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading site details'
    ]);
}
?>