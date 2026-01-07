<?php
/**
 * SAR Inventory Permission Middleware
 * Implements inventory-specific permission validation with role-based access control
 * and company isolation enforcement
 */

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Menu.php';
require_once __DIR__ . '/../includes/logger.php';

class SarInvPermissionMiddleware {
    
    // Inventory module permissions
    const PERMISSION_VIEW_DASHBOARD = 'sar_inv_dashboard_view';
    const PERMISSION_MANAGE_WAREHOUSES = 'sar_inv_warehouses_manage';
    const PERMISSION_VIEW_WAREHOUSES = 'sar_inv_warehouses_view';
    const PERMISSION_MANAGE_PRODUCTS = 'sar_inv_products_manage';
    const PERMISSION_VIEW_PRODUCTS = 'sar_inv_products_view';
    const PERMISSION_MANAGE_CATEGORIES = 'sar_inv_categories_manage';
    const PERMISSION_VIEW_CATEGORIES = 'sar_inv_categories_view';
    const PERMISSION_MANAGE_STOCK = 'sar_inv_stock_manage';
    const PERMISSION_VIEW_STOCK = 'sar_inv_stock_view';
    const PERMISSION_MANAGE_DISPATCHES = 'sar_inv_dispatches_manage';
    const PERMISSION_VIEW_DISPATCHES = 'sar_inv_dispatches_view';
    const PERMISSION_APPROVE_DISPATCHES = 'sar_inv_dispatches_approve';
    const PERMISSION_MANAGE_TRANSFERS = 'sar_inv_transfers_manage';
    const PERMISSION_VIEW_TRANSFERS = 'sar_inv_transfers_view';
    const PERMISSION_APPROVE_TRANSFERS = 'sar_inv_transfers_approve';
    const PERMISSION_MANAGE_ASSETS = 'sar_inv_assets_manage';
    const PERMISSION_VIEW_ASSETS = 'sar_inv_assets_view';
    const PERMISSION_MANAGE_REPAIRS = 'sar_inv_repairs_manage';
    const PERMISSION_VIEW_REPAIRS = 'sar_inv_repairs_view';
    const PERMISSION_MANAGE_MATERIALS = 'sar_inv_materials_manage';
    const PERMISSION_VIEW_MATERIALS = 'sar_inv_materials_view';
    const PERMISSION_APPROVE_MATERIALS = 'sar_inv_materials_approve';
    const PERMISSION_VIEW_REPORTS = 'sar_inv_reports_view';
    const PERMISSION_EXPORT_DATA = 'sar_inv_export_data';
    const PERMISSION_VIEW_AUDIT_LOG = 'sar_inv_audit_log_view';
    const PERMISSION_ADMIN_FULL = 'sar_inv_admin_full';
    
