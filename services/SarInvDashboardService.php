<?php
/**
 * SAR Inventory Dashboard Service
 * Real-time inventory metrics calculation and KPI tracking
 */

// Load database first
require_once __DIR__ . '/../config/database.php';

// Load base model
require_once __DIR__ . '/../models/BaseModel.php';
require_once __DIR__ . '/../models/SarInvBaseModel.php';

// Load all required models individually
require_once __DIR__ . '/../models/SarInvWarehouse.php';
require_once __DIR__ . '/../models/SarInvProductCategory.php';
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvDispatch.php';
require_once __DIR__ . '/../models/SarInvTransfer.php';
require_once __DIR__ . '/../models/SarInvAsset.php';
require_once __DIR__ . '/../models/SarInvRepair.php';
require_once __DIR__ . '/../models/SarInvMaterialMaster.php';
require_once __DIR__ . '/../models/SarInvItemHistory.php';
require_once __DIR__ . '/../models/SarInvMaterialRequest.php';

class SarInvDashboardService {
    private $warehouseModel;
    private $productModel;
    private $stockModel;
    private $dispatchModel;
    private $transferModel;
    private $assetModel;
    private $repairModel;
    private $materialRequestModel;
    private $historyModel;
    
    private static $cache = [];
    private static $cacheExpiry = 300; // 5 minutes cache
    
    public function __construct() {
        $this->warehouseModel = new SarInvWarehouse();
        $this->productModel = new SarInvProduct();
        $this->stockModel = new SarInvStock();
        $this->dispatchModel = new SarInvDispatch();
        $this->transferModel = new SarInvTransfer();
        $this->assetModel = new SarInvAsset();
        $this->repairModel = new SarInvRepair();
        $this->materialRequestModel = new SarInvMaterialRequest();
        $this->historyModel = new SarInvItemHistory();
    }
    
