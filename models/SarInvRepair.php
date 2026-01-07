<?php
require_once __DIR__ . '/SarInvBaseModel.php';
require_once __DIR__ . '/SarInvAsset.php';

/**
 * SAR Inventory Repair Model
 * Manages repair workflow with cost tracking and asset status updates
 */
class SarInvRepair extends SarInvBaseModel {
    protected $table = 'sar_inv_repairs';
    protected $enableCompanyIsolation = false;
    
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Validate repair data
     */
    public function validate($data, $isUpdate = false, $id = null) {
        $errors = [];
        
        if (empty($data['asset_id'])) {
            $errors[] = 'Asset is required';
        } else {
            $assetModel = new SarInvAsset();
            $asset = $assetModel->find($data['asset_id']);
            if (!$asset) {
                $errors[] = 'Asset not found';
            }
        }
        
        if (isset($data['status']) && !in_array($data['status'], [
            self::STATUS_PENDING, self::STATUS_IN_PROGRESS, 
            self::STATUS_COMPLETED, self::STATUS_CANCELLED
        ])) {
            $errors[] = 'Invalid status value';
        }
        
        if (isset($data['cost']) && $data['cost'] < 0) {
            $errors[] = 'Cost cannot be negative';
        }
        
        return $errors;
    }
    
