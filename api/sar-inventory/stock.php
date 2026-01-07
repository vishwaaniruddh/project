<?php
/**
 * SAR Inventory Stock API
 * Stock operations including entry, adjustment, reservation, and queries
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvStockService.php';

class StockApi extends SarInvApiController {
    private $stockService;
    
    public function __construct() {
        parent::__construct();
        $this->stockService = new SarInvStockService();
    }
    
    public function handle(): void {
        $this->requireAuth();
        
        $method = $this->getMethod();
        $action = $this->getQuery('action');
        
        if ($action) {
            $this->handleAction($action);
            return;
        }
        
        switch ($method) {
            case 'GET':
                $this->getStock();
                break;
            case 'POST':
                $this->requirePermission('create');
                $this->addStock();
                break;
            case 'PUT':
                $this->requirePermission('edit');
                $this->adjustStock();
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    private function handleAction(string $action): void {
        switch ($action) {
            case 'add':
                $this->requirePermission('create');
                $this->addStock();
                break;
            case 'remove':
                $this->requirePermission('edit');
                $this->removeStock();
                break;
            case 'adjust':
                $this->requirePermission('edit');
                $this->adjustStock();
                break;
            case 'reserve':
                $this->requirePermission('edit');
                $this->reserveStock();
                break;
            case 'release':
                $this->requirePermission('edit');
                $this->releaseStock();
                break;
            case 'levels':
                $this->getStockLevels();
                break;
            case 'entries':
                $this->getStockEntries();
                break;
            case 'low_stock':
                $this->getLowStock();
                break;
            case 'validate':
                $this->validateAvailability();
                break;
            case 'history':
                $this->getHistory();
                break;
            default:
                $this->sendError('Unknown action', 400);
        }
    }

    private function getStock(): void {
        $productId = $this->getQueryInt('product_id');
        $warehouseId = $this->getQueryInt('warehouse_id');
        
        if ($productId && $warehouseId) {
            $stockLevel = $this->stockService->getStockLevel($productId, $warehouseId);
            $this->sendSuccess(['stock' => $stockLevel]);
        } elseif ($productId) {
            $stock = $this->stockService->getProductStock($productId);
            $this->sendSuccess(['stock' => $stock]);
        } elseif ($warehouseId) {
            $stock = $this->stockService->getWarehouseStock($warehouseId);
            $this->sendSuccess(['stock' => $stock]);
        } else {
            $this->sendError('Please provide product_id or warehouse_id', 400);
        }
    }
    
    private function addStock(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['product_id', 'warehouse_id', 'quantity']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->stockService->addStock(
            intval($data['product_id']),
            intval($data['warehouse_id']),
            floatval($data['quantity']),
            $data['reference_type'] ?? null,
            isset($data['reference_id']) ? intval($data['reference_id']) : null,
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            $this->logAction('stock_add', 'sar_inv_stock', null, $data);
            $this->sendSuccess($result, $result['message']);
        } else {
            $this->sendError('Failed to add stock', 400, $result['errors']);
        }
    }
    
    private function removeStock(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['product_id', 'warehouse_id', 'quantity']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->stockService->removeStock(
            intval($data['product_id']),
            intval($data['warehouse_id']),
            floatval($data['quantity']),
            $data['reference_type'] ?? null,
            isset($data['reference_id']) ? intval($data['reference_id']) : null,
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            $this->logAction('stock_remove', 'sar_inv_stock', null, $data);
            $this->sendSuccess($result, $result['message']);
        } else {
            $this->sendError('Failed to remove stock', 400, $result['errors']);
        }
    }
    
    private function adjustStock(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['product_id', 'warehouse_id', 'adjustment']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->stockService->adjustStock(
            intval($data['product_id']),
            intval($data['warehouse_id']),
            floatval($data['adjustment']),
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            $this->logAction('stock_adjust', 'sar_inv_stock', null, $data);
            $this->sendSuccess($result, $result['message']);
        } else {
            $this->sendError('Failed to adjust stock', 400, $result['errors']);
        }
    }
    
    private function reserveStock(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['product_id', 'warehouse_id', 'quantity']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->stockService->reserveStock(
            intval($data['product_id']),
            intval($data['warehouse_id']),
            floatval($data['quantity']),
            $data['reference_type'] ?? null,
            isset($data['reference_id']) ? intval($data['reference_id']) : null,
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            $this->logAction('stock_reserve', 'sar_inv_stock', null, $data);
            $this->sendSuccess($result, $result['message']);
        } else {
            $this->sendError('Failed to reserve stock', 400, $result['errors']);
        }
    }
    
    private function releaseStock(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['product_id', 'warehouse_id', 'quantity']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->stockService->releaseStock(
            intval($data['product_id']),
            intval($data['warehouse_id']),
            floatval($data['quantity']),
            $data['reference_type'] ?? null,
            isset($data['reference_id']) ? intval($data['reference_id']) : null,
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            $this->logAction('stock_release', 'sar_inv_stock', null, $data);
            $this->sendSuccess($result, $result['message']);
        } else {
            $this->sendError('Failed to release stock', 400, $result['errors']);
        }
    }
    
    private function getStockLevels(): void {
        $search = $this->getQuery('search');
        $warehouseId = $this->getQueryInt('warehouse_id') ?: null;
        $categoryId = $this->getQueryInt('category_id') ?: null;
        
        $levels = $this->stockService->getStockLevels($search, $warehouseId, $categoryId);
        $this->sendSuccess(['stock_levels' => $levels]);
    }
    
    private function getStockEntries(): void {
        $productId = $this->getQueryInt('product_id');
        $warehouseId = $this->getQueryInt('warehouse_id') ?: null;
        $limit = $this->getQueryInt('limit', 100);
        
        if (!$productId) {
            $this->sendError('Product ID required', 400);
        }
        
        $entries = $this->stockService->getStockEntries($productId, $warehouseId, $limit);
        $this->sendSuccess(['entries' => $entries]);
    }
    
    private function getLowStock(): void {
        $items = $this->stockService->getLowStockItems();
        $this->sendSuccess(['items' => $items]);
    }
    
    private function validateAvailability(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            $this->sendError('Items array required', 400);
        }
        
        $result = $this->stockService->validateStockAvailability($data['items']);
        $this->sendSuccess($result);
    }
    
    private function getHistory(): void {
        list($page, $perPage) = $this->getPagination();
        
        $filters = [
            'product_id' => $this->getQueryInt('product_id') ?: null,
            'warehouse_id' => $this->getQueryInt('warehouse_id') ?: null,
            'transaction_type' => $this->getQuery('transaction_type'),
            'date_from' => $this->getQuery('date_from'),
            'date_to' => $this->getQuery('date_to')
        ];
        
        $filters = array_filter($filters, fn($v) => $v !== null);
        
        $result = $this->stockService->getItemHistory($filters, $page, $perPage);
        
        $this->sendPaginated(
            $result['data'],
            $page,
            $perPage,
            $result['total']
        );
    }
}

// Execute API
$api = new StockApi();
$api->handle();
?>
