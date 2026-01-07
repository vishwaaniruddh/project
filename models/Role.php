<?php
/**
 * Role Model
 * 
 * Handles role management for the RBAC system.
 * Requirements: 1.1, 1.2, 1.4, 3.2
 */

require_once __DIR__ . '/BaseModel.php';

class Role extends BaseModel
{
    protected $table = 'roles';

    /**
     * Find a role by its name
     * 
     * @param string $name Role name (e.g., 'superadmin', 'admin')
     * @return array|null Role data or null if not found
     */
    public function findByName(string $name): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE name = ?");
        $stmt->execute([$name]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get all permissions assigned to a role
     * 
     * @param int $roleId Role ID
     * @return array Array of permission records
     */
    public function getPermissions(int $roleId): array
    {
        $sql = "SELECT p.* 
                FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = ?
                ORDER BY p.module, p.action";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleId]);
        return $stmt->fetchAll();
    }

    /**
     * Assign a permission to a role
     * 
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool Success status
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        try {
            $sql = "INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$roleId, $permissionId]);
        } catch (PDOException $e) {
            return false;
        }
    }


    /**
     * Revoke a permission from a role
     * 
     * @param int $roleId Role ID
     * @param int $permissionId Permission ID
     * @return bool Success status
     */
    public function revokePermission(int $roleId, int $permissionId): bool
    {
        $sql = "DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$roleId, $permissionId]);
    }

    /**
     * Sync permissions for a role (replace all existing with new set)
     * 
     * @param int $roleId Role ID
     * @param array $permissionIds Array of permission IDs to assign
     * @return bool Success status
     */
    public function syncPermissions(int $roleId, array $permissionIds): bool
    {
        try {
            $this->db->beginTransaction();

            // Remove all existing permissions for this role
            $deleteStmt = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $deleteStmt->execute([$roleId]);

            // Insert new permissions
            if (!empty($permissionIds)) {
                $insertSql = "INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)";
                $insertStmt = $this->db->prepare($insertSql);
                
                foreach ($permissionIds as $permissionId) {
                    $insertStmt->execute([$roleId, (int)$permissionId]);
                }
            }

            $this->db->commit();
            return true;
        } catch (PDOException $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Check if a role is a system role (cannot be deleted)
     * 
     * @param int $roleId Role ID
     * @return bool True if system role
     */
    public function isSystemRole(int $roleId): bool
    {
        $stmt = $this->db->prepare("SELECT is_system_role FROM {$this->table} WHERE id = ?");
        $stmt->execute([$roleId]);
        $result = $stmt->fetch();
        return $result && (bool)$result['is_system_role'];
    }

    /**
     * Get all roles with their permission counts
     * 
     * @return array Array of roles with permission_count field
     */
    public function getAllWithPermissionCount(): array
    {
        $sql = "SELECT r.*, COUNT(rp.permission_id) as permission_count
                FROM {$this->table} r
                LEFT JOIN role_permissions rp ON r.id = rp.role_id
                GROUP BY r.id
                ORDER BY r.id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get role with its permissions
     * 
     * @param int $roleId Role ID
     * @return array|null Role data with permissions array
     */
    public function getWithPermissions(int $roleId): ?array
    {
        $role = $this->find($roleId);
        if (!$role) {
            return null;
        }
        
        $role['permissions'] = $this->getPermissions($roleId);
        return $role;
    }

    /**
     * Get permission keys for a role
     * 
     * @param int $roleId Role ID
     * @return array Array of permission key strings
     */
    public function getPermissionKeys(int $roleId): array
    {
        $sql = "SELECT p.permission_key 
                FROM permissions p
                INNER JOIN role_permissions rp ON p.id = rp.permission_id
                WHERE rp.role_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Delete a role (only if not a system role)
     * 
     * @param int $id Role ID
     * @return bool Success status
     */
    public function delete($id): bool
    {
        if ($this->isSystemRole($id)) {
            return false;
        }
        return parent::delete($id);
    }
}
