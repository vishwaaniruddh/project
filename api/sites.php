<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/SitesController.php';
require_once __DIR__ . '/../models/Site.php';

header('Content-Type: application/json');

// Require authentication
Auth::requireRole(ADMIN_ROLE);

$controller = new SitesController();
$siteModel = new Site();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

try {
    switch ($action) {
        case 'stats':
            // Get site statistics for dashboard
            $stats = getSiteStats();
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'list':
            // Get paginated sites list
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $search = $_GET['search'] ?? '';
            
            $filters = [
                'city' => $_GET['city'] ?? '',
                'state' => $_GET['state'] ?? '',
                'activity_status' => $_GET['activity_status'] ?? '',
                'vendor' => $_GET['vendor'] ?? '',
                'survey_status' => $_GET['survey_status'] ?? ''
            ];
            
            $result = $siteModel->getAllWithPagination($page, $limit, $search, $filters);
            
            // Get filter options
            $cities = $siteModel->getUniqueValues('city');
            $states = $siteModel->getUniqueValues('state');
            $activityStatuses = $siteModel->getUniqueValues('activity_status');
            $vendors = $siteModel->getUniqueValues('vendor');
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'sites' => $result['sites'],
                    'pagination' => [
                        'current_page' => $result['page'],
                        'total_pages' => $result['pages'],
                        'total_records' => $result['total'],
                        'limit' => $result['limit']
                    ],
                    'filter_options' => [
                        'cities' => $cities,
                        'states' => $states,
                        'activity_statuses' => $activityStatuses,
                        'vendors' => $vendors
                    ]
                ]
            ]);
            break;
            
        case 'view':
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'Site ID required']);
                exit;
            }
            $controller->show($id);
            break;
            
        case 'create':
            if ($method !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'POST method required']);
                exit;
            }
            $controller->store();
            break;
            
        case 'update':
            if ($method !== 'POST' || !$id) {
                echo json_encode(['success' => false, 'message' => 'POST method and ID required']);
                exit;
            }
            $controller->update($id);
            break;
            
        case 'delete':
            if ($method !== 'POST' || !$id) {
                echo json_encode(['success' => false, 'message' => 'POST method and ID required']);
                exit;
            }
            $controller->delete($id);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    error_log("Sites API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}

function getSiteStats() {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Total sites
        $stmt = $db->query("SELECT COUNT(*) FROM sites");
        $totalSites = $stmt->fetchColumn();
        
        // Survey pending - sites without approved/completed surveys
        $surveyPending = 0;
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM sites WHERE id NOT IN (SELECT DISTINCT site_id FROM site_surveys WHERE survey_status IN ('submitted', 'approved', 'completed'))");
            $surveyPending = $stmt->fetchColumn();
        } catch (Exception $e) {
            // site_surveys table might not exist
            $surveyPending = $totalSites;
        }
        
        // Survey completed/approved
        $surveyCompleted = 0;
        try {
            $stmt = $db->query("SELECT COUNT(DISTINCT site_id) FROM site_surveys WHERE survey_status IN ('approved', 'completed')");
            $surveyCompleted = $stmt->fetchColumn();
        } catch (Exception $e) {
            // site_surveys table might not exist
        }
        
        // Installation pending
        $stmt = $db->query("SELECT COUNT(*) FROM sites WHERE installation_status = 0 OR installation_status IS NULL");
        $installationPending = $stmt->fetchColumn();
        
        // Installation done
        $stmt = $db->query("SELECT COUNT(*) FROM sites WHERE installation_status = 1");
        $installationDone = $stmt->fetchColumn();
        
        // Sites with vendors assigned
        $stmt = $db->query("SELECT COUNT(*) FROM sites WHERE vendor IS NOT NULL AND vendor != ''");
        $vendorAssigned = $stmt->fetchColumn();
        
        // Delegated sites
        $delegatedSites = 0;
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM sites WHERE is_delegate = 1");
            $delegatedSites = $stmt->fetchColumn();
        } catch (Exception $e) {
            // is_delegate column might not exist
        }
        
        // Material requests pending
        $materialPending = 0;
        try {
            $stmt = $db->query("SELECT COUNT(*) FROM material_requests WHERE status = 'pending'");
            $materialPending = $stmt->fetchColumn();
        } catch (Exception $e) {
            // material_requests table might not exist
        }
        
        return [
            'total_sites' => (int)$totalSites,
            'survey_pending' => (int)$surveyPending,
            'survey_completed' => (int)$surveyCompleted,
            'installation_pending' => (int)$installationPending,
            'installation_done' => (int)$installationDone,
            'vendor_assigned' => (int)$vendorAssigned,
            'delegated_sites' => (int)$delegatedSites,
            'material_pending' => (int)$materialPending
        ];
    } catch (Exception $e) {
        error_log("getSiteStats error: " . $e->getMessage());
        return [
            'total_sites' => 0,
            'survey_pending' => 0,
            'survey_completed' => 0,
            'installation_pending' => 0,
            'installation_done' => 0,
            'vendor_assigned' => 0,
            'delegated_sites' => 0,
            'material_pending' => 0
        ];
    }
}
?>