    /**
     * Get complete dashboard data
     * @param bool $useCache Whether to use cached data
     * @return array Dashboard data
     */
    public function getDashboardData(bool $useCache = true): array {
        $cacheKey = 'dashboard_data';
        
        if ($useCache && $this->isCacheValid($cacheKey)) {
            return self::$cache[$cacheKey]['data'];
        }
        
        $data = [
            'summary' => $this->getSummaryMetrics(),
            'stock_alerts' => $this->getStockAlerts(),
            'pending_actions' => $this->getPendingActions(),
            'recent_activity' => $this->getRecentActivity(),
            'warehouse_utilization' => $this->getWarehouseUtilization(),
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        $this->setCache($cacheKey, $data);
        
        return $data;
    }
    
    /**
     * Get summary metrics
     * @return array Summary metrics
     */
    public function getSummaryMetrics(): array {
        return [
            'warehouses' => $this->getWarehouseMetrics(),
            'products' => $this->getProductMetrics(),
            'stock' => $this->getStockMetrics(),
            'dispatches' => $this->getDispatchMetrics(),
            'transfers' => $this->getTransferMetrics(),
            'assets' => $this->getAssetMetrics(),
            'repairs' => $this->getRepairMetrics(),
            'material_requests' => $this->getMaterialRequestMetrics()
        ];
    }
    
    /**
     * Get warehouse metrics
     */
    private function getWarehouseMetrics(): array {
        $warehouses = $this->warehouseModel->findAll();
        $active = array_filter($warehouses, fn($w) => $w['status'] === 'active');
        
        return [
            'total' => count($warehouses),
            'active' => count($active),
            'inactive' => count($warehouses) - count($active)
        ];
    }
    
    /**
     * Get product metrics
     */
    private function getProductMetrics(): array {
        $products = $this->productModel->findAll();
        $active = array_filter($products, fn($p) => $p['status'] === 'active');
        $lowStock = $this->productModel->getLowStockProducts();
        
        return [
            'total' => count($products),
            'active' => count($active),
            'low_stock' => count($lowStock)
        ];
    }
    
    /**
     * Get stock metrics
     */
    private function getStockMetrics(): array {
        $lowStockItems = $this->stockModel->getLowStockItems();
        
        $db = $this->stockModel->getDb();
        $stmt = $db->query("SELECT 
            COUNT(DISTINCT product_id) as products_in_stock,
            SUM(quantity) as total_quantity,
            SUM(reserved_quantity) as total_reserved
            FROM sar_inv_stock WHERE quantity > 0");
        $stockSummary = $stmt->fetch();
        
        return [
            'products_in_stock' => intval($stockSummary['products_in_stock'] ?? 0),
            'total_quantity' => floatval($stockSummary['total_quantity'] ?? 0),
            'total_reserved' => floatval($stockSummary['total_reserved'] ?? 0),
            'low_stock_items' => count($lowStockItems)
        ];
    }

    /**
     * Get dispatch metrics
     */
    private function getDispatchMetrics(): array {
        $pending = $this->dispatchModel->getByStatus(SarInvDispatch::STATUS_PENDING);
        $approved = $this->dispatchModel->getByStatus(SarInvDispatch::STATUS_APPROVED);
        $shipped = $this->dispatchModel->getByStatus(SarInvDispatch::STATUS_SHIPPED);
        $inTransit = $this->dispatchModel->getByStatus(SarInvDispatch::STATUS_IN_TRANSIT);
        
        return [
            'pending' => count($pending),
            'approved' => count($approved),
            'shipped' => count($shipped),
            'in_transit' => count($inTransit),
            'total_active' => count($pending) + count($approved) + count($shipped) + count($inTransit)
        ];
    }
    
    /**
     * Get transfer metrics
     */
    private function getTransferMetrics(): array {
        $pending = $this->transferModel->getByStatus(SarInvTransfer::STATUS_PENDING);
        $approved = $this->transferModel->getByStatus(SarInvTransfer::STATUS_APPROVED);
        $inTransit = $this->transferModel->getByStatus(SarInvTransfer::STATUS_IN_TRANSIT);
        
        return [
            'pending' => count($pending),
            'approved' => count($approved),
            'in_transit' => count($inTransit),
            'total_active' => count($pending) + count($approved) + count($inTransit)
        ];
    }
    
    /**
     * Get asset metrics
     */
    private function getAssetMetrics(): array {
        $available = $this->assetModel->getByStatus(SarInvAsset::STATUS_AVAILABLE);
        $dispatched = $this->assetModel->getByStatus(SarInvAsset::STATUS_DISPATCHED);
        $inRepair = $this->assetModel->getByStatus(SarInvAsset::STATUS_IN_REPAIR);
        $expiringWarranty = $this->assetModel->getExpiringWarranty(30);
        
        return [
            'available' => count($available),
            'dispatched' => count($dispatched),
            'in_repair' => count($inRepair),
            'expiring_warranty' => count($expiringWarranty),
            'total' => count($available) + count($dispatched) + count($inRepair)
        ];
    }
    
    /**
     * Get repair metrics
     */
    private function getRepairMetrics(): array {
        $pending = $this->repairModel->getByStatus(SarInvRepair::STATUS_PENDING);
        $inProgress = $this->repairModel->getByStatus(SarInvRepair::STATUS_IN_PROGRESS);
        $overdue = $this->repairModel->getOverdueRepairs(7);
        
        return [
            'pending' => count($pending),
            'in_progress' => count($inProgress),
            'overdue' => count($overdue),
            'total_active' => count($pending) + count($inProgress)
        ];
    }
    
    /**
     * Get material request metrics
     */
    private function getMaterialRequestMetrics(): array {
        $stats = $this->materialRequestModel->getStatistics();
        
        return [
            'pending' => intval($stats['pending_count'] ?? 0),
            'approved' => intval($stats['approved_count'] ?? 0),
            'partially_fulfilled' => intval($stats['partially_fulfilled_count'] ?? 0),
            'total_active' => intval($stats['pending_count'] ?? 0) + intval($stats['approved_count'] ?? 0) + intval($stats['partially_fulfilled_count'] ?? 0)
        ];
    }
    
    /**
     * Get stock alerts
     * @return array Stock alerts
     */
    public function getStockAlerts(): array {
        $lowStockItems = $this->stockModel->getLowStockItems();
        
        $alerts = [];
        foreach ($lowStockItems as $item) {
            $alerts[] = [
                'type' => 'low_stock',
                'severity' => $item['available_quantity'] <= 0 ? 'critical' : 'warning',
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'warehouse_name' => $item['warehouse_name'],
                'available_quantity' => $item['available_quantity'],
                'minimum_level' => $item['minimum_stock_level'],
                'message' => "Low stock: {$item['product_name']} in {$item['warehouse_name']}"
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Get pending actions requiring attention
     * @return array Pending actions
     */
    public function getPendingActions(): array {
        $actions = [];
        
        // Pending dispatches
        $pendingDispatches = $this->dispatchModel->getByStatus(SarInvDispatch::STATUS_PENDING);
        foreach (array_slice($pendingDispatches, 0, 5) as $dispatch) {
            $actions[] = [
                'type' => 'dispatch_pending',
                'id' => $dispatch['id'],
                'reference' => $dispatch['dispatch_number'],
                'message' => "Dispatch {$dispatch['dispatch_number']} awaiting approval",
                'created_at' => $dispatch['created_at']
            ];
        }
        
        // Pending transfers
        $pendingTransfers = $this->transferModel->getByStatus(SarInvTransfer::STATUS_PENDING);
        foreach (array_slice($pendingTransfers, 0, 5) as $transfer) {
            $actions[] = [
                'type' => 'transfer_pending',
                'id' => $transfer['id'],
                'reference' => $transfer['transfer_number'],
                'message' => "Transfer {$transfer['transfer_number']} awaiting approval",
                'created_at' => $transfer['created_at']
            ];
        }
        
        // Pending material requests
        $pendingRequests = $this->materialRequestModel->getByStatus(SarInvMaterialRequest::STATUS_PENDING, 5);
        foreach ($pendingRequests as $request) {
            $actions[] = [
                'type' => 'material_request_pending',
                'id' => $request['id'],
                'reference' => $request['request_number'],
                'message' => "Material request {$request['request_number']} awaiting approval",
                'created_at' => $request['created_at']
            ];
        }
        
        // Overdue repairs
        $overdueRepairs = $this->repairModel->getOverdueRepairs(7);
        foreach (array_slice($overdueRepairs, 0, 5) as $repair) {
            $actions[] = [
                'type' => 'repair_overdue',
                'id' => $repair['id'],
                'reference' => $repair['repair_number'],
                'message' => "Repair {$repair['repair_number']} overdue ({$repair['days_pending']} days)",
                'created_at' => $repair['created_at']
            ];
        }
        
        // Sort by created_at descending
        usort($actions, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
        
        return array_slice($actions, 0, 10);
    }

    /**
     * Get recent activity
     * @param int $limit Number of records
     * @return array Recent activity
     */
    public function getRecentActivity(int $limit = 10): array {
        $history = $this->historyModel->search([], $limit, 0);
        
        $activity = [];
        foreach ($history as $item) {
            $activity[] = [
                'type' => $item['transaction_type'],
                'product_name' => $item['product_name'],
                'warehouse_name' => $item['warehouse_name'],
                'quantity' => $item['quantity'],
                'created_at' => $item['created_at'],
                'description' => $this->formatActivityDescription($item)
            ];
        }
        
        return $activity;
    }
    
    /**
     * Format activity description
     */
    private function formatActivityDescription(array $item): string {
        $qty = abs($item['quantity']);
        $product = $item['product_name'];
        $warehouse = $item['warehouse_name'] ?? 'Unknown';
        
        switch ($item['transaction_type']) {
            case 'stock_in':
                return "Added {$qty} {$product} to {$warehouse}";
            case 'stock_out':
                return "Removed {$qty} {$product} from {$warehouse}";
            case 'adjustment':
                $action = $item['quantity'] >= 0 ? 'increased' : 'decreased';
                return "Stock {$action} by {$qty} for {$product} in {$warehouse}";
            case 'transfer_out':
                return "Transferred {$qty} {$product} from {$warehouse}";
            case 'transfer_in':
                return "Received {$qty} {$product} at {$warehouse}";
            case 'dispatch':
                return "Dispatched {$qty} {$product} from {$warehouse}";
            case 'reservation':
                return "Reserved {$qty} {$product} in {$warehouse}";
            case 'release':
                return "Released {$qty} {$product} in {$warehouse}";
            default:
                return "{$item['transaction_type']}: {$qty} {$product}";
        }
    }
    
    /**
     * Get warehouse utilization
     * @return array Warehouse utilization data
     */
    public function getWarehouseUtilization(): array {
        $warehouses = $this->warehouseModel->getActiveWarehouses();
        $utilization = [];
        
        foreach ($warehouses as $warehouse) {
            $util = $this->warehouseModel->getCapacityUtilization($warehouse['id']);
            $utilization[] = [
                'warehouse_id' => $warehouse['id'],
                'warehouse_name' => $warehouse['name'],
                'warehouse_code' => $warehouse['code'],
                'capacity' => $util['capacity'],
                'used' => $util['used'],
                'available' => $util['available'],
                'utilization_percentage' => $util['utilization_percentage']
            ];
        }
        
        return $utilization;
    }
    
    /**
     * Get inventory trends
     * @param int $days Number of days
     * @return array Trend data
     */
    public function getInventoryTrends(int $days = 30): array {
        return $this->historyModel->getDailySummary([], $days);
    }
    
    /**
     * Get KPIs
     * @return array Key Performance Indicators
     */
    public function getKPIs(): array {
        $metrics = $this->getSummaryMetrics();
        
        // Calculate KPIs
        $totalProducts = $metrics['products']['total'];
        $lowStockProducts = $metrics['products']['low_stock'];
        $stockHealthPercentage = $totalProducts > 0 
            ? round((($totalProducts - $lowStockProducts) / $totalProducts) * 100, 2) 
            : 100;
        
        $totalAssets = $metrics['assets']['total'];
        $availableAssets = $metrics['assets']['available'];
        $assetAvailabilityPercentage = $totalAssets > 0 
            ? round(($availableAssets / $totalAssets) * 100, 2) 
            : 100;
        
        return [
            'stock_health' => [
                'value' => $stockHealthPercentage,
                'unit' => '%',
                'label' => 'Stock Health',
                'status' => $stockHealthPercentage >= 80 ? 'good' : ($stockHealthPercentage >= 50 ? 'warning' : 'critical')
            ],
            'asset_availability' => [
                'value' => $assetAvailabilityPercentage,
                'unit' => '%',
                'label' => 'Asset Availability',
                'status' => $assetAvailabilityPercentage >= 70 ? 'good' : ($assetAvailabilityPercentage >= 40 ? 'warning' : 'critical')
            ],
            'pending_dispatches' => [
                'value' => $metrics['dispatches']['pending'],
                'unit' => '',
                'label' => 'Pending Dispatches',
                'status' => $metrics['dispatches']['pending'] <= 5 ? 'good' : ($metrics['dispatches']['pending'] <= 15 ? 'warning' : 'critical')
            ],
            'pending_transfers' => [
                'value' => $metrics['transfers']['pending'],
                'unit' => '',
                'label' => 'Pending Transfers',
                'status' => $metrics['transfers']['pending'] <= 5 ? 'good' : ($metrics['transfers']['pending'] <= 15 ? 'warning' : 'critical')
            ],
            'active_repairs' => [
                'value' => $metrics['repairs']['total_active'],
                'unit' => '',
                'label' => 'Active Repairs',
                'status' => $metrics['repairs']['total_active'] <= 10 ? 'good' : ($metrics['repairs']['total_active'] <= 25 ? 'warning' : 'critical')
            ]
        ];
    }
    
    /**
     * Check if cache is valid
     */
    private function isCacheValid(string $key): bool {
        if (!isset(self::$cache[$key])) {
            return false;
        }
        
        return (time() - self::$cache[$key]['timestamp']) < self::$cacheExpiry;
    }
    
    /**
     * Set cache
     */
    private function setCache(string $key, $data): void {
        self::$cache[$key] = [
            'data' => $data,
            'timestamp' => time()
        ];
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void {
        self::$cache = [];
    }
    
    /**
     * Refresh dashboard data
     * @return array Fresh dashboard data
     */
    public function refreshDashboard(): array {
        $this->clearCache();
        return $this->getDashboardData(false);
    }
}
?>
