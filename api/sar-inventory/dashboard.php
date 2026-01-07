<?php
/**
 * SAR Inventory Dashboard API
 * Real-time metrics and KPIs
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvDashboardService.php';

class DashboardApi extends SarInvApiController {
    private $dashboardService;
    
    public function __construct() {
        parent::__construct();
        $this->dashboardService = new SarInvDashboardService();
    }
    
    public function handle(): void {
        $this->requireAuth();
        
        $method = $this->getMethod();
        $action = $this->getQuery('action');
        
        if ($method !== 'GET') {
            $this->sendError('Method not allowed', 405);
        }
        
        if ($action) {
            $this->handleAction($action);
            return;
        }
        
        // Default: return full dashboard data
        $this->getDashboard();
    }
    
    private function handleAction(string $action): void {
        switch ($action) {
            case 'summary':
                $this->getSummary();
                break;
            case 'kpis':
                $this->getKPIs();
                break;
            case 'alerts':
                $this->getAlerts();
                break;
            case 'pending':
                $this->getPendingActions();
                break;
            case 'activity':
                $this->getRecentActivity();
                break;
            case 'warehouse_utilization':
                $this->getWarehouseUtilization();
                break;
            case 'trends':
                $this->getTrends();
                break;
            case 'refresh':
                $this->refresh();
                break;
            default:
                $this->sendError('Unknown action', 400);
        }
    }
    
    private function getDashboard(): void {
        $useCache = $this->getQuery('cache', 'true') === 'true';
        $data = $this->dashboardService->getDashboardData($useCache);
        $this->sendSuccess($data);
    }
    
    private function getSummary(): void {
        $summary = $this->dashboardService->getSummaryMetrics();
        $this->sendSuccess(['summary' => $summary]);
    }
    
    private function getKPIs(): void {
        $kpis = $this->dashboardService->getKPIs();
        $this->sendSuccess(['kpis' => $kpis]);
    }
    
    private function getAlerts(): void {
        $alerts = $this->dashboardService->getStockAlerts();
        $this->sendSuccess(['alerts' => $alerts]);
    }
    
    private function getPendingActions(): void {
        $pending = $this->dashboardService->getPendingActions();
        $this->sendSuccess(['pending_actions' => $pending]);
    }
    
    private function getRecentActivity(): void {
        $limit = $this->getQueryInt('limit', 10);
        $activity = $this->dashboardService->getRecentActivity($limit);
        $this->sendSuccess(['activity' => $activity]);
    }
    
    private function getWarehouseUtilization(): void {
        $utilization = $this->dashboardService->getWarehouseUtilization();
        $this->sendSuccess(['utilization' => $utilization]);
    }
    
    private function getTrends(): void {
        $days = $this->getQueryInt('days', 30);
        $trends = $this->dashboardService->getInventoryTrends($days);
        $this->sendSuccess(['trends' => $trends]);
    }
    
    private function refresh(): void {
        $data = $this->dashboardService->refreshDashboard();
        $this->sendSuccess($data, 'Dashboard refreshed');
    }
}

// Execute API
$api = new DashboardApi();
$api->handle();
?>
