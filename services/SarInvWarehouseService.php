<?php
require_once __DIR__ . '/../models/SarInvWarehouse.php';
require_once __DIR__ . '/../models/SarInvAuditLog.php';

/**
 * SAR Inventory Warehouse Service
 * Business logic for warehouse operations with validation and audit logging
 */
class SarInvWarehouseService {
    private $warehouseModel;
    private $auditLog;
    
    public function __construct() {
        $this->warehouseModel = new SarInvWarehouse();
        $this->auditLog = new SarInvAuditLog();
    }
    
    /**
     * Create a new warehouse
     * @param array $data Warehouse data
     * @return array Result with success status and warehouse ID or errors
     */
    public function createWarehouse(array $data): array {
        // Validate data
        $errors = $this->warehouseModel->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $warehouseId = $this->warehouseModel->create($data);
            
            if ($warehouseId) {
                return [
                    'success' => true,
                    'warehouse_id' => $warehouseId,
                    'message' => 'Warehouse created successfully'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to create warehouse']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Update an existing warehouse
     * @param int $id Warehouse ID
     * @param array $data Updated warehouse data
     * @return array Result with success status
     */
    public function updateWarehouse(int $id, array $data): array {
        // Check if warehouse exists
        $warehouse = $this->warehouseModel->find($id);
        if (!$warehouse) {
            return ['success' => false, 'errors' => ['Warehouse not found']];
        }
        
        // Validate data
        $errors = $this->warehouseModel->validate($data, true, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $result = $this->warehouseModel->update($id, $data);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Warehouse updated successfully'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to update warehouse']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Delete a warehouse with inventory check
     * @param int $id Warehouse ID
     * @return array Result with success status
     */
    public function deleteWarehouse(int $id): array {
        // Check if warehouse exists
        $warehouse = $this->warehouseModel->find($id);
        if (!$warehouse) {
            return ['success' => false, 'errors' => ['Warehouse not found']];
        }
        
        // Check for existing inventory
        if ($this->warehouseModel->hasInventory($id)) {
            return [
                'success' => false,
                'errors' => ['Cannot delete warehouse with existing inventory. Please transfer or remove all inventory first.']
            ];
        }
        
        // Check for pending dispatches
        if ($this->warehouseModel->hasPendingDispatches($id)) {
            return [
                'success' => false,
                'errors' => ['Cannot delete warehouse with pending dispatches. Please complete or cancel all dispatches first.']
            ];
        }
        
        // Check for pending transfers
        if ($this->warehouseModel->hasPendingTransfers($id)) {
            return [
                'success' => false,
                'errors' => ['Cannot delete warehouse with pending transfers. Please complete or cancel all transfers first.']
            ];
        }
        
        try {
            $result = $this->warehouseModel->delete($id);
            
            if ($result) {
                return [
                    'success' => true,
                    'message' => 'Warehouse deleted successfully'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to delete warehouse']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Get warehouse by ID
     * @param int $id Warehouse ID
     * @return array|null Warehouse data or null
     */
    public function getWarehouse(int $id): ?array {
        $warehouse = $this->warehouseModel->find($id);
        return $warehouse ?: null;
    }
    
    /**
     * Get warehouse by code
     * @param string $code Warehouse code
     * @return array|null Warehouse data or null
     */
    public function getWarehouseByCode(string $code): ?array {
        $warehouse = $this->warehouseModel->findByCode($code);
        return $warehouse ?: null;
    }
    
    /**
     * Get all warehouses
     * @param string|null $status Filter by status
     * @return array List of warehouses
     */
    public function getAllWarehouses(?string $status = null): array {
        if ($status) {
            return $this->warehouseModel->findAll(['status' => $status]);
        }
        return $this->warehouseModel->findAll();
    }
    
    /**
     * Get active warehouses
     * @return array List of active warehouses
     */
    public function getActiveWarehouses(): array {
        return $this->warehouseModel->getActiveWarehouses();
    }
    
    /**
     * Search warehouses
     * @param string|null $keyword Search keyword
     * @param string|null $status Filter by status
     * @return array List of matching warehouses
     */
    public function searchWarehouses(?string $keyword = null, ?string $status = null): array {
        return $this->warehouseModel->search($keyword, $status);
    }
    
    /**
     * Get warehouse capacity utilization
     * @param int $warehouseId Warehouse ID
     * @return array Capacity utilization data
     */
    public function getCapacityUtilization(int $warehouseId): array {
        return $this->warehouseModel->getCapacityUtilization($warehouseId);
    }
    
    /**
     * Get warehouse stock summary
     * @param int $warehouseId Warehouse ID
     * @return array Stock summary data
     */
    public function getStockSummary(int $warehouseId): array {
        return $this->warehouseModel->getStockSummary($warehouseId);
    }
    
    /**
     * Update warehouse status
     * @param int $id Warehouse ID
     * @param string $status New status
     * @return array Result with success status
     */
    public function updateStatus(int $id, string $status): array {
        $validStatuses = [
            SarInvWarehouse::STATUS_ACTIVE,
            SarInvWarehouse::STATUS_INACTIVE,
            SarInvWarehouse::STATUS_MAINTENANCE
        ];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'errors' => ['Invalid status value']];
        }
        
        return $this->updateWarehouse($id, ['status' => $status]);
    }
    
    /**
     * Check if warehouse can be deleted
     * @param int $id Warehouse ID
     * @return array Check result with reasons if cannot delete
     */
    public function canDelete(int $id): array {
        $reasons = [];
        
        if ($this->warehouseModel->hasInventory($id)) {
            $reasons[] = 'Warehouse has existing inventory';
        }
        
        if ($this->warehouseModel->hasPendingDispatches($id)) {
            $reasons[] = 'Warehouse has pending dispatches';
        }
        
        if ($this->warehouseModel->hasPendingTransfers($id)) {
            $reasons[] = 'Warehouse has pending transfers';
        }
        
        return [
            'can_delete' => empty($reasons),
            'reasons' => $reasons
        ];
    }
    
    /**
     * Get warehouse audit history
     * @param int $warehouseId Warehouse ID
     * @param int $limit Number of records to return
     * @return array Audit log entries
     */
    public function getAuditHistory(int $warehouseId, int $limit = 50): array {
        return $this->auditLog->getLogsForRecord('sar_inv_warehouses', $warehouseId, $limit);
    }
}
?>
