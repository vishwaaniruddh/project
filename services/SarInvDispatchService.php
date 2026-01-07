<?php
require_once __DIR__ . '/../models/SarInvDispatch.php';
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvWarehouse.php';
require_once __DIR__ . '/../models/SarInvAuditLog.php';

/**
 * SAR Inventory Dispatch Service
 * Business logic for dispatch operations with stock validation and status tracking
 */
class SarInvDispatchService {
    private $dispatchModel;
    private $stockModel;
    private $warehouseModel;
    private $auditLog;
    
    public function __construct() {
        $this->dispatchModel = new SarInvDispatch();
        $this->stockModel = new SarInvStock();
        $this->warehouseModel = new SarInvWarehouse();
        $this->auditLog = new SarInvAuditLog();
    }
    
    /**
     * Create a new dispatch with items
     * @param array $dispatchData Dispatch header data
     * @param array $items Array of items with product_id, quantity, notes
     * @return array Result with success status and dispatch ID
     */
    public function createDispatch(array $dispatchData, array $items): array {
        // Validate dispatch data
        $errors = $this->dispatchModel->validate($dispatchData);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate warehouse exists and is active
        $warehouse = $this->warehouseModel->find($dispatchData['source_warehouse_id']);
        if (!$warehouse) {
            return ['success' => false, 'errors' => ['Source warehouse not found']];
        }
        
        if ($warehouse['status'] !== SarInvWarehouse::STATUS_ACTIVE) {
            return ['success' => false, 'errors' => ['Source warehouse is not active']];
        }
        
        // Validate items
        if (empty($items)) {
            return ['success' => false, 'errors' => ['At least one item is required']];
        }
        
        // Validate stock availability for all items
        $stockErrors = [];
        foreach ($items as $index => $item) {
            if (empty($item['product_id']) || empty($item['quantity'])) {
                $stockErrors[] = "Item " . ($index + 1) . ": Product ID and quantity are required";
                continue;
            }
            
            if ($item['quantity'] <= 0) {
                $stockErrors[] = "Item " . ($index + 1) . ": Quantity must be greater than zero";
                continue;
            }
            
            $available = $this->stockModel->getAvailableQuantity($item['product_id'], $dispatchData['source_warehouse_id']);
            if ($available < $item['quantity']) {
                $stockErrors[] = "Item " . ($index + 1) . ": Insufficient stock. Available: {$available}, Requested: {$item['quantity']}";
            }
        }
        
        if (!empty($stockErrors)) {
            return ['success' => false, 'errors' => $stockErrors];
        }
        
        try {
            $dispatchId = $this->dispatchModel->createDispatch($dispatchData, $items);
            
            if ($dispatchId) {
                $dispatch = $this->dispatchModel->find($dispatchId);
                return [
                    'success' => true,
                    'dispatch_id' => $dispatchId,
                    'dispatch_number' => $dispatch['dispatch_number'],
                    'message' => 'Dispatch created successfully'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to create dispatch']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Update dispatch status
     * @param int $dispatchId Dispatch ID
     * @param string $newStatus New status
     * @return array Result with success status
     */
    public function updateStatus(int $dispatchId, string $newStatus): array {
        $dispatch = $this->dispatchModel->find($dispatchId);
        if (!$dispatch) {
            return ['success' => false, 'errors' => ['Dispatch not found']];
        }
        
        try {
            $result = $this->dispatchModel->updateStatus($dispatchId, $newStatus);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => "Dispatch status updated to {$newStatus}",
                    'previous_status' => $dispatch['status'],
                    'new_status' => $newStatus
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to update dispatch status']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Approve dispatch
     * @param int $dispatchId Dispatch ID
     * @return array Result with success status
     */
    public function approveDispatch(int $dispatchId): array {
        return $this->updateStatus($dispatchId, SarInvDispatch::STATUS_APPROVED);
    }
    
    /**
     * Ship dispatch (reduces stock)
     * @param int $dispatchId Dispatch ID
     * @return array Result with success status
     */
    public function shipDispatch(int $dispatchId): array {
        return $this->updateStatus($dispatchId, SarInvDispatch::STATUS_SHIPPED);
    }
    
    /**
     * Mark dispatch as in transit
     * @param int $dispatchId Dispatch ID
     * @return array Result with success status
     */
    public function markInTransit(int $dispatchId): array {
        return $this->updateStatus($dispatchId, SarInvDispatch::STATUS_IN_TRANSIT);
    }
    
    /**
     * Mark dispatch as delivered
     * @param int $dispatchId Dispatch ID
     * @return array Result with success status
     */
    public function markDelivered(int $dispatchId): array {
        return $this->updateStatus($dispatchId, SarInvDispatch::STATUS_DELIVERED);
    }
    
    /**
     * Cancel dispatch
     * @param int $dispatchId Dispatch ID
     * @return array Result with success status
     */
    public function cancelDispatch(int $dispatchId): array {
        return $this->updateStatus($dispatchId, SarInvDispatch::STATUS_CANCELLED);
    }
    
    /**
     * Add item to existing dispatch
     * @param int $dispatchId Dispatch ID
     * @param int $productId Product ID
     * @param float $quantity Quantity
     * @param string|null $notes Notes
     * @return array Result with success status
     */
    public function addItem(int $dispatchId, int $productId, float $quantity, ?string $notes = null): array {
        $dispatch = $this->dispatchModel->find($dispatchId);
        if (!$dispatch) {
            return ['success' => false, 'errors' => ['Dispatch not found']];
        }
        
        if ($dispatch['status'] !== SarInvDispatch::STATUS_PENDING) {
            return ['success' => false, 'errors' => ['Can only add items to pending dispatches']];
        }
        
        if ($quantity <= 0) {
            return ['success' => false, 'errors' => ['Quantity must be greater than zero']];
        }
        
        // Check stock availability
        $available = $this->stockModel->getAvailableQuantity($productId, $dispatch['source_warehouse_id']);
        if ($available < $quantity) {
            return ['success' => false, 'errors' => ["Insufficient stock. Available: {$available}, Requested: {$quantity}"]];
        }
        
        try {
            $result = $this->dispatchModel->addItem($dispatchId, $productId, $quantity, $notes);
            
            if ($result) {
                return ['success' => true, 'message' => 'Item added successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to add item']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Update item quantity
     * @param int $itemId Item ID
     * @param float $quantity New quantity
     * @return array Result with success status
     */
    public function updateItemQuantity(int $itemId, float $quantity): array {
        if ($quantity <= 0) {
            return ['success' => false, 'errors' => ['Quantity must be greater than zero']];
        }
        
        try {
            $result = $this->dispatchModel->updateItemQuantity($itemId, $quantity);
            
            if ($result) {
                return ['success' => true, 'message' => 'Item quantity updated successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to update item quantity']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Remove item from dispatch
     * @param int $itemId Item ID
     * @return array Result with success status
     */
    public function removeItem(int $itemId): array {
        try {
            $result = $this->dispatchModel->removeItem($itemId);
            
            if ($result) {
                return ['success' => true, 'message' => 'Item removed successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to remove item']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Record item receipt
     * @param int $itemId Item ID
     * @param float $receivedQuantity Received quantity
     * @return array Result with success status
     */
    public function recordItemReceipt(int $itemId, float $receivedQuantity): array {
        if ($receivedQuantity < 0) {
            return ['success' => false, 'errors' => ['Received quantity cannot be negative']];
        }
        
        try {
            $result = $this->dispatchModel->recordItemReceipt($itemId, $receivedQuantity);
            
            if ($result) {
                return ['success' => true, 'message' => 'Item receipt recorded successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to record item receipt']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Get dispatch by ID
     * @param int $id Dispatch ID
     * @return array|null Dispatch data
     */
    public function getDispatch(int $id): ?array {
        $dispatch = $this->dispatchModel->find($id);
        return $dispatch ?: null;
    }
    
    /**
     * Get dispatch with full details
     * @param int $id Dispatch ID
     * @return array|null Dispatch with items and warehouse info
     */
    public function getDispatchWithDetails(int $id): ?array {
        $dispatch = $this->dispatchModel->getWithDetails($id);
        return $dispatch ?: null;
    }
    
    /**
     * Get dispatch by number
     * @param string $dispatchNumber Dispatch number
     * @return array|null Dispatch data
     */
    public function getDispatchByNumber(string $dispatchNumber): ?array {
        $dispatch = $this->dispatchModel->findByNumber($dispatchNumber);
        return $dispatch ?: null;
    }
    
    /**
     * Get dispatch items
     * @param int $dispatchId Dispatch ID
     * @return array Items
     */
    public function getDispatchItems(int $dispatchId): array {
        return $this->dispatchModel->getItems($dispatchId);
    }
    
    /**
     * Get dispatches by status
     * @param string $status Status filter
     * @return array Dispatches
     */
    public function getDispatchesByStatus(string $status): array {
        return $this->dispatchModel->getByStatus($status);
    }
    
    /**
     * Get dispatches by warehouse
     * @param int $warehouseId Warehouse ID
     * @param string|null $status Optional status filter
     * @return array Dispatches
     */
    public function getDispatchesByWarehouse(int $warehouseId, ?string $status = null): array {
        return $this->dispatchModel->getByWarehouse($warehouseId, $status);
    }
    
    /**
     * Search dispatches
     * @param string|null $keyword Search keyword
     * @param string|null $status Status filter
     * @param int|null $warehouseId Warehouse filter
     * @param string|null $dateFrom Date from filter
     * @param string|null $dateTo Date to filter
     * @return array Dispatches
     */
    public function searchDispatches(?string $keyword = null, ?string $status = null, ?int $warehouseId = null, ?string $dateFrom = null, ?string $dateTo = null): array {
        return $this->dispatchModel->search($keyword, $status, $warehouseId, $dateFrom, $dateTo);
    }
    
    /**
     * Get pending dispatches count
     * @return int Count
     */
    public function getPendingCount(): int {
        $dispatches = $this->dispatchModel->getByStatus(SarInvDispatch::STATUS_PENDING);
        return count($dispatches);
    }
    
    /**
     * Get dispatch audit history
     * @param int $dispatchId Dispatch ID
     * @param int $limit Number of records
     * @return array Audit log entries
     */
    public function getAuditHistory(int $dispatchId, int $limit = 50): array {
        return $this->auditLog->getLogsForRecord('sar_inv_dispatches', $dispatchId, $limit);
    }
    
    /**
     * Get valid status transitions for a dispatch
     * @param int $dispatchId Dispatch ID
     * @return array Valid next statuses
     */
    public function getValidStatusTransitions(int $dispatchId): array {
        $dispatch = $this->dispatchModel->find($dispatchId);
        if (!$dispatch) {
            return [];
        }
        
        $transitions = [
            SarInvDispatch::STATUS_PENDING => [SarInvDispatch::STATUS_APPROVED, SarInvDispatch::STATUS_CANCELLED],
            SarInvDispatch::STATUS_APPROVED => [SarInvDispatch::STATUS_SHIPPED, SarInvDispatch::STATUS_CANCELLED],
            SarInvDispatch::STATUS_SHIPPED => [SarInvDispatch::STATUS_IN_TRANSIT, SarInvDispatch::STATUS_DELIVERED],
            SarInvDispatch::STATUS_IN_TRANSIT => [SarInvDispatch::STATUS_DELIVERED],
            SarInvDispatch::STATUS_DELIVERED => [],
            SarInvDispatch::STATUS_CANCELLED => []
        ];
        
        return $transitions[$dispatch['status']] ?? [];
    }
}
?>
