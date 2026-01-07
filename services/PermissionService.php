<?php
/**
 * Permission Service
 * 
 * Handles permission checking for the RBAC system.
 * Supports checking user permissions from database or JWT token.
 * Requirements: 7.3, 7.4, 7.5
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/UserPermission.php';

class PermissionService
{
    private $db;
    private $roleModel;
    private $permissionModel;
    private $userPermissionModel;
    
    // Cache for user permissions to avoid repeated DB queries
    private static $permissionCache = [];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
        $this->userPermissionModel = new UserPermission();
    }
    
    /**
     * Check if a user has a specific permission
     * First checks user-specific overrides, then falls back to role permissions
     * 
     * @param int $userId User ID
     * @param string $permissionKey Permission key to check
     * @return bool True if user has the permission
     */
    public function hasPermission(int $userId, string $permissionKey): bool
    {
        // Get user's role_id
        $roleId = $this->getUserRoleId($userId);
        
        if (!$roleId) {
            return false;
        }
        
        return $this->userPermissionModel->hasPermission($userId, $roleId, $permissionKey);
    }
    
    /**
     * Check if a user has any of the specified permissions (OR logic)
     * 
     * @param int $userId User ID
     * @param array $permissionKeys Array of permission keys
     * @return bool True if user has at least one permission
     */
    public function hasAnyPermission(int $userId, array $permissionKeys): bool
    {
        if (empty($permissionKeys)) {
            return false;
        }
        
        foreach ($permissionKeys as $permissionKey) {
            if ($this->hasPermission($userId, $permissionKey)) {
                return true;
            }
        }
        
        return false;
    }

    
    /**
     * Check if a user has all of the specified permissions (AND logic)
     * 
     * @param int $userId User ID
     * @param array $permissionKeys Array of permission keys
     * @return bool True if user has all permissions
     */
    public function hasAllPermissions(int $userId, array $permissionKeys): bool
    {
        if (empty($permissionKeys)) {
            return true;
        }
        
        foreach ($permissionKeys as $permissionKey) {
            if (!$this->hasPermission($userId, $permissionKey)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get all effective permissions for a user
     * Combines role permissions with user-specific overrides
     * 
     * @param int $userId User ID
     * @return array Array of permission records with source info
     */
    public function getUserPermissions(int $userId): array
    {
        $roleId = $this->getUserRoleId($userId);
        
        if (!$roleId) {
            return [];
        }
        
        return $this->userPermissionModel->getEffectivePermissions($userId, $roleId);
    }
    
    /**
     * Get permission keys for a user
     * 
     * @param int $userId User ID
     * @return array Array of permission key strings
     */
    public function getPermissionKeys(int $userId): array
    {
        // Check cache first
        $cacheKey = "user_{$userId}_keys";
        if (isset(self::$permissionCache[$cacheKey])) {
            return self::$permissionCache[$cacheKey];
        }
        
        $roleId = $this->getUserRoleId($userId);
        
        if (!$roleId) {
            return [];
        }
        
        $keys = $this->userPermissionModel->getEffectivePermissionKeys($userId, $roleId);
        
        // Cache the result
        self::$permissionCache[$cacheKey] = $keys;
        
        return $keys;
    }
    
    /**
     * Check permissions from JWT token payload
     * 
     * @param array $tokenPermissions Permissions array from JWT token
     * @param string $requiredPermission Permission key to check
     * @return bool True if permission exists in token
     */
    public function checkTokenPermissions(array $tokenPermissions, string $requiredPermission): bool
    {
        return in_array($requiredPermission, $tokenPermissions, true);
    }
    
    /**
     * Check if token has any of the specified permissions
     * 
     * @param array $tokenPermissions Permissions array from JWT token
     * @param array $requiredPermissions Array of permission keys
     * @return bool True if any permission exists
     */
    public function checkTokenHasAnyPermission(array $tokenPermissions, array $requiredPermissions): bool
    {
        foreach ($requiredPermissions as $permission) {
            if (in_array($permission, $tokenPermissions, true)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Check if token has all of the specified permissions
     * 
     * @param array $tokenPermissions Permissions array from JWT token
     * @param array $requiredPermissions Array of permission keys
     * @return bool True if all permissions exist
     */
    public function checkTokenHasAllPermissions(array $tokenPermissions, array $requiredPermissions): bool
    {
        foreach ($requiredPermissions as $permission) {
            if (!in_array($permission, $tokenPermissions, true)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Get user's role ID
     * 
     * @param int $userId User ID
     * @return int|null Role ID or null
     */
    private function getUserRoleId(int $userId): ?int
    {
        // Check cache
        $cacheKey = "user_{$userId}_role";
        if (isset(self::$permissionCache[$cacheKey])) {
            return self::$permissionCache[$cacheKey];
        }
        
        try {
            $sql = "SELECT role_id FROM users WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $roleId = $result ? (int)$result['role_id'] : null;
            
            // Cache the result
            self::$permissionCache[$cacheKey] = $roleId;
            
            return $roleId;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get permissions grouped by module for a user
     * 
     * @param int $userId User ID
     * @return array Permissions grouped by module
     */
    public function getUserPermissionsGrouped(int $userId): array
    {
        $permissions = $this->getUserPermissions($userId);
        
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
     * Check if user is a superadmin (has all permissions)
     * 
     * @param int $userId User ID
     * @return bool True if superadmin
     */
    public function isSuperAdmin(int $userId): bool
    {
        $roleId = $this->getUserRoleId($userId);
        
        if (!$roleId) {
            return false;
        }
        
        try {
            $sql = "SELECT name FROM roles WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && $result['name'] === 'superadmin';
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Clear permission cache for a user
     * Should be called when user's permissions change
     * 
     * @param int $userId User ID
     */
    public function clearUserCache(int $userId): void
    {
        unset(self::$permissionCache["user_{$userId}_keys"]);
        unset(self::$permissionCache["user_{$userId}_role"]);
    }
    
    /**
     * Clear all permission cache
     */
    public function clearAllCache(): void
    {
        self::$permissionCache = [];
    }
    
    /**
     * Get all available permissions
     * 
     * @return array All permissions
     */
    public function getAllPermissions(): array
    {
        return $this->permissionModel->findAll();
    }
    
    /**
     * Get all permissions grouped by module
     * 
     * @return array Permissions grouped by module
     */
    public function getAllPermissionsGrouped(): array
    {
        return $this->permissionModel->getAllGroupedByModule();
    }
    
    /**
     * Get role permissions
     * 
     * @param int $roleId Role ID
     * @return array Array of permission records
     */
    public function getRolePermissions(int $roleId): array
    {
        return $this->roleModel->getPermissions($roleId);
    }
    
    /**
     * Get role permission keys
     * 
     * @param int $roleId Role ID
     * @return array Array of permission key strings
     */
    public function getRolePermissionKeys(int $roleId): array
    {
        return $this->roleModel->getPermissionKeys($roleId);
    }
}
