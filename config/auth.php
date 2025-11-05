<?php
// Authentication configuration
session_start();

// Define auth-specific constants first (before including constants.php)
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
}
if (!defined('ADMIN_ROLE')) {
    define('ADMIN_ROLE', 'admin');
}
if (!defined('VENDOR_ROLE')) {
    define('VENDOR_ROLE', 'vendor');
}

// Include constants for BASE_URL (used by other parts of the application)
require_once __DIR__ . '/constants.php';

class Auth {
    public static function login($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['vendor_id'] = $user['vendor_id'] ?? null;
        $_SESSION['login_time'] = time();
    }
    
    public static function logout() {
        session_destroy();
        // Use relative path to avoid BASE_URL dependency
        $loginPath = self::getLoginPath();
        header('Location: ' . $loginPath);
        exit();
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && self::isSessionValid();
    }
    
    public static function isSessionValid() {
        if (!isset($_SESSION['login_time'])) {
            return false;
        }
        
        if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
            self::clearSession();
            return false;
        }
        
        return true;
    }
    
    public static function requireAuth() {
        if (!self::isLoggedIn()) {
            // If it's an AJAX request, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Session expired', 'redirect' => true]);
                exit();
            }
            
            // For regular requests, redirect to login
            $loginPath = self::getLoginPath();
            header('Location: ' . $loginPath);
            exit();
        }
    }
    
    public static function requireRole($role) {
        self::requireAuth();
        if ($_SESSION['role'] !== $role) {
            // If it's an AJAX request, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Access denied', 'redirect' => false]);
                exit();
            }
            
            header('HTTP/1.0 403 Forbidden');
            exit('Access denied - Required role: ' . $role);
        }
    }
    
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'vendor_id' => $_SESSION['vendor_id'] ?? null
            ];
        }
        return null;
    }
    
    public static function updateSession($user) {
        if (self::isLoggedIn()) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['vendor_id'] = $user['vendor_id'] ?? null;
        }
    }
    
    public static function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    public static function getVendorId() {
        return $_SESSION['vendor_id'] ?? null;
    }
    
    public static function isVendor() {
        return isset($_SESSION['role']) && $_SESSION['role'] === VENDOR_ROLE;
    }
    
    public static function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === ADMIN_ROLE;
    }
    
    public static function requireVendor() {
        self::requireAuth();
        if (!self::isVendor()) {
            // If it's an AJAX request, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Vendor access required', 'redirect' => false]);
                exit();
            }
            
            header('HTTP/1.0 403 Forbidden');
            exit('Access denied - Vendor access required');
        }
    }
    
    public static function requireAdminOrVendor() {
        self::requireAuth();
        if (!self::isAdmin() && !self::isVendor()) {
            header('HTTP/1.0 403 Forbidden');
            exit('Access denied');
        }
    }
    
    public static function requireVendorPermission($permission) {
        self::requireVendor();
        
        require_once __DIR__ . '/../models/VendorPermission.php';
        $permissionModel = new VendorPermission();
        
        if (!$permissionModel->hasPermission(self::getVendorId(), $permission)) {
            // If it's an AJAX request, return JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Permission required: ' . $permission, 'redirect' => false]);
                exit();
            }
            
            header('HTTP/1.0 403 Forbidden');
            exit('Access denied - Permission required: ' . $permission);
        }
    }
    
    public static function hasVendorPermission($permission) {
        if (!self::isVendor()) {
            return false;
        }
        
        require_once __DIR__ . '/../models/VendorPermission.php';
        $permissionModel = new VendorPermission();
        
        return $permissionModel->hasPermission(self::getVendorId(), $permission);
    }
    
    private static function getLoginPath() {
        // Determine the correct path to login based on current location
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        if (strpos($currentPath, '/admin/') !== false) {
            return '../../auth/login.php';
        } elseif (strpos($currentPath, '/vendor/') !== false) {
            return '../auth/login.php';
        } else {
            return '../auth/login.php';
        }
    }
    
    private static function clearSession() {
        // Clear session without redirect to avoid BASE_URL issues
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
?>