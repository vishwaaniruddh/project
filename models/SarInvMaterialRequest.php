<?php
require_once __DIR__ . '/SarInvBaseModel.php';

/**
 * SAR Inventory Material Request Model
 * Manages material requests with workflow (pending, approved, fulfilled, rejected)
 */
class SarInvMaterialRequest extends SarInvBaseModel {
    protected $table = 'sar_inv_material_requests';
    protected $enableCompanyIsolation = false; // Table doesn't have company_id directly
    
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_FULFILLED = 'fulfilled';
    const STATUS_PARTIALLY_FULFILLED = 'partially_fulfilled';
    const STATUS_CANCELLED = 'cancelled';
    
    /**
     * Validate material request data
     */
    public function validate($data, $isUpdate = false, $id = null) {
        $errors = [];
        
        if (!$isUpdate && empty($data['quantity'])) {
            $errors[] = 'Quantity is required';
        }
        
        if (isset($data['quantity']) && $data['quantity'] <= 0) {
            $errors[] = 'Quantity must be greater than zero';
        }
        
        if (!$isUpdate && empty($data['material_master_id']) && empty($data['product_id'])) {
            $errors[] = 'Either material master or product must be specified';
        }
        
        if (isset($data['status']) && !in_array($data['status'], [
            self::STATUS_PENDING, self::STATUS_APPROVED, self::STATUS_REJECTED,
            self::STATUS_FULFILLED, self::STATUS_PARTIALLY_FULFILLED, self::STATUS_CANCELLED
        ])) {
            $errors[] = 'Invalid status value';
        }
        
        if (isset($data['fulfilled_quantity']) && $data['fulfilled_quantity'] < 0) {
            $errors[] = 'Fulfilled quantity cannot be negative';
        }
        
        return $errors;
    }
    
