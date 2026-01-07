<?php
require_once __DIR__ . '/BaseModel.php';

/**
 * Base Model for SAR Inventory Management System
 * Extends BaseModel with company isolation, audit logging, and optimistic locking support
 */
class SarInvBaseModel extends BaseModel {
    protected $companyIdField = 'company_id';
    protected $versionField = 'version';
    protected $auditTable = 'sar_inv_audit_log';
    protected $enableAuditLog = true;
    protected $enableCompanyIsolation = true;
    
    /**
     * Get current user ID from session
     */
    protected function getCurrentUserId() {
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
    
    /**
     * Get current company ID from session
     */
    protected function getCurrentCompanyId() {
        return isset($_SESSION['company_id']) ? $_SESSION['company_id'] : 1;
    }
    
    /**
     * Get client IP address
     */
    protected function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
    }
    
    /**
     * Log changes to audit table
     */
    public function logChange($recordId, $action, $oldValues = null, $newValues = null) {
        if (!$this->enableAuditLog) {
            return true;
        }
        
        $sql = "INSERT INTO {$this->auditTable} (table_name, record_id, action, old_values, new_values, user_id, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $this->table,
            $recordId,
            $action,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $this->getCurrentUserId(),
            $this->getClientIp()
        ]);
    }
    
    /**
     * Find record by ID with company isolation
     */
    public function find($id) {
        if ($this->enableCompanyIsolation && $this->hasCompanyField()) {
            $sql = "SELECT * FROM {$this->table} WHERE id = ? AND {$this->companyIdField} = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id, $this->getCurrentCompanyId()]);
            return $stmt->fetch();
        }
        return parent::find($id);
    }
    
    /**
     * Find all records with company isolation
     */
    public function findAll($conditions = [], $limit = null, $offset = null) {
        if ($this->enableCompanyIsolation && $this->hasCompanyField()) {
            $conditions[$this->companyIdField] = $this->getCurrentCompanyId();
        }
        return parent::findAll($conditions, $limit, $offset);
    }
    
    /**
     * Create record with company isolation and audit logging
     */
    public function create($data) {
        if ($this->enableCompanyIsolation && $this->hasCompanyField() && !isset($data[$this->companyIdField])) {
            $data[$this->companyIdField] = $this->getCurrentCompanyId();
        }
        
        $id = parent::create($data);
        
        if ($id && $this->enableAuditLog) {
            $this->logChange($id, 'create', null, $data);
        }
        
        return $id;
    }
    
    /**
     * Update record with optimistic locking and audit logging
     */
    public function update($id, $data) {
        $oldRecord = $this->findWithoutIsolation($id);
        
        if (!$oldRecord) {
            return false;
        }
        
        // Check company isolation
        if ($this->enableCompanyIsolation && $this->hasCompanyField()) {
            if ($oldRecord[$this->companyIdField] != $this->getCurrentCompanyId()) {
                return false;
            }
        }
        
        $result = parent::update($id, $data);
        
        if ($result && $this->enableAuditLog) {
            $this->logChange($id, 'update', $oldRecord, $data);
        }
        
        return $result;
    }
    
    /**
     * Update with optimistic locking
     */
    public function updateWithVersion($id, $data, $currentVersion) {
        $oldRecord = $this->findWithoutIsolation($id);
        
        if (!$oldRecord) {
            return false;
        }
        
        // Check company isolation
        if ($this->enableCompanyIsolation && $this->hasCompanyField()) {
            if ($oldRecord[$this->companyIdField] != $this->getCurrentCompanyId()) {
                return false;
            }
        }
        
        // Check version for optimistic locking
        if (isset($oldRecord[$this->versionField]) && $oldRecord[$this->versionField] != $currentVersion) {
            throw new Exception('Concurrent modification detected. Please refresh and try again.');
        }
        
        // Increment version
        if ($this->hasVersionField()) {
            $data[$this->versionField] = $currentVersion + 1;
        }
        
        $result = parent::update($id, $data);
        
        if ($result && $this->enableAuditLog) {
            $this->logChange($id, 'update', $oldRecord, $data);
        }
        
        return $result;
    }
    
    /**
     * Delete record with company isolation and audit logging
     */
    public function delete($id) {
        $oldRecord = $this->findWithoutIsolation($id);
        
        if (!$oldRecord) {
            return false;
        }
        
        // Check company isolation
        if ($this->enableCompanyIsolation && $this->hasCompanyField()) {
            if ($oldRecord[$this->companyIdField] != $this->getCurrentCompanyId()) {
                return false;
            }
        }
        
        $result = parent::delete($id);
        
        if ($result && $this->enableAuditLog) {
            $this->logChange($id, 'delete', $oldRecord, null);
        }
        
        return $result;
    }
    
    /**
     * Count records with company isolation
     */
    public function count($conditions = []) {
        if ($this->enableCompanyIsolation && $this->hasCompanyField()) {
            $conditions[$this->companyIdField] = $this->getCurrentCompanyId();
        }
        return parent::count($conditions);
    }
    
    /**
     * Find record without company isolation (internal use)
     */
    protected function findWithoutIsolation($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Check if table has company_id field
     */
    protected function hasCompanyField() {
        static $cache = [];
        
        if (!isset($cache[$this->table])) {
            try {
                $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
                $stmt->execute([$this->companyIdField]);
                $cache[$this->table] = $stmt->fetch() !== false;
            } catch (PDOException $e) {
                // Table might not exist yet
                $cache[$this->table] = false;
            }
        }
        
        return $cache[$this->table];
    }
    
    /**
     * Check if table has version field
     */
    protected function hasVersionField() {
        static $cache = [];
        
        if (!isset($cache[$this->table])) {
            try {
                $stmt = $this->db->prepare("SHOW COLUMNS FROM {$this->table} LIKE ?");
                $stmt->execute([$this->versionField]);
                $cache[$this->table] = $stmt->fetch() !== false;
            } catch (PDOException $e) {
                // Table might not exist yet
                $cache[$this->table] = false;
            }
        }
        
        return $cache[$this->table];
    }
    
    /**
     * Begin database transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit database transaction
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback database transaction
     */
    public function rollback() {
        return $this->db->rollBack();
    }
    
    /**
     * Get database connection
     */
    public function getDb() {
        return $this->db;
    }
}
?>
