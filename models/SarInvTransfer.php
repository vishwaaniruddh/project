<?php
require_once __DIR__ . '/SarInvBaseModel.php';
require_once __DIR__ . '/SarInvStock.php';

/**
 * SAR Inventory Transfer Model
 * Manages inter-warehouse transfers with approval workflow and stock conservation
 */
class SarInvTransfer extends SarInvBaseModel {
    protected $table = 'sar_inv_transfers';
    protected $itemsTable = 'sar_inv_transfer_items';
    protected $enableCompanyIsolation = false;
    
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_IN_TRANSIT = 'in_transit';
    const STATUS_RECEIVED = 'received';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Validate transfer data
     */
    public function validate($data, $isUpdate = false, $id = null) {
        $errors = [];
        
        if (empty($data['source_warehouse_id'])) {
            $errors[] = 'Source warehouse is required';
        }
        
        if (empty($data['destination_warehouse_id'])) {
            $errors[] = 'Destination warehouse is required';
        }
        
        if (!empty($data['source_warehouse_id']) && !empty($data['destination_warehouse_id'])) {
            if ($data['source_warehouse_id'] == $data['destination_warehouse_id']) {
                $errors[] = 'Source and destination warehouses must be different';
            }
        }
        
        if (isset($data['status']) && !in_array($data['status'], [
            self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_IN_TRANSIT, 
            self::STATUS_RECEIVED, self::STATUS_CANCELLED
        ])) {
            $errors[] = 'Invalid status value';
        }
        
        return $errors;
    }
    
