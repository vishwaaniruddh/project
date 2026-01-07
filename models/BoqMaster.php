<?php
require_once __DIR__ . '/BaseMaster.php';
require_once __DIR__ . '/../config/auth.php';

class BoqMaster extends BaseMaster {
    protected $table = 'boq_master';
    protected $primaryKey = 'boq_id';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function create($data) {
        // Add created_by from current user
        $currentUser = Auth::getCurrentUser();
        if ($currentUser) {
            $data['created_by'] = $currentUser['id'];
        }
        
        // Convert boolean values
        if (isset($data['is_serial_number_required'])) {
            $data['is_serial_number_required'] = $data['is_serial_number_required'] ? 1 : 0;
        }
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
    
    public function update($id, $data) {
        // Add updated_by from current user
        $currentUser = Auth::getCurrentUser();
        if ($currentUser) {
            $data['updated_by'] = $currentUser['id'];
        }
        
        // Convert boolean values
        if (isset($data['is_serial_number_required'])) {
            $data['is_serial_number_required'] = $data['is_serial_number_required'] ? 1 : 0;
        }
        
        $fields = array_keys($data);
        $setClause = array_map(function($field) {
            return "$field = ?";
        }, $fields);
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . " WHERE {$this->primaryKey} = ?";
        
        $params = array_values($data);
        $params[] = $id;
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }
    
    public function getAllWithPagination($page = 1, $limit = 20, $search = '', $status = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        // Search functionality
        if (!empty($search)) {
            $conditions[] = "boq_name LIKE ?";
            $params[] = "%$search%";
        }
        
        // Filter by status
        if (!empty($status)) {
            $conditions[] = "status = ?";
            $params[] = $status;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(" AND ", $conditions);
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated results with user information
        $sql = "SELECT b.*, 
                       cu.username as created_by_name,
                       uu.username as updated_by_name
                FROM {$this->table} b 
                LEFT JOIN users cu ON b.created_by = cu.id 
                LEFT JOIN users uu ON b.updated_by = uu.id 
                $whereClause 
                ORDER BY b.boq_name ASC 
                LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();
        
        return [
            'records' => $records,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    public function getAllWithItemCount($page = 1, $limit = 20, $search = '', $status = '') {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        // Search functionality
        if (!empty($search)) {
            $conditions[] = "b.boq_name LIKE ?";
            $params[] = "%$search%";
        }
        
        // Filter by status
        if (!empty($status)) {
            $conditions[] = "b.status = ?";
            $params[] = $status;
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(" AND ", $conditions);
        }
        
        // Get total count
        $countSql = "SELECT COUNT(*) FROM {$this->table} b $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated results with user information and item count
        $sql = "SELECT b.*, 
                       cu.username as created_by_name,
                       uu.username as updated_by_name,
                       COALESCE(item_counts.item_count, 0) as item_count
                FROM {$this->table} b 
                LEFT JOIN users cu ON b.created_by = cu.id 
                LEFT JOIN users uu ON b.updated_by = uu.id 
                LEFT JOIN (
                    SELECT boq_master_id, COUNT(*) as item_count 
                    FROM boq_master_items 
                    WHERE status = 'active' 
                    GROUP BY boq_master_id
                ) item_counts ON b.boq_id = item_counts.boq_master_id
                $whereClause 
                ORDER BY b.boq_name ASC 
                LIMIT $limit OFFSET $offset";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();
        
        return [
            'records' => $records,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    public function toggleStatus($id) {
        $record = $this->find($id);
        if (!$record) {
            return false;
        }
        
        $newStatus = $record['status'] === 'active' ? 'inactive' : 'active';
        return $this->update($id, ['status' => $newStatus]);
    }
    
    public function validateBoqData($data, $isUpdate = false, $recordId = null) {
        $errors = [];
        
        // BOQ name validation
        if (empty($data['boq_name'])) {
            $errors['boq_name'] = 'BOQ name is required';
        } elseif (strlen($data['boq_name']) < 2) {
            $errors['boq_name'] = 'BOQ name must be at least 2 characters';
        } elseif (strlen($data['boq_name']) > 200) {
            $errors['boq_name'] = 'BOQ name must not exceed 200 characters';
        } else {
            // Check if name already exists
            $existingRecord = $this->findByName($data['boq_name']);
            if ($existingRecord && (!$isUpdate || $existingRecord[$this->primaryKey] != $recordId)) {
                $errors['boq_name'] = 'BOQ name already exists';
            }
        }
        
        // Status validation
        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            $errors['status'] = 'Invalid status selected';
        }
        
        return $errors;
    }
    
    public function findByName($name) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE boq_name = ?");
        $stmt->execute([$name]);
        return $stmt->fetch();
    }
    
    public function getActive() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY boq_name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getAll() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY boq_name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getBoqStats() {
        $stats = [];
        
        // Total records
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Active records
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'");
        $stats['active'] = $stmt->fetchColumn();
        
        // Inactive records
        $stats['inactive'] = $stats['total'] - $stats['active'];
        
        // Serial number required
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE is_serial_number_required = 1");
        $stats['serial_required'] = $stmt->fetchColumn();
        
        // Recent records (last 30 days)
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['recent'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    public function getWithItems($boqId) {
        // First get the BOQ master details
        $boqMaster = $this->find($boqId);
        if (!$boqMaster) {
            return null;
        }
        
        // Get associated items with details from boq_items table
        $sql = "SELECT bmi.*, 
                       bi.item_name,
                       bi.item_code,
                       bi.description as item_description,
                       bi.unit,
                       bi.category,
                       bi.icon_class,
                       bi.need_serial_number,
                       bi.status as item_status
                FROM boq_master_items bmi
                INNER JOIN boq_items bi ON bmi.boq_item_id = bi.id
                WHERE bmi.boq_master_id = ? AND bmi.status = 'active'
                ORDER BY bmi.sort_order ASC, bi.item_name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$boqId]);
        $items = $stmt->fetchAll();
        
        // Add items to the BOQ master data
        $boqMaster['items'] = $items;
        $boqMaster['item_count'] = count($items);
        
        return $boqMaster;
    }
}