<?php
/**
 * Token Service
 * 
 * Handles JWT access token and refresh token management for the RBAC system.
 * Requirements: 7.1, 7.2
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/jwt_helper.php';

class TokenService
{
    private $db;
    private $accessTokenExpiry = 3600;      // 1 hour
    private $refreshTokenExpiry = 604800;   // 7 days
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Generate an access token for a user with permissions
     * 
     * @param array $user User data
     * @param array $permissions Array of permission keys
     * @return string JWT access token
     */
    public function generateAccessToken(array $user, array $permissions): string
    {
        return JWTHelper::createUserToken($user, $permissions, $this->accessTokenExpiry);
    }
    
    /**
     * Generate a refresh token and store it in the database
     * 
     * @param int $userId User ID
     * @return string Refresh token
     */
    public function generateRefreshToken(int $userId): string
    {
        // Generate a secure random token
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + $this->refreshTokenExpiry);
        
        try {
            // Store the hashed token in the database
            $sql = "INSERT INTO refresh_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $tokenHash, $expiresAt]);
            
            return $token;
        } catch (PDOException $e) {
            throw new Exception('Failed to generate refresh token: ' . $e->getMessage());
        }
    }

    
    /**
     * Validate an access token
     * 
     * @param string $token JWT access token
     * @return array|null Decoded token payload or null if invalid
     */
    public function validateToken(string $token): ?array
    {
        $payload = JWTHelper::validateToken($token);
        
        if ($payload === false) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Refresh an access token using a refresh token
     * 
     * @param string $refreshToken Refresh token
     * @return array|null Array with new access_token and user data, or null if invalid
     */
    public function refreshAccessToken(string $refreshToken): ?array
    {
        $tokenHash = hash('sha256', $refreshToken);
        
        try {
            // Find the refresh token in the database
            $sql = "SELECT rt.*, u.id as user_id, u.username, u.email, u.role_id, r.name as role_name
                    FROM refresh_tokens rt
                    INNER JOIN users u ON rt.user_id = u.id
                    LEFT JOIN roles r ON u.role_id = r.id
                    WHERE rt.token_hash = ? 
                    AND rt.expires_at > NOW() 
                    AND rt.revoked_at IS NULL";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tokenHash]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result) {
                return null;
            }
            
            // Get user permissions
            $permissions = $this->getUserPermissionKeys($result['user_id'], $result['role_id']);
            
            // Generate new access token
            $user = [
                'id' => $result['user_id'],
                'username' => $result['username'],
                'email' => $result['email'],
                'role_id' => $result['role_id'],
                'role_name' => $result['role_name']
            ];
            
            $accessToken = $this->generateAccessToken($user, $permissions);
            
            return [
                'access_token' => $accessToken,
                'user' => $user,
                'permissions' => $permissions,
                'expires_in' => $this->accessTokenExpiry
            ];
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Invalidate a refresh token (for logout)
     * 
     * @param string $refreshToken Refresh token to invalidate
     * @return bool Success status
     */
    public function invalidateToken(string $refreshToken): bool
    {
        $tokenHash = hash('sha256', $refreshToken);
        
        try {
            $sql = "UPDATE refresh_tokens SET revoked_at = NOW() WHERE token_hash = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$tokenHash]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Invalidate all refresh tokens for a user
     * 
     * @param int $userId User ID
     * @return bool Success status
     */
    public function invalidateAllUserTokens(int $userId): bool
    {
        try {
            $sql = "UPDATE refresh_tokens SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Decode a token without validation (for inspection)
     * 
     * @param string $token JWT token
     * @return array|null Decoded payload or null
     */
    public function decodeToken(string $token): ?array
    {
        return JWTHelper::getPayload($token);
    }
    
    /**
     * Check if a token is expired
     * 
     * @param string $token JWT token
     * @return bool True if expired
     */
    public function isTokenExpired(string $token): bool
    {
        return JWTHelper::isExpired($token);
    }
    
    /**
     * Get user permission keys (helper method)
     * 
     * @param int $userId User ID
     * @param int|null $roleId Role ID
     * @return array Array of permission keys
     */
    private function getUserPermissionKeys(int $userId, ?int $roleId): array
    {
        if (!$roleId) {
            return [];
        }
        
        try {
            // Get role permissions
            $sql = "SELECT p.permission_key 
                    FROM permissions p
                    INNER JOIN role_permissions rp ON p.id = rp.permission_id
                    WHERE rp.role_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId]);
            $rolePermissions = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Get user-specific overrides
            $sql = "SELECT p.permission_key, up.is_granted
                    FROM user_permissions up
                    INNER JOIN permissions p ON up.permission_id = p.id
                    WHERE up.user_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $overrides = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Apply overrides
            $permissions = array_flip($rolePermissions);
            
            foreach ($overrides as $override) {
                if ($override['is_granted']) {
                    $permissions[$override['permission_key']] = true;
                } else {
                    unset($permissions[$override['permission_key']]);
                }
            }
            
            return array_keys($permissions);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Clean up expired refresh tokens
     * 
     * @return int Number of tokens deleted
     */
    public function cleanupExpiredTokens(): int
    {
        try {
            $sql = "DELETE FROM refresh_tokens WHERE expires_at < NOW() OR revoked_at IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            return $stmt->rowCount();
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Get active refresh tokens for a user
     * 
     * @param int $userId User ID
     * @return array Array of active tokens
     */
    public function getActiveTokens(int $userId): array
    {
        try {
            $sql = "SELECT id, created_at, expires_at 
                    FROM refresh_tokens 
                    WHERE user_id = ? AND expires_at > NOW() AND revoked_at IS NULL
                    ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Set access token expiry time
     * 
     * @param int $seconds Expiry time in seconds
     */
    public function setAccessTokenExpiry(int $seconds): void
    {
        $this->accessTokenExpiry = $seconds;
    }
    
    /**
     * Set refresh token expiry time
     * 
     * @param int $seconds Expiry time in seconds
     */
    public function setRefreshTokenExpiry(int $seconds): void
    {
        $this->refreshTokenExpiry = $seconds;
    }
    
    /**
     * Get access token expiry time
     * 
     * @return int Expiry time in seconds
     */
    public function getAccessTokenExpiry(): int
    {
        return $this->accessTokenExpiry;
    }
    
    /**
     * Get refresh token expiry time
     * 
     * @return int Expiry time in seconds
     */
    public function getRefreshTokenExpiry(): int
    {
        return $this->refreshTokenExpiry;
    }
}
