<?php
/**
 * SAR Inventory Transfers API
 * Inter-warehouse transfer management with approval workflow
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvTransferService.php';

class TransfersApi extends SarInvApiController {
    private $transferService;
    
    public function __construct() {
        parent::__construct();
        $this->transferService = new SarInvTransferService();
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
                    $this->sendError('Transfer ID required', 400);
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
            case 'items':
                $this->getItems($id);
                break;
            case 'add_item':
                $this->requirePermission('edit');
                $this->addItem($id);
                break;
            case 'approve':
                $this->requirePermission('approve');
                $this->approve($id);
                break;
            case 'ship':
                $this->requirePermission('edit');
                $this->ship($id);
                break;
            case 'receive':
                $this->requirePermission('edit');
                $this->receive($id);
                break;
            case 'cancel':
                $this->requirePermission('edit');
                $this->cancel($id);
                break;
            case 'transitions':
                $this->getTransitions($id);
                break;
            case 'verify':
                $this->verify($id);
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
        $sourceWarehouseId = $this->getQueryInt('source_warehouse_id') ?: null;
        $destWarehouseId = $this->getQueryInt('destination_warehouse_id') ?: null;
        $dateFrom = $this->getQuery('date_from');
        $dateTo = $this->getQuery('date_to');
        
        $transfers = $this->transferService->searchTransfers($keyword, $status, $sourceWarehouseId, $destWarehouseId, $dateFrom, $dateTo);
        
        $this->sendSuccess([
            'transfers' => $transfers,
            'total' => count($transfers)
        ]);
    }
    
    private function show(int $id): void {
        $transfer = $this->transferService->getTransferWithDetails($id);
        
        if (!$transfer) {
            $this->sendError('Transfer not found', 404);
        }
        
        // Include valid transitions
        $transfer['valid_transitions'] = $this->transferService->getValidStatusTransitions($id);
        
        $this->sendSuccess(['transfer' => $transfer]);
    }
    
    private function store(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['source_warehouse_id', 'destination_warehouse_id']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        if (empty($data['items']) || !is_array($data['items'])) {
            $this->sendError('Items array required', 400);
        }
        
        $transferData = [
            'source_warehouse_id' => intval($data['source_warehouse_id']),
            'destination_warehouse_id' => intval($data['destination_warehouse_id']),
            'transfer_date' => $data['transfer_date'] ?? date('Y-m-d'),
            'notes' => $data['notes'] ?? null
        ];
        
        $result = $this->transferService->createTransfer($transferData, $data['items']);
        
        if ($result['success']) {
            $this->logAction('create', 'sar_inv_transfers', $result['transfer_id'], $data);
            $transfer = $this->transferService->getTransferWithDetails($result['transfer_id']);
            $this->sendSuccess(['transfer' => $transfer], $result['message'], 201);
        } else {
            $this->sendError('Failed to create transfer', 400, $result['errors']);
        }
    }
    
    private function updateStatus(int $id): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $status = $data['status'] ?? '';
        
        switch ($status) {
            case 'approved':
                $result = $this->transferService->approveTransfer($id, $this->userId);
                break;
            case 'in_transit':
                $result = $this->transferService->shipTransfer($id);
                break;
            case 'received':
                $receivedItems = $data['received_items'] ?? null;
                $result = $this->transferService->receiveTransfer($id, $receivedItems);
                break;
            case 'cancelled':
                $result = $this->transferService->cancelTransfer($id, $data['reason'] ?? null);
                break;
            default:
                $this->sendError('Invalid status', 400);
                return;
        }
        
        if ($result['success']) {
            $this->logAction('update_status', 'sar_inv_transfers', $id, $data);
            $transfer = $this->transferService->getTransfer($id);
            $this->sendSuccess(['transfer' => $transfer], $result['message']);
        } else {
            $this->sendError('Failed to update status', 400, $result['errors']);
        }
    }
    
    private function search(): void {
        $keyword = $this->getQuery('q');
        $status = $this->getQuery('status');
        $sourceWarehouseId = $this->getQueryInt('source_warehouse_id') ?: null;
        $destWarehouseId = $this->getQueryInt('destination_warehouse_id') ?: null;
        $dateFrom = $this->getQuery('date_from');
        $dateTo = $this->getQuery('date_to');
        
        $transfers = $this->transferService->searchTransfers($keyword, $status, $sourceWarehouseId, $destWarehouseId, $dateFrom, $dateTo);
        $this->sendSuccess(['transfers' => $transfers]);
    }
    
    private function getByStatus(): void {
        $status = $this->getQuery('status');
        
        if (empty($status)) {
            $this->sendError('Status required', 400);
        }
        
        $transfers = $this->transferService->getTransfersByStatus($status);
        $this->sendSuccess(['transfers' => $transfers]);
    }
    
    private function getItems(int $id): void {
        if (!$id) {
            $this->sendError('Transfer ID required', 400);
        }
        
        $items = $this->transferService->getTransferItems($id);
        $this->sendSuccess(['items' => $items]);
    }
    
    private function addItem(int $id): void {
        if (!$id) {
            $this->sendError('Transfer ID required', 400);
        }
        
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['product_id', 'quantity']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->transferService->addItem(
            $id,
            intval($data['product_id']),
            floatval($data['quantity']),
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            $this->logAction('add_item', 'sar_inv_transfers', $id, $data);
            $this->sendSuccess(null, $result['message']);
        } else {
            $this->sendError('Failed to add item', 400, $result['errors']);
        }
    }
    
    private function approve(int $id): void {
        if (!$id) {
            $this->sendError('Transfer ID required', 400);
        }
        
        $result = $this->transferService->approveTransfer($id, $this->userId);
        
        if ($result['success']) {
            $this->logAction('approve', 'sar_inv_transfers', $id);
            $transfer = $this->transferService->getTransfer($id);
            $this->sendSuccess(['transfer' => $transfer], $result['message']);
        } else {
            $this->sendError('Failed to approve transfer', 400, $result['errors']);
        }
    }
    
    private function ship(int $id): void {
        if (!$id) {
            $this->sendError('Transfer ID required', 400);
        }
        
        $result = $this->transferService->shipTransfer($id);
        
        if ($result['success']) {
            $this->logAction('ship', 'sar_inv_transfers', $id);
            $transfer = $this->transferService->getTransfer($id);
            $this->sendSuccess(['transfer' => $transfer], $result['message']);
        } else {
            $this->sendError('Failed to ship transfer', 400, $result['errors']);
        }
    }
    
    private function receive(int $id): void {
        if (!$id) {
            $this->sendError('Transfer ID required', 400);
        }
        
        $data = $this->getJsonBody();
        $receivedItems = $data['received_items'] ?? null;
        
        $result = $this->transferService->receiveTransfer($id, $receivedItems);
        
        if ($result['success']) {
            $this->logAction('receive', 'sar_inv_transfers', $id, $data);
            $transfer = $this->transferService->getTransfer($id);
            $this->sendSuccess(['transfer' => $transfer], $result['message']);
        } else {
            $this->sendError('Failed to receive transfer', 400, $result['errors']);
        }
    }
    
    private function cancel(int $id): void {
        if (!$id) {
            $this->sendError('Transfer ID required', 400);
        }
        
        $data = $this->getJsonBody();
        $reason = $data['reason'] ?? null;
        
        $result = $this->transferService->cancelTransfer($id, $reason);
        
        if ($result['success']) {
            $this->logAction('cancel', 'sar_inv_transfers', $id, ['reason' => $reason]);
            $transfer = $this->transferService->getTransfer($id);
            $this->sendSuccess(['transfer' => $transfer], $result['message']);
        } else {
            $this->sendError('Failed to cancel transfer', 400, $result['errors']);
        }
    }
    
    private function getTransitions(int $id): void {
        if (!$id) {
            $this->sendError('Transfer ID required', 400);
        }
        
        $transitions = $this->transferService->getValidStatusTransitions($id);
        $this->sendSuccess(['transitions' => $transitions]);
    }
    
    private function verify(int $id): void {
        if (!$id) {
            $this->sendError('Transfer ID required', 400);
        }
        
        $result = $this->transferService->verifyStockConservation($id);
        $this->sendSuccess($result);
    }
    
    private function getByNumber(): void {
        $number = $this->getQuery('number');
        
        if (empty($number)) {
            $this->sendError('Transfer number required', 400);
        }
        
        $transfer = $this->transferService->getTransferByNumber($number);
        
        if (!$transfer) {
            $this->sendError('Transfer not found', 404);
        }
        
        $this->sendSuccess(['transfer' => $transfer]);
    }
}

// Execute API
$api = new TransfersApi();
$api->handle();
?>
