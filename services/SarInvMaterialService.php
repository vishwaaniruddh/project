<?php
require_once __DIR__ . '/../models/SarInvMaterialMaster.php';
require_once __DIR__ . '/../models/SarInvMaterialRequest.php';
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvAuditLog.php';

/**
 * SAR Inventory Material Service
 * Business logic for material master and request management with fulfillment tracking
 */
class SarInvMaterialService {
    private $materialMasterModel;
    private $materialRequestModel;
    private $stockModel;
    private $auditLog;
    
    public function __construct() {
        $this->materialMasterModel = new SarInvMaterialMaster();
        $this->materialRequestModel = new SarInvMaterialRequest();
        $this->stockModel = new SarInvStock();
        $this->auditLog = new SarInvAuditLog();
    }
    
    // ==================== MATERIAL MASTER OPERATIONS ====================
    
    /**
     * Create a new material master
     * @param array $data Material master data
     * @return array Result with success status and material ID
     */
    public function createMaterialMaster(array $data): array {
        $errors = $this->materialMasterModel->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $materialId = $this->materialMasterModel->create($data);
            
            if ($materialId) {
                return [
                    'success' => true,
                    'material_id' => $materialId,
                    'message' => 'Material master created successfully'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to create material master']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Update material master
     * @param int $id Material master ID
     * @param array $data Updated data
     * @return array Result with success status
     */
    public function updateMaterialMaster(int $id, array $data): array {
        $material = $this->materialMasterModel->find($id);
        if (!$material) {
            return ['success' => false, 'errors' => ['Material master not found']];
        }
        
        $errors = $this->materialMasterModel->validate($data, true, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $result = $this->materialMasterModel->update($id, $data);
            
            if ($result) {
                return ['success' => true, 'message' => 'Material master updated successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to update material master']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Delete material master
     * @param int $id Material master ID
     * @return array Result with success status
     */
    public function deleteMaterialMaster(int $id): array {
        $material = $this->materialMasterModel->find($id);
        if (!$material) {
            return ['success' => false, 'errors' => ['Material master not found']];
        }
        
        if ($this->materialMasterModel->hasRequests($id)) {
            return [
                'success' => false,
                'errors' => ['Cannot delete material master with existing requests']
            ];
        }
        
        try {
            $result = $this->materialMasterModel->delete($id);
            
            if ($result) {
                return ['success' => true, 'message' => 'Material master deleted successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to delete material master']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Get material master by ID
     */
    public function getMaterialMaster(int $id): ?array {
        $material = $this->materialMasterModel->find($id);
        return $material ?: null;
    }
    
    /**
     * Get material master by code
     */
    public function getMaterialMasterByCode(string $code): ?array {
        $material = $this->materialMasterModel->findByCode($code);
        return $material ?: null;
    }
    
    /**
     * Get all material masters
     */
    public function getAllMaterialMasters(): array {
        return $this->materialMasterModel->findAll();
    }
    
    /**
     * Get active material masters
     */
    public function getActiveMaterialMasters(): array {
        return $this->materialMasterModel->getActiveMaterials();
    }
    
    /**
     * Search material masters
     */
    public function searchMaterialMasters(?string $keyword = null, ?string $status = null): array {
        return $this->materialMasterModel->search($keyword, $status);
    }

    // ==================== MATERIAL REQUEST OPERATIONS ====================
    
    /**
     * Create a new material request
     * @param array $data Request data
     * @return array Result with success status and request ID
     */
    public function createRequest(array $data): array {
        $errors = $this->materialRequestModel->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate material master if provided
        if (!empty($data['material_master_id'])) {
            $validation = $this->materialRequestModel->validateAgainstMaterialMaster($data['material_master_id']);
            if (!$validation['valid']) {
                return ['success' => false, 'errors' => [$validation['error']]];
            }
        }
        
        try {
            $requestId = $this->materialRequestModel->create($data);
            
            if ($requestId) {
                $request = $this->materialRequestModel->find($requestId);
                return [
                    'success' => true,
                    'request_id' => $requestId,
                    'request_number' => $request['request_number'],
                    'message' => 'Material request created successfully'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to create material request']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Approve material request
     * @param int $requestId Request ID
     * @param int|null $approverId Approver user ID
     * @param string|null $notes Approval notes
     * @return array Result with success status
     */
    public function approveRequest(int $requestId, ?int $approverId = null, ?string $notes = null): array {
        $request = $this->materialRequestModel->find($requestId);
        if (!$request) {
            return ['success' => false, 'errors' => ['Material request not found']];
        }
        
        if ($request['status'] !== SarInvMaterialRequest::STATUS_PENDING) {
            return ['success' => false, 'errors' => ['Only pending requests can be approved']];
        }
        
        try {
            $result = $this->materialRequestModel->approve($requestId, $approverId, $notes);
            
            if ($result) {
                return ['success' => true, 'message' => 'Material request approved successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to approve material request']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Reject material request
     * @param int $requestId Request ID
     * @param int|null $approverId Approver user ID
     * @param string|null $reason Rejection reason
     * @return array Result with success status
     */
    public function rejectRequest(int $requestId, ?int $approverId = null, ?string $reason = null): array {
        $request = $this->materialRequestModel->find($requestId);
        if (!$request) {
            return ['success' => false, 'errors' => ['Material request not found']];
        }
        
        if ($request['status'] !== SarInvMaterialRequest::STATUS_PENDING) {
            return ['success' => false, 'errors' => ['Only pending requests can be rejected']];
        }
        
        try {
            $result = $this->materialRequestModel->reject($requestId, $approverId, $reason);
            
            if ($result) {
                return ['success' => true, 'message' => 'Material request rejected'];
            }
            
            return ['success' => false, 'errors' => ['Failed to reject material request']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Fulfill material request (full or partial)
     * @param int $requestId Request ID
     * @param float $fulfilledQuantity Quantity fulfilled
     * @param int|null $warehouseId Source warehouse ID
     * @param string|null $notes Fulfillment notes
     * @return array Result with success status
     */
    public function fulfillRequest(int $requestId, float $fulfilledQuantity, ?int $warehouseId = null, ?string $notes = null): array {
        $request = $this->materialRequestModel->getWithDetails($requestId);
        if (!$request) {
            return ['success' => false, 'errors' => ['Material request not found']];
        }
        
        if (!in_array($request['status'], [SarInvMaterialRequest::STATUS_APPROVED, SarInvMaterialRequest::STATUS_PARTIALLY_FULFILLED])) {
            return ['success' => false, 'errors' => ['Only approved or partially fulfilled requests can be fulfilled']];
        }
        
        if ($fulfilledQuantity <= 0) {
            return ['success' => false, 'errors' => ['Fulfilled quantity must be greater than zero']];
        }
        
        // Check if fulfillment would exceed requested quantity
        $remaining = floatval($request['quantity']) - floatval($request['fulfilled_quantity']);
        if ($fulfilledQuantity > $remaining) {
            return ['success' => false, 'errors' => ["Fulfilled quantity cannot exceed remaining quantity ({$remaining})"]];
        }
        
        // If warehouse specified and product linked, validate stock
        if ($warehouseId && $request['product_id']) {
            $stockValidation = $this->materialRequestModel->validateAgainstStock($request['product_id'], $warehouseId, $fulfilledQuantity);
            if (!$stockValidation['available']) {
                return [
                    'success' => false,
                    'errors' => ["Insufficient stock. Available: {$stockValidation['available_quantity']}, Requested: {$fulfilledQuantity}"]
                ];
            }
        }
        
        try {
            // Reduce stock if warehouse and product specified
            if ($warehouseId && $request['product_id']) {
                $this->stockModel->removeStock(
                    $request['product_id'],
                    $warehouseId,
                    $fulfilledQuantity,
                    'material_request',
                    $requestId,
                    "Material request #{$request['request_number']} fulfillment"
                );
            }
            
            $result = $this->materialRequestModel->fulfill($requestId, $fulfilledQuantity, $notes);
            
            if ($result) {
                $updatedRequest = $this->materialRequestModel->find($requestId);
                return [
                    'success' => true,
                    'message' => 'Material request fulfilled successfully',
                    'status' => $updatedRequest['status'],
                    'fulfilled_quantity' => $updatedRequest['fulfilled_quantity']
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to fulfill material request']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Cancel material request
     * @param int $requestId Request ID
     * @param string|null $reason Cancellation reason
     * @return array Result with success status
     */
    public function cancelRequest(int $requestId, ?string $reason = null): array {
        $request = $this->materialRequestModel->find($requestId);
        if (!$request) {
            return ['success' => false, 'errors' => ['Material request not found']];
        }
        
        if (in_array($request['status'], [SarInvMaterialRequest::STATUS_FULFILLED, SarInvMaterialRequest::STATUS_CANCELLED])) {
            return ['success' => false, 'errors' => ['Cannot cancel fulfilled or already cancelled requests']];
        }
        
        try {
            $result = $this->materialRequestModel->cancel($requestId, $reason);
            
            if ($result) {
                return ['success' => true, 'message' => 'Material request cancelled'];
            }
            
            return ['success' => false, 'errors' => ['Failed to cancel material request']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Get material request by ID
     */
    public function getRequest(int $id): ?array {
        $request = $this->materialRequestModel->find($id);
        return $request ?: null;
    }
    
    /**
     * Get material request with details
     */
    public function getRequestWithDetails(int $id): ?array {
        $request = $this->materialRequestModel->getWithDetails($id);
        return $request ?: null;
    }
    
    /**
     * Get material request by number
     */
    public function getRequestByNumber(string $requestNumber): ?array {
        $request = $this->materialRequestModel->findByRequestNumber($requestNumber);
        return $request ?: null;
    }
    
    /**
     * Get requests by status
     */
    public function getRequestsByStatus(string $status, ?int $limit = null, ?int $offset = null): array {
        return $this->materialRequestModel->getByStatus($status, $limit, $offset);
    }
    
    /**
     * Get requests by requester
     */
    public function getRequestsByRequester(int $requesterId, ?string $status = null): array {
        return $this->materialRequestModel->getByRequester($requesterId, $status);
    }
    
    /**
     * Search material requests
     */
    public function searchRequests(?string $keyword = null, ?string $status = null, ?int $requesterId = null, ?string $dateFrom = null, ?string $dateTo = null, int $limit = 100, int $offset = 0): array {
        return $this->materialRequestModel->search($keyword, $status, $requesterId, $dateFrom, $dateTo, $limit, $offset);
    }
    
    /**
     * Get all requests with details
     */
    public function getAllRequests(int $limit = 100, int $offset = 0): array {
        return $this->materialRequestModel->getAllWithDetails($limit, $offset);
    }
    
    /**
     * Get pending requests count
     */
    public function getPendingRequestsCount(): int {
        return $this->materialRequestModel->getPendingCount();
    }
    
    /**
     * Get fulfillment progress for a request
     */
    public function getFulfillmentProgress(int $requestId): ?array {
        return $this->materialRequestModel->getFulfillmentProgress($requestId);
    }
    
    /**
     * Get request statistics
     */
    public function getRequestStatistics(?string $dateFrom = null, ?string $dateTo = null): array {
        return $this->materialRequestModel->getStatistics($dateFrom, $dateTo);
    }
    
    /**
     * Validate stock availability for request
     */
    public function validateStockForRequest(int $productId, int $warehouseId, float $quantity): array {
        return $this->materialRequestModel->validateAgainstStock($productId, $warehouseId, $quantity);
    }
    
    /**
     * Get material master audit history
     */
    public function getMaterialMasterAuditHistory(int $materialId, int $limit = 50): array {
        return $this->auditLog->getLogsForRecord('sar_inv_material_masters', $materialId, $limit);
    }
    
    /**
     * Get material request audit history
     */
    public function getRequestAuditHistory(int $requestId, int $limit = 50): array {
        return $this->auditLog->getLogsForRecord('sar_inv_material_requests', $requestId, $limit);
    }
}
?>
