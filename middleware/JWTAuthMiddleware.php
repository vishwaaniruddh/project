<?php
/**
 * JWT Authentication Middleware
 * 
 * Handles JWT token validation for API requests.
 * Extracts and validates Bearer tokens from Authorization header.
 * Requirements: 6.3, 12.5
 */

require_once __DIR__ . '/../includes/jwt_helper.php';
require_once __DIR__ . '/../services/TokenService.php';

class JWTAuthMiddleware
{
    private static $currentUser = null;
    private static $tokenPayload = null;
    
    /**
     * Authenticate the request by validating the Bearer token
     * 
     * @return array Token payload with user info and permissions
     * @throws Exception on authentication failure
     */
    public static function authenticate(): array
    {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            self::unauthorizedResponse('AUTH_REQUIRED', 'No authentication token provided');
        }
        
        // Validate the token
        $payload = JWTHelper::validateToken($token);
        
        if ($payload === false) {
            // Check if token is expired vs invalid
            if (JWTHelper::isExpired($token)) {
                self::unauthorizedResponse('TOKEN_EXPIRED', 'Authentication token has expired');
            }
            self::unauthorizedResponse('TOKEN_INVALID', 'Invalid authentication token');
        }
        
        // Store the payload for later use
        self::$tokenPayload = $payload;
        self::$currentUser = [
            'id' => $payload['user_id'] ?? null,
            'username' => $payload['username'] ?? null,
            'role_id' => $payload['role_id'] ?? null,
            'role_name' => $payload['role_name'] ?? null,
            'vendor_id' => $payload['vendor_id'] ?? null,
            'permissions' => $payload['permissions'] ?? []
        ];
        
        return $payload;
    }

    
    /**
     * Extract Bearer token from Authorization header
     * 
     * @return string|null Token string or null if not found
     */
    public static function getTokenFromHeader(): ?string
    {
        $headers = self::getAuthorizationHeader();
        
        if (empty($headers)) {
            return null;
        }
        
        // Check for Bearer token format
        if (preg_match('/Bearer\s+(.+)$/i', $headers, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Get the Authorization header from the request
     * 
     * @return string|null Authorization header value
     */
    private static function getAuthorizationHeader(): ?string
    {
        // Try different methods to get the Authorization header
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        // Apache specific
        if (function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            if (isset($headers['Authorization'])) {
                return $headers['Authorization'];
            }
            // Case-insensitive check
            foreach ($headers as $key => $value) {
                if (strtolower($key) === 'authorization') {
                    return $value;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get the current authenticated user from the token
     * 
     * @return array|null User data or null if not authenticated
     */
    public static function getCurrentUser(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }
        
        // Try to authenticate if not already done
        try {
            self::authenticate();
            return self::$currentUser;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get the current authenticated user's ID
     * 
     * @return int|null User ID or null if not authenticated
     */
    public static function getCurrentUserId(): ?int
    {
        $user = self::getCurrentUser();
        return $user ? ($user['id'] ?? null) : null;
    }
    
    /**
     * Get the current user's role ID
     * 
     * @return int|null Role ID or null if not authenticated
     */
    public static function getCurrentRoleId(): ?int
    {
        $user = self::getCurrentUser();
        return $user ? ($user['role_id'] ?? null) : null;
    }
    
    /**
     * Get the current user's role name
     * 
     * @return string|null Role name or null if not authenticated
     */
    public static function getCurrentRoleName(): ?string
    {
        $user = self::getCurrentUser();
        return $user ? ($user['role_name'] ?? null) : null;
    }
    
    /**
     * Get the current user's permissions from the token
     * 
     * @return array Array of permission keys
     */
    public static function getCurrentPermissions(): array
    {
        $user = self::getCurrentUser();
        return $user ? ($user['permissions'] ?? []) : [];
    }
    
    /**
     * Get the full token payload
     * 
     * @return array|null Token payload or null
     */
    public static function getTokenPayload(): ?array
    {
        return self::$tokenPayload;
    }
    
    /**
     * Check if the current request is authenticated
     * 
     * @return bool True if authenticated
     */
    public static function isAuthenticated(): bool
    {
        return self::getCurrentUser() !== null;
    }
    
    /**
     * Check if the current user is a superadmin
     * 
     * @return bool True if superadmin
     */
    public static function isSuperAdmin(): bool
    {
        $user = self::getCurrentUser();
        return $user && ($user['role_name'] === 'superadmin');
    }
    
    /**
     * Send unauthorized response and exit
     * 
     * @param string $code Error code
     * @param string $message Error message
     */
    private static function unauthorizedResponse(string $code, string $message): void
    {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ]);
        exit;
    }
    
    /**
     * Reset the middleware state (useful for testing)
     */
    public static function reset(): void
    {
        self::$currentUser = null;
        self::$tokenPayload = null;
    }
}
