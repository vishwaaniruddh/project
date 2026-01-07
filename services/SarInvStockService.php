<?php
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvWarehouse.php';
require_once __DIR__ . '/../models/SarInvItemHistory.php';
require_once __DIR__ . '/../models/SarInvAuditLog.php';

/**
 * SAR Inventory Stock Service
 * Business logic for stock operations with validation, history logging, and optimistic locking
 */
class SarInvStockService {
    private $stockModel;
    private $productModel;
    private $warehouseModel;
    private $historyModel;
    private $auditLog;
    
    public function __construct() {
        $this->stockModel = new SarInvStock();
        $this->productModel = new SarInvProduct();
        $this->warehouseModel = new SarInvWarehouse();
        $this->historyModel = new SarInvItemHistory();
        $this->auditLog = new SarInvAuditLog();
    }
    
    /**
     * Add stock to warehouse
     * @param int $productId Product ID
     * @param int $warehouseId Warehouse ID
     * @param float $quantity Quantity to add
     * @param string|null $referenceType Reference type (e.g., 'purchase', 'return')
     * @param int|null $referenceId Reference ID
     * @param string|null $notes Additional notes
     * @return array Result with success status
     */
    public function addStock(int $productId, int $warehouseId, float $quantity, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null): array {
        // Validate product exists
        $product = $this->productModel->find($productId);
        if (!$product) {
            return ['success' => false, 'errors' => ['Product not found']];
        }
        
        // Validate warehouse exists and is active
        $warehouse = $this->warehouseModel->find($warehouseId);
        if (!$warehouse) {
            return ['success' => false, 'errors' => ['Warehouse not found']];
        }
        
        if ($warehouse['status'] !== SarInvWarehouse::STATUS_ACTIVE) {
            return ['success' => false, 'errors' => ['Warehouse is not active']];
        }
        
        // Validate quantity
        if ($quantity <= 0) {
            return ['success' => false, 'errors' => ['Quantity must be greater than zero']];
        }
        
        try {
            $result = $this->stockModel->addStock($productId, $warehouseId, $quantity, $referenceType, $referenceId, $notes);
            
            if ($result) {
                $newStock = $this->stockModel->getStock($productId, $warehouseId);
                return [
                    'success' => true,
                    'message' => 'Stock added successfully',
                    'new_quantity' => $newStock['quantity'],
                    'available_quantity' => $newStock['quantity'] - $newStock['reserved_quantity']
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to add stock']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Remove stock from warehouse
     * @param int $productId Product ID
     * @param int $warehouseId Warehouse ID
     * @param float $quantity Quantity to remove
     * @param string|null $referenceType Reference type
     * @param int|null $referenceId Reference ID
     * @param string|null $notes Additional notes
     * @return array Result with success status
     */
    public function removeStock(int $productId, int $warehouseId, float $quantity, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null): array {
        // Validate product exists
        $product = $this->productModel->find($productId);
        if (!$product) {
            return ['success' => false, 'errors' => ['Product not found']];
        }
        
        // Validate warehouse exists
        $warehouse = $this->warehouseModel->find($warehouseId);
        if (!$warehouse) {
            return ['success' => false, 'errors' => ['Warehouse not found']];
        }
        
        // Validate quantity
        if ($quantity <= 0) {
            return ['success' => false, 'errors' => ['Quantity must be greater than zero']];
        }
        
        // Check available stock
        $available = $this->stockModel->getAvailableQuantity($productId, $warehouseId);
        if ($available < $quantity) {
            return [
                'success' => false,
                'errors' => ["Insufficient stock. Available: {$available}, Requested: {$quantity}"]
            ];
        }
        
        try {
            $result = $this->stockModel->removeStock($productId, $warehouseId, $quantity, $referenceType, $referenceId, $notes);
            
            if ($result) {
                $newStock = $this->stockModel->getStock($productId, $warehouseId);
                return [
                    'success' => true,
                    'message' => 'Stock removed successfully',
                    'new_quantity' => $newStock['quantity'],
                    'available_quantity' => $newStock['quantity'] - $newStock['reserved_quantity']
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to remove stock']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Adjust stock (positive or negative)
     * @param int $productId Product ID
     * @param int $warehouseId Warehouse ID
     * @param float $adjustment Adjustment amount (positive or negative)
     * @param string|null $notes Reason for adjustment
     * @return array Result with success status
     */
    public function adjustStock(int $productId, int $warehouseId, float $adjustment, ?string $notes = null): array {
        // Validate product exists
        $product = $this->productModel->find($productId);
        if (!$product) {
            return ['success' => false, 'errors' => ['Product not found']];
        }
        
        // Validate warehouse exists
        $warehouse = $this->warehouseModel->find($warehouseId);
        if (!$warehouse) {
            return ['success' => false, 'errors' => ['Warehouse not found']];
        }
        
        if ($adjustment == 0) {
            return ['success' => false, 'errors' => ['Adjustment cannot be zero']];
        }
        
        // For negative adjustments, check available stock
        if ($adjustment < 0) {
            $available = $this->stockModel->getAvailableQuantity($productId, $warehouseId);
            if ($available < abs($adjustment)) {
                return [
                    'success' => false,
                    'errors' => ["Insufficient stock for adjustment. Available: {$available}, Adjustment: {$adjustment}"]
                ];
            }
        }
        
        try {
            $result = $this->stockModel->adjustStock($productId, $warehouseId, $adjustment, $notes);
            
            if ($result) {
                $newStock = $this->stockModel->getStock($productId, $warehouseId);
                return [
                    'success' => true,
                    'message' => 'Stock adjusted successfully',
                    'new_quantity' => $newStock['quantity'],
                    'available_quantity' => $newStock['quantity'] - $newStock['reserved_quantity']
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to adjust stock']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Reserve stock for dispatch/transfer
     * @param int $productId Product ID
     * @param int $warehouseId Warehouse ID
     * @param float $quantity Quantity to reserve
     * @param string|null $referenceType Reference type
     * @param int|null $referenceId Reference ID
     * @param string|null $notes Additional notes
     * @return array Result with success status
     */
    public function reserveStock(int $productId, int $warehouseId, float $quantity, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null): array {
        if ($quantity <= 0) {
            return ['success' => false, 'errors' => ['Quantity must be greater than zero']];
        }
        
        $available = $this->stockModel->getAvailableQuantity($productId, $warehouseId);
        if ($available < $quantity) {
            return [
                'success' => false,
                'errors' => ["Insufficient stock for reservation. Available: {$available}, Requested: {$quantity}"]
            ];
        }
        
        try {
            $result = $this->stockModel->reserve($productId, $warehouseId, $quantity, $referenceType, $referenceId, $notes);
            
            if ($result) {
                $newStock = $this->stockModel->getStock($productId, $warehouseId);
                return [
                    'success' => true,
                    'message' => 'Stock reserved successfully',
                    'reserved_quantity' => $newStock['reserved_quantity'],
                    'available_quantity' => $newStock['quantity'] - $newStock['reserved_quantity']
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to reserve stock']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Release reserved stock
     * @param int $productId Product ID
     * @param int $warehouseId Warehouse ID
     * @param float $quantity Quantity to release
     * @param string|null $referenceType Reference type
     * @param int|null $referenceId Reference ID
     * @param string|null $notes Additional notes
     * @return array Result with success status
     */
    public function releaseStock(int $productId, int $warehouseId, float $quantity, ?string $referenceType = null, ?int $referenceId = null, ?string $notes = null): array {
        if ($quantity <= 0) {
            return ['success' => false, 'errors' => ['Quantity must be greater than zero']];
        }
        
        try {
            $result = $this->stockModel->release($productId, $warehouseId, $quantity, $referenceType, $referenceId, $notes);
            
            if ($result) {
                $newStock = $this->stockModel->getStock($productId, $warehouseId);
                return [
                    'success' => true,
                    'message' => 'Stock released successfully',
                    'reserved_quantity' => $newStock['reserved_quantity'],
                    'available_quantity' => $newStock['quantity'] - $newStock['reserved_quantity']
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to release stock']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Get stock level for product in warehouse
     * @param int $productId Product ID
     * @param int $warehouseId Warehouse ID
     * @return array Stock information
     */
    public function getStockLevel(int $productId, int $warehouseId): array {
        $stock = $this->stockModel->getStock($productId, $warehouseId);
        
        if (!$stock) {
            return [
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'quantity' => 0,
                'reserved_quantity' => 0,
                'available_quantity' => 0
            ];
        }
        
        return [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'quantity' => floatval($stock['quantity']),
            'reserved_quantity' => floatval($stock['reserved_quantity']),
            'available_quantity' => floatval($stock['quantity']) - floatval($stock['reserved_quantity'])
        ];
    }
    
    /**
     * Get available quantity
     * @param int $productId Product ID
     * @param int $warehouseId Warehouse ID
     * @return float Available quantity
     */
    public function getAvailableQuantity(int $productId, int $warehouseId): float {
        return $this->stockModel->getAvailableQuantity($productId, $warehouseId);
    }
    
    /**
     * Check if sufficient stock is available
     * @param int $productId Product ID
     * @param int $warehouseId Warehouse ID
     * @param float $requiredQuantity Required quantity
     * @return bool True if sufficient stock available
     */
    public function hasSufficientStock(int $productId, int $warehouseId, float $requiredQuantity): bool {
        return $this->stockModel->hasSufficientStock($productId, $warehouseId, $requiredQuantity);
    }
    
    /**
     * Validate stock availability for multiple items
     * @param array $items Array of ['product_id', 'warehouse_id', 'quantity']
     * @return array Validation result with details
     */
    public function validateStockAvailability(array $items): array {
        $results = [];
        $allAvailable = true;
        
        foreach ($items as $item) {
            $available = $this->stockModel->getAvailableQuantity($item['product_id'], $item['warehouse_id']);
            $isAvailable = $available >= $item['quantity'];
            
            if (!$isAvailable) {
                $allAvailable = false;
            }
            
            $results[] = [
                'product_id' => $item['product_id'],
                'warehouse_id' => $item['warehouse_id'],
                'requested' => $item['quantity'],
                'available' => $available,
                'is_available' => $isAvailable,
                'shortage' => max(0, $item['quantity'] - $available)
            ];
        }
        
        return [
            'all_available' => $allAvailable,
            'items' => $results
        ];
    }
    
    /**
     * Get all stock for a warehouse
     * @param int $warehouseId Warehouse ID
     * @return array Stock records
     */
    public function getWarehouseStock(int $warehouseId): array {
        return $this->stockModel->getWarehouseStock($warehouseId);
    }
    
    /**
     * Get all stock for a product across warehouses
     * @param int $productId Product ID
     * @return array Stock records
     */
    public function getProductStock(int $productId): array {
        return $this->stockModel->getProductStock($productId);
    }
    
    /**
     * Get stock levels with product and warehouse info
     * @param string|null $search Search keyword
     * @param int|null $warehouseId Warehouse filter
     * @param int|null $categoryId Category filter
     * @return array Stock levels with details
     */
    public function getStockLevels(?string $search = null, ?int $warehouseId = null, ?int $categoryId = null): array {
        return $this->stockModel->getStockLevelsWithDetails($search, $warehouseId, $categoryId);
    }
    
    /**
     * Get stock entries/history for a product
     * @param int $productId Product ID
     * @param int|null $warehouseId Optional warehouse filter
     * @param int $limit Number of records
     * @return array Stock entries
     */
    public function getStockEntries(int $productId, ?int $warehouseId = null, int $limit = 100): array {
        return $this->stockModel->getStockEntries($productId, $warehouseId, $limit);
    }
    
    /**
     * Get low stock items
     * @return array Low stock items
     */
    public function getLowStockItems(): array {
        return $this->stockModel->getLowStockItems();
    }
    
    /**
     * Get item history with filters
     * @param array $filters Filter criteria
     * @param int $page Page number
     * @param int $perPage Items per page
     * @return array Paginated history
     */
    public function getItemHistory(array $filters = [], int $page = 1, int $perPage = 50): array {
        return $this->historyModel->getPaginated($filters, $page, $perPage);
    }
    
    /**
     * Get history summary by transaction type
     * @param array $filters Filter criteria
     * @return array Summary data
     */
    public function getHistorySummary(array $filters = []): array {
        return $this->historyModel->getSummaryByType($filters);
    }
    
    /**
     * Export item history to CSV
     * @param array $filters Filter criteria
     * @return array Export data with filename and content
     */
    public function exportHistoryToCsv(array $filters = []): array {
        return $this->historyModel->exportToCsv($filters);
    }
    
    /**
     * Export item history to Excel
     * @param array $filters Filter criteria
     * @return array Export data with filename and content
     */
    public function exportHistoryToExcel(array $filters = []): array {
        return $this->historyModel->exportToExcel($filters);
    }
}
?>
