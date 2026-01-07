<?php
require_once __DIR__ . '/SarInvBaseModel.php';
require_once __DIR__ . '/SarInvStock.php';

/**
 * SAR Inventory Dispatch Model
 * Manages dispatch operations with stock validation and status tracking
 */
class SarInvDispatch extends SarInvBaseModel {
    protected $table = 'sar_inv_dispatches';
    protected $itemsTable = 'sar_inv_dispatch_items';
    protected $enableCompanyIsolation = false;
    
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    
    const DEST_WAREHOUSE = 'warehouse';
    const DEST_SITE = 'site';
    const DEST_VENDOR = 'vendor';
    const DEST_CUSTOMER = 'customer';
    const DEST_OTHER = 'other';
    
    /**
     * Validate dispatch data
     */
    public function validate($data, $isUpdate = false, $id = null) {
        $errors = [];
        
        if (empty($data['source_warehouse_id'])) {
            $errors[] = 'Source warehouse is required';
        }
        
        if (empty($data['destination_type'])) {
            $errors[] = 'Destination type is required';
        } elseif (!in_array($data['destination_type'], [self::DEST_WAREHOUSE, self::DEST_SITE, self::DEST_VENDOR, self::DEST_CUSTOMER, self::DEST_OTHER])) {
            $errors[] = 'Invalid destination type';
        }
        
        if (isset($data['status']) && !in_array($data['status'], [
            self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_SHIPPED, 
            self::STATUS_IN_TRANSIT, self::STATUS_DELIVERED, self::STATUS_CANCELLED
        ])) {
            $errors[] = 'Invalid status value';
        }
        
        return $errors;
    }
    
