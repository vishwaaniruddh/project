<?php
/**
 * Users API Routes
 * 
 * Handles user management endpoints including RBAC functionality.
 * Endpoints:
 *   GET    /api/users.php                                    - List users with pagination
 *   GET    /api/users.php?id={id}                            - Get user details
 *   POST   /api/users.php                                    - Create new user
 *   PUT    /api/users.php?id={id}                            - Update user
 *   DELETE /api/users.php?id={id}                            - Delete user
 *   PUT    /api/users.php?id={id}&action=role                - Change user role
 *   GET    /api/users.php?id={id}&action=permissions         - Get user permissions
 *   PUT    /api/users.php?id={id}&action=permissions         - Set permission overrides
 *   DELETE /api/users.php?id={id}&action=permissions&pid={pid} - Remove permission override
 *   PUT    /api/users.php?id={id}&action=toggle-status       - Toggle user status
 * 
 * Permission requirements:
 *   - users.read: View users and their permissions
 *   - users.create: Create new users
 *   - users.update: Update user details
 *   - users.delete: Delete users
 *   - users.manage_roles: Change roles and permission overrides
 * 
 * Requirements: 12.1, 12.2, 12.3
 */

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/UsersController.php';
require_once __DIR__ . '/../middleware/JWTAuthMiddleware.php';
require_once __DIR__ . '/../middleware/ApiPermissionMiddleware.php';

// Get parameters
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? '';
$permissionId = isset($_GET['pid']) ? (int)$_GET['pid'] : null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    $controller = new UsersController();
    
    // Handle action-specific endpoints
    if ($action && $id) {
        switch ($action) {
            case 'role':
                // PUT /api/users.php?id={id}&action=role
                if ($method !== 'PUT') {
                    methodNotAllowed('PUT');
                }
                $controller->changeRole($id);
                break;
                
            case 'permissions':
                switch ($method) {
                    case 'GET':
                        // GET /api/users.php?id={id}&action=permissions
                        $controller->getPermissions($id);
                        break;
                        
                    case 'PUT':
                        // PUT /api/users.php?id={id}&action=permissions
                        $controller->setPermissionOverrides($id);
                        break;
                        
                    case 'DELETE':
                        // DELETE /api/users.php?id={id}&action=permissions&pid={pid}
                        if (!$permissionId) {
                            http_response_code(400);
                            echo json_encode([
                                'success' => false,
                                'error' => [
                                    'code' => 'VALIDATION_ERROR',
                                    'message' => 'Permission ID (pid) is required'
                                ]
                            ]);
                            exit;
                        }
                        $controller->removePermissionOverride($id, $permissionId);
                        break;
                        
                    default:
                        methodNotAllowed('GET, PUT, DELETE');
                }
                break;
                
            case 'toggle-status':
                // PUT /api/users.php?id={id}&action=toggle-status
                if ($method !== 'PUT') {
                    methodNotAllowed('PUT');
                }
                // Require authentication and permission
                ApiPermissionMiddleware::require('users.update');
                $result = $controller->toggleStatus($id);
                if (is_array($result)) {
                    echo json_encode($result);
                }
                break;
                
            default:
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_ACTION',
                        'message' => "Unknown action: {$action}"
                    ]
                ]);
        }
        exit;
    }
    
    // Handle standard CRUD endpoints
    switch ($method) {
        case 'GET':
            // Require authentication and permission
            ApiPermissionMiddleware::require('users.read');
            
            if ($id) {
                // GET /api/users.php?id={id}
                $result = $controller->show($id);
            } else {
                // GET /api/users.php
                $result = $controller->index();
                echo json_encode([
                    'success' => true,
                    'data' => $result
                ]);
            }
            break;
            
        case 'POST':
            // POST /api/users.php
            // Require authentication and permission
            ApiPermissionMiddleware::require('users.create');
            $result = $controller->store();
            if (is_array($result)) {
                echo json_encode($result);
            }
            break;
            
        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'User ID is required for update'
                    ]
                ]);
                exit;
            }
            // PUT /api/users.php?id={id}
            // Require authentication and permission
            ApiPermissionMiddleware::require('users.update');
            $result = $controller->update($id);
            if (is_array($result)) {
                echo json_encode($result);
            }
            break;
            
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'User ID is required for deletion'
                    ]
                ]);
                exit;
            }
            // DELETE /api/users.php?id={id}
            // Require authentication and permission
            ApiPermissionMiddleware::require('users.delete');
            $result = $controller->delete($id);
            if (is_array($result)) {
                echo json_encode($result);
            }
            break;
            
        default:
            methodNotAllowed('GET, POST, PUT, DELETE');
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
 * @param string $allowed Allowed HTTP methods
 */
function methodNotAllowed(string $allowed): void
{
    http_response_code(405);
    header('Allow: ' . $allowed);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => "Method not allowed. Allowed methods: {$allowed}"
        ]
    ]);
    exit;
}
