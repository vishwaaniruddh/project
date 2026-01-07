<?php
require_once __DIR__ . '/SarInvBaseModel.php';

/**
 * SAR Inventory Asset Model
 * Manages individual asset tracking with unique identifiers and location history
 */
class SarInvAsset extends SarInvBaseModel {
    protected $table = 'sar_inv_assets';
    protected $historyTable = 'sar_inv_asset_history';
    
    const STATUS_AVAILABLE = 'available';
    const STATUS_DISPATCHED = 'dispatched';
    const STATUS_IN_REPAIR = 'in_repair';
    const STATUS_RETIRED = 'retired';
    const STATUS_LOST = 'lost';
    
    const LOCATION_WAREHOUSE = 'warehouse';
    const LOCATION_DISPATCH = 'dispatch';
    const LOCATION_REPAIR = 'repair';
    const LOCATION_SITE = 'site';
    const LOCATION_VENDOR = 'vendor';
    const LOCATION_CUSTOMER = 'customer';
    
    const ACTION_CREATED = 'created';
    const ACTION_MOVED = 'moved';
    const ACTION_DISPATCHED = 'dispatched';
    const ACTION_RECEIVED = 'received';
    const ACTION_REPAIR_START = 'repair_start';
    const ACTION_REPAIR_END = 'repair_end';
    const ACTION_RETIRED = 'retired';
    const ACTION_STATUS_CHANGE = 'status_change';
    
    /**
     * Validate asset data
     */
    public function validate($data, $isUpdate = false, $id = null) {
        $errors = [];
        
        if (empty($data['product_id'])) {
            $errors[] = 'Product is required';
        }
        
        if (!empty($data['serial_number'])) {
            if ($this->serialNumberExists($data['serial_number'], $id)) {
                $errors[] = 'Serial number already exists';
            }
        }
        
        if (!empty($data['barcode'])) {
            if ($this->barcodeExists($data['barcode'], $id)) {
                $errors[] = 'Barcode already exists';
            }
        }
        
        if (isset($data['status']) && !in_array($data['status'], [
            self::STATUS_AVAILABLE, self::STATUS_DISPATCHED, self::STATUS_IN_REPAIR, 
            self::STATUS_RETIRED, self::STATUS_LOST
        ])) {
            $errors[] = 'Invalid status value';
        }
        
        if (isset($data['current_location_type']) && !in_array($data['current_location_type'], [
            self::LOCATION_WAREHOUSE, self::LOCATION_DISPATCH, self::LOCATION_REPAIR,
            self::LOCATION_SITE, self::LOCATION_VENDOR, self::LOCATION_CUSTOMER
        ])) {
            $errors[] = 'Invalid location type';
        }
        
        return $errors;
    }
    
    /**
     * Check if serial number exists
     */
    public function serialNumberExists($serialNumber, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE serial_number = ?";
        $params = [$serialNumber];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Check if barcode exists
     */
    public function barcodeExists($barcode, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE barcode = ?";
        $params = [$barcode];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Register new asset
     */
    public function register($data) {
        $this->beginTransaction();
        
        try {
            // Set defaults
            if (empty($data['status'])) {
                $data['status'] = self::STATUS_AVAILABLE;
            }
            
            if (empty($data['current_location_type'])) {
                $data['current_location_type'] = self::LOCATION_WAREHOUSE;
            }
            
            $assetId = $this->create($data);
            
            if ($assetId) {
                // Log creation in history
                $this->logHistory($assetId, self::ACTION_CREATED, null, null, 
                    $data['current_location_type'], $data['current_location_id'] ?? null, 
                    'Asset registered');
            }
            
            $this->commit();
            return $assetId;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Find asset by serial number
     */
    public function findBySerialNumber($serialNumber) {
        $sql = "SELECT a.*, p.name as product_name, p.sku
                FROM {$this->table} a
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE a.serial_number = ? AND a.company_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$serialNumber, $this->getCurrentCompanyId()]);
        return $stmt->fetch();
    }
    
    /**
     * Find asset by barcode
     */
    public function findByBarcode($barcode) {
        $sql = "SELECT a.*, p.name as product_name, p.sku
                FROM {$this->table} a
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE a.barcode = ? AND a.company_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$barcode, $this->getCurrentCompanyId()]);
        return $stmt->fetch();
    }
    