    // Role-based permission mappings
    private static $rolePermissions = [
        'admin' => [
            self::PERMISSION_ADMIN_FULL,
            self::PERMISSION_VIEW_DASHBOARD,
            self::PERMISSION_MANAGE_WAREHOUSES,
            self::PERMISSION_VIEW_WAREHOUSES,
            self::PERMISSION_MANAGE_PRODUCTS,
            self::PERMISSION_VIEW_PRODUCTS,
            self::PERMISSION_MANAGE_CATEGORIES,
            self::PERMISSION_VIEW_CATEGORIES,
            self::PERMISSION_MANAGE_STOCK,
            self::PERMISSION_VIEW_STOCK,
            self::PERMISSION_MANAGE_DISPATCHES,
            self::PERMISSION_VIEW_DISPATCHES,
            self::PERMISSION_APPROVE_DISPATCHES,
            self::PERMISSION_MANAGE_TRANSFERS,
            self::PERMISSION_VIEW_TRANSFERS,
            self::PERMISSION_APPROVE_TRANSFERS,
            self::PERMISSION_MANAGE_ASSETS,
            self::PERMISSION_VIEW_ASSETS,
            self::PERMISSION_MANAGE_REPAIRS,
            self::PERMISSION_VIEW_REPAIRS,
            self::PERMISSION_MANAGE_MATERIALS,
            self::PERMISSION_VIEW_MATERIALS,
            self::PERMISSION_APPROVE_MATERIALS,
            self::PERMISSION_VIEW_REPORTS,
            self::PERMISSION_EXPORT_DATA,
            self::PERMISSION_VIEW_AUDIT_LOG
        ],
        'vendor' => [
            self::PERMISSION_VIEW_DASHBOARD,
            self::PERMISSION_VIEW_WAREHOUSES,
            self::PERMISSION_VIEW_PRODUCTS,
            self::PERMISSION_VIEW_CATEGORIES,
            self::PERMISSION_VIEW_STOCK,
            self::PERMISSION_VIEW_DISPATCHES,
            self::PERMISSION_VIEW_TRANSFERS,
            self::PERMISSION_VIEW_ASSETS,
            self::PERMISSION_VIEW_REPAIRS,
            self::PERMISSION_VIEW_MATERIALS
        ],
        'warehouse_manager' => [
            self::PERMISSION_VIEW_DASHBOARD,
            self::PERMISSION_MANAGE_WAREHOUSES,
            self::PERMISSION_VIEW_WAREHOUSES,
            self::PERMISSION_VIEW_PRODUCTS,
            self::PERMISSION_VIEW_CATEGORIES,
            self::PERMISSION_MANAGE_STOCK,
            self::PERMISSION_VIEW_STOCK,
            self::PERMISSION_MANAGE_DISPATCHES,
            self::PERMISSION_VIEW_DISPATCHES,
            self::PERMISSION_MANAGE_TRANSFERS,
            self::PERMISSION_VIEW_TRANSFERS,
            self::PERMISSION_APPROVE_TRANSFERS,
            self::PERMISSION_VIEW_ASSETS,
            self::PERMISSION_VIEW_REPORTS
        ],
        'inventory_clerk' => [
            self::PERMISSION_VIEW_DASHBOARD,
            self::PERMISSION_VIEW_WAREHOUSES,
            self::PERMISSION_VIEW_PRODUCTS,
            self::PERMISSION_VIEW_CATEGORIES,
            self::PERMISSION_MANAGE_STOCK,
            self::PERMISSION_VIEW_STOCK,
            self::PERMISSION_VIEW_DISPATCHES,
            self::PERMISSION_VIEW_TRANSFERS,
            self::PERMISSION_VIEW_ASSETS
        ]
    ];
    
    /**
     * Check if user is authenticated
     */
    public static function requireAuth() {
        if (!Auth::isLoggedIn()) {
            self::handleUnauthorized('Authentication required');
        }
        return true;
    }
    
    /**
     * Check if user has a specific permission
     */
    public static function hasPermission($permission) {
        if (!Auth::isLoggedIn()) {
            return false;
        }
        
        $currentUser = Auth::getCurrentUser();
        $userRole = $currentUser['role'] ?? '';
        
        // Admin has full access
        if ($userRole === 'admin') {
            return true;
        }
        
        // Check role-based permissions
        if (isset(self::$rolePermissions[$userRole])) {
            if (in_array($permission, self::$rolePermissions[$userRole])) {
                return true;
            }
        }
        
        // Check user-specific permissions from database
        return self::checkUserPermission($currentUser['id'], $permission);
    }
    
    /**
     * Require a specific permission
     */
    public static function requirePermission($permission) {
        self::requireAuth();
        
        if (!self::hasPermission($permission)) {
            self::logAccessDenied($permission);
            self::handleForbidden("Permission required: $permission");
        }
        
        return true;
    }
    
    /**
     * Require any of the specified permissions
     */
    public static function requireAnyPermission(array $permissions) {
        self::requireAuth();
        
        foreach ($permissions as $permission) {
            if (self::hasPermission($permission)) {
                return true;
            }
        }
        
        self::logAccessDenied(implode(', ', $permissions));
        self::handleForbidden("One of these permissions required: " . implode(', ', $permissions));
    }
    
    /**
     * Require all of the specified permissions
     */
    public static function requireAllPermissions(array $permissions) {
        self::requireAuth();
        
        foreach ($permissions as $permission) {
            if (!self::hasPermission($permission)) {
                self::logAccessDenied($permission);
                self::handleForbidden("Permission required: $permission");
            }
        }
        
        return true;
    }
    