    /**
     * Generate unique dispatch number
     */
    public function generateDispatchNumber() {
        $prefix = 'DSP';
        $date = date('Ymd');
        
        $sql = "SELECT MAX(CAST(SUBSTRING(dispatch_number, 12) AS UNSIGNED)) as max_num 
                FROM {$this->table} 
                WHERE dispatch_number LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix . $date . '%']);
        $result = $stmt->fetch();
        
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return $prefix . $date . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create dispatch with items
     */
    public function createDispatch($data, $items = []) {
        $this->beginTransaction();
        
        try {
            // Generate dispatch number if not provided
            if (empty($data['dispatch_number'])) {
                $data['dispatch_number'] = $this->generateDispatchNumber();
            }
            
            // Set default status
            if (empty($data['status'])) {
                $data['status'] = self::STATUS_PENDING;
            }
            
            // Set created_by
            $data['created_by'] = $this->getCurrentUserId();
            
            // Validate stock availability for all items
            $stockModel = new SarInvStock();
            foreach ($items as $item) {
                if (!$stockModel->hasSufficientStock($item['product_id'], $data['source_warehouse_id'], $item['quantity'])) {
                    throw new Exception("Insufficient stock for product ID: {$item['product_id']}");
                }
            }
            
            // Create dispatch
            $dispatchId = parent::create($data);
            
            if (!$dispatchId) {
                throw new Exception('Failed to create dispatch');
            }
            
            // Add items
            foreach ($items as $item) {
                $this->addItem($dispatchId, $item['product_id'], $item['quantity'], $item['notes'] ?? null);
            }
            
            $this->commit();
            return $dispatchId;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Add item to dispatch
     */
    public function addItem($dispatchId, $productId, $quantity, $notes = null) {
        $sql = "INSERT INTO {$this->itemsTable} (dispatch_id, product_id, quantity, notes, status) 
                VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$dispatchId, $productId, $quantity, $notes]);
    }
    
    /**
     * Get dispatch items
     */
    public function getItems($dispatchId) {
        $sql = "SELECT di.*, p.name as product_name, p.sku, p.unit_of_measure
                FROM {$this->itemsTable} di
                JOIN sar_inv_products p ON di.product_id = p.id
                WHERE di.dispatch_id = ?
                ORDER BY p.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dispatchId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Update item quantity
     */
    public function updateItemQuantity($itemId, $quantity) {
        $sql = "UPDATE {$this->itemsTable} SET quantity = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$quantity, $itemId]);
    }
    
    /**
     * Remove item from dispatch
     */
    public function removeItem($itemId) {
        $sql = "DELETE FROM {$this->itemsTable} WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$itemId]);
    }
    
    /**
     * Update dispatch status
     */
    public function updateStatus($dispatchId, $newStatus) {
        $dispatch = $this->find($dispatchId);
        if (!$dispatch) {
            throw new Exception('Dispatch not found');
        }
        
        $validTransitions = $this->getValidStatusTransitions($dispatch['status']);
        if (!in_array($newStatus, $validTransitions)) {
            throw new Exception("Invalid status transition from {$dispatch['status']} to {$newStatus}");
        }
        
        $this->beginTransaction();
        
        try {
            $updateData = ['status' => $newStatus];
            
            // Handle stock operations based on status change
            if ($newStatus === self::STATUS_SHIPPED && $dispatch['status'] !== self::STATUS_SHIPPED) {
                // Reduce stock when shipped
                $this->processStockReduction($dispatchId);
                $updateData['dispatch_date'] = date('Y-m-d');
            }
            
            if ($newStatus === self::STATUS_DELIVERED) {
                $updateData['received_date'] = date('Y-m-d');
                // Update item statuses
                $this->updateItemStatuses($dispatchId, 'received');
            }
            
            if ($newStatus === self::STATUS_CANCELLED) {
                // Release any reserved stock
                $this->releaseReservedStock($dispatchId);
            }
            
            $result = $this->update($dispatchId, $updateData);
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Get valid status transitions
     */
    protected function getValidStatusTransitions($currentStatus) {
        $transitions = [
            self::STATUS_PENDING => [self::STATUS_APPROVED, self::STATUS_CANCELLED],
            self::STATUS_APPROVED => [self::STATUS_SHIPPED, self::STATUS_CANCELLED],
            self::STATUS_SHIPPED => [self::STATUS_IN_TRANSIT, self::STATUS_DELIVERED],
            self::STATUS_IN_TRANSIT => [self::STATUS_DELIVERED],
            self::STATUS_DELIVERED => [],
            self::STATUS_CANCELLED => []
        ];
        
        return $transitions[$currentStatus] ?? [];
    }
    
    /**
     * Process stock reduction when dispatch is shipped
     */
    protected function processStockReduction($dispatchId) {
        $dispatch = $this->find($dispatchId);
        $items = $this->getItems($dispatchId);
        $stockModel = new SarInvStock();
        
        foreach ($items as $item) {
            $stockModel->removeStock(
                $item['product_id'],
                $dispatch['source_warehouse_id'],
                $item['quantity'],
                'dispatch',
                $dispatchId,
                "Dispatch #{$dispatch['dispatch_number']}"
            );
        }
    }
    
    /**
     * Release reserved stock when dispatch is cancelled
     */
    protected function releaseReservedStock($dispatchId) {
        $dispatch = $this->find($dispatchId);
        $items = $this->getItems($dispatchId);
        $stockModel = new SarInvStock();
        
        foreach ($items as $item) {
            if ($item['status'] === 'pending') {
                try {
                    $stockModel->release(
                        $item['product_id'],
                        $dispatch['source_warehouse_id'],
                        $item['quantity'],
                        'dispatch_cancel',
                        $dispatchId,
                        "Dispatch #{$dispatch['dispatch_number']} cancelled"
                    );
                } catch (Exception $e) {
                    // Stock may not have been reserved yet
                }
            }
        }
    }
    
    /**
     * Update all item statuses
     */
    protected function updateItemStatuses($dispatchId, $status) {
        $sql = "UPDATE {$this->itemsTable} SET status = ? WHERE dispatch_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $dispatchId]);
    }
    
    /**
     * Get dispatch with full details
     */
    public function getWithDetails($dispatchId) {
        $dispatch = $this->find($dispatchId);
        if (!$dispatch) {
            return null;
        }
        
        // Get warehouse info
        $sql = "SELECT name, code FROM sar_inv_warehouses WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dispatch['source_warehouse_id']]);
        $warehouse = $stmt->fetch();
        
        $dispatch['source_warehouse_name'] = $warehouse['name'] ?? '';
        $dispatch['source_warehouse_code'] = $warehouse['code'] ?? '';
        $dispatch['items'] = $this->getItems($dispatchId);
        
        return $dispatch;
    }
    
    /**
     * Get dispatches by status
     */
    public function getByStatus($status) {
        $sql = "SELECT d.*, w.name as source_warehouse_name
                FROM {$this->table} d
                JOIN sar_inv_warehouses w ON d.source_warehouse_id = w.id
                WHERE d.status = ?
                ORDER BY d.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get dispatches by warehouse
     */
    public function getByWarehouse($warehouseId, $status = null) {
        $sql = "SELECT d.*, w.name as source_warehouse_name
                FROM {$this->table} d
                JOIN sar_inv_warehouses w ON d.source_warehouse_id = w.id
                WHERE d.source_warehouse_id = ?";
        $params = [$warehouseId];
        
        if ($status) {
            $sql .= " AND d.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY d.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Search dispatches
     */
    public function search($keyword = null, $status = null, $warehouseId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT d.*, w.name as source_warehouse_name
                FROM {$this->table} d
                JOIN sar_inv_warehouses w ON d.source_warehouse_id = w.id
                WHERE 1=1";
        $params = [];
        
        if ($keyword) {
            $sql .= " AND (d.dispatch_number LIKE ? OR d.destination_address LIKE ?)";
            $keyword = "%{$keyword}%";
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        if ($status) {
            $sql .= " AND d.status = ?";
            $params[] = $status;
        }
        
        if ($warehouseId) {
            $sql .= " AND d.source_warehouse_id = ?";
            $params[] = $warehouseId;
        }
        
        if ($dateFrom) {
            $sql .= " AND d.dispatch_date >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND d.dispatch_date <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY d.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Find by dispatch number
     */
    public function findByNumber($dispatchNumber) {
        $sql = "SELECT * FROM {$this->table} WHERE dispatch_number = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dispatchNumber]);
        return $stmt->fetch();
    }
    
    /**
     * Record item receipt
     */
    public function recordItemReceipt($itemId, $receivedQuantity) {
        $sql = "UPDATE {$this->itemsTable} 
                SET received_quantity = ?, 
                    status = CASE 
                        WHEN ? >= quantity THEN 'received' 
                        WHEN ? > 0 THEN 'partial' 
                        ELSE status 
                    END
                WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$receivedQuantity, $receivedQuantity, $receivedQuantity, $itemId]);
    }
}
?>
