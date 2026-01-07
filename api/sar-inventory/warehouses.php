<?php
/**
 * SAR Inventory Warehouses API
 * CRUD operations and warehouse management endpoints
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvWarehouseService.php';

class WarehousesApi extends SarInvApiController {
    private $warehouseService;
    
    public function __construct() {
        parent::__construct();
        $this->warehouseService = new SarInvWarehouseService();
    }
    
    public function handle(): void {
        $this->requireAuth();
        
        $method = $this->getMethod();
        $id = $this->getQueryInt('id');
        $action = $this->getQuery('action');
        
        // Handle special actions
        if ($action) {
            $this->handleAction($action, $id);
            return;
        }
        
        // Handle CRUD operations
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->show($id);
                } else {
                    $this->index();
                }
                break;
            case 'POST':
                $this->requirePermission('create');
                $this->store();
                break;
            case 'PUT':
                $this->requirePermission('edit');
                if (!$id) {
                    $this->sendError('Warehouse ID required', 400);
                }
                $this->update($id);
                break;
            case 'DELETE':
                $this->requirePermission('edit');
                if (!$id) {
                    $this->sendError('Warehouse ID required', 400);
                }
                $this->destroy($id);
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    private function handleAction(string $action, int $id): void {
        switch ($action) {
            case 'capacity':
                $this->getCapacity($id);
                break;
            case 'stock':
                $this->getStock($id);
                break;
            case 'search':
                $this->search();
                break;
            case 'active':
                $this->getActive();
                break;
            case 'can_delete':
                $this->canDelete($id);
                break;
            default:
                $this->sendError('Unknown action', 400);
        }
    }

    private function index(): void {
        $keyword = $this->getQuery('search');
        $status = $this->getQuery('status');
        
        $warehouses = $this->warehouseService->searchWarehouses($keyword, $status);
        
        $this->sendSuccess([
            'warehouses' => $warehouses,
            'total' => count($warehouses)
        ]);
    }
    
    private function show(int $id): void {
        $warehouse = $this->warehouseService->getWarehouse($id);
        
        if (!$warehouse) {
            $this->sendError('Warehouse not found', 404);
        }
        
        // Include capacity utilization
        $capacity = $this->warehouseService->getCapacityUtilization($id);
        $warehouse['capacity_utilization'] = $capacity;
        
        $this->sendSuccess(['warehouse' => $warehouse]);
    }
    
    private function store(): void {
        $data = $this->getJsonBody();
        
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['name', 'code']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->warehouseService->createWarehouse($data);
        
        if ($result['success']) {
            $this->logAction('create', 'sar_inv_warehouses', $result['warehouse_id'], $data);
            $warehouse = $this->warehouseService->getWarehouse($result['warehouse_id']);
            $this->sendSuccess(['warehouse' => $warehouse], $result['message'], 201);
        } else {
            $this->sendError('Failed to create warehouse', 400, $result['errors']);
        }
    }
    
    private function update(int $id): void {
        $data = $this->getJsonBody();
        
        if (empty($data)) {
            $data = $_POST;
        }
        
        $result = $this->warehouseService->updateWarehouse($id, $data);
        
        if ($result['success']) {
            $this->logAction('update', 'sar_inv_warehouses', $id, $data);
            $warehouse = $this->warehouseService->getWarehouse($id);
            $this->sendSuccess(['warehouse' => $warehouse], $result['message']);
        } else {
            $this->sendError('Failed to update warehouse', 400, $result['errors']);
        }
    }
    
    private function destroy(int $id): void {
        $result = $this->warehouseService->deleteWarehouse($id);
        
        if ($result['success']) {
            $this->logAction('delete', 'sar_inv_warehouses', $id);
            $this->sendSuccess(null, $result['message']);
        } else {
            $this->sendError('Failed to delete warehouse', 400, $result['errors']);
        }
    }
    
    private function getCapacity(int $id): void {
        if (!$id) {
            $this->sendError('Warehouse ID required', 400);
        }
        
        $warehouse = $this->warehouseService->getWarehouse($id);
        if (!$warehouse) {
            $this->sendError('Warehouse not found', 404);
        }
        
        $capacity = $this->warehouseService->getCapacityUtilization($id);
        $this->sendSuccess(['capacity' => $capacity]);
    }
    
    private function getStock(int $id): void {
        if (!$id) {
            $this->sendError('Warehouse ID required', 400);
        }
        
        $warehouse = $this->warehouseService->getWarehouse($id);
        if (!$warehouse) {
            $this->sendError('Warehouse not found', 404);
        }
        
        $stock = $this->warehouseService->getStockSummary($id);
        $this->sendSuccess(['stock' => $stock]);
    }
    
    private function search(): void {
        $keyword = $this->getQuery('q');
        $status = $this->getQuery('status');
        
        $warehouses = $this->warehouseService->searchWarehouses($keyword, $status);
        $this->sendSuccess(['warehouses' => $warehouses]);
    }
    
    private function getActive(): void {
        $warehouses = $this->warehouseService->getActiveWarehouses();
        $this->sendSuccess(['warehouses' => $warehouses]);
    }
    
    private function canDelete(int $id): void {
        if (!$id) {
            $this->sendError('Warehouse ID required', 400);
        }
        
        $result = $this->warehouseService->canDelete($id);
        $this->sendSuccess($result);
    }
}

// Execute API
$api = new WarehousesApi();
$api->handle();
?>