    /**
     * Check company isolation - ensure user can only access their company's data
     */
    public static function enforceCompanyIsolation($companyId) {
        self::requireAuth();
        
        $currentCompanyId = self::getCurrentCompanyId();
        
        if ($currentCompanyId !== null && $companyId != $currentCompanyId) {
            self::logSecurityEvent('company_isolation_violation', [
                'requested_company' => $companyId,
                'user_company' => $currentCompanyId
            ]);
            self::handleForbidden('Access denied: Company isolation violation');
        }
        
        return true;
    }
    
    /**
     * Get current user's company ID
     */
    public static function getCurrentCompanyId() {
        return $_SESSION['company_id'] ?? 1;
    }
    
    /**
     * Get current user ID
     */
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user role
     */
    public static function getCurrentUserRole() {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Check if current user is admin
     */
    public static function isAdmin() {
        return Auth::isAdmin();
    }
    
    /**
     * Check if current user is vendor
     */
    public static function isVendor() {
        return Auth::isVendor();
    }
    
    /**
     * Require admin role
     */
    public static function requireAdmin() {
        self::requireAuth();
        
        if (!self::isAdmin()) {
            self::logAccessDenied('admin_role');
            self::handleForbidden('Admin access required');
        }
        
        return true;
    }
    
    /**
     * Require admin or specific permission
     */
    public static function requireAdminOrPermission($permission) {
        self::requireAuth();
        
        if (self::isAdmin()) {
            return true;
        }
        
        if (!self::hasPermission($permission)) {
            self::logAccessDenied($permission);
            self::handleForbidden("Admin access or permission required: $permission");
        }
        
        return true;
    }
    
    /**
     * Check user-specific permission from database
     */
    private static function checkUserPermission($userId, $permission) {
        try {
            $db = Database::getInstance()->getConnection();
            
            // Check if sar_inv_user_permissions table exists
            $stmt = $db->prepare("SHOW TABLES LIKE 'sar_inv_user_permissions'");
            $stmt->execute();
            if (!$stmt->fetch()) {
                return false;
            }
            
            $sql = "SELECT COUNT(*) FROM sar_inv_user_permissions 
                    WHERE user_id = ? AND permission = ? AND is_granted = 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$userId, $permission]);
            
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            Logger::error('Permission check failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Grant permission to user
     */
    public static function grantPermission($userId, $permission) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $sql = "INSERT INTO sar_inv_user_permissions (user_id, permission, is_granted, granted_by, granted_at) 
                    VALUES (?, ?, 1, ?, NOW())
                    ON DUPLICATE KEY UPDATE is_granted = 1, granted_by = ?, granted_at = NOW()";
            $stmt = $db->prepare($sql);
            $grantedBy = self::getCurrentUserId();
            $stmt->execute([$userId, $permission, $grantedBy, $grantedBy]);
            
            self::logSecurityEvent('permission_granted', [
                'target_user' => $userId,
                'permission' => $permission
            ]);
            
            return true;
        } catch (PDOException $e) {
            Logger::error('Grant permission failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Revoke permission from user
     */
    public static function revokePermission($userId, $permission) {
        try {
            $db = Database::getInstance()->getConnection();
            
            $sql = "UPDATE sar_inv_user_permissions 
                    SET is_granted = 0, revoked_by = ?, revoked_at = NOW() 
                    WHERE user_id = ? AND permission = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([self::getCurrentUserId(), $userId, $permission]);
            
            self::logSecurityEvent('permission_revoked', [
                'target_user' => $userId,
                'permission' => $permission
            ]);
            
            return true;
        } catch (PDOException $e) {
            Logger::error('Revoke permission failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
    
    /**
     * Get all permissions for a user
     */
    public static function getUserPermissions($userId) {
        $permissions = [];
        
        // Get role-based permissions
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user && isset(self::$rolePermissions[$user['role']])) {
                $permissions = self::$rolePermissions[$user['role']];
            }
        } catch (PDOException $e) {
            Logger::error('Get user role failed', ['error' => $e->getMessage()]);
        }
        
        // Get user-specific permissions
        try {
            $db = Database::getInstance()->getConnection();
            
            $stmt = $db->prepare("SHOW TABLES LIKE 'sar_inv_user_permissions'");
            $stmt->execute();
            if ($stmt->fetch()) {
                $sql = "SELECT permission FROM sar_inv_user_permissions 
                        WHERE user_id = ? AND is_granted = 1";
                $stmt = $db->prepare($sql);
                $stmt->execute([$userId]);
                
                while ($row = $stmt->fetch()) {
                    if (!in_array($row['permission'], $permissions)) {
                        $permissions[] = $row['permission'];
                    }
                }
            }
        } catch (PDOException $e) {
            Logger::error('Get user permissions failed', ['error' => $e->getMessage()]);
        }
        
        return $permissions;
    }
    
    /**
     * Validate access to inventory module URL
     */
    public static function validateModuleAccess($moduleUrl = null) {
        self::requireAuth();
        
        if ($moduleUrl === null) {
            $moduleUrl = $_SERVER['REQUEST_URI'] ?? '';
        }
        
        // Map URLs to required permissions
        $urlPermissions = [
            '/admin/sar-inventory/' => self::PERMISSION_VIEW_DASHBOARD,
            '/admin/sar-inventory/index.php' => self::PERMISSION_VIEW_DASHBOARD,
            '/admin/sar-inventory/warehouses/' => self::PERMISSION_VIEW_WAREHOUSES,
            '/admin/sar-inventory/warehouses/create' => self::PERMISSION_MANAGE_WAREHOUSES,
            '/admin/sar-inventory/warehouses/edit' => self::PERMISSION_MANAGE_WAREHOUSES,
            '/admin/sar-inventory/warehouses/delete' => self::PERMISSION_MANAGE_WAREHOUSES,
            '/admin/sar-inventory/products/' => self::PERMISSION_VIEW_PRODUCTS,
            '/admin/sar-inventory/products/create' => self::PERMISSION_MANAGE_PRODUCTS,
            '/admin/sar-inventory/products/edit' => self::PERMISSION_MANAGE_PRODUCTS,
            '/admin/sar-inventory/product-category/' => self::PERMISSION_VIEW_CATEGORIES,
            '/admin/sar-inventory/product-category/create' => self::PERMISSION_MANAGE_CATEGORIES,
            '/admin/sar-inventory/product-category/edit' => self::PERMISSION_MANAGE_CATEGORIES,
            '/admin/sar-inventory/stock-entry/' => self::PERMISSION_VIEW_STOCK,
            '/admin/sar-inventory/stock-entry/create' => self::PERMISSION_MANAGE_STOCK,
            '/admin/sar-inventory/dispatches/' => self::PERMISSION_VIEW_DISPATCHES,
            '/admin/sar-inventory/dispatches/create' => self::PERMISSION_MANAGE_DISPATCHES,
            '/admin/sar-inventory/dispatches/approve' => self::PERMISSION_APPROVE_DISPATCHES,
            '/admin/sar-inventory/transfers/' => self::PERMISSION_VIEW_TRANSFERS,
            '/admin/sar-inventory/transfers/create' => self::PERMISSION_MANAGE_TRANSFERS,
            '/admin/sar-inventory/transfers/approve' => self::PERMISSION_APPROVE_TRANSFERS,
            '/admin/sar-inventory/assets/' => self::PERMISSION_VIEW_ASSETS,
            '/admin/sar-inventory/assets/create' => self::PERMISSION_MANAGE_ASSETS,
            '/admin/sar-inventory/repairs/' => self::PERMISSION_VIEW_REPAIRS,
            '/admin/sar-inventory/repairs/create' => self::PERMISSION_MANAGE_REPAIRS,
            '/admin/sar-inventory/materials/' => self::PERMISSION_VIEW_MATERIALS,
            '/admin/sar-inventory/materials/create' => self::PERMISSION_MANAGE_MATERIALS,
            '/admin/sar-inventory/materials/requests/approve' => self::PERMISSION_APPROVE_MATERIALS,
            '/admin/sar-inventory/reports/' => self::PERMISSION_VIEW_REPORTS,
            '/admin/sar-inventory/audit-log/' => self::PERMISSION_VIEW_AUDIT_LOG,
            '/admin/sar-inventory/item-history/' => self::PERMISSION_VIEW_STOCK
        ];
        
        // Find matching permission for URL
        foreach ($urlPermissions as $pattern => $permission) {
            if (strpos($moduleUrl, $pattern) !== false) {
                return self::requirePermission($permission);
            }
        }
        
        // Default: require at least dashboard view permission for any sar-inventory URL
        if (strpos($moduleUrl, '/sar-inventory/') !== false) {
            return self::requirePermission(self::PERMISSION_VIEW_DASHBOARD);
        }
        
        return true;
    }
    
    /**
     * Handle unauthorized access (not logged in)
     */
    private static function handleUnauthorized($message = 'Authentication required') {
        if (self::isAjaxRequest()) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message,
                'redirect' => true
            ]);
            exit;
        }
        
        // Redirect to login
        header('Location: ' . self::getLoginUrl());
        exit;
    }
    
    /**
     * Handle forbidden access (logged in but no permission)
     */
    private static function handleForbidden($message = 'Access denied') {
        if (self::isAjaxRequest()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message,
                'redirect' => false
            ]);
            exit;
        }
        
