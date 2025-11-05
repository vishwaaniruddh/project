<?php
// Suppress warnings to ensure clean JSON output
error_reporting(E_ERROR | E_PARSE);

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

$id = $_GET['id'] ?? null;
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Site ID is required']);
    exit;
}

$vendorId = Auth::getVendorId();
$siteModel = new Site();
$delegationModel = new SiteDelegation();

try {
    // First, get all vendor delegations to find the site
    $vendorDelegations = $delegationModel->getVendorDelegations($vendorId, 'active');
    $completedDelegations = $delegationModel->getVendorDelegations($vendorId, 'completed');
    $allDelegations = array_merge($vendorDelegations, $completedDelegations);
    
    // Find the site by ID in delegations
    $targetSite = null;
    $delegation = null;
    
    foreach ($allDelegations as $delegatedSite) {
        if ($delegatedSite['id'] == $id) {
            $targetSite = $delegatedSite;
            
            // Create delegation info from the delegated site data
            $delegation = [
                'id' => $delegatedSite['delegation_id'] ?? null,
                'vendor_id' => $vendorId,
                'delegation_date' => $delegatedSite['delegation_date'] ?? null,
                'status' => $delegatedSite['delegation_status'] ?? 'active',
                'notes' => $delegatedSite['notes'] ?? null
            ];
            
            break;
        }
    }
    
    if (!$targetSite) {
        echo json_encode(['success' => false, 'message' => 'Site not found or not assigned to you']);
        exit;
    }
    
    // Get additional site details with relations
    $siteWithRelations = $siteModel->findWithRelations($targetSite['site_id']);
    if ($siteWithRelations) {
        $targetSite = array_merge($targetSite, $siteWithRelations);
    }
    
    $site = $targetSite;
    
    // Combine site and delegation data
    $siteData = array_merge($site, [
        'delegation_id' => $delegation ? ($delegation['id'] ?? null) : null,
        'delegation_date' => $delegation ? ($delegation['delegation_date'] ?? null) : null,
        'delegation_status' => $delegation ? ($delegation['status'] ?? null) : null,
        'notes' => $delegation ? ($delegation['notes'] ?? null) : null
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