    /**
     * Generate unique transfer number
     */
    public function generateTransferNumber() {
        $prefix = 'TRF';
        $date = date('Ymd');
        
        $sql = "SELECT MAX(CAST(SUBSTRING(transfer_number, 12) AS UNSIGNED)) as max_num 
                FROM {$this->table} 
                WHERE transfer_number LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix . $date . '%']);
        $result = $stmt->fetch();
        
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return $prefix . $date . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create transfer with items
     */
    public function createTransfer($data, $items = []) {
        $this->beginTransaction();
        
        try {
            // Generate transfer number if not provided
            if (empty($data['transfer_number'])) {
                $data['transfer_number'] = $this->generateTransferNumber();
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
            
            // Create transfer
            $transferId = parent::create($data);
            
            if (!$transferId) {
                throw new Exception('Failed to create transfer');
            }
            
            // Add items
            foreach ($items as $item) {
                $this->addItem($transferId, $item['product_id'], $item['quantity'], $item['notes'] ?? null);
            }
            
            $this->commit();
            return $transferId;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Add item to transfer
     */
    public function addItem($transferId, $productId, $quantity, $notes = null) {
        $sql = "INSERT INTO {$this->itemsTable} (transfer_id, product_id, quantity, notes, status) 
                VALUES (?, ?, ?, ?, 'pending')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$transferId, $productId, $quantity, $notes]);
    }
    
    /**
     * Get transfer items
     */
    public function getItems($transferId) {
        $sql = "SELECT ti.*, p.name as product_name, p.sku, p.unit_of_measure
                FROM {$this->itemsTable} ti
                JOIN sar_inv_products p ON ti.product_id = p.id
                WHERE ti.transfer_id = ?
                ORDER BY p.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transferId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Approve transfer - reserves stock at source
     */
    public function approve($transferId, $approverId = null) {
        $transfer = $this->find($transferId);
        if (!$transfer) {
            throw new Exception('Transfer not found');
        }
        
        if ($transfer['status'] !== self::STATUS_PENDING) {
            throw new Exception('Only pending transfers can be approved');
        }
        
        $this->beginTransaction();
        
        try {
            $items = $this->getItems($transferId);
            $stockModel = new SarInvStock();
            
            // Reserve stock for all items
            foreach ($items as $item) {
                $stockModel->reserve(
                    $item['product_id'],
                    $transfer['source_warehouse_id'],
                    $item['quantity'],
                    'transfer',
                    $transferId,
                    "Transfer #{$transfer['transfer_number']} approved"
                );
            }
            
            // Update transfer status
            $result = $this->update($transferId, [
                'status' => self::STATUS_APPROVED,
                'approved_by' => $approverId ?? $this->getCurrentUserId(),
                'transfer_date' => date('Y-m-d')
            ]);
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Ship transfer - moves stock from source
     */
    public function ship($transferId) {
        $transfer = $this->find($transferId);
        if (!$transfer) {
            throw new Exception('Transfer not found');
        }
        
        if ($transfer['status'] !== self::STATUS_APPROVED) {
            throw new Exception('Only approved transfers can be shipped');
        }
        
        $this->beginTransaction();
        
        try {
            $items = $this->getItems($transferId);
            $stockModel = new SarInvStock();
            
            foreach ($items as $item) {
                // Release reservation
                $stockModel->release(
                    $item['product_id'],
                    $transfer['source_warehouse_id'],
                    $item['quantity'],
                    'transfer',
                    $transferId
                );
                
                // Remove stock from source
                $stockModel->removeStock(
                    $item['product_id'],
                    $transfer['source_warehouse_id'],
                    $item['quantity'],
                    'transfer_out',
                    $transferId,
                    "Transfer #{$transfer['transfer_number']} shipped"
                );
            }
            
            // Update transfer and item statuses
            $this->update($transferId, ['status' => self::STATUS_IN_TRANSIT]);
            $this->updateItemStatuses($transferId, 'in_transit');
            
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Receive transfer - adds stock to destination
     */
    public function receive($transferId, $receivedItems = null) {
        $transfer = $this->find($transferId);
        if (!$transfer) {
            throw new Exception('Transfer not found');
        }
        
        if (!in_array($transfer['status'], [self::STATUS_APPROVED, self::STATUS_IN_TRANSIT])) {
            throw new Exception('Only approved or in-transit transfers can be received');
        }
        
        $this->beginTransaction();
        
        try {
            $items = $this->getItems($transferId);
            $stockModel = new SarInvStock();
            
            // If transfer was approved but not shipped, handle stock from source first
            if ($transfer['status'] === self::STATUS_APPROVED) {
                foreach ($items as $item) {
                    // Release reservation
                    $stockModel->release(
                        $item['product_id'],
                        $transfer['source_warehouse_id'],
                        $item['quantity'],
                        'transfer',
                        $transferId
                    );
                    
                    // Remove stock from source
                    $stockModel->removeStock(
                        $item['product_id'],
                        $transfer['source_warehouse_id'],
                        $item['quantity'],
                        'transfer_out',
                        $transferId,
                        "Transfer #{$transfer['transfer_number']} received"
                    );
                }
            }
            
            // Add stock to destination
            foreach ($items as $item) {
                $receivedQty = $item['quantity'];
                
                // Check if specific received quantities were provided
                if ($receivedItems && isset($receivedItems[$item['id']])) {
                    $receivedQty = $receivedItems[$item['id']];
                }
                
                $stockModel->addStock(
                    $item['product_id'],
                    $transfer['destination_warehouse_id'],
                    $receivedQty,
                    'transfer_in',
                    $transferId,
                    "Transfer #{$transfer['transfer_number']} received"
                );
                
                // Update item received quantity
                $this->updateItemReceived($item['id'], $receivedQty);
            }
            
            // Update transfer status
            $this->update($transferId, [
                'status' => self::STATUS_RECEIVED,
                'received_date' => date('Y-m-d')
            ]);
            
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Cancel transfer
     */
    public function cancel($transferId, $reason = null) {
        $transfer = $this->find($transferId);
        if (!$transfer) {
            throw new Exception('Transfer not found');
        }
        
        if (in_array($transfer['status'], [self::STATUS_RECEIVED, self::STATUS_CANCELLED])) {
            throw new Exception('Cannot cancel completed or already cancelled transfers');
        }
        
        $this->beginTransaction();
        
        try {
            // Release any reserved stock if transfer was approved
            if ($transfer['status'] === self::STATUS_APPROVED) {
                $items = $this->getItems($transferId);
                $stockModel = new SarInvStock();
                
                foreach ($items as $item) {
                    try {
                        $stockModel->release(
                            $item['product_id'],
                            $transfer['source_warehouse_id'],
                            $item['quantity'],
                            'transfer_cancel',
                            $transferId,
                            "Transfer #{$transfer['transfer_number']} cancelled: {$reason}"
                        );
                    } catch (Exception $e) {
                        // Stock may not have been reserved
                    }
                }
            }
            
            // Update transfer status
            $notes = $transfer['notes'] ? $transfer['notes'] . "\n" : '';
            $notes .= "Cancelled: " . ($reason ?? 'No reason provided');
            
            $result = $this->update($transferId, [
                'status' => self::STATUS_CANCELLED,
                'notes' => $notes
            ]);
            
            $this->updateItemStatuses($transferId, 'cancelled');
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Update all item statuses
     */
    protected function updateItemStatuses($transferId, $status) {
        $sql = "UPDATE {$this->itemsTable} SET status = ? WHERE transfer_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status, $transferId]);
    }
    
    /**
     * Update item received quantity
     */
    protected function updateItemReceived($itemId, $receivedQuantity) {
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
    
    /**
     * Get transfer with full details
     */
    public function getWithDetails($transferId) {
        $sql = "SELECT t.*, 
                    sw.name as source_warehouse_name, sw.code as source_warehouse_code,
                    dw.name as destination_warehouse_name, dw.code as destination_warehouse_code
                FROM {$this->table} t
                JOIN sar_inv_warehouses sw ON t.source_warehouse_id = sw.id
                JOIN sar_inv_warehouses dw ON t.destination_warehouse_id = dw.id
                WHERE t.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transferId]);
        $transfer = $stmt->fetch();
        
        if ($transfer) {
            $transfer['items'] = $this->getItems($transferId);
        }
        
        return $transfer;
    }
    
    /**
     * Get transfers by status
     */
    public function getByStatus($status) {
        $sql = "SELECT t.*, 
                    sw.name as source_warehouse_name,
                    dw.name as destination_warehouse_name
                FROM {$this->table} t
                JOIN sar_inv_warehouses sw ON t.source_warehouse_id = sw.id
                JOIN sar_inv_warehouses dw ON t.destination_warehouse_id = dw.id
                WHERE t.status = ?
                ORDER BY t.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search transfers
     */
    public function search($keyword = null, $status = null, $sourceWarehouseId = null, $destWarehouseId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT t.*, 
                    sw.name as source_warehouse_name,
                    dw.name as destination_warehouse_name
                FROM {$this->table} t
                JOIN sar_inv_warehouses sw ON t.source_warehouse_id = sw.id
                JOIN sar_inv_warehouses dw ON t.destination_warehouse_id = dw.id
                WHERE 1=1";
        $params = [];
        
        if ($keyword) {
            $sql .= " AND t.transfer_number LIKE ?";
            $params[] = "%{$keyword}%";
        }
        
        if ($status) {
            $sql .= " AND t.status = ?";
            $params[] = $status;
        }
        
        if ($sourceWarehouseId) {
            $sql .= " AND t.source_warehouse_id = ?";
            $params[] = $sourceWarehouseId;
        }
        
        if ($destWarehouseId) {
            $sql .= " AND t.destination_warehouse_id = ?";
            $params[] = $destWarehouseId;
        }
        
        if ($dateFrom) {
            $sql .= " AND t.transfer_date >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND t.transfer_date <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY t.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Find by transfer number
     */
    public function findByNumber($transferNumber) {
        $sql = "SELECT * FROM {$this->table} WHERE transfer_number = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$transferNumber]);
        return $stmt->fetch();
    }
}
?>
