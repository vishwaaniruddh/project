<?php
require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../config/auth.php';

class BoqMasterItem extends BaseModel {
    protected $table = 'boq_master_items';
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Create a new BOQ master item association
     */
    public function create($data) {
        // Add created_by from current user
        $currentUser = Auth::getCurrentUser();
        if ($currentUser) {
            $data['created_by'] = $currentUser['id'];
        }
        
        // Set default values
        if (!isset($data['default_quantity'])) {
            $data['default_quantity'] = 1.00;
        }
        if (!isset($data['sort_order'])) {
            $data['sort_order'] = 0;
        }
        if (!isset($data['status'])) {
            $data['status'] = 'active';
        }
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute(array_values($data));
        
        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * Update an existing BOQ master item association
     */
    public function update($id, $data) {
        // Add updated_by from current user
        $currentUser = Auth::getCurrentUser();
        if ($currentUser) {
            $data['updated_by'] = $currentUser['id'];
        }
        
        $fields = array_keys($data);
        $setClause = array_map(function($field) {
            return "$field = ?";
        }, $fields);
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE id = ?";
        
        $params = array_values($data);
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Delete a BOQ master item association
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Find a BOQ master item by ID
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all items associated with a specific BOQ master
     * Includes joined data from boq_items table
     */
    public function getByBoqMaster($boqMasterId) {
        $sql = "SELECT bmi.*, 
                       bi.item_name, 
                       bi.item_code, 
                       bi.description as item_description,
                       bi.unit, 
                       bi.category,
                       bi.icon_class,
                       bi.need_serial_number
                FROM {$this->table} bmi
                INNER JOIN boq_items bi ON bmi.boq_item_id = bi.id
                WHERE bmi.boq_master_id = ? 
                AND bmi.status = 'active'
                ORDER BY bmi.sort_order ASC, bi.item_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boqMasterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if a BOQ item is already associated with a BOQ master
     */
    public function isDuplicate($boqMasterId, $boqItemId, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE boq_master_id = ? AND boq_item_id = ?";
        $params = [$boqMasterId, $boqItemId];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Validate BOQ master item data
     */
    public function validateData($data, $isUpdate = false, $id = null) {
        $errors = [];
        
        // BOQ master ID validation
        if (empty($data['boq_master_id'])) {
            $errors['boq_master_id'] = 'BOQ master ID is required';
        } elseif (!is_numeric($data['boq_master_id'])) {
            $errors['boq_master_id'] = 'BOQ master ID must be a number';
        }
        
        // BOQ item ID validation
        if (empty($data['boq_item_id'])) {
            $errors['boq_item_id'] = 'Please select an item';
        } elseif (!is_numeric($data['boq_item_id'])) {
            $errors['boq_item_id'] = 'BOQ item ID must be a number';
        }
        
        // Check for duplicate association
        if (!empty($data['boq_master_id']) && !empty($data['boq_item_id'])) {
            $excludeId = $isUpdate ? $id : null;
            if ($this->isDuplicate($data['boq_master_id'], $data['boq_item_id'], $excludeId)) {
                $errors['boq_item_id'] = 'This item is already added to this BOQ';
            }
        }
        
        // Default quantity validation
        if (isset($data['default_quantity'])) {
            if (!is_numeric($data['default_quantity'])) {
                $errors['default_quantity'] = 'Quantity must be a number';
            } elseif ($data['default_quantity'] <= 0) {
                $errors['default_quantity'] = 'Quantity must be greater than 0';
            }
        }
        
        // Sort order validation
        if (isset($data['sort_order']) && !is_numeric($data['sort_order'])) {
            $errors['sort_order'] = 'Sort order must be a number';
        }
        
        // Status validation
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            $errors['status'] = 'Invalid status selected';
        }
        
        return $errors;
    }
    
    /**
     * Get BOQ master item with joined BOQ item details
     */
    public function getWithItemDetails($id) {
        $sql = "SELECT bmi.*, 
                       bi.item_name, 
                       bi.item_code, 
                       bi.description as item_description,
                       bi.unit, 
                       bi.category,
                       bi.icon_class,
                       bi.need_serial_number
                FROM {$this->table} bmi
                INNER JOIN boq_items bi ON bmi.boq_item_id = bi.id
                WHERE bmi.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update sort order for multiple items
     */
    public function updateSortOrder($boqMasterId, $itemOrders) {
        $currentUser = Auth::getCurrentUser();
        $userId = $currentUser ? $currentUser['id'] : null;
        
        $sql = "UPDATE {$this->table} SET sort_order = ?, updated_by = ? WHERE id = ? AND boq_master_id = ?";
        $stmt = $this->db->prepare($sql);
        
        $success = true;
        foreach ($itemOrders as $itemId => $sortOrder) {
            $result = $stmt->execute([$sortOrder, $userId, $itemId, $boqMasterId]);
            if (!$result) {
                $success = false;
            }
        }
        
        return $success;
    }
    
    /**
     * Get count of items for a BOQ master
     */
    public function getItemCountByBoqMaster($boqMasterId) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE boq_master_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boqMasterId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Delete all items for a BOQ master (used when deleting BOQ master)
     */
    public function deleteByBoqMaster($boqMasterId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE boq_master_id = ?");
        return $stmt->execute([$boqMasterId]);
    }
    
    /**
     * Get active items for a BOQ master
     */
    public function getActiveByBoqMaster($boqMasterId) {
        $sql = "SELECT bmi.*, 
                       bi.item_name, 
                       bi.item_code, 
                       bi.unit, 
                       bi.category
                FROM {$this->table} bmi
                INNER JOIN boq_items bi ON bmi.boq_item_id = bi.id
                WHERE bmi.boq_master_id = ? 
                AND bmi.status = 'active'
                AND bi.status = 'active'
                ORDER BY bmi.sort_order ASC, bi.item_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boqMasterId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Toggle status of a BOQ master item
     */
    public function toggleStatus($id) {
        $record = $this->find($id);
        if (!$record) {
            return false;
        }
        
        $newStatus = $record['status'] === 'active' ? 'inactive' : 'active';
        return $this->update($id, ['status' => $newStatus]);
    }
    
    /**
     * Get statistics for BOQ master items
     */
    public function getStats() {
        $stats = [];
        
        // Total associations
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Active associations
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'");
        $stats['active'] = $stmt->fetchColumn();
        
        // Inactive associations
        $stats['inactive'] = $stats['total'] - $stats['active'];
        
        // BOQ masters with items
        $stmt = $this->db->query("SELECT COUNT(DISTINCT boq_master_id) FROM {$this->table}");
        $stats['boq_masters_with_items'] = $stmt->fetchColumn();
        
        // Average items per BOQ
        if ($stats['boq_masters_with_items'] > 0) {
            $stats['avg_items_per_boq'] = round($stats['active'] / $stats['boq_masters_with_items'], 2);
        } else {
            $stats['avg_items_per_boq'] = 0;
        }
        
        return $stats;
    }
    
    /**
     * Update BOQ master item by BOQ master ID and item ID
     */
    public function updateByBoqMasterAndItem($boqMasterId, $boqItemId, $data) {
        // Add updated_by from current user
        $currentUser = Auth::getCurrentUser();
        if ($currentUser) {
            $data['updated_by'] = $currentUser['id'];
        }
        
        $fields = array_keys($data);
        $setClause = array_map(function($field) {
            return "$field = ?";
        }, $fields);
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE boq_master_id = ? AND boq_item_id = ?";
        
        $params = array_values($data);
        $params[] = $boqMasterId;
        $params[] = $boqItemId;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    /**
     * Remove item from BOQ master
     */
    public function removeFromBoqMaster($boqMasterId, $boqItemId) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE boq_master_id = ? AND boq_item_id = ?");
        return $stmt->execute([$boqMasterId, $boqItemId]);
    }
}
?>