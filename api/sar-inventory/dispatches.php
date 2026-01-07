<?php
/**
 * SAR Inventory Dispatches API
 * Dispatch management with status tracking
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvDispatchService.php';

class DispatchesApi extends SarInvApiController {
    private $dispatchService;
    
    public function __construct() {
        parent::__construct();
        $this->dispatchService = new SarInvDispatchService();
    }
    
    public function handle(): void {
        $this->requireAuth();
        
        $method = $this->getMethod();
        $id = $this->getQueryInt('id');
        $action = $this->getQuery('action');
        
        if ($action) {
            $this->handleAction($action, $id);
            return;
        }
        
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
                    $this->sendError('Dispatch ID required', 400);
                }
                $this->updateStatus($id);
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    private function handleAction(string $action, int $id): void {
        switch ($action) {
            case 'search':
                $this->search();
                break;
            case 'by_status':
                $this->getByStatus();
                break;
            case 'by_warehouse':
                $this->getByWarehouse();
                break;
            case 'items':
                $this->getItems($id);
                break;
            case 'add_item':
                $this->requirePermission('edit');
                $this->addItem($id);
                break;
            case 'remove_item':
                $this->requirePermission('edit');
                $this->removeItem();
                break;
            case 'approve':
                $this->requirePermission('approve');
                $this->approve($id);
                break;
            case 'ship':
                $this->requirePermission('edit');
                $this->ship($id);
                break;
            case 'deliver':
                $this->requirePermission('edit');
                $this->deliver($id);
                break;
            case 'cancel':
                $this->requirePermission('edit');
                $this->cancel($id);
                break;
            case 'transitions':
                $this->getTransitions($id);
                break;
            case 'by_number':
                $this->getByNumber();
                break;
            default:
                $this->sendError('Unknown action', 400);
        }
    }

    private function index(): void {
        $keyword = $this->getQuery('search');
        $status = $this->getQuery('status');
        $warehouseId = $this->getQueryInt('warehouse_id') ?: null;
        $dateFrom = $this->getQuery('date_from');
        $dateTo = $this->getQuery('date_to');
        
        $dispatches = $this->dispatchService->searchDispatches($keyword, $status, $warehouseId, $dateFrom, $dateTo);
        
        $this->sendSuccess([
            'dispatches' => $dispatches,
            'total' => count($dispatches)
        ]);
    }
    
    private function show(int $id): void {
        $dispatch = $this->dispatchService->getDispatchWithDetails($id);
        
        if (!$dispatch) {
            $this->sendError('Dispatch not found', 404);
        }
        
        // Include valid transitions
        $dispatch['valid_transitions'] = $this->dispatchService->getValidStatusTransitions($id);
        
        $this->sendSuccess(['dispatch' => $dispatch]);
    }
    
    private function store(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['source_warehouse_id', 'destination_type']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            $this->sendError('Items array required', 400);
        }
        
        $dispatchData = [
            'source_warehouse_id' => intval($data['source_warehouse_id']),
            'destination_type' => $data['destination_type'],
            'destination_id' => isset($data['destination_id']) ? intval($data['destination_id']) : null,
            'destination_address' => $data['destination_address'] ?? null,
            'dispatch_date' => $data['dispatch_date'] ?? date('Y-m-d'),
            'notes' => $data['notes'] ?? null
        ];
        
        $result = $this->dispatchService->createDispatch($dispatchData, $data['items']);
        
        if ($result['success']) {
            $this->logAction('create', 'sar_inv_dispatches', $result['dispatch_id'], $data);
            $dispatch = $this->dispatchService->getDispatchWithDetails($result['dispatch_id']);
            $this->sendSuccess(['dispatch' => $dispatch], $result['message'], 201);
        } else {
            $this->sendError('Failed to create dispatch', 400, $result['errors']);
        }
    }
    
    private function updateStatus(int $id): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['status']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->dispatchService->updateStatus($id, $data['status']);
        
        if ($result['success']) {
            $this->logAction('update_status', 'sar_inv_dispatches', $id, $data);
            $dispatch = $this->dispatchService->getDispatch($id);
            $this->sendSuccess(['dispatch' => $dispatch], $result['message']);
        } else {
            $this->sendError('Failed to update status', 400, $result['errors']);
        }
    }
    
    private function search(): void {
        $keyword = $this->getQuery('q');
        $status = $this->getQuery('status');
        $warehouseId = $this->getQueryInt('warehouse_id') ?: null;
        $dateFrom = $this->getQuery('date_from');
        $dateTo = $this->getQuery('date_to');
        
        $dispatches = $this->dispatchService->searchDispatches($keyword, $status, $warehouseId, $dateFrom, $dateTo);
        $this->sendSuccess(['dispatches' => $dispatches]);
    }
    
    private function getByStatus(): void {
        $status = $this->getQuery('status');
        
        if (empty($status)) {
            $this->sendError('Status required', 400);
        }
        
        $dispatches = $this->dispatchService->getDispatchesByStatus($status);
        $this->sendSuccess(['dispatches' => $dispatches]);
    }
    
    private function getByWarehouse(): void {
        $warehouseId = $this->getQueryInt('warehouse_id');
        $status = $this->getQuery('status');
        
        if (!$warehouseId) {
            $this->sendError('Warehouse ID required', 400);
        }
        
        $dispatches = $this->dispatchService->getDispatchesByWarehouse($warehouseId, $status);
        $this->sendSuccess(['dispatches' => $dispatches]);
    }
    
    private function getItems(int $id): void {
        if (!$id) {
            $this->sendError('Dispatch ID required', 400);
        }
        
        $items = $this->dispatchService->getDispatchItems($id);
        $this->sendSuccess(['items' => $items]);
    }
    
    private function addItem(int $id): void {
        if (!$id) {
            $this->sendError('Dispatch ID required', 400);
        }
        
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['product_id', 'quantity']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->dispatchService->addItem(
            $id,
            intval($data['product_id']),
            floatval($data['quantity']),
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            $this->logAction('add_item', 'sar_inv_dispatches', $id, $data);
            $this->sendSuccess(null, $result['message']);
        } else {
            $this->sendError('Failed to add item', 400, $result['errors']);
        }
    }
    
    private function removeItem(): void {
        $itemId = $this->getQueryInt('item_id');
        
        if (!$itemId) {
            $this->sendError('Item ID required', 400);
        }
        
        $result = $this->dispatchService->removeItem($itemId);
        
        if ($result['success']) {
            $this->logAction('remove_item', 'sar_inv_dispatch_items', $itemId);
            $this->sendSuccess(null, $result['message']);
        } else {
            $this->sendError('Failed to remove item', 400, $result['errors']);
        }
    }
    
    private function approve(int $id): void {
        if (!$id) {
            $this->sendError('Dispatch ID required', 400);
        }
        
        $result = $this->dispatchService->approveDispatch($id);
        
        if ($result['success']) {
            $this->logAction('approve', 'sar_inv_dispatches', $id);
            $dispatch = $this->dispatchService->getDispatch($id);
            $this->sendSuccess(['dispatch' => $dispatch], $result['message']);
        } else {
            $this->sendError('Failed to approve dispatch', 400, $result['errors']);
        }
    }
    
    private function ship(int $id): void {
        if (!$id) {
            $this->sendError('Dispatch ID required', 400);
        }
        
        $result = $this->dispatchService->shipDispatch($id);
        
        if ($result['success']) {
            $this->logAction('ship', 'sar_inv_dispatches', $id);
            $dispatch = $this->dispatchService->getDispatch($id);
            $this->sendSuccess(['dispatch' => $dispatch], $result['message']);
        } else {
            $this->sendError('Failed to ship dispatch', 400, $result['errors']);
        }
    }
    
    private function deliver(int $id): void {
        if (!$id) {
            $this->sendError('Dispatch ID required', 400);
        }
        
        $result = $this->dispatchService->markDelivered($id);
        
        if ($result['success']) {
            $this->logAction('deliver', 'sar_inv_dispatches', $id);
            $dispatch = $this->dispatchService->getDispatch($id);
            $this->sendSuccess(['dispatch' => $dispatch], $result['message']);
        } else {
            $this->sendError('Failed to mark as delivered', 400, $result['errors']);
        }
    }
    
    private function cancel(int $id): void {
        if (!$id) {
            $this->sendError('Dispatch ID required', 400);
        }
        
        $result = $this->dispatchService->cancelDispatch($id);
        
        if ($result['success']) {
            $this->logAction('cancel', 'sar_inv_dispatches', $id);
            $dispatch = $this->dispatchService->getDispatch($id);
            $this->sendSuccess(['dispatch' => $dispatch], $result['message']);
        } else {
            $this->sendError('Failed to cancel dispatch', 400, $result['errors']);
        }
    }
    
    private function getTransitions(int $id): void {
        if (!$id) {
            $this->sendError('Dispatch ID required', 400);
        }
        
        $transitions = $this->dispatchService->getValidStatusTransitions($id);
        $this->sendSuccess(['transitions' => $transitions]);
    }
    
    private function getByNumber(): void {
        $number = $this->getQuery('number');
        
        if (empty($number)) {
            $this->sendError('Dispatch number required', 400);
        }
        
        $dispatch = $this->dispatchService->getDispatchByNumber($number);
        
        if (!$dispatch) {
            $this->sendError('Dispatch not found', 404);
        }
        
        $this->sendSuccess(['dispatch' => $dispatch]);
    }
}

// Execute API
$api = new DispatchesApi();
$api->handle();
?>