    /**
     * Generate unique repair number
     */
    public function generateRepairNumber() {
        $prefix = 'RPR';
        $date = date('Ymd');
        
        $sql = "SELECT MAX(CAST(SUBSTRING(repair_number, 12) AS UNSIGNED)) as max_num 
                FROM {$this->table} 
                WHERE repair_number LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix . $date . '%']);
        $result = $stmt->fetch();
        
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return $prefix . $date . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create repair and update asset status
     */
    public function createRepair($data) {
        $this->beginTransaction();
        
        try {
            // Generate repair number if not provided
            if (empty($data['repair_number'])) {
                $data['repair_number'] = $this->generateRepairNumber();
            }
            
            // Set default status
            if (empty($data['status'])) {
                $data['status'] = self::STATUS_PENDING;
            }
            
            // Set created_by
            $data['created_by'] = $this->getCurrentUserId();
            
            // Create repair record
            $repairId = parent::create($data);
            
            if (!$repairId) {
                throw new Exception('Failed to create repair');
            }
            
            // Update asset status to in_repair
            $assetModel = new SarInvAsset();
            $assetModel->startRepair($data['asset_id'], $repairId, $data['vendor_id'] ?? null);
            
            $this->commit();
            return $repairId;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Start repair (change status to in_progress)
     */
    public function startRepair($repairId) {
        $repair = $this->find($repairId);
        if (!$repair) {
            throw new Exception('Repair not found');
        }
        
        if ($repair['status'] !== self::STATUS_PENDING) {
            throw new Exception('Only pending repairs can be started');
        }
        
        return $this->update($repairId, [
            'status' => self::STATUS_IN_PROGRESS,
            'start_date' => date('Y-m-d')
        ]);
    }
    
    /**
     * Complete repair and update asset status
     */
    public function completeRepair($repairId, $repairNotes = null, $cost = null, $returnWarehouseId = null) {
        $repair = $this->find($repairId);
        if (!$repair) {
            throw new Exception('Repair not found');
        }
        
        if (!in_array($repair['status'], [self::STATUS_PENDING, self::STATUS_IN_PROGRESS])) {
            throw new Exception('Only pending or in-progress repairs can be completed');
        }
        
        $this->beginTransaction();
        
        try {
            $updateData = [
                'status' => self::STATUS_COMPLETED,
                'completion_date' => date('Y-m-d')
            ];
            
            if ($repairNotes !== null) {
                $updateData['repair_notes'] = $repairNotes;
            }
            
            if ($cost !== null) {
                $updateData['cost'] = $cost;
            }
            
            $result = $this->update($repairId, $updateData);
            
            if ($result) {
                // Update asset status back to available
                $assetModel = new SarInvAsset();
                $locationType = $returnWarehouseId ? SarInvAsset::LOCATION_WAREHOUSE : SarInvAsset::LOCATION_WAREHOUSE;
                $assetModel->endRepair($repair['asset_id'], $locationType, $returnWarehouseId);
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Cancel repair and restore asset status
     */
    public function cancelRepair($repairId, $reason = null) {
        $repair = $this->find($repairId);
        if (!$repair) {
            throw new Exception('Repair not found');
        }
        
        if ($repair['status'] === self::STATUS_COMPLETED) {
            throw new Exception('Cannot cancel completed repairs');
        }
        
        if ($repair['status'] === self::STATUS_CANCELLED) {
            throw new Exception('Repair is already cancelled');
        }
        
        $this->beginTransaction();
        
        try {
            $notes = $repair['repair_notes'] ? $repair['repair_notes'] . "\n" : '';
            $notes .= "Cancelled: " . ($reason ?? 'No reason provided');
            
            $result = $this->update($repairId, [
                'status' => self::STATUS_CANCELLED,
                'repair_notes' => $notes
            ]);
            
            if ($result) {
                // Restore asset status
                $assetModel = new SarInvAsset();
                $assetModel->updateStatus($repair['asset_id'], SarInvAsset::STATUS_AVAILABLE, 
                    "Repair #{$repair['repair_number']} cancelled");
            }
            
            $this->commit();
            return $result;
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
    
    /**
     * Update repair cost
     */
    public function updateCost($repairId, $cost) {
        if ($cost < 0) {
            throw new Exception('Cost cannot be negative');
        }
        
        return $this->update($repairId, ['cost' => $cost]);
    }
    
    /**
     * Update diagnosis
     */
    public function updateDiagnosis($repairId, $diagnosis) {
        return $this->update($repairId, ['diagnosis' => $diagnosis]);
    }
    
    /**
     * Get repair with asset and product info
     */
    public function getWithDetails($repairId) {
        $sql = "SELECT r.*, 
                    a.serial_number, a.barcode, a.status as asset_status,
                    p.name as product_name, p.sku
                FROM {$this->table} r
                JOIN sar_inv_assets a ON r.asset_id = a.id
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE r.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$repairId]);
        return $stmt->fetch();
    }
    
    /**
     * Get repairs by status
     */
    public function getByStatus($status) {
        $sql = "SELECT r.*, 
                    a.serial_number, a.barcode,
                    p.name as product_name, p.sku
                FROM {$this->table} r
                JOIN sar_inv_assets a ON r.asset_id = a.id
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE r.status = ?
                ORDER BY r.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get repairs by asset
     */
    public function getByAsset($assetId) {
        $sql = "SELECT * FROM {$this->table} WHERE asset_id = ? ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assetId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get repairs by vendor
     */
    public function getByVendor($vendorId, $status = null) {
        $sql = "SELECT r.*, 
                    a.serial_number, a.barcode,
                    p.name as product_name, p.sku
                FROM {$this->table} r
                JOIN sar_inv_assets a ON r.asset_id = a.id
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE r.vendor_id = ?";
        $params = [$vendorId];
        
        if ($status) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Search repairs
     */
    public function search($keyword = null, $status = null, $vendorId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT r.*, 
                    a.serial_number, a.barcode,
                    p.name as product_name, p.sku
                FROM {$this->table} r
                JOIN sar_inv_assets a ON r.asset_id = a.id
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE 1=1";
        $params = [];
        
        if ($keyword) {
            $sql .= " AND (r.repair_number LIKE ? OR a.serial_number LIKE ? OR p.name LIKE ? OR r.issue_description LIKE ?)";
            $keyword = "%{$keyword}%";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        if ($status) {
            $sql .= " AND r.status = ?";
            $params[] = $status;
        }
        
        if ($vendorId) {
            $sql .= " AND r.vendor_id = ?";
            $params[] = $vendorId;
        }
        
        if ($dateFrom) {
            $sql .= " AND r.start_date >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND r.start_date <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Find by repair number
     */
    public function findByNumber($repairNumber) {
        $sql = "SELECT * FROM {$this->table} WHERE repair_number = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$repairNumber]);
        return $stmt->fetch();
    }
    
    /**
     * Get total repair costs
     */
    public function getTotalCosts($filters = []) {
        $sql = "SELECT 
                    COUNT(*) as total_repairs,
                    SUM(cost) as total_cost,
                    AVG(cost) as average_cost
                FROM {$this->table}
                WHERE status = 'completed'";
        $params = [];
        
        if (!empty($filters['vendor_id'])) {
            $sql .= " AND vendor_id = ?";
            $params[] = $filters['vendor_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND completion_date >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND completion_date <= ?";
            $params[] = $filters['date_to'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get overdue repairs (pending or in_progress for more than X days)
     */
    public function getOverdueRepairs($days = 7) {
        $sql = "SELECT r.*, 
                    a.serial_number, a.barcode,
                    p.name as product_name, p.sku,
                    DATEDIFF(CURDATE(), r.created_at) as days_pending
                FROM {$this->table} r
                JOIN sar_inv_assets a ON r.asset_id = a.id
                JOIN sar_inv_products p ON a.product_id = p.id
                WHERE r.status IN ('pending', 'in_progress')
                AND DATEDIFF(CURDATE(), r.created_at) > ?
                ORDER BY r.created_at ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get repair statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count,
                    SUM(cost) as total_cost
                FROM {$this->table}
                WHERE 1=1";
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND created_at >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND created_at <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " GROUP BY status";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