    /**
     * Generate unique request number
     */
    public function generateRequestNumber() {
        $prefix = 'MR';
        $date = date('Ymd');
        
        $sql = "SELECT MAX(CAST(SUBSTRING(request_number, 11) AS UNSIGNED)) as max_num 
                FROM {$this->table} 
                WHERE request_number LIKE ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$prefix . $date . '%']);
        $result = $stmt->fetch();
        
        $nextNum = ($result && $result['max_num']) ? $result['max_num'] + 1 : 1;
        return $prefix . $date . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create material request with auto-generated request number
     */
    public function create($data) {
        if (empty($data['request_number'])) {
            $data['request_number'] = $this->generateRequestNumber();
        }
        
        if (empty($data['requester_id'])) {
            $data['requester_id'] = $this->getCurrentUserId();
        }
        
        if (empty($data['status'])) {
            $data['status'] = self::STATUS_PENDING;
        }
        
        return parent::create($data);
    }
    
    /**
     * Find by request number
     */
    public function findByRequestNumber($requestNumber) {
        $sql = "SELECT mr.*, 
                    mm.name as material_name, mm.code as material_code,
                    p.name as product_name, p.sku as product_sku
                FROM {$this->table} mr
                LEFT JOIN sar_inv_material_masters mm ON mr.material_master_id = mm.id
                LEFT JOIN sar_inv_products p ON mr.product_id = p.id
                WHERE mr.request_number = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$requestNumber]);
        return $stmt->fetch();
    }
    
    /**
     * Get request with details
     */
    public function getWithDetails($id) {
        $sql = "SELECT mr.*, 
                    mm.name as material_name, mm.code as material_code, mm.unit_of_measure as material_uom,
                    p.name as product_name, p.sku as product_sku, p.unit_of_measure as product_uom,
                    CONCAT(req.first_name, ' ', req.last_name) as requester_name,
                    CONCAT(app.first_name, ' ', app.last_name) as approver_name
                FROM {$this->table} mr
                LEFT JOIN sar_inv_material_masters mm ON mr.material_master_id = mm.id
                LEFT JOIN sar_inv_products p ON mr.product_id = p.id
                LEFT JOIN users req ON mr.requester_id = req.id
                LEFT JOIN users app ON mr.approver_id = app.id
                WHERE mr.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Get requests by status
     */
    public function getByStatus($status, $limit = null, $offset = null) {
        $sql = "SELECT mr.*, 
                    mm.name as material_name, mm.code as material_code,
                    p.name as product_name, p.sku as product_sku,
                    CONCAT(req.first_name, ' ', req.last_name) as requester_name
                FROM {$this->table} mr
                LEFT JOIN sar_inv_material_masters mm ON mr.material_master_id = mm.id
                LEFT JOIN sar_inv_products p ON mr.product_id = p.id
                LEFT JOIN users req ON mr.requester_id = req.id
                WHERE mr.status = ?
                ORDER BY mr.created_at DESC";
        
        $params = [$status];
        
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
            if ($offset !== null) {
                $sql .= " OFFSET ?";
                $params[] = $offset;
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get requests by requester
     */
    public function getByRequester($requesterId, $status = null) {
        $sql = "SELECT mr.*, 
                    mm.name as material_name, mm.code as material_code,
                    p.name as product_name, p.sku as product_sku
                FROM {$this->table} mr
                LEFT JOIN sar_inv_material_masters mm ON mr.material_master_id = mm.id
                LEFT JOIN sar_inv_products p ON mr.product_id = p.id
                WHERE mr.requester_id = ?";
        $params = [$requesterId];
        
        if ($status) {
            $sql .= " AND mr.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY mr.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Approve request
     */
    public function approve($id, $approverId = null, $notes = null) {
        $request = $this->find($id);
        if (!$request) {
            throw new Exception('Material request not found');
        }
        
        if ($request['status'] !== self::STATUS_PENDING) {
            throw new Exception('Only pending requests can be approved');
        }
        
        $data = [
            'status' => self::STATUS_APPROVED,
            'approver_id' => $approverId ?: $this->getCurrentUserId()
        ];
        
        if ($notes) {
            $data['notes'] = $request['notes'] ? $request['notes'] . "\n[Approval] " . $notes : "[Approval] " . $notes;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Reject request
     */
    public function reject($id, $approverId = null, $reason = null) {
        $request = $this->find($id);
        if (!$request) {
            throw new Exception('Material request not found');
        }
        
        if ($request['status'] !== self::STATUS_PENDING) {
            throw new Exception('Only pending requests can be rejected');
        }
        
        $data = [
            'status' => self::STATUS_REJECTED,
            'approver_id' => $approverId ?: $this->getCurrentUserId()
        ];
        
        if ($reason) {
            $data['notes'] = $request['notes'] ? $request['notes'] . "\n[Rejection] " . $reason : "[Rejection] " . $reason;
        }
        
        return $this->update($id, $data);
    }

    /**
     * Fulfill request (full or partial)
     */
    public function fulfill($id, $fulfilledQuantity, $notes = null) {
        $request = $this->find($id);
        if (!$request) {
            throw new Exception('Material request not found');
        }
        
        if (!in_array($request['status'], [self::STATUS_APPROVED, self::STATUS_PARTIALLY_FULFILLED])) {
            throw new Exception('Only approved or partially fulfilled requests can be fulfilled');
        }
        
        if ($fulfilledQuantity <= 0) {
            throw new Exception('Fulfilled quantity must be greater than zero');
        }
        
        $newFulfilledQuantity = floatval($request['fulfilled_quantity']) + $fulfilledQuantity;
        $requestedQuantity = floatval($request['quantity']);
        
        if ($newFulfilledQuantity > $requestedQuantity) {
            throw new Exception('Fulfilled quantity cannot exceed requested quantity');
        }
        
        $data = [
            'fulfilled_quantity' => $newFulfilledQuantity
        ];
        
        // Determine new status
        if ($newFulfilledQuantity >= $requestedQuantity) {
            $data['status'] = self::STATUS_FULFILLED;
        } else {
            $data['status'] = self::STATUS_PARTIALLY_FULFILLED;
        }
        
        if ($notes) {
            $data['notes'] = $request['notes'] ? $request['notes'] . "\n[Fulfillment] " . $notes : "[Fulfillment] " . $notes;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Cancel request
     */
    public function cancel($id, $reason = null) {
        $request = $this->find($id);
        if (!$request) {
            throw new Exception('Material request not found');
        }
        
        if (in_array($request['status'], [self::STATUS_FULFILLED, self::STATUS_CANCELLED])) {
            throw new Exception('Cannot cancel fulfilled or already cancelled requests');
        }
        
        $data = ['status' => self::STATUS_CANCELLED];
        
        if ($reason) {
            $data['notes'] = $request['notes'] ? $request['notes'] . "\n[Cancellation] " . $reason : "[Cancellation] " . $reason;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Validate against stock levels
     */
    public function validateAgainstStock($productId, $warehouseId, $quantity) {
        require_once __DIR__ . '/SarInvStock.php';
        $stockModel = new SarInvStock();
        
        $availableQuantity = $stockModel->getAvailableQuantity($productId, $warehouseId);
        
        return [
            'available' => $availableQuantity >= $quantity,
            'available_quantity' => $availableQuantity,
            'requested_quantity' => $quantity,
            'shortage' => max(0, $quantity - $availableQuantity)
        ];
    }
    
    /**
     * Validate against material master
     */
    public function validateAgainstMaterialMaster($materialMasterId) {
        require_once __DIR__ . '/SarInvMaterialMaster.php';
        $materialModel = new SarInvMaterialMaster();
        
        $material = $materialModel->find($materialMasterId);
        
        if (!$material) {
            return ['valid' => false, 'error' => 'Material master not found'];
        }
        
        if ($material['status'] !== 'active') {
            return ['valid' => false, 'error' => 'Material master is not active'];
        }
        
        return ['valid' => true, 'material' => $material];
    }
    
    /**
     * Get pending count
     */
    public function getPendingCount() {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([self::STATUS_PENDING]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get fulfillment progress
     */
    public function getFulfillmentProgress($id) {
        $request = $this->find($id);
        if (!$request) {
            return null;
        }
        
        $requested = floatval($request['quantity']);
        $fulfilled = floatval($request['fulfilled_quantity']);
        
        return [
            'requested' => $requested,
            'fulfilled' => $fulfilled,
            'remaining' => $requested - $fulfilled,
            'percentage' => $requested > 0 ? round(($fulfilled / $requested) * 100, 2) : 0
        ];
    }

    /**
     * Search requests
     */
    public function search($keyword = null, $status = null, $requesterId = null, $dateFrom = null, $dateTo = null, $limit = 100, $offset = 0) {
        $sql = "SELECT mr.*, 
                    mm.name as material_name, mm.code as material_code,
                    p.name as product_name, p.sku as product_sku,
                    CONCAT(req.first_name, ' ', req.last_name) as requester_name,
                    CONCAT(app.first_name, ' ', app.last_name) as approver_name
                FROM {$this->table} mr
                LEFT JOIN sar_inv_material_masters mm ON mr.material_master_id = mm.id
                LEFT JOIN sar_inv_products p ON mr.product_id = p.id
                LEFT JOIN users req ON mr.requester_id = req.id
                LEFT JOIN users app ON mr.approver_id = app.id
                WHERE 1=1";
        $params = [];
        
        if ($keyword) {
            $sql .= " AND (mr.request_number LIKE ? OR mm.name LIKE ? OR mm.code LIKE ? OR p.name LIKE ? OR p.sku LIKE ?)";
            $keyword = "%{$keyword}%";
            $params = array_merge($params, [$keyword, $keyword, $keyword, $keyword, $keyword]);
        }
        
        if ($status) {
            $sql .= " AND mr.status = ?";
            $params[] = $status;
        }
        
        if ($requesterId) {
            $sql .= " AND mr.requester_id = ?";
            $params[] = $requesterId;
        }
        
        if ($dateFrom) {
            $sql .= " AND DATE(mr.created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(mr.created_at) <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " ORDER BY mr.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get all requests with pagination
     */
    public function getAllWithDetails($limit = 100, $offset = 0) {
        $sql = "SELECT mr.*, 
                    mm.name as material_name, mm.code as material_code,
                    p.name as product_name, p.sku as product_sku,
                    CONCAT(req.first_name, ' ', req.last_name) as requester_name,
                    CONCAT(app.first_name, ' ', app.last_name) as approver_name
                FROM {$this->table} mr
                LEFT JOIN sar_inv_material_masters mm ON mr.material_master_id = mm.id
                LEFT JOIN sar_inv_products p ON mr.product_id = p.id
                LEFT JOIN users req ON mr.requester_id = req.id
                LEFT JOIN users app ON mr.approver_id = app.id
                ORDER BY mr.created_at DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get request statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
                    SUM(CASE WHEN status = 'fulfilled' THEN 1 ELSE 0 END) as fulfilled_count,
                    SUM(CASE WHEN status = 'partially_fulfilled' THEN 1 ELSE 0 END) as partially_fulfilled_count,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count,
                    SUM(quantity) as total_quantity_requested,
                    SUM(fulfilled_quantity) as total_quantity_fulfilled
                FROM {$this->table}
                WHERE 1=1";
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $dateTo;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }
}
?>
