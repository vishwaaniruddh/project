<?php
/**
 * Auth Controller
 * 
 * Handles authentication endpoints for the RBAC system.
 * Provides login, logout, token refresh, and current user info.
 * Requirements: 7.1, 7.2
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../services/TokenService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../middleware/JWTAuthMiddleware.php';

class AuthController extends BaseController
{
    private $userModel;
    private $tokenService;
    private $permissionService;
    
    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->tokenService = new TokenService();
        $this->permissionService = new PermissionService();
    }
    
    /**
     * Handle user login
     * POST /api/auth/login
     * 
     * @return void JSON response with JWT token and user info
     */
    public function login(): void
    {
        header('Content-Type: application/json');
        
        // Get JSON input
        $input = $this->getJsonInput();
        
        // Validate required fields
        if (empty($input['username']) && empty($input['email'])) {
            $this->errorResponse('VALIDATION_ERROR', 'Username or email is required', 422);
        }
        
        if (empty($input['password'])) {
            $this->errorResponse('VALIDATION_ERROR', 'Password is required', 422);
        }
        
        // Find user by username or email
        $identifier = $input['username'] ?? $input['email'];
        $user = $this->userModel->findByUsername($identifier);
        
        if (!$user) {
            $user = $this->userModel->findByEmail($identifier);
        }
        
        if (!$user) {
            $user = $this->userModel->findByEmailOrPhone($identifier);
        }
        
        if (!$user) {
            $this->errorResponse('AUTH_FAILED', 'Invalid credentials', 401);
        }
        
        // Check if user is active
        if ($user['status'] !== 'active') {
            $this->errorResponse('ACCOUNT_INACTIVE', 'Your account is inactive. Please contact administrator.', 401);
        }
        
        // Verify password
        if (!password_verify($input['password'], $user['password_hash'])) {
            $this->errorResponse('AUTH_FAILED', 'Invalid credentials', 401);
        }
        
        // Get user's role info
        $roleInfo = $this->getUserRoleInfo($user['role_id']);
        
        // Get user permissions
        $permissions = $this->permissionService->getPermissionKeys((int)$user['id']);
        
        // Prepare user data for token
        $userData = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role_id' => $user['role_id'],
            'role_name' => $roleInfo['name'] ?? $user['role'] ?? null,
            'vendor_id' => $user['vendor_id'] ?? null
        ];
        
        // Generate tokens
        $accessToken = $this->tokenService->generateAccessToken($userData, $permissions);
        $refreshToken = $this->tokenService->generateRefreshToken((int)$user['id']);
        
        // Update last login (optional)
        $this->updateLastLogin($user['id']);
        
        $this->successResponse([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $this->tokenService->getAccessTokenExpiry(),
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? null,
                'role_id' => $user['role_id'],
                'role_name' => $roleInfo['name'] ?? $user['role'] ?? null,
                'role_display_name' => $roleInfo['display_name'] ?? null,
                'vendor_id' => $user['vendor_id'] ?? null
            ],
            'permissions' => $permissions
        ], 'Login successful');
    }

    
    /**
     * Handle user logout
     * POST /api/auth/logout
     * 
     * @return void JSON response confirming logout
     */
    public function logout(): void
    {
        header('Content-Type: application/json');
        
        // Authenticate the request
        JWTAuthMiddleware::authenticate();
        
        // Get refresh token from request body
        $input = $this->getJsonInput();
        
        if (!empty($input['refresh_token'])) {
            // Invalidate the specific refresh token
            $this->tokenService->invalidateToken($input['refresh_token']);
        } else {
            // Invalidate all refresh tokens for the user
            $userId = JWTAuthMiddleware::getCurrentUserId();
            if ($userId) {
                $this->tokenService->invalidateAllUserTokens($userId);
            }
        }
        
        $this->successResponse(null, 'Logged out successfully');
    }
    
    /**
     * Refresh access token using refresh token
     * POST /api/auth/refresh
     * 
     * @return void JSON response with new access token
     */
    public function refresh(): void
    {
        header('Content-Type: application/json');
        
        // Get refresh token from request body
        $input = $this->getJsonInput();
        
        if (empty($input['refresh_token'])) {
            $this->errorResponse('VALIDATION_ERROR', 'Refresh token is required', 422);
        }
        
        // Attempt to refresh the access token
        $result = $this->tokenService->refreshAccessToken($input['refresh_token']);
        
        if (!$result) {
            $this->errorResponse('TOKEN_INVALID', 'Invalid or expired refresh token', 401);
        }
        
        $this->successResponse([
            'access_token' => $result['access_token'],
            'token_type' => 'Bearer',
            'expires_in' => $result['expires_in'],
            'user' => $result['user'],
            'permissions' => $result['permissions']
        ], 'Token refreshed successfully');
    }
    
    /**
     * Get current authenticated user info
     * GET /api/auth/me
     * 
     * @return void JSON response with user info and permissions
     */
    public function me(): void
    {
        header('Content-Type: application/json');
        
        // Authenticate the request
        JWTAuthMiddleware::authenticate();
        
        $userId = JWTAuthMiddleware::getCurrentUserId();
        
        if (!$userId) {
            $this->errorResponse('AUTH_REQUIRED', 'Authentication required', 401);
        }
        
        // Get full user info from database
        $user = $this->userModel->findWithVendor($userId);
        
        if (!$user) {
            $this->errorResponse('USER_NOT_FOUND', 'User not found', 404);
        }
        
        // Get role info
        $roleInfo = $this->getUserRoleInfo($user['role_id']);
        
        // Get fresh permissions from database
        $permissions = $this->permissionService->getPermissionKeys($userId);
        $permissionsGrouped = $this->permissionService->getUserPermissionsGrouped($userId);
        
        $this->successResponse([
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'phone' => $user['phone'] ?? null,
                'role_id' => $user['role_id'],
                'role_name' => $roleInfo['name'] ?? $user['role'] ?? null,
                'role_display_name' => $roleInfo['display_name'] ?? null,
                'vendor_id' => $user['vendor_id'] ?? null,
                'vendor_name' => $user['vendor_name'] ?? null,
                'status' => $user['status'],
                'profile_picture' => $user['profile_picture'] ?? null,
                'created_at' => $user['created_at'] ?? null
            ],
            'permissions' => $permissions,
            'permissions_grouped' => $permissionsGrouped
        ]);
    }

    
    /**
     * Get JSON input from request body
     * 
     * @return array Parsed JSON data
     */
    private function getJsonInput(): array
    {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Try to get from POST if JSON parsing fails
            return $_POST;
        }
        
        return $input ?? [];
    }
    
    /**
     * Get user role information
     * 
     * @param int|null $roleId Role ID
     * @return array|null Role info or null
     */
    private function getUserRoleInfo(?int $roleId): ?array
    {
        if (!$roleId) {
            return null;
        }
        
        try {
            $sql = "SELECT id, name, display_name, description FROM roles WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Update user's last login timestamp
     * 
     * @param int $userId User ID
     */
    private function updateLastLogin(int $userId): void
    {
        try {
            $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            // Silently fail - not critical
        }
    }
    
    /**
     * Send success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @param int $status HTTP status code
     */
    private function successResponse($data = null, string $message = '', int $status = 200): void
    {
        http_response_code($status);
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Send error response
     * 
     * @param string $code Error code
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param array|null $details Additional error details
     */
    private function errorResponse(string $code, string $message, int $status = 400, ?array $details = null): void
    {
        http_response_code($status);
        
        $response = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];
        
        if ($details !== null) {
            $response['error']['details'] = $details;
        }
        
        echo json_encode($response);
        exit;
    }
}
