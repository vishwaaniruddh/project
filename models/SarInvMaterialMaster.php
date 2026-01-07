<?php
require_once __DIR__ . '/SarInvBaseModel.php';

/**
 * SAR Inventory Material Master Model
 * Manages material templates with specifications
 */
class SarInvMaterialMaster extends SarInvBaseModel {
    protected $table = 'sar_inv_material_masters';
    
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    
    /**
     * Validate material master data
     */
    public function validate($data, $isUpdate = false, $id = null) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Material name is required';
        }
        
        if (empty($data['code'])) {
            $errors[] = 'Material code is required';
        } else {
            if ($this->codeExists($data['code'], $id)) {
                $errors[] = 'Material code already exists';
            }
        }
        
        if (isset($data['default_quantity']) && $data['default_quantity'] < 0) {
            $errors[] = 'Default quantity cannot be negative';
        }
        
        if (isset($data['status']) && !in_array($data['status'], [self::STATUS_ACTIVE, self::STATUS_INACTIVE])) {
            $errors[] = 'Invalid status value';
        }
        
        return $errors;
    }
    
    /**
     * Check if code exists
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
     * Find by code
     */
    public function findByCode($code) {
        $sql = "SELECT * FROM {$this->table} WHERE code = ? AND company_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$code, $this->getCurrentCompanyId()]);
        return $stmt->fetch();
    }
    
    /**
     * Get active material masters
     */
    public function getActiveMaterials() {
        return $this->findAll(['status' => self::STATUS_ACTIVE]);
    }
    
    /**
     * Create material master with JSON specifications handling
     */
    public function create($data) {
        if (isset($data['specifications']) && is_array($data['specifications'])) {
            $data['specifications'] = json_encode($data['specifications']);
        }
        return parent::create($data);
    }
    
    /**
     * Update material master with JSON specifications handling
     */
    public function update($id, $data) {
        if (isset($data['specifications']) && is_array($data['specifications'])) {
            $data['specifications'] = json_encode($data['specifications']);
        }
        return parent::update($id, $data);
    }
    
    /**
     * Set specifications
     */
    public function setSpecifications($materialId, $specifications) {
        $data = ['specifications' => json_encode($specifications)];
        return $this->update($materialId, $data);
    }
    
    /**
     * Get specifications (decoded JSON)
     */
    public function getSpecifications($materialId) {
        $material = $this->find($materialId);
        if (!$material || !$material['specifications']) {
            return [];
        }
        return json_decode($material['specifications'], true) ?: [];
    }
    
    /**
     * Search material masters
     */
    public function search($keyword = null, $status = null) {
        $sql = "SELECT * FROM {$this->table} WHERE company_id = ?";
        $params = [$this->getCurrentCompanyId()];
        
        if ($keyword) {
            $sql .= " AND (name LIKE ? OR code LIKE ? OR description LIKE ?)";
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
    
    /**
     * Get material masters with request counts
     */
    public function getWithRequestCounts() {
        $sql = "SELECT m.*, 
                    COUNT(mr.id) as total_requests,
                    SUM(CASE WHEN mr.status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
                    SUM(CASE WHEN mr.status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled_requests
                FROM {$this->table} m
                LEFT JOIN sar_inv_material_requests mr ON m.id = mr.material_master_id
                WHERE m.company_id = ?
                GROUP BY m.id
                ORDER BY m.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->getCurrentCompanyId()]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if material master has requests
     */
    public function hasRequests($materialId) {
        $sql = "SELECT COUNT(*) FROM sar_inv_material_requests WHERE material_master_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$materialId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Safe delete - prevents deletion if material has requests
     */
    public function safeDelete($materialId) {
        if ($this->hasRequests($materialId)) {
            return ['success' => false, 'error' => 'Cannot delete material master with existing requests'];
        }
        
        $result = $this->delete($materialId);
        return ['success' => $result, 'error' => $result ? null : 'Failed to delete material master'];
    }
    
    /**
     * Duplicate material master
     */
    public function duplicate($materialId, $newCode = null) {
        $material = $this->find($materialId);
        if (!$material) {
            throw new Exception('Material master not found');
        }
        
        // Remove id and timestamps
        unset($material['id'], $material['created_at'], $material['updated_at']);
        
        // Set new code
        if ($newCode) {
            $material['code'] = $newCode;
        } else {
            $material['code'] = $material['code'] . '_copy_' . time();
        }
        
        $material['name'] = $material['name'] . ' (Copy)';
        
        return $this->create($material);
    }
}
?>
