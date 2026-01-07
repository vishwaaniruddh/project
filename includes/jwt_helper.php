<?php
/**
 * JWT Helper Class
 * 
 * Handles JWT token generation, validation, and management for the RBAC system.
 * Requirements: 7.2, 12.1
 */

class JWTHelper {
    private static $secretKey = 'your-secret-key-change-this-in-production';
    private static $algorithm = 'HS256';
    private static $defaultExpiry = 3600; // 1 hour default
    
    /**
     * Encode payload into JWT token
     * 
     * @param array $payload Data to encode
     * @param int|null $expiry Custom expiry time in seconds (optional)
     * @return string JWT token
     */
    public static function encode(array $payload, ?int $expiry = null): string
    {
        // Add issued at time if not present
        if (!isset($payload['iat'])) {
            $payload['iat'] = time();
        }
        
        // Add expiry if not present
        if (!isset($payload['exp'])) {
            $expiryTime = $expiry ?? self::$defaultExpiry;
            $payload['exp'] = time() + $expiryTime;
        }
        
        $header = json_encode(['typ' => 'JWT', 'alg' => self::$algorithm]);
        $payloadJson = json_encode($payload);
        
        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payloadJson);
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secretKey, true);
        $base64Signature = self::base64UrlEncode($signature);
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Decode JWT token and return payload
     * 
     * @param string $jwt JWT token string
     * @return array|null Decoded payload or null on failure
     */
    public static function decode(string $jwt): ?array
    {
        try {
            $tokenParts = explode('.', $jwt);
            
            if (count($tokenParts) !== 3) {
                return null;
            }
            
            $header = self::base64UrlDecode($tokenParts[0]);
            $payload = self::base64UrlDecode($tokenParts[1]);
            $signatureProvided = $tokenParts[2];
            
            if ($header === false || $payload === false) {
                return null;
            }
            
            // Verify signature
            $base64Header = self::base64UrlEncode($header);
            $base64Payload = self::base64UrlEncode($payload);
            
            $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, self::$secretKey, true);
            $base64Signature = self::base64UrlEncode($signature);
            
            if (!hash_equals($base64Signature, $signatureProvided)) {
                return null;
            }
            
            $decodedPayload = json_decode($payload, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return null;
            }
            
            return $decodedPayload;
        } catch (Exception $e) {
            return null;
        }
    }

    
    /**
     * Create a JWT token for a user with permissions
     * 
     * @param array $user User data (must include id, username, role_id, role_name)
     * @param array $permissions Array of permission keys
     * @param int|null $expiry Custom expiry time in seconds (optional)
     * @return string JWT token
     */
    public static function createUserToken(array $user, array $permissions, ?int $expiry = null): string
    {
        $payload = [
            'user_id' => $user['id'] ?? $user['user_id'],
            'username' => $user['username'],
            'role_id' => $user['role_id'] ?? null,
            'role_name' => $user['role_name'] ?? $user['role'] ?? null,
            'vendor_id' => $user['vendor_id'] ?? null,
            'permissions' => $permissions,
            'iat' => time(),
            'exp' => time() + ($expiry ?? self::$defaultExpiry)
        ];
        
        return self::encode($payload);
    }
    
    /**
     * Validate a JWT token
     * 
     * @param string $token JWT token string
     * @return array|false Decoded payload if valid, false otherwise
     */
    public static function validateToken(string $token)
    {
        $decoded = self::decode($token);
        
        if ($decoded === null) {
            return false;
        }
        
        // Check if token is expired
        if (self::isExpired($token)) {
            return false;
        }
        
        return $decoded;
    }
    
    /**
     * Check if a token is expired
     * 
     * @param string $token JWT token string
     * @return bool True if expired
     */
    public static function isExpired(string $token): bool
    {
        $payload = self::getPayload($token);
        
        if ($payload === null) {
            return true;
        }
        
        if (!isset($payload['exp'])) {
            return false; // No expiry set, consider not expired
        }
        
        return $payload['exp'] < time();
    }
    
    /**
     * Get payload from token without validation
     * 
     * @param string $token JWT token string
     * @return array|null Payload or null on failure
     */
    public static function getPayload(string $token): ?array
    {
        return self::decode($token);
    }
    
    /**
     * Generate token for user (legacy method for backward compatibility)
     * 
     * @param int $userId User ID
     * @param string $username Username
     * @param string $role User role
     * @param int|null $vendorId Vendor ID (optional)
     * @return string JWT token
     */
    public static function generateToken($userId, $username, $role, $vendorId = null): string
    {
        $payload = [
            'user_id' => $userId,
            'username' => $username,
            'role' => $role,
            'vendor_id' => $vendorId,
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60) // 24 hours
        ];
        
        return self::encode($payload);
    }
    
    /**
     * Get the secret key (for testing purposes only)
     * 
     * @return string Secret key
     */
    public static function getSecretKey(): string
    {
        return self::$secretKey;
    }
    
    /**
     * Set the secret key (should be called during application bootstrap)
     * 
     * @param string $key Secret key
     */
    public static function setSecretKey(string $key): void
    {
        self::$secretKey = $key;
    }
    
    /**
     * Set default expiry time
     * 
     * @param int $seconds Expiry time in seconds
     */
    public static function setDefaultExpiry(int $seconds): void
    {
        self::$defaultExpiry = $seconds;
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data Data to encode
     * @return string Encoded string
     */
    private static function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
    
    /**
     * Base64 URL decode
     * 
     * @param string $data Data to decode
     * @return string|false Decoded string or false on failure
     */
    private static function base64UrlDecode(string $data)
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }
}
