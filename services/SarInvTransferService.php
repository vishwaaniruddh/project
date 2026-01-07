<?php
require_once __DIR__ . '/../models/SarInvTransfer.php';
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvWarehouse.php';
require_once __DIR__ . '/../models/SarInvAuditLog.php';

/**
 * SAR Inventory Transfer Service
 * Business logic for inter-warehouse transfers with approval workflow and stock conservation
 */
class SarInvTransferService {
    private $transferModel;
    private $stockModel;
    private $warehouseModel;
    private $auditLog;
    
    public function __construct() {
        $this->transferModel = new SarInvTransfer();
        $this->stockModel = new SarInvStock();
        $this->warehouseModel = new SarInvWarehouse();
        $this->auditLog = new SarInvAuditLog();
    }
    
    /**
     * Create a new transfer with items
     * @param array $transferData Transfer header data
     * @param array $items Array of items with product_id, quantity, notes
     * @return array Result with success status and transfer ID
     */
    public function createTransfer(array $transferData, array $items): array {
        // Validate transfer data
        $errors = $this->transferModel->validate($transferData);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Validate source warehouse
        $sourceWarehouse = $this->warehouseModel->find($transferData['source_warehouse_id']);
        if (!$sourceWarehouse) {
            return ['success' => false, 'errors' => ['Source warehouse not found']];
        }
        
        if ($sourceWarehouse['status'] !== SarInvWarehouse::STATUS_ACTIVE) {
            return ['success' => false, 'errors' => ['Source warehouse is not active']];
        }
        
        // Validate destination warehouse
        $destWarehouse = $this->warehouseModel->find($transferData['destination_warehouse_id']);
        if (!$destWarehouse) {
            return ['success' => false, 'errors' => ['Destination warehouse not found']];
        }
        
        if ($destWarehouse['status'] !== SarInvWarehouse::STATUS_ACTIVE) {
            return ['success' => false, 'errors' => ['Destination warehouse is not active']];
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
            
            $available = $this->stockModel->getAvailableQuantity($item['product_id'], $transferData['source_warehouse_id']);
            if ($available < $item['quantity']) {
                $stockErrors[] = "Item " . ($index + 1) . ": Insufficient stock. Available: {$available}, Requested: {$item['quantity']}";
            }
        }
        
        if (!empty($stockErrors)) {
            return ['success' => false, 'errors' => $stockErrors];
        }
        
        try {
            $transferId = $this->transferModel->createTransfer($transferData, $items);
            
            if ($transferId) {
                $transfer = $this->transferModel->find($transferId);
                return [
                    'success' => true,
                    'transfer_id' => $transferId,
                    'transfer_number' => $transfer['transfer_number'],
                    'message' => 'Transfer created successfully'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to create transfer']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Approve transfer (reserves stock at source)
     * @param int $transferId Transfer ID
     * @param int|null $approverId Approver user ID
     * @return array Result with success status
     */
    public function approveTransfer(int $transferId, ?int $approverId = null): array {
        $transfer = $this->transferModel->find($transferId);
        if (!$transfer) {
            return ['success' => false, 'errors' => ['Transfer not found']];
        }
        
        if ($transfer['status'] !== SarInvTransfer::STATUS_PENDING) {
            return ['success' => false, 'errors' => ['Only pending transfers can be approved']];
        }
        
        // Re-validate stock availability before approval
        $items = $this->transferModel->getItems($transferId);
        foreach ($items as $item) {
            $available = $this->stockModel->getAvailableQuantity($item['product_id'], $transfer['source_warehouse_id']);
            if ($available < $item['quantity']) {
                return [
                    'success' => false,
                    'errors' => ["Insufficient stock for {$item['product_name']}. Available: {$available}, Required: {$item['quantity']}"]
                ];
            }
        }
        
        try {
            $result = $this->transferModel->approve($transferId, $approverId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Transfer approved successfully. Stock has been reserved.'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to approve transfer']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Ship transfer (moves stock from source)
     * @param int $transferId Transfer ID
     * @return array Result with success status
     */
    public function shipTransfer(int $transferId): array {
        $transfer = $this->transferModel->find($transferId);
        if (!$transfer) {
            return ['success' => false, 'errors' => ['Transfer not found']];
        }
        
        if ($transfer['status'] !== SarInvTransfer::STATUS_APPROVED) {
            return ['success' => false, 'errors' => ['Only approved transfers can be shipped']];
        }
        
        try {
            $result = $this->transferModel->ship($transferId);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Transfer shipped successfully. Stock has been removed from source warehouse.'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to ship transfer']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Receive transfer (adds stock to destination)
     * @param int $transferId Transfer ID
     * @param array|null $receivedItems Optional array of item_id => received_quantity
     * @return array Result with success status
     */
    public function receiveTransfer(int $transferId, ?array $receivedItems = null): array {
        $transfer = $this->transferModel->find($transferId);
        if (!$transfer) {
            return ['success' => false, 'errors' => ['Transfer not found']];
        }
        
        if (!in_array($transfer['status'], [SarInvTransfer::STATUS_APPROVED, SarInvTransfer::STATUS_IN_TRANSIT])) {
            return ['success' => false, 'errors' => ['Only approved or in-transit transfers can be received']];
        }
        
        try {
            $result = $this->transferModel->receive($transferId, $receivedItems);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Transfer received successfully. Stock has been added to destination warehouse.'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to receive transfer']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Cancel transfer
     * @param int $transferId Transfer ID
     * @param string|null $reason Cancellation reason
     * @return array Result with success status
     */
    public function cancelTransfer(int $transferId, ?string $reason = null): array {
        $transfer = $this->transferModel->find($transferId);
        if (!$transfer) {
            return ['success' => false, 'errors' => ['Transfer not found']];
        }
        
        if (in_array($transfer['status'], [SarInvTransfer::STATUS_RECEIVED, SarInvTransfer::STATUS_CANCELLED])) {
            return ['success' => false, 'errors' => ['Cannot cancel completed or already cancelled transfers']];
        }
        
        try {
            $result = $this->transferModel->cancel($transferId, $reason);
            
            if ($result) {
                $message = 'Transfer cancelled successfully.';
                if ($transfer['status'] === SarInvTransfer::STATUS_APPROVED) {
                    $message .= ' Reserved stock has been released.';
                }
                return ['success' => true, 'message' => $message];
            }
            
            return ['success' => false, 'errors' => ['Failed to cancel transfer']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Add item to existing transfer
     * @param int $transferId Transfer ID
     * @param int $productId Product ID
     * @param float $quantity Quantity
     * @param string|null $notes Notes
     * @return array Result with success status
     */
    public function addItem(int $transferId, int $productId, float $quantity, ?string $notes = null): array {
        $transfer = $this->transferModel->find($transferId);
        if (!$transfer) {
            return ['success' => false, 'errors' => ['Transfer not found']];
        }
        
        if ($transfer['status'] !== SarInvTransfer::STATUS_PENDING) {
            return ['success' => false, 'errors' => ['Can only add items to pending transfers']];
        }
        
        if ($quantity <= 0) {
            return ['success' => false, 'errors' => ['Quantity must be greater than zero']];
        }
        
        // Check stock availability
        $available = $this->stockModel->getAvailableQuantity($productId, $transfer['source_warehouse_id']);
        if ($available < $quantity) {
            return ['success' => false, 'errors' => ["Insufficient stock. Available: {$available}, Requested: {$quantity}"]];
        }
        
        try {
            $result = $this->transferModel->addItem($transferId, $productId, $quantity, $notes);
            
            if ($result) {
                return ['success' => true, 'message' => 'Item added successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to add item']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Get transfer by ID
     * @param int $id Transfer ID
     * @return array|null Transfer data
     */
    public function getTransfer(int $id): ?array {
        $transfer = $this->transferModel->find($id);
        return $transfer ?: null;
    }
    
    /**
     * Get transfer with full details
     * @param int $id Transfer ID
     * @return array|null Transfer with items and warehouse info
     */
    public function getTransferWithDetails(int $id): ?array {
        $transfer = $this->transferModel->getWithDetails($id);
        return $transfer ?: null;
    }
    
    /**
     * Get transfer by number
     * @param string $transferNumber Transfer number
     * @return array|null Transfer data
     */
    public function getTransferByNumber(string $transferNumber): ?array {
        $transfer = $this->transferModel->findByNumber($transferNumber);
        return $transfer ?: null;
    }
    
    /**
     * Get transfer items
     * @param int $transferId Transfer ID
     * @return array Items
     */
    public function getTransferItems(int $transferId): array {
        return $this->transferModel->getItems($transferId);
    }
    
    /**
     * Get transfers by status
     * @param string $status Status filter
     * @return array Transfers
     */
    public function getTransfersByStatus(string $status): array {
        return $this->transferModel->getByStatus($status);
    }
    
    /**
     * Search transfers
     * @param string|null $keyword Search keyword
     * @param string|null $status Status filter
     * @param int|null $sourceWarehouseId Source warehouse filter
     * @param int|null $destWarehouseId Destination warehouse filter
     * @param string|null $dateFrom Date from filter
     * @param string|null $dateTo Date to filter
     * @return array Transfers
     */
    public function searchTransfers(?string $keyword = null, ?string $status = null, ?int $sourceWarehouseId = null, ?int $destWarehouseId = null, ?string $dateFrom = null, ?string $dateTo = null): array {
        return $this->transferModel->search($keyword, $status, $sourceWarehouseId, $destWarehouseId, $dateFrom, $dateTo);
    }
    
    /**
     * Get pending transfers count
     * @return int Count
     */
    public function getPendingCount(): int {
        $transfers = $this->transferModel->getByStatus(SarInvTransfer::STATUS_PENDING);
        return count($transfers);
    }
    
    /**
     * Get transfer audit history
     * @param int $transferId Transfer ID
     * @param int $limit Number of records
     * @return array Audit log entries
     */
    public function getAuditHistory(int $transferId, int $limit = 50): array {
        return $this->auditLog->getLogsForRecord('sar_inv_transfers', $transferId, $limit);
    }
    
    /**
     * Get valid status transitions for a transfer
     * @param int $transferId Transfer ID
     * @return array Valid next statuses
     */
    public function getValidStatusTransitions(int $transferId): array {
        $transfer = $this->transferModel->find($transferId);
        if (!$transfer) {
            return [];
        }
        
        $transitions = [
            SarInvTransfer::STATUS_PENDING => [SarInvTransfer::STATUS_APPROVED, SarInvTransfer::STATUS_CANCELLED],
            SarInvTransfer::STATUS_APPROVED => [SarInvTransfer::STATUS_IN_TRANSIT, SarInvTransfer::STATUS_RECEIVED, SarInvTransfer::STATUS_CANCELLED],
            SarInvTransfer::STATUS_IN_TRANSIT => [SarInvTransfer::STATUS_RECEIVED],
            SarInvTransfer::STATUS_RECEIVED => [],
            SarInvTransfer::STATUS_CANCELLED => []
        ];
        
        return $transitions[$transfer['status']] ?? [];
    }
    
    /**
     * Verify stock conservation for a transfer
     * @param int $transferId Transfer ID
     * @return array Verification result
     */
    public function verifyStockConservation(int $transferId): array {
        $transfer = $this->transferModel->getWithDetails($transferId);
        if (!$transfer) {
            return ['verified' => false, 'error' => 'Transfer not found'];
        }
        
        if ($transfer['status'] !== SarInvTransfer::STATUS_RECEIVED) {
            return ['verified' => false, 'error' => 'Transfer not yet completed'];
        }
        
        $discrepancies = [];
        foreach ($transfer['items'] as $item) {
            if ($item['quantity'] != $item['received_quantity']) {
                $discrepancies[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'sent' => $item['quantity'],
                    'received' => $item['received_quantity'],
                    'difference' => $item['quantity'] - $item['received_quantity']
                ];
            }
        }
        
        return [
            'verified' => empty($discrepancies),
            'discrepancies' => $discrepancies
        ];
    }
}
?>
