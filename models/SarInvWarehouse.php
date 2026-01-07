<?php
require_once __DIR__ . '/SarInvBaseModel.php';

/**
 * SAR Inventory Warehouse Model
 * Manages warehouse CRUD operations with validation and capacity tracking
 */
class SarInvWarehouse extends SarInvBaseModel {
    protected $table = 'sar_inv_warehouses';
    
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_MAINTENANCE = 'maintenance';
    
    /**
     * Validate warehouse data
     */
    public function validate($data, $isUpdate = false, $id = null) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Warehouse name is required';
        }
        
        if (empty($data['code'])) {
            $errors[] = 'Warehouse code is required';
        } else {
            // Check for unique code within company
            if ($this->codeExists($data['code'], $id)) {
                $errors[] = 'Warehouse code already exists';
            }
        }
        
        if (isset($data['capacity']) && $data['capacity'] < 0) {
            $errors[] = 'Capacity cannot be negative';
        }
        
        if (isset($data['status']) && !in_array($data['status'], [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_MAINTENANCE])) {
            $errors[] = 'Invalid status value';
        }
        
        return $errors;
    }
    
    /**
     * Check if warehouse code exists
     */
    public function codeExists($code, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE code = ? AND company_id = ?";
        $params = [$code, $this->getCurrentCompanyId()];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Get warehouse by code
     */
    public function findByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE code = ? AND company_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code, $this->getCurrentCompanyId()]);
        return $stmt->fetch();
    }
    
    /**
     * Get all active warehouses
     */
    public function getActiveWarehouses() {
        return $this->findAll(['status' => self::STATUS_ACTIVE]);
    }
    
    /**
     * Calculate capacity utilization for a warehouse
     */
    public function getCapacityUtilization($warehouseId) {
        $warehouse = $this->find($warehouseId);
        
        if (!$warehouse || !$warehouse['capacity']) {
            return [
                'warehouse_id' => $warehouseId,
                'capacity' => 0,
                'used' => 0,
                'available' => 0,
                'utilization_percentage' => 0
            ];
        }
        
        // Get total stock quantity in this warehouse
        $sql = "SELECT COALESCE(SUM(quantity), 0) as total_quantity 
                FROM sar_inv_stock 
                WHERE warehouse_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$warehouseId]);
        $result = $stmt->fetch();
        
        $used = floatval($result['total_quantity']);
        $capacity = floatval($warehouse['capacity']);
        $available = max(0, $capacity - $used);
        $utilization = $capacity > 0 ? ($used / $capacity) * 100 : 0;
        
        return [
            'warehouse_id' => $warehouseId,
            'capacity' => $capacity,
            'used' => $used,
            'available' => $available,
            'utilization_percentage' => round($utilization, 2)
        ];
    }
    
    /**
     * Check if warehouse has inventory
     */
    public function hasInventory($warehouseId) {
        $sql = "SELECT COUNT(*) FROM sar_inv_stock WHERE warehouse_id = ? AND quantity > 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$warehouseId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if warehouse has pending dispatches
     */
    public function hasPendingDispatches($warehouseId) {
        $sql = "SELECT COUNT(*) FROM sar_inv_dispatches 
                WHERE source_warehouse_id = ? AND status IN ('pending', 'approved', 'shipped', 'in_transit')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$warehouseId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if warehouse has pending transfers
     */
    public function hasPendingTransfers($warehouseId) {
        $sql = "SELECT COUNT(*) FROM sar_inv_transfers 
                WHERE (source_warehouse_id = ? OR destination_warehouse_id = ?) 
                AND status IN ('pending', 'approved', 'in_transit')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$warehouseId, $warehouseId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Safe delete - prevents deletion if warehouse has inventory
     */
    public function safeDelete($warehouseId) {
        if ($this->hasInventory($warehouseId)) {
            return ['success' => false, 'error' => 'Cannot delete warehouse with existing inventory'];
        }
        
        if ($this->hasPendingDispatches($warehouseId)) {
            return ['success' => false, 'error' => 'Cannot delete warehouse with pending dispatches'];
        }
        
        if ($this->hasPendingTransfers($warehouseId)) {
            return ['success' => false, 'error' => 'Cannot delete warehouse with pending transfers'];
        }
        
        $result = $this->delete($warehouseId);
        return ['success' => $result, 'error' => $result ? null : 'Failed to delete warehouse'];
    }
    
    /**
     * Get warehouse stock summary
     */
    public function getStockSummary($warehouseId) {
        $sql = "SELECT 
                    p.id as product_id,
                    p.name as product_name,
                    p.sku,
                    s.quantity,
                    s.reserved_quantity,
                    (s.quantity - s.reserved_quantity) as available_quantity
                FROM sar_inv_stock s
                JOIN sar_inv_products p ON s.product_id = p.id
                WHERE s.warehouse_id = ?
                ORDER BY p.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$warehouseId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search warehouses
     */
    public function search($keyword, $status = null) {
        $sql = "SELECT * FROM {$this->table} WHERE company_id = ?";
        $params = [$this->getCurrentCompanyId()];
        
        if ($keyword) {
            $sql .= " AND (name LIKE ? OR code LIKE ? OR location LIKE ?)";
            $keyword = "%{$keyword}%";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
