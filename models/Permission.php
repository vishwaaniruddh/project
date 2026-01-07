<?php
/**
 * Permission Model
 * 
 * Handles permission management for the RBAC system.
 * Requirements: 2.1, 2.2
 */

require_once __DIR__ . '/BaseModel.php';

class Permission extends BaseModel
{
    protected $table = 'permissions';

    /**
     * Find a permission by its unique key
     * 
     * @param string $permissionKey Permission key (e.g., 'users.create', 'inventory.stock.manage')
     * @return array|null Permission data or null if not found
     */
    public function findByKey(string $permissionKey): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE permission_key = ?");
        $stmt->execute([$permissionKey]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get all permissions for a specific module
     * 
     * @param string $module Module name (e.g., 'users', 'inventory', 'masters')
     * @return array Array of permission records
     */
    public function getByModule(string $module): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE module = ? ORDER BY action");
        $stmt->execute([$module]);
        return $stmt->fetchAll();
    }

    /**
     * Get all permissions grouped by module for UI display
     * 
     * @return array Associative array with module names as keys
     */
    public function getAllGroupedByModule(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY module, action");
        $stmt->execute();
        $permissions = $stmt->fetchAll();

        $grouped = [];
        foreach ($permissions as $permission) {
            $module = $permission['module'];
            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }
            $grouped[$module][] = $permission;
        }

        return $grouped;
    }


    /**
     * Get all roles that have a specific permission
     * 
     * @param int $permissionId Permission ID
     * @return array Array of role records
     */
    public function getRolesWithPermission(int $permissionId): array
    {
        $sql = "SELECT r.* 
                FROM roles r
                INNER JOIN role_permissions rp ON r.id = rp.role_id
                WHERE rp.permission_id = ?
                ORDER BY r.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$permissionId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all unique modules
     * 
     * @return array Array of module names
     */
    public function getModules(): array
    {
        $stmt = $this->db->prepare("SELECT DISTINCT module FROM {$this->table} ORDER BY module");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get permission count by module
     * 
     * @return array Associative array with module => count
     */
    public function getCountByModule(): array
    {
        $sql = "SELECT module, COUNT(*) as count 
                FROM {$this->table} 
                GROUP BY module 
                ORDER BY module";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();

        $counts = [];
        foreach ($results as $row) {
            $counts[$row['module']] = (int)$row['count'];
        }
        return $counts;
    }

    /**
     * Find multiple permissions by their keys
     * 
     * @param array $permissionKeys Array of permission key strings
     * @return array Array of permission records
     */
    public function findByKeys(array $permissionKeys): array
    {
        if (empty($permissionKeys)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($permissionKeys), '?'));
        $sql = "SELECT * FROM {$this->table} WHERE permission_key IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($permissionKeys);
        return $stmt->fetchAll();
    }

    /**
     * Get permission IDs by their keys
     * 
     * @param array $permissionKeys Array of permission key strings
     * @return array Array of permission IDs
     */
    public function getIdsByKeys(array $permissionKeys): array
    {
        if (empty($permissionKeys)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($permissionKeys), '?'));
        $sql = "SELECT id FROM {$this->table} WHERE permission_key IN ($placeholders)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($permissionKeys);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