    /**
     * Update asset location
     */
    public function updateLocation($assetId, $locationType, $locationId = null, $notes = null) {
        $asset = $this->find($assetId);
        if (!$asset) {
            throw new Exception('Asset not found');
        }
        
        $this->beginTransaction();
        
        try {
            $oldLocationType = $asset['current_location_type'];
            $oldLocationId = $asset['current_location_id'];
            
            $result = $this->update($assetId, [
                'current_location_type' => $locationType,
                'current_location_id' => $locationId
            ]);
            
            if ($result) {
                $this->logHistory($assetId, self::ACTION_MOVED, 
                    $oldLocationType, $oldLocationId,
                    $locationType, $locationId, $notes);
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Update asset status
     */
    public function updateStatus($assetId, $newStatus, $notes = null) {
        $asset = $this->find($assetId);
        if (!$asset) {
            throw new Exception('Asset not found');
        }
        
        $this->beginTransaction();
        
        try {
            $result = $this->update($assetId, ['status' => $newStatus]);
            
            if ($result) {
                $actionType = self::ACTION_STATUS_CHANGE;
                
                // Use specific action types for certain status changes
                if ($newStatus === self::STATUS_RETIRED) {
                    $actionType = self::ACTION_RETIRED;
                }
                
                $this->logHistory($assetId, $actionType, 
                    $asset['current_location_type'], $asset['current_location_id'],
                    $asset['current_location_type'], $asset['current_location_id'],
                    "Status changed from {$asset['status']} to {$newStatus}. " . ($notes ?? ''));
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Mark asset as dispatched
     */
    public function markDispatched($assetId, $dispatchId, $destinationType, $destinationId = null) {
        $asset = $this->find($assetId);
        if (!$asset) {
            throw new Exception('Asset not found');
        }
        
        if ($asset['status'] !== self::STATUS_AVAILABLE) {
            throw new Exception('Only available assets can be dispatched');
        }
        
        $this->beginTransaction();
        
        try {
            $result = $this->update($assetId, [
                'status' => self::STATUS_DISPATCHED,
                'current_location_type' => $destinationType,
                'current_location_id' => $destinationId
            ]);
            
            if ($result) {
                $this->logHistory($assetId, self::ACTION_DISPATCHED,
                    $asset['current_location_type'], $asset['current_location_id'],
                    $destinationType, $destinationId,
                    "Dispatched via dispatch #{$dispatchId}");
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Mark asset as received
     */
    public function markReceived($assetId, $locationType, $locationId = null, $notes = null) {
        $asset = $this->find($assetId);
        if (!$asset) {
            throw new Exception('Asset not found');
        }
        
        $this->beginTransaction();
        
        try {
            $result = $this->update($assetId, [
                'status' => self::STATUS_AVAILABLE,
                'current_location_type' => $locationType,
                'current_location_id' => $locationId
            ]);
            
            if ($result) {
                $this->logHistory($assetId, self::ACTION_RECEIVED,
                    $asset['current_location_type'], $asset['current_location_id'],
                    $locationType, $locationId, $notes);
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Start repair for asset
     */
    public function startRepair($assetId, $repairId, $vendorId = null) {
        $asset = $this->find($assetId);
        if (!$asset) {
            throw new Exception('Asset not found');
        }
        
        $this->beginTransaction();
        
        try {
            $result = $this->update($assetId, [
                'status' => self::STATUS_IN_REPAIR,
                'current_location_type' => self::LOCATION_REPAIR,
                'current_location_id' => $repairId
            ]);
            
            if ($result) {
                $this->logHistory($assetId, self::ACTION_REPAIR_START,
                    $asset['current_location_type'], $asset['current_location_id'],
                    self::LOCATION_REPAIR, $repairId,
                    "Repair started - Repair #{$repairId}");
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * End repair for asset
     */
    public function endRepair($assetId, $returnLocationType, $returnLocationId = null) {
        $asset = $this->find($assetId);
        if (!$asset) {
            throw new Exception('Asset not found');
        }
        
        if ($asset['status'] !== self::STATUS_IN_REPAIR) {
            throw new Exception('Asset is not in repair');
        }
        
        $this->beginTransaction();
        
        try {
            $result = $this->update($assetId, [
                'status' => self::STATUS_AVAILABLE,
                'current_location_type' => $returnLocationType,
                'current_location_id' => $returnLocationId
            ]);
            
            if ($result) {
                $this->logHistory($assetId, self::ACTION_REPAIR_END,
                    $asset['current_location_type'], $asset['current_location_id'],
                    $returnLocationType, $returnLocationId,
                    'Repair completed');
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Log asset history
     */
    protected function logHistory($assetId, $actionType, $fromLocationType, $fromLocationId, $toLocationType, $toLocationId, $notes = null) {
        $sql = "INSERT INTO {$this->historyTable} 
                (asset_id, action_type, from_location_type, from_location_id, to_location_type, to_location_id, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $assetId,
            $actionType,
            $fromLocationType,
            $fromLocationId,
            $toLocationType,
            $toLocationId,
            $notes,
            $this->getCurrentUserId()
        ]);
    }
    
    /**
     * Get asset history
     */
    public function getHistory($assetId, $limit = 100) {
        $sql = "SELECT * FROM {$this->historyTable} 
                WHERE asset_id = ? 
                ORDER BY created_at DESC 
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get asset with product info
     */
    public function getWithProduct($assetId) {
        $sql = "SELECT a.*, p.name as product_name, p.sku, p.unit_of_measure
                FROM {$this->table} a
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE a.id = ? AND a.company_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId, $this->getCurrentCompanyId()]);
        return $stmt->fetch();
    }
    
    /**
     * Get assets by product
     */
    public function getByProduct($productId, $warehouseId = null, $status = null) {
        $sql = "SELECT a.*, w.name as warehouse_name 
                FROM {$this->table} a
                LEFT JOIN sar_inv_warehouses w ON a.warehouse_id = w.id
                WHERE a.product_id = ? AND a.company_id = ?";
        $params = [$productId, $this->getCurrentCompanyId()];
        
        if ($warehouseId) {
            $sql .= " AND a.warehouse_id = ?";
            $params[] = $warehouseId;
        }
        
        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY a.serial_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get assets by location
     */
    public function getByLocation($locationType, $locationId = null) {
        $sql = "SELECT a.*, p.name as product_name, p.sku
                FROM {$this->table} a
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE a.current_location_type = ? AND a.company_id = ?";
        $params = [$locationType, $this->getCurrentCompanyId()];
        
        if ($locationId !== null) {
            $sql .= " AND a.current_location_id = ?";
            $params[] = $locationId;
        }
        
        $sql .= " ORDER BY p.name, a.serial_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get assets by status
     */
    public function getByStatus($status) {
        $sql = "SELECT a.*, p.name as product_name, p.sku
                FROM {$this->table} a
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE a.status = ? AND a.company_id = ?
                ORDER BY p.name, a.serial_number";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status, $this->getCurrentCompanyId()]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search assets
     */
    public function search($keyword = null, $status = null, $productId = null, $locationType = null) {
        $sql = "SELECT a.*, p.name as product_name, p.sku
                FROM {$this->table} a
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE a.company_id = ?";
        $params = [$this->getCurrentCompanyId()];
        
        if ($keyword) {
            $sql .= " AND (a.serial_number LIKE ? OR a.barcode LIKE ? OR p.name LIKE ?)";
            $keyword = "%{$keyword}%";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        if ($status) {
            $sql .= " AND a.status = ?";
            $params[] = $status;
        }
        
        if ($productId) {
            $sql .= " AND a.product_id = ?";
            $params[] = $productId;
        }
        
        if ($locationType) {
            $sql .= " AND a.current_location_type = ?";
            $params[] = $locationType;
        }
        
        $sql .= " ORDER BY p.name, a.serial_number";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if asset is available
     */
    public function isAvailable($assetId) {
        $asset = $this->find($assetId);
        return $asset && $asset['status'] === self::STATUS_AVAILABLE;
    }
    
    /**
     * Get assets with expiring warranty
     */
    public function getExpiringWarranty($daysAhead = 30) {
        $sql = "SELECT a.*, p.name as product_name, p.sku
                FROM {$this->table} a
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE a.company_id = ? 
                AND a.warranty_expiry IS NOT NULL
                AND a.warranty_expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND a.status != 'retired'
                ORDER BY a.warranty_expiry";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->getCurrentCompanyId(), $daysAhead]);
        return $stmt->fetchAll();
    }
}
?>
