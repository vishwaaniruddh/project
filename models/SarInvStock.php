<?php
require_once __DIR__ . '/SarInvBaseModel.php';

/**
 * SAR Inventory Stock Model
 * Manages stock levels with optimistic locking and stock entry logging
 */
class SarInvStock extends SarInvBaseModel {
    protected $table = 'sar_inv_stock';
    protected $entriesTable = 'sar_inv_stock_entries';
    protected $historyTable = 'sar_inv_item_history';
    protected $enableCompanyIsolation = false; // Stock table doesn't have company_id
    
    const ENTRY_TYPE_IN = 'in';
    const ENTRY_TYPE_OUT = 'out';
    
    const TRANSACTION_STOCK_IN = 'stock_in';
    const TRANSACTION_STOCK_OUT = 'stock_out';
    const TRANSACTION_ADJUSTMENT = 'adjustment';
    const TRANSACTION_TRANSFER_OUT = 'transfer_out';
    const TRANSACTION_TRANSFER_IN = 'transfer_in';
    const TRANSACTION_DISPATCH = 'dispatch';
    const TRANSACTION_RETURN = 'return';
    const TRANSACTION_RESERVATION = 'reservation';
    const TRANSACTION_RELEASE = 'release';
    
    /**
     * Get stock record for product in warehouse
     */
    public function getStock($productId, $warehouseId) {
        $sql = "SELECT * FROM {$this->table} WHERE product_id = ? AND warehouse_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId, $warehouseId]);
        return $stmt->fetch();
    }
    
    /**
     * Get available quantity (total - reserved)
     */
    public function getAvailableQuantity($productId, $warehouseId) {
        $stock = $this->getStock($productId, $warehouseId);
        if (!$stock) {
            return 0;
        }
        return floatval($stock['quantity']) - floatval($stock['reserved_quantity']);
    }
    
    /**
     * Add stock to warehouse
     */
    public function addStock($productId, $warehouseId, $quantity, $referenceType = null, $referenceId = null, $notes = null) {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be positive');
        }
        
        $this->beginTransaction();
        
