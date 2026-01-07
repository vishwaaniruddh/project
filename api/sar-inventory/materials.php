<?php
/**
 * SAR Inventory Materials API
 * Material master and request management
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvMaterialService.php';

class MaterialsApi extends SarInvApiController {
    private $materialService;
    
    public function __construct() {
        parent::__construct();
        $this->materialService = new SarInvMaterialService();
    }
    
    public function handle(): void {
        $this->requireAuth();
        
        $method = $this->getMethod();
        $id = $this->getQueryInt('id');
        $action = $this->getQuery('action');
        $type = $this->getQuery('type', 'master'); // 'master' or 'request'
        
        if ($action) {
            $this->handleAction($action, $id, $type);
            return;
        }
        
        if ($type === 'request') {
            $this->handleRequests($method, $id);
        } else {
            $this->handleMasters($method, $id);
        }
    }
    
    private function handleMasters(string $method, int $id): void {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->showMaster($id);
                } else {
                    $this->indexMasters();
                }
                break;
            case 'POST':
                $this->requirePermission('create');
                $this->storeMaster();
                break;
            case 'PUT':
                $this->requirePermission('edit');
                if (!$id) {
                    $this->sendError('Material ID required', 400);
                }
                $this->updateMaster($id);
                break;
            case 'DELETE':
                $this->requirePermission('edit');
                if (!$id) {
                    $this->sendError('Material ID required', 400);
                }
                $this->deleteMaster($id);
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    private function handleRequests(string $method, int $id): void {
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->showRequest($id);
                } else {
                    $this->indexRequests();
                }
                break;
            case 'POST':
                $this->requirePermission('create');
                $this->storeRequest();
                break;
            case 'PUT':
                $this->requirePermission('edit');
                if (!$id) {
                    $this->sendError('Request ID required', 400);
                }
                $this->updateRequest($id);
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    private function handleAction(string $action, int $id, string $type): void {
        switch ($action) {
            // Master actions
            case 'search_masters':
                $this->searchMasters();
                break;
            case 'active_masters':
                $this->getActiveMasters();
                break;
            case 'by_code':
                $this->getMasterByCode();
                break;
            
            // Request actions
            case 'search_requests':
                $this->searchRequests();
                break;
            case 'by_status':
                $this->getRequestsByStatus();
                break;
            case 'by_requester':
                $this->getRequestsByRequester();
                break;
            case 'approve':
                $this->requirePermission('approve');
                $this->approveRequest($id);
                break;
            case 'reject':
                $this->requirePermission('approve');
                $this->rejectRequest($id);
                break;
            case 'fulfill':
                $this->requirePermission('edit');
                $this->fulfillRequest($id);
                break;
            case 'cancel':
                $this->requirePermission('edit');
                $this->cancelRequest($id);
                break;
            case 'progress':
                $this->getFulfillmentProgress($id);
                break;
            case 'statistics':
                $this->getStatistics();
                break;
            case 'validate_stock':
                $this->validateStock();
                break;
            default:
                $this->sendError('Unknown action', 400);
        }
    }

    // Material Master Methods
    private function indexMasters(): void {
        $materials = $this->materialService->getAllMaterialMasters();
        $this->sendSuccess([
            'materials' => $materials,
            'total' => count($materials)
        ]);
    }
    
    private function showMaster(int $id): void {
        $material = $this->materialService->getMaterialMaster($id);
        if (!$material) {
            $this->sendError('Material master not found', 404);
        }
        $this->sendSuccess(['material' => $material]);
    }
    
    private function storeMaster(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['name', 'code']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->materialService->createMaterialMaster($data);
        
        if ($result['success']) {
            $this->logAction('create', 'sar_inv_material_masters', $result['material_id'], $data);
            $material = $this->materialService->getMaterialMaster($result['material_id']);
            $this->sendSuccess(['material' => $material], $result['message'], 201);
        } else {
            $this->sendError('Failed to create material master', 400, $result['errors']);
        }
    }
    
    private function updateMaster(int $id): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $result = $this->materialService->updateMaterialMaster($id, $data);
        
        if ($result['success']) {
            $this->logAction('update', 'sar_inv_material_masters', $id, $data);
            $material = $this->materialService->getMaterialMaster($id);
            $this->sendSuccess(['material' => $material], $result['message']);
        } else {
            $this->sendError('Failed to update material master', 400, $result['errors']);
        }
    }
    
    private function deleteMaster(int $id): void {
        $result = $this->materialService->deleteMaterialMaster($id);
        
        if ($result['success']) {
            $this->logAction('delete', 'sar_inv_material_masters', $id);
            $this->sendSuccess(null, $result['message']);
        } else {
            $this->sendError('Failed to delete material master', 400, $result['errors']);
        }
    }
    
    private function searchMasters(): void {
        $keyword = $this->getQuery('q');
        $status = $this->getQuery('status');
        
        $materials = $this->materialService->searchMaterialMasters($keyword, $status);
        $this->sendSuccess(['materials' => $materials]);
    }
    
    private function getActiveMasters(): void {
        $materials = $this->materialService->getActiveMaterialMasters();
        $this->sendSuccess(['materials' => $materials]);
    }
    
    private function getMasterByCode(): void {
        $code = $this->getQuery('code');
        if (empty($code)) {
            $this->sendError('Code required', 400);
        }
        
        $material = $this->materialService->getMaterialMasterByCode($code);
        if (!$material) {
            $this->sendError('Material master not found', 404);
        }
        
        $this->sendSuccess(['material' => $material]);
    }
    
    // Material Request Methods
    private function indexRequests(): void {
        list($page, $perPage) = $this->getPagination();
        $offset = ($page - 1) * $perPage;
        
        $requests = $this->materialService->getAllRequests($perPage, $offset);
        
        $this->sendSuccess([
            'requests' => $requests,
            'total' => count($requests)
        ]);
    }
    
    private function showRequest(int $id): void {
        $request = $this->materialService->getRequestWithDetails($id);
        if (!$request) {
            $this->sendError('Material request not found', 404);
        }
        
        // Include fulfillment progress
        $progress = $this->materialService->getFulfillmentProgress($id);
        $request['fulfillment_progress'] = $progress;
        
        $this->sendSuccess(['request' => $request]);
    }
    
    private function storeRequest(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['quantity']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->materialService->createRequest($data);
        
        if ($result['success']) {
            $this->logAction('create', 'sar_inv_material_requests', $result['request_id'], $data);
            $request = $this->materialService->getRequestWithDetails($result['request_id']);
            $this->sendSuccess(['request' => $request], $result['message'], 201);
        } else {
            $this->sendError('Failed to create material request', 400, $result['errors']);
        }
    }
    
    private function updateRequest(int $id): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        // Handle status-based updates
        if (isset($data['action'])) {
            switch ($data['action']) {
                case 'approve':
                    $result = $this->materialService->approveRequest($id, $this->userId, $data['notes'] ?? null);
                    break;
                case 'reject':
                    $result = $this->materialService->rejectRequest($id, $this->userId, $data['reason'] ?? null);
                    break;
                case 'fulfill':
                    $result = $this->materialService->fulfillRequest(
                        $id,
                        floatval($data['fulfilled_quantity']),
                        isset($data['warehouse_id']) ? intval($data['warehouse_id']) : null,
                        $data['notes'] ?? null
                    );
                    break;
                case 'cancel':
                    $result = $this->materialService->cancelRequest($id, $data['reason'] ?? null);
                    break;
                default:
                    $this->sendError('Invalid action', 400);
                    return;
            }
        } else {
            $this->sendError('Action required', 400);
            return;
        }
        
        if ($result['success']) {
            $this->logAction('update', 'sar_inv_material_requests', $id, $data);
            $request = $this->materialService->getRequest($id);
            $this->sendSuccess(['request' => $request], $result['message']);
        } else {
            $this->sendError('Failed to update request', 400, $result['errors']);
        }
    }
    
    private function searchRequests(): void {
        $keyword = $this->getQuery('q');
        $status = $this->getQuery('status');
        $requesterId = $this->getQueryInt('requester_id') ?: null;
        $dateFrom = $this->getQuery('date_from');
        $dateTo = $this->getQuery('date_to');
        
        $requests = $this->materialService->searchRequests($keyword, $status, $requesterId, $dateFrom, $dateTo);
        $this->sendSuccess(['requests' => $requests]);
    }
    
    private function getRequestsByStatus(): void {
        $status = $this->getQuery('status');
        if (empty($status)) {
            $this->sendError('Status required', 400);
        }
        
        $requests = $this->materialService->getRequestsByStatus($status);
        $this->sendSuccess(['requests' => $requests]);
    }
    
    private function getRequestsByRequester(): void {
        $requesterId = $this->getQueryInt('requester_id');
        $status = $this->getQuery('status');
        
        if (!$requesterId) {
            $this->sendError('Requester ID required', 400);
        }
        
        $requests = $this->materialService->getRequestsByRequester($requesterId, $status);
        $this->sendSuccess(['requests' => $requests]);
    }
    
    private function approveRequest(int $id): void {
        if (!$id) {
            $this->sendError('Request ID required', 400);
        }
        
        $data = $this->getJsonBody();
        $result = $this->materialService->approveRequest($id, $this->userId, $data['notes'] ?? null);
        
        if ($result['success']) {
            $this->logAction('approve', 'sar_inv_material_requests', $id);
            $request = $this->materialService->getRequest($id);
            $this->sendSuccess(['request' => $request], $result['message']);
        } else {
            $this->sendError('Failed to approve request', 400, $result['errors']);
        }
    }
    
    private function rejectRequest(int $id): void {
        if (!$id) {
            $this->sendError('Request ID required', 400);
        }
        
        $data = $this->getJsonBody();
        $result = $this->materialService->rejectRequest($id, $this->userId, $data['reason'] ?? null);
        
        if ($result['success']) {
            $this->logAction('reject', 'sar_inv_material_requests', $id, $data);
            $request = $this->materialService->getRequest($id);
            $this->sendSuccess(['request' => $request], $result['message']);
        } else {
            $this->sendError('Failed to reject request', 400, $result['errors']);
        }
    }
    
    private function fulfillRequest(int $id): void {
        if (!$id) {
            $this->sendError('Request ID required', 400);
        }
        
        $data = $this->getJsonBody();
        if (!isset($data['fulfilled_quantity'])) {
            $this->sendError('Fulfilled quantity required', 400);
        }
        
        $result = $this->materialService->fulfillRequest(
            $id,
            floatval($data['fulfilled_quantity']),
            isset($data['warehouse_id']) ? intval($data['warehouse_id']) : null,
            $data['notes'] ?? null
        );
        
        if ($result['success']) {
            $this->logAction('fulfill', 'sar_inv_material_requests', $id, $data);
            $this->sendSuccess($result, $result['message']);
        } else {
            $this->sendError('Failed to fulfill request', 400, $result['errors']);
        }
    }
    
    private function cancelRequest(int $id): void {
        if (!$id) {
            $this->sendError('Request ID required', 400);
        }
        
        $data = $this->getJsonBody();
        $result = $this->materialService->cancelRequest($id, $data['reason'] ?? null);
        
        if ($result['success']) {
            $this->logAction('cancel', 'sar_inv_material_requests', $id, $data);
            $request = $this->materialService->getRequest($id);
            $this->sendSuccess(['request' => $request], $result['message']);
        } else {
            $this->sendError('Failed to cancel request', 400, $result['errors']);
        }
    }
    
    private function getFulfillmentProgress(int $id): void {
        if (!$id) {
            $this->sendError('Request ID required', 400);
        }
        
        $progress = $this->materialService->getFulfillmentProgress($id);
        if (!$progress) {
            $this->sendError('Request not found', 404);
        }
        
        $this->sendSuccess(['progress' => $progress]);
    }
    
    private function getStatistics(): void {
        $dateFrom = $this->getQuery('date_from');
        $dateTo = $this->getQuery('date_to');
        
        $statistics = $this->materialService->getRequestStatistics($dateFrom, $dateTo);
        $this->sendSuccess(['statistics' => $statistics]);
    }
    
    private function validateStock(): void {
        $productId = $this->getQueryInt('product_id');
        $warehouseId = $this->getQueryInt('warehouse_id');
        $quantity = floatval($this->getQuery('quantity', 0));
        
        if (!$productId || !$warehouseId || $quantity <= 0) {
            $this->sendError('Product ID, warehouse ID, and quantity required', 400);
        }
        
        $result = $this->materialService->validateStockForRequest($productId, $warehouseId, $quantity);
        $this->sendSuccess($result);
    }
}

// Execute API
$api = new MaterialsApi();
$api->handle();
?>
