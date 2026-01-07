<?php
/**
 * API Permission Middleware
 * 
 * Handles permission checking for API endpoints.
 * Works with JWT tokens to verify user has required permissions.
 * Requirements: 6.1, 6.2, 6.5, 12.2
 */

require_once __DIR__ . '/JWTAuthMiddleware.php';
require_once __DIR__ . '/../services/PermissionService.php';

class ApiPermissionMiddleware
{
    /**
     * Require a single permission to access the resource
     * 
     * @param string $permission Permission key required
     * @return void Exits with 403 if permission denied
     */
    public static function require(string $permission): void
    {
        // First ensure user is authenticated
        JWTAuthMiddleware::authenticate();
        
        // Check if user is superadmin (has all permissions)
        if (JWTAuthMiddleware::isSuperAdmin()) {
            return;
        }
        
        // Get permissions from token
        $tokenPermissions = JWTAuthMiddleware::getCurrentPermissions();
        
        // Check if permission exists in token
        if (in_array($permission, $tokenPermissions, true)) {
            return;
        }
        
        // Permission denied
        self::forbiddenResponse($permission);
    }
    
    /**
     * Require any one of the specified permissions (OR logic)
     * 
     * @param array $permissions Array of permission keys
     * @return void Exits with 403 if none of the permissions are granted
     */
    public static function requireAny(array $permissions): void
    {
        if (empty($permissions)) {
            return;
        }
        
        // First ensure user is authenticated
        JWTAuthMiddleware::authenticate();
        
        // Check if user is superadmin (has all permissions)
        if (JWTAuthMiddleware::isSuperAdmin()) {
            return;
        }

        
        // Get permissions from token
        $tokenPermissions = JWTAuthMiddleware::getCurrentPermissions();
        
        // Check if any permission exists in token
        foreach ($permissions as $permission) {
            if (in_array($permission, $tokenPermissions, true)) {
                return;
            }
        }
        
        // None of the permissions granted
        self::forbiddenResponse($permissions[0], $permissions);
    }
    
    /**
     * Require all of the specified permissions (AND logic)
     * 
     * @param array $permissions Array of permission keys
     * @return void Exits with 403 if any permission is missing
     */
    public static function requireAll(array $permissions): void
    {
        if (empty($permissions)) {
            return;
        }
        
        // First ensure user is authenticated
        JWTAuthMiddleware::authenticate();
        
        // Check if user is superadmin (has all permissions)
        if (JWTAuthMiddleware::isSuperAdmin()) {
            return;
        }
        
        // Get permissions from token
        $tokenPermissions = JWTAuthMiddleware::getCurrentPermissions();
        
        // Check if all permissions exist in token
        $missingPermissions = [];
        foreach ($permissions as $permission) {
            if (!in_array($permission, $tokenPermissions, true)) {
                $missingPermissions[] = $permission;
            }
        }
        
        if (!empty($missingPermissions)) {
            self::forbiddenResponse($missingPermissions[0], $missingPermissions);
        }
    }
    
    /**
     * Check if user has a specific permission without blocking
     * 
     * @param string $permission Permission key to check
     * @return bool True if user has the permission
     */
    public static function hasPermission(string $permission): bool
    {
        // Check if user is superadmin
        if (JWTAuthMiddleware::isSuperAdmin()) {
            return true;
        }
        
        $tokenPermissions = JWTAuthMiddleware::getCurrentPermissions();
        return in_array($permission, $tokenPermissions, true);
    }
    
    /**
     * Check if user has any of the specified permissions without blocking
     * 
     * @param array $permissions Array of permission keys
     * @return bool True if user has at least one permission
     */
    public static function hasAnyPermission(array $permissions): bool
    {
        if (empty($permissions)) {
            return false;
        }
        
        // Check if user is superadmin
        if (JWTAuthMiddleware::isSuperAdmin()) {
            return true;
        }
        
        $tokenPermissions = JWTAuthMiddleware::getCurrentPermissions();
        
        foreach ($permissions as $permission) {
            if (in_array($permission, $tokenPermissions, true)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if user has all of the specified permissions without blocking
     * 
     * @param array $permissions Array of permission keys
     * @return bool True if user has all permissions
     */
    public static function hasAllPermissions(array $permissions): bool
    {
        if (empty($permissions)) {
            return true;
        }
        
        // Check if user is superadmin
        if (JWTAuthMiddleware::isSuperAdmin()) {
            return true;
        }
        
        $tokenPermissions = JWTAuthMiddleware::getCurrentPermissions();
        
        foreach ($permissions as $permission) {
            if (!in_array($permission, $tokenPermissions, true)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Send forbidden response and exit
     * 
     * @param string $requiredPermission The primary permission that was required
     * @param array|null $allRequired All required permissions (for multiple permission checks)
     */
    private static function forbiddenResponse(string $requiredPermission, ?array $allRequired = null): void
    {
        http_response_code(403);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'error' => [
                'code' => 'PERMISSION_DENIED',
                'message' => 'You do not have permission to access this resource',
                'required_permission' => $requiredPermission
            ]
        ];
        
        // Include all required permissions if multiple were checked
        if ($allRequired !== null && count($allRequired) > 1) {
            $response['error']['required_permissions'] = $allRequired;
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Require user to be authenticated (alias for JWTAuthMiddleware::authenticate)
     * 
     * @return array Token payload
     */
    public static function requireAuth(): array
    {
        return JWTAuthMiddleware::authenticate();
    }
    
    /**
     * Require user to be a superadmin
     * 
     * @return void Exits with 403 if not superadmin
     */
    public static function requireSuperAdmin(): void
    {
        JWTAuthMiddleware::authenticate();
        
        if (!JWTAuthMiddleware::isSuperAdmin()) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => [
                    'code' => 'PERMISSION_DENIED',
                    'message' => 'Superadmin access required',
                    'required_permission' => 'superadmin'
                ]
            ]);
            exit;
        }
    }
    
    /**
     * Get the current authenticated user ID
     * 
     * @return int|null User ID or null
     */
    public static function getCurrentUserId(): ?int
    {
        return JWTAuthMiddleware::getCurrentUserId();
    }
    
    /**
     * Get the current authenticated user
     * 
     * @return array|null User data or null
     */
    public static function getCurrentUser(): ?array
    {
        return JWTAuthMiddleware::getCurrentUser();
    }
}
