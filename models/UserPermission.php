<?php
/**
 * UserPermission Model
 * 
 * Handles user-specific permission overrides for the RBAC system.
 * Allows granting or revoking specific permissions for individual users
 * independent of their role assignments.
 * 
 * Requirements: 5.1, 5.2, 5.4
 */

require_once __DIR__ . '/BaseModel.php';

class UserPermission extends BaseModel
{
    protected $table = 'user_permissions';

    /**
     * Get all permission overrides for a specific user
     * 
     * @param int $userId User ID
     * @return array Array of override records with permission details
     */
    public function getUserOverrides(int $userId): array
    {
        $sql = "SELECT up.*, p.permission_key, p.module, p.action, p.display_name as permission_name
                FROM {$this->table} up
                INNER JOIN permissions p ON up.permission_id = p.id
                WHERE up.user_id = ?
                ORDER BY p.module, p.action";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Set a permission override for a user
     * 
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @param bool $isGranted True to grant, false to revoke
     * @param int $grantedBy ID of user making the change
     * @return bool Success status
     */
    public function setOverride(int $userId, int $permissionId, bool $isGranted, int $grantedBy): bool
    {
        try {
            // Use INSERT ... ON DUPLICATE KEY UPDATE for upsert behavior
            $sql = "INSERT INTO {$this->table} (user_id, permission_id, is_granted, granted_by) 
                    VALUES (?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        is_granted = VALUES(is_granted),
                        granted_by = VALUES(granted_by),
                        updated_at = CURRENT_TIMESTAMP";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId, $permissionId, $isGranted ? 1 : 0, $grantedBy]);
        } catch (PDOException $e) {
            return false;
        }
    }


    /**
     * Remove a permission override for a user
     * 
     * @param int $userId User ID
     * @param int $permissionId Permission ID
     * @return bool Success status
     */
    public function removeOverride(int $userId, int $permissionId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND permission_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId, $permissionId]);
    }

    /**
     * Get effective permissions for a user combining role permissions and overrides
     * 
     * User-specific overrides take precedence:
     * - If is_granted = true, permission is granted regardless of role
     * - If is_granted = false, permission is denied regardless of role
     * - If no override exists, role permission applies
     * 
     * @param int $userId User ID
     * @param int $roleId User's role ID
     * @return array Array of effective permission records
     */
    public function getEffectivePermissions(int $userId, int $roleId): array
    {
        // Get all permissions with their effective status
        $sql = "SELECT 
                    p.id,
                    p.module,
                    p.action,
                    p.permission_key,
                    p.display_name,
                    p.description,
                    CASE 
                        WHEN up.id IS NOT NULL THEN up.is_granted
                        WHEN rp.id IS NOT NULL THEN 1
                        ELSE 0
                    END as is_granted,
                    CASE 
                        WHEN up.id IS NOT NULL THEN 'override'
                        WHEN rp.id IS NOT NULL THEN 'role'
                        ELSE 'none'
                    END as source
                FROM permissions p
                LEFT JOIN role_permissions rp ON p.id = rp.permission_id AND rp.role_id = ?
                LEFT JOIN user_permissions up ON p.id = up.permission_id AND up.user_id = ?
                WHERE (up.is_granted = 1 OR rp.id IS NOT NULL OR up.is_granted IS NULL)
                ORDER BY p.module, p.action";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleId, $userId]);
        $allPermissions = $stmt->fetchAll();

        // Filter to only granted permissions
        return array_filter($allPermissions, function($p) {
            return (int)$p['is_granted'] === 1;
        });
    }

    /**
     * Get effective permission keys for a user
     * 
     * @param int $userId User ID
     * @param int $roleId User's role ID
     * @return array Array of permission key strings
     */
    public function getEffectivePermissionKeys(int $userId, int $roleId): array
    {
        $permissions = $this->getEffectivePermissions($userId, $roleId);
        return array_column($permissions, 'permission_key');
    }

    /**
     * Check if a user has a specific permission (considering overrides)
     * 
     * @param int $userId User ID
     * @param int $roleId User's role ID
     * @param string $permissionKey Permission key to check
     * @return bool True if user has the permission
     */
    public function hasPermission(int $userId, int $roleId, string $permissionKey): bool
    {
        // First check for user-specific override
        $sql = "SELECT up.is_granted 
                FROM {$this->table} up
                INNER JOIN permissions p ON up.permission_id = p.id
                WHERE up.user_id = ? AND p.permission_key = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $permissionKey]);
        $override = $stmt->fetch();

        if ($override !== false) {
            return (bool)$override['is_granted'];
        }

        // No override, check role permission
        $sql = "SELECT rp.id 
                FROM role_permissions rp
                INNER JOIN permissions p ON rp.permission_id = p.id
                WHERE rp.role_id = ? AND p.permission_key = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$roleId, $permissionKey]);
        return $stmt->fetch() !== false;
    }

    /**
     * Remove all overrides for a user
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function clearUserOverrides(int $userId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$userId]);
    }

    /**
     * Get users with a specific permission override
     * 
     * @param int $permissionId Permission ID
     * @param bool|null $isGranted Filter by grant status (null for all)
     * @return array Array of user override records
     */
    public function getUsersWithOverride(int $permissionId, ?bool $isGranted = null): array
    {
        $sql = "SELECT up.*, u.username, u.email
                FROM {$this->table} up
                INNER JOIN users u ON up.user_id = u.id
                WHERE up.permission_id = ?";
        
        $params = [$permissionId];
        
        if ($isGranted !== null) {
            $sql .= " AND up.is_granted = ?";
            $params[] = $isGranted ? 1 : 0;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