        http_response_code(403);
        header('Location: ' . self::getDashboardUrl() . '?error=access_denied');
        exit;
    }
    
    /**
     * Check if current request is AJAX
     */
    private static function isAjaxRequest() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Get login URL
     */
    private static function getLoginUrl() {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        if (strpos($currentPath, '/admin/sar-inventory/') !== false) {
            return '../../../auth/login.php';
        } elseif (strpos($currentPath, '/admin/') !== false) {
            return '../../auth/login.php';
        }
        
        return '../auth/login.php';
    }
    
    /**
     * Get dashboard URL
     */
    private static function getDashboardUrl() {
        return '/admin/dashboard.php';
    }
    
    /**
     * Log access denied event
     */
    private static function logAccessDenied($permission) {
        $currentUser = Auth::getCurrentUser();
        Logger::logSecurityEvent('access_denied', [
            'user_id' => $currentUser['id'] ?? null,
            'username' => $currentUser['username'] ?? 'unknown',
            'role' => $currentUser['role'] ?? 'unknown',
            'permission' => $permission,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'ip_address' => self::getClientIp()
        ]);
    }
    
    /**
     * Log security event
     */
    private static function logSecurityEvent($event, $details = []) {
        $currentUser = Auth::getCurrentUser();
        $details['user_id'] = $currentUser['id'] ?? null;
        $details['username'] = $currentUser['username'] ?? 'unknown';
        $details['ip_address'] = self::getClientIp();
        $details['timestamp'] = date('Y-m-d H:i:s');
        
        Logger::logSecurityEvent($event, $details);
    }
    
    /**
     * Get client IP address
     */
    private static function getClientIp() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Get all available permissions
     */
    public static function getAllPermissions() {
        return [
            self::PERMISSION_VIEW_DASHBOARD,
            self::PERMISSION_MANAGE_WAREHOUSES,
            self::PERMISSION_VIEW_WAREHOUSES,
            self::PERMISSION_MANAGE_PRODUCTS,
            self::PERMISSION_VIEW_PRODUCTS,
            self::PERMISSION_MANAGE_CATEGORIES,
            self::PERMISSION_VIEW_CATEGORIES,
            self::PERMISSION_MANAGE_STOCK,
            self::PERMISSION_VIEW_STOCK,
            self::PERMISSION_MANAGE_DISPATCHES,
            self::PERMISSION_VIEW_DISPATCHES,
            self::PERMISSION_APPROVE_DISPATCHES,
            self::PERMISSION_MANAGE_TRANSFERS,
            self::PERMISSION_VIEW_TRANSFERS,
            self::PERMISSION_APPROVE_TRANSFERS,
            self::PERMISSION_MANAGE_ASSETS,
            self::PERMISSION_VIEW_ASSETS,
            self::PERMISSION_MANAGE_REPAIRS,
            self::PERMISSION_VIEW_REPAIRS,
            self::PERMISSION_MANAGE_MATERIALS,
            self::PERMISSION_VIEW_MATERIALS,
            self::PERMISSION_APPROVE_MATERIALS,
            self::PERMISSION_VIEW_REPORTS,
            self::PERMISSION_EXPORT_DATA,
            self::PERMISSION_VIEW_AUDIT_LOG,
            self::PERMISSION_ADMIN_FULL
        ];
    }
    
    /**
     * Get permissions for a role
     */
    public static function getRolePermissions($role) {
        return self::$rolePermissions[$role] ?? [];
    }
    
    /**
     * Get all roles
     */
    public static function getAllRoles() {
        return array_keys(self::$rolePermissions);
    }
}
?>
