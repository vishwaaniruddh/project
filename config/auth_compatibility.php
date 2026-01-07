<?php
/**
 * Backward Compatibility Layer for Auth System
 * 
 * This file provides backward compatibility during the transition from
 * the legacy role-based system to the new RBAC system.
 * 
 * Features:
 * - Keeps legacy role column functional
 * - Adds deprecation logging for old permission checks
 * - Provides migration helpers
 * 
 * Usage:
 *   require_once __DIR__ . '/auth_compatibility.php';
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/database.php';

class AuthCompatibility
{
    private static $deprecationLogEnabled = true;
    private static $logFile = __DIR__ . '/../logs/auth_deprecation.log';
    
    /**
     * Enable or disable deprecation logging
     */
    public static function setDeprecationLogging(bool $enabled): void
    {
        self::$deprecationLogEnabled = $enabled;
    }
    
    /**
     * Log deprecation warning
     */
    private static function logDeprecation(string $method, string $message): void
    {
        if (!self::$deprecationLogEnabled) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[2] ?? $backtrace[1] ?? [];
        $file = $caller['file'] ?? 'unknown';
        $line = $caller['line'] ?? 0;
        
        $logMessage = sprintf(
            "[%s] DEPRECATED: %s - %s (called from %s:%d)\n",
            $timestamp,
            $method,
            $message,
            $file,
            $line
        );
        
        // Ensure logs directory exists
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        error_log($logMessage, 3, self::$logFile);
    }
    
    /**
     * Sync role_id with legacy role column
     * This ensures backward compatibility when role is updated
     */
    public static function syncLegacyRole(int $userId, ?int $roleId): bool
    {
        if ($roleId === null) {
            return true;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Map role_id to legacy role
            $legacyRoleMap = [
                1 => 'admin', // Superadmin -> admin
                2 => 'admin', // Admin -> admin
                3 => 'admin', // Manager -> admin
                4 => 'admin', // Engineer -> admin
                5 => 'vendor' // Vendor -> vendor
            ];
            
            $legacyRole = $legacyRoleMap[$roleId] ?? 'admin';
            
            $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$legacyRole, $userId]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Failed to sync legacy role: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync legacy role with role_id
     * This ensures backward compatibility when legacy role is updated
     */
    public static function syncRoleId(int $userId, string $legacyRole): bool
    {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Map legacy role to role_id (default mapping)
            $roleIdMap = [
                'admin' => 2,  // Admin role
                'vendor' => 5  // Vendor role
            ];
            
            $roleId = $roleIdMap[$legacyRole] ?? 2;
            
            $stmt = $db->prepare("UPDATE users SET role_id = ? WHERE id = ?");
            $stmt->execute([$roleId, $userId]);
            
            return true;
        } catch (PDOException $e) {
            error_log("Failed to sync role_id: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has permission using legacy vendor_permissions table
     * This is for backward compatibility only
     * 
     * @deprecated Use PermissionService::hasPermission() instead
     */
    public static function hasLegacyVendorPermission(int $userId, string $permissionKey): bool
    {
        self::logDeprecation(
            'hasLegacyVendorPermission',
            "Legacy vendor permission check for '$permissionKey'. Migrate to RBAC system."
        );
        
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if vendor_permissions table exists
            $stmt = $db->query("SHOW TABLES LIKE 'vendor_permissions'");
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            $stmt = $db->prepare("
                SELECT permission_value 
                FROM vendor_permissions 
                WHERE user_id = ? AND permission_key = ?
            ");
            $stmt->execute([$userId, $permissionKey]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (bool)$result['permission_value'] : false;
        } catch (PDOException $e) {
            error_log("Legacy permission check failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user has permission using either new RBAC or legacy system
     * This provides a transition path
     */
    public static function hasPermission(int $userId, string $permissionKey): bool
    {
        // Try new RBAC system first
        if (class_exists('PermissionService')) {
            require_once __DIR__ . '/../services/PermissionService.php';
            $permissionService = new PermissionService();
            
            try {
                return $permissionService->hasPermission($userId, $permissionKey);
            } catch (Exception $e) {
                // Fall back to legacy system if RBAC fails
                self::logDeprecation(
                    'hasPermission',
                    "RBAC check failed, falling back to legacy system: " . $e->getMessage()
                );
            }
        }
        
        // Fall back to legacy vendor permissions
        return self::hasLegacyVendorPermission($userId, $permissionKey);
    }
    
    /**
     * Get user's role name from role_id
     */
    public static function getRoleName(?int $roleId): ?string
    {
        if ($roleId === null) {
            return null;
        }
        
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("SELECT name FROM roles WHERE id = ?");
            $stmt->execute([$roleId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['name'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get user's role_id from legacy role
     */
    public static function getRoleIdFromLegacyRole(string $legacyRole): ?int
    {
        $roleIdMap = [
            'admin' => 2,  // Admin role
            'vendor' => 5  // Vendor role
        ];
        
        return $roleIdMap[$legacyRole] ?? null;
    }
    
    /**
     * Check if RBAC system is available
     */
    public static function isRBACAvailable(): bool
    {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if RBAC tables exist
            $requiredTables = ['roles', 'permissions', 'role_permissions', 'user_permissions'];
            
            foreach ($requiredTables as $table) {
                $stmt = $db->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() === 0) {
                    return false;
                }
            }
            
            // Check if role_id column exists in users table
            $stmt = $db->query("SHOW COLUMNS FROM users LIKE 'role_id'");
            if ($stmt->rowCount() === 0) {
                return false;
            }
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get migration status
     */
    public static function getMigrationStatus(): array
    {
        try {
            $db = Database::getInstance()->getConnection();
            
            $status = [
                'rbac_available' => self::isRBACAvailable(),
                'users_migrated' => 0,
                'users_not_migrated' => 0,
                'legacy_permissions_exist' => false
            ];
            
            if (!$status['rbac_available']) {
                return $status;
            }
            
            // Count migrated users
            $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role_id IS NOT NULL AND role_id > 0");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $status['users_migrated'] = (int)$result['count'];
            
            // Count non-migrated users
            $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role_id IS NULL OR role_id = 0");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $status['users_not_migrated'] = (int)$result['count'];
            
            // Check if legacy vendor_permissions table exists
            $stmt = $db->query("SHOW TABLES LIKE 'vendor_permissions'");
            $status['legacy_permissions_exist'] = $stmt->rowCount() > 0;
            
            return $status;
        } catch (PDOException $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Wrapper for Auth::requireRole with deprecation logging
     * 
     * @deprecated Use PermissionService with specific permissions instead
     */
    public static function requireRole(string $role): void
    {
        self::logDeprecation(
            'requireRole',
            "Legacy role check for '$role'. Consider using permission-based checks instead."
        );
        
        Auth::requireRole($role);
    }
    
    /**
     * Wrapper for Auth::requireVendorPermission with deprecation logging
     * 
     * @deprecated Use PermissionService::hasPermission() instead
     */
    public static function requireVendorPermission(string $permission): void
    {
        self::logDeprecation(
            'requireVendorPermission',
            "Legacy vendor permission check for '$permission'. Migrate to RBAC system."
        );
        
        Auth::requireVendorPermission($permission);
    }
    
    /**
     * Get deprecation log entries
     */
    public static function getDeprecationLog(int $limit = 100): array
    {
        if (!file_exists(self::$logFile)) {
            return [];
        }
        
        $lines = file(self::$logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }
        
        // Return last N lines
        return array_slice($lines, -$limit);
    }
    
    /**
     * Clear deprecation log
     */
    public static function clearDeprecationLog(): bool
    {
        if (file_exists(self::$logFile)) {
            return unlink(self::$logFile);
        }
        return true;
    }
    
    /**
     * Get deprecation statistics
     */
    public static function getDeprecationStats(): array
    {
        $logs = self::getDeprecationLog(1000);
        
        $stats = [
            'total_calls' => count($logs),
            'by_method' => [],
            'by_file' => []
        ];
        
        foreach ($logs as $log) {
            // Parse log entry
            if (preg_match('/DEPRECATED: (\w+).*\(called from (.+):(\d+)\)/', $log, $matches)) {
                $method = $matches[1];
                $file = basename($matches[2]);
                
                // Count by method
                if (!isset($stats['by_method'][$method])) {
                    $stats['by_method'][$method] = 0;
                }
                $stats['by_method'][$method]++;
                
                // Count by file
                if (!isset($stats['by_file'][$file])) {
                    $stats['by_file'][$file] = 0;
                }
                $stats['by_file'][$file]++;
            }
        }
        
        // Sort by count
        arsort($stats['by_method']);
        arsort($stats['by_file']);
        
        return $stats;
    }
}

/**
 * Helper function to check migration status
 */
function checkAuthMigrationStatus(): void
{
    $status = AuthCompatibility::getMigrationStatus();
    
    echo "==============================================\n";
    echo "Auth System Migration Status\n";
    echo "==============================================\n\n";
    
    if (isset($status['error'])) {
        echo "✗ Error: {$status['error']}\n";
        return;
    }
    
    echo "RBAC System: " . ($status['rbac_available'] ? "✓ Available" : "✗ Not Available") . "\n";
    echo "Users Migrated: {$status['users_migrated']}\n";
    echo "Users Not Migrated: {$status['users_not_migrated']}\n";
    echo "Legacy Permissions: " . ($status['legacy_permissions_exist'] ? "Yes" : "No") . "\n";
    
    if ($status['users_not_migrated'] > 0) {
        echo "\n⚠ Warning: {$status['users_not_migrated']} user(s) not migrated to RBAC system\n";
        echo "Run: php database/migrate_existing_users.php\n";
    }
    
    echo "\n";
}

/**
 * Helper function to view deprecation statistics
 */
function viewDeprecationStats(): void
{
    $stats = AuthCompatibility::getDeprecationStats();
    
    echo "==============================================\n";
    echo "Deprecation Statistics\n";
    echo "==============================================\n\n";
    
    echo "Total Deprecated Calls: {$stats['total_calls']}\n\n";
    
    if (!empty($stats['by_method'])) {
        echo "By Method:\n";
        echo "----------------------------------------------\n";
        foreach ($stats['by_method'] as $method => $count) {
            echo "  $method: $count call(s)\n";
        }
        echo "\n";
    }
    
    if (!empty($stats['by_file'])) {
        echo "By File:\n";
        echo "----------------------------------------------\n";
        foreach ($stats['by_file'] as $file => $count) {
            echo "  $file: $count call(s)\n";
        }
        echo "\n";
    }
    
    if ($stats['total_calls'] === 0) {
        echo "✓ No deprecated auth methods in use\n\n";
    } else {
        echo "⚠ Consider migrating to RBAC permission checks\n\n";
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    $action = isset($argv[1]) ? $argv[1] : 'status';
    
    switch ($action) {
        case 'stats':
            viewDeprecationStats();
            break;
        case 'clear-log':
            if (AuthCompatibility::clearDeprecationLog()) {
                echo "✓ Deprecation log cleared\n";
            } else {
                echo "✗ Failed to clear deprecation log\n";
            }
            break;
        case 'status':
        default:
            checkAuthMigrationStatus();
            break;
    }
}