        try {
            $stock = $this->getStock($productId, $warehouseId);
            
            if ($stock) {
                // Update existing stock with optimistic locking
                $newQuantity = floatval($stock['quantity']) + $quantity;
                $result = $this->updateWithVersion($stock['id'], [
                    'quantity' => $newQuantity
                ], $stock['version']);
            } else {
                // Create new stock record
                $result = $this->create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $quantity,
                    'reserved_quantity' => 0,
                    'version' => 1
                ]);
            }
            
            if ($result) {
                // Log stock entry
                $this->logStockEntry($productId, $warehouseId, $quantity, self::ENTRY_TYPE_IN, $referenceType, $referenceId, $notes);
                
                // Log item history
                $newStock = $this->getStock($productId, $warehouseId);
                $this->logItemHistory($productId, $warehouseId, self::TRANSACTION_STOCK_IN, $quantity, $referenceType, $referenceId, $newStock['quantity'], $notes);
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Remove stock from warehouse
     */
    public function removeStock($productId, $warehouseId, $quantity, $referenceType = null, $referenceId = null, $notes = null) {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be positive');
        }
        
        $available = $this->getAvailableQuantity($productId, $warehouseId);
        if ($available < $quantity) {
            throw new Exception('Insufficient stock available');
        }
        
        $this->beginTransaction();
        
        try {
            $stock = $this->getStock($productId, $warehouseId);
            $newQuantity = floatval($stock['quantity']) - $quantity;
            
            $result = $this->updateWithVersion($stock['id'], [
                'quantity' => $newQuantity
            ], $stock['version']);
            
            if ($result) {
                // Log stock entry
                $this->logStockEntry($productId, $warehouseId, $quantity, self::ENTRY_TYPE_OUT, $referenceType, $referenceId, $notes);
                
                // Log item history
                $newStock = $this->getStock($productId, $warehouseId);
                $this->logItemHistory($productId, $warehouseId, self::TRANSACTION_STOCK_OUT, -$quantity, $referenceType, $referenceId, $newStock['quantity'], $notes);
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Reserve stock for dispatch/transfer
     */
    public function reserve($productId, $warehouseId, $quantity, $referenceType = null, $referenceId = null, $notes = null) {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be positive');
        }
        
        $available = $this->getAvailableQuantity($productId, $warehouseId);
        if ($available < $quantity) {
            throw new Exception('Insufficient stock available for reservation');
        }
        
        $this->beginTransaction();
        
        try {
            $stock = $this->getStock($productId, $warehouseId);
            $newReserved = floatval($stock['reserved_quantity']) + $quantity;
            
            $result = $this->updateWithVersion($stock['id'], [
                'reserved_quantity' => $newReserved
            ], $stock['version']);
            
            if ($result) {
                // Log item history
                $newStock = $this->getStock($productId, $warehouseId);
                $this->logItemHistory($productId, $warehouseId, self::TRANSACTION_RESERVATION, $quantity, $referenceType, $referenceId, $newStock['quantity'], $notes);
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Release reserved stock
     */
    public function release($productId, $warehouseId, $quantity, $referenceType = null, $referenceId = null, $notes = null) {
        if ($quantity <= 0) {
            throw new Exception('Quantity must be positive');
        }
        
        $stock = $this->getStock($productId, $warehouseId);
        if (!$stock || floatval($stock['reserved_quantity']) < $quantity) {
            throw new Exception('Cannot release more than reserved quantity');
        }
        
        $this->beginTransaction();
        
        try {
            $newReserved = floatval($stock['reserved_quantity']) - $quantity;
            
            $result = $this->updateWithVersion($stock['id'], [
                'reserved_quantity' => $newReserved
            ], $stock['version']);
            
            if ($result) {
                // Log item history
                $newStock = $this->getStock($productId, $warehouseId);
                $this->logItemHistory($productId, $warehouseId, self::TRANSACTION_RELEASE, -$quantity, $referenceType, $referenceId, $newStock['quantity'], $notes);
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Adjust stock (can be positive or negative)
     */
    public function adjustStock($productId, $warehouseId, $adjustment, $notes = null) {
        $this->beginTransaction();
        
        try {
            $stock = $this->getStock($productId, $warehouseId);
            
            if ($stock) {
                $newQuantity = floatval($stock['quantity']) + $adjustment;
                if ($newQuantity < 0) {
                    throw new Exception('Adjustment would result in negative stock');
                }
                
                // Check if adjustment would make available negative
                $newAvailable = $newQuantity - floatval($stock['reserved_quantity']);
                if ($newAvailable < 0) {
                    throw new Exception('Adjustment would result in negative available stock');
                }
                
                $result = $this->updateWithVersion($stock['id'], [
                    'quantity' => $newQuantity
                ], $stock['version']);
            } else {
                if ($adjustment < 0) {
                    throw new Exception('Cannot create stock with negative quantity');
                }
                $result = $this->create([
                    'product_id' => $productId,
                    'warehouse_id' => $warehouseId,
                    'quantity' => $adjustment,
                    'reserved_quantity' => 0,
                    'version' => 1
                ]);
            }
            
            if ($result) {
                // Log stock entry
                $entryType = $adjustment >= 0 ? self::ENTRY_TYPE_IN : self::ENTRY_TYPE_OUT;
                $this->logStockEntry($productId, $warehouseId, abs($adjustment), $entryType, 'adjustment', null, $notes);
                
                // Log item history
                $newStock = $this->getStock($productId, $warehouseId);
                $this->logItemHistory($productId, $warehouseId, self::TRANSACTION_ADJUSTMENT, $adjustment, 'adjustment', null, $newStock['quantity'], $notes);
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Log stock entry
     */
    protected function logStockEntry($productId, $warehouseId, $quantity, $entryType, $referenceType = null, $referenceId = null, $notes = null) {
        $sql = "INSERT INTO {$this->entriesTable} 
                (product_id, warehouse_id, quantity, entry_type, reference_type, reference_id, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $productId,
            $warehouseId,
            $quantity,
            $entryType,
            $referenceType,
            $referenceId,
            $notes,
            $this->getCurrentUserId()
        ]);
    }
    
    /**
     * Log item history
     */
    protected function logItemHistory($productId, $warehouseId, $transactionType, $quantity, $referenceType = null, $referenceId = null, $balanceAfter = null, $notes = null) {
        $sql = "INSERT INTO {$this->historyTable} 
                (product_id, warehouse_id, transaction_type, quantity, reference_type, reference_id, balance_after, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $productId,
            $warehouseId,
            $transactionType,
            $quantity,
            $referenceType,
            $referenceId,
            $balanceAfter,
            $notes,
            $this->getCurrentUserId()
        ]);
    }
    
    /**
     * Get stock entries for a product
     */
    public function getStockEntries($productId, $warehouseId = null, $limit = 100) {
        $sql = "SELECT se.*, p.name as product_name, w.name as warehouse_name
                FROM {$this->entriesTable} se
                JOIN sar_inv_products p ON se.product_id = p.id
                JOIN sar_inv_warehouses w ON se.warehouse_id = w.id
                WHERE se.product_id = ?";
        $params = [$productId];
        
        if ($warehouseId) {
            $sql .= " AND se.warehouse_id = ?";
            $params[] = $warehouseId;
        }
        
        $sql .= " ORDER BY se.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all stock for a warehouse
     */
    public function getWarehouseStock($warehouseId) {
        $sql = "SELECT s.*, p.name as product_name, p.sku, p.unit_of_measure,
                    (s.quantity - s.reserved_quantity) as available_quantity
                FROM {$this->table} s
                JOIN sar_inv_products p ON s.product_id = p.id
                WHERE s.warehouse_id = ?
                ORDER BY p.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$warehouseId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all stock for a product across warehouses
     */
    public function getProductStock($productId) {
        $sql = "SELECT s.*, w.name as warehouse_name, w.code as warehouse_code,
                    (s.quantity - s.reserved_quantity) as available_quantity
                FROM {$this->table} s
                JOIN sar_inv_warehouses w ON s.warehouse_id = w.id
                WHERE s.product_id = ?
                ORDER BY w.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if sufficient stock is available
     */
    public function hasSufficientStock($productId, $warehouseId, $requiredQuantity) {
        return $this->getAvailableQuantity($productId, $warehouseId) >= $requiredQuantity;
    }
    
    /**
     * Get low stock items
     */
    public function getLowStockItems() {
        $sql = "SELECT s.*, p.name as product_name, p.sku, p.minimum_stock_level,
                    w.name as warehouse_name,
                    (s.quantity - s.reserved_quantity) as available_quantity
                FROM {$this->table} s
                JOIN sar_inv_products p ON s.product_id = p.id
                JOIN sar_inv_warehouses w ON s.warehouse_id = w.id
                WHERE (s.quantity - s.reserved_quantity) < p.minimum_stock_level
                ORDER BY (s.quantity - s.reserved_quantity) ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get stock levels with product and warehouse details
     */
    public function getStockLevelsWithDetails($search = null, $warehouseId = null, $categoryId = null) {
        $sql = "SELECT s.*, 
                    p.name as product_name, p.sku, p.minimum_stock_level, p.is_serializable, p.category_id,
                    w.name as warehouse_name, w.code as warehouse_code,
                    (s.quantity - s.reserved_quantity) as available_quantity
                FROM {$this->table} s
                JOIN sar_inv_products p ON s.product_id = p.id
                JOIN sar_inv_warehouses w ON s.warehouse_id = w.id
                WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        if ($warehouseId) {
            $sql .= " AND s.warehouse_id = ?";
            $params[] = $warehouseId;
        }
        
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " ORDER BY p.name, w.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
