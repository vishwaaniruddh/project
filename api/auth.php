<?php
/**
 * Authentication API Routes
 * 
 * Handles authentication endpoints for the RBAC system.
 * Endpoints:
 *   POST /api/auth.php?action=login    - User login, returns JWT
 *   POST /api/auth.php?action=logout   - Invalidate token
 *   POST /api/auth.php?action=refresh  - Refresh JWT token
 *   GET  /api/auth.php?action=me       - Get current user info
 * 
 * Requirements: 12.1, 12.2, 12.3
 */

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';

// Get the action from query parameter
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $controller = new AuthController();
    
    switch ($action) {
        case 'login':
            // POST /api/auth.php?action=login
            if ($method !== 'POST') {
                methodNotAllowed('POST');
            }
            $controller->login();
            break;
            
        case 'logout':
            // POST /api/auth.php?action=logout
            if ($method !== 'POST') {
                methodNotAllowed('POST');
            }
            $controller->logout();
            break;
            
        case 'refresh':
            // POST /api/auth.php?action=refresh
            if ($method !== 'POST') {
                methodNotAllowed('POST');
            }
            $controller->refresh();
            break;
            
        case 'me':
            // GET /api/auth.php?action=me
            if ($method !== 'GET') {
                methodNotAllowed('GET');
            }
            $controller->me();
            break;
            
        default:
            // No action specified - return API info
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Authentication API',
                'endpoints' => [
                    [
                        'method' => 'POST',
                        'path' => '/api/auth.php?action=login',
                        'description' => 'User login - returns JWT token',
                        'body' => ['username' => 'string', 'password' => 'string']
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/auth.php?action=logout',
                        'description' => 'User logout - invalidates token',
                        'auth' => 'Bearer token required'
                    ],
                    [
                        'method' => 'POST',
                        'path' => '/api/auth.php?action=refresh',
                        'description' => 'Refresh access token',
                        'body' => ['refresh_token' => 'string']
                    ],
                    [
                        'method' => 'GET',
                        'path' => '/api/auth.php?action=me',
                        'description' => 'Get current user info',
                        'auth' => 'Bearer token required'
                    ]
                ]
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'SERVER_ERROR',
            'message' => 'An unexpected error occurred'
        ]
    ]);
}

/**
 * Send method not allowed response
 * 
 * @param string $allowed Allowed HTTP method
 */
function methodNotAllowed(string $allowed): void
{
    http_response_code(405);
    header('Allow: ' . $allowed);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => "Method not allowed. Use {$allowed}."
        ]
    ]);
    exit;
}
