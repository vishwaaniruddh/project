<?php
require_once __DIR__ . '/SarInvBaseModel.php';

/**
 * SAR Inventory Audit Log Model
 * Manages audit log creation, retrieval, and search functionality
 */
class SarInvAuditLog extends SarInvBaseModel {
    protected $table = 'sar_inv_audit_log';
    protected $enableCompanyIsolation = false; // Audit log doesn't have company_id
    protected $enableAuditLog = false; // Don't audit the audit log itself
    
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    
    /**
     * Create audit log entry
     */
    public function createLog($tableName, $recordId, $action, $oldValues = null, $newValues = null, $userId = null, $ipAddress = null) {
        $data = [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_values' => $oldValues ? (is_array($oldValues) ? json_encode($oldValues) : $oldValues) : null,
            'new_values' => $newValues ? (is_array($newValues) ? json_encode($newValues) : $newValues) : null,
            'user_id' => $userId ?: $this->getCurrentUserId(),
            'ip_address' => $ipAddress ?: $this->getClientIp()
        ];
        
        $sql = "INSERT INTO {$this->table} (table_name, record_id, action, old_values, new_values, user_id, ip_address) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['table_name'],
            $data['record_id'],
            $data['action'],
            $data['old_values'],
            $data['new_values'],
            $data['user_id'],
            $data['ip_address']
        ]);
    }
    
    /**
     * Get logs for a specific record
     */
    public function getLogsForRecord($tableName, $recordId, $limit = 100) {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = ? AND al.record_id = ?
                ORDER BY al.created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tableName, $recordId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get logs for a specific table
     */
    public function getLogsForTable($tableName, $limit = 100, $offset = 0) {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.table_name = ?
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tableName, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get logs by user
     */
    public function getLogsByUser($userId, $limit = 100, $offset = 0) {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.user_id = ?
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get logs by action type
     */
    public function getLogsByAction($action, $limit = 100, $offset = 0) {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.action = ?
                ORDER BY al.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$action, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Search audit logs with filters
     */
    public function search($filters = [], $limit = 100, $offset = 0) {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['table_name'])) {
            $sql .= " AND al.table_name = ?";
            $params[] = $filters['table_name'];
        }
        
        if (!empty($filters['record_id'])) {
            $sql .= " AND al.record_id = ?";
            $params[] = $filters['record_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND al.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND al.user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['ip_address'])) {
            $sql .= " AND al.ip_address = ?";
            $params[] = $filters['ip_address'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(al.created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['keyword'])) {
            $sql .= " AND (al.old_values LIKE ? OR al.new_values LIKE ?)";
            $keyword = "%{$filters['keyword']}%";
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Count logs with filters
     */
    public function countLogs($filters = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE 1=1";
        $params = [];
        
        if (!empty($filters['table_name'])) {
            $sql .= " AND table_name = ?";
            $params[] = $filters['table_name'];
        }
        
        if (!empty($filters['record_id'])) {
            $sql .= " AND record_id = ?";
            $params[] = $filters['record_id'];
        }
        
        if (!empty($filters['action'])) {
            $sql .= " AND action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['user_id'])) {
            $sql .= " AND user_id = ?";
            $params[] = $filters['user_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(created_at) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(created_at) <= ?";
            $params[] = $filters['date_to'];
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
    
    /**
     * Get recent logs
     */
    public function getRecentLogs($limit = 50) {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                ORDER BY al.created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get log with decoded values
     */
    public function getLogWithDecodedValues($id) {
        $log = $this->find($id);
        if (!$log) {
            return null;
        }
        
        if ($log['old_values']) {
            $log['old_values_decoded'] = json_decode($log['old_values'], true);
        }
        
        if ($log['new_values']) {
            $log['new_values_decoded'] = json_decode($log['new_values'], true);
        }
        
        return $log;
    }
    
    /**
     * Get changes between old and new values
     */
    public function getChanges($id) {
        $log = $this->getLogWithDecodedValues($id);
        if (!$log) {
            return null;
        }
        
        $changes = [];
        $oldValues = $log['old_values_decoded'] ?? [];
        $newValues = $log['new_values_decoded'] ?? [];
        
        // For create action, all new values are changes
        if ($log['action'] === self::ACTION_CREATE) {
            foreach ($newValues as $field => $value) {
                $changes[$field] = [
                    'old' => null,
                    'new' => $value
                ];
            }
            return $changes;
        }
        
        // For delete action, all old values are changes
        if ($log['action'] === self::ACTION_DELETE) {
            foreach ($oldValues as $field => $value) {
                $changes[$field] = [
                    'old' => $value,
                    'new' => null
                ];
            }
            return $changes;
        }
        
        // For update action, compare old and new values
        $allFields = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));
        foreach ($allFields as $field) {
            $oldValue = $oldValues[$field] ?? null;
            $newValue = $newValues[$field] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
        
        return $changes;
    }
    
    /**
     * Get available table names for filtering
     */
    public function getAvailableTables() {
        $sql = "SELECT DISTINCT table_name FROM {$this->table} ORDER BY table_name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Get statistics
     */
    public function getStatistics($dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    COUNT(*) as total_logs,
                    SUM(CASE WHEN action = 'create' THEN 1 ELSE 0 END) as create_count,
                    SUM(CASE WHEN action = 'update' THEN 1 ELSE 0 END) as update_count,
                    SUM(CASE WHEN action = 'delete' THEN 1 ELSE 0 END) as delete_count,
                    COUNT(DISTINCT table_name) as tables_affected,
                    COUNT(DISTINCT user_id) as users_involved
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
    
    /**
     * Get activity by table
     */
    public function getActivityByTable($dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    table_name,
                    COUNT(*) as total_actions,
                    SUM(CASE WHEN action = 'create' THEN 1 ELSE 0 END) as creates,
                    SUM(CASE WHEN action = 'update' THEN 1 ELSE 0 END) as updates,
                    SUM(CASE WHEN action = 'delete' THEN 1 ELSE 0 END) as deletes
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
        
        $sql .= " GROUP BY table_name ORDER BY total_actions DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get activity by user
     */
    public function getActivityByUser($dateFrom = null, $dateTo = null, $limit = 20) {
        $sql = "SELECT 
                    al.user_id,
                    u.name as user_name,
                    COUNT(*) as total_actions,
                    SUM(CASE WHEN al.action = 'create' THEN 1 ELSE 0 END) as creates,
                    SUM(CASE WHEN al.action = 'update' THEN 1 ELSE 0 END) as updates,
                    SUM(CASE WHEN al.action = 'delete' THEN 1 ELSE 0 END) as deletes
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE 1=1";
        $params = [];
        
        if ($dateFrom) {
            $sql .= " AND DATE(al.created_at) >= ?";
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $sql .= " AND DATE(al.created_at) <= ?";
            $params[] = $dateTo;
        }
        
        $sql .= " GROUP BY al.user_id, u.name ORDER BY total_actions DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Purge old logs
     */
    public function purgeOldLogs($daysToKeep = 90) {
        $sql = "DELETE FROM {$this->table} WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$daysToKeep]);
        return $stmt->rowCount();
    }
}
?>
