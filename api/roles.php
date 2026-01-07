<?php
/**
 * Roles API Routes
 * 
 * Handles role management endpoints for the RBAC system.
 * Endpoints:
 *   GET    /api/roles.php                         - List all roles
 *   GET    /api/roles.php?id={id}                 - Get role details
 *   POST   /api/roles.php                         - Create new role
 *   PUT    /api/roles.php?id={id}                 - Update role
 *   DELETE /api/roles.php?id={id}                 - Delete role
 *   GET    /api/roles.php?id={id}&action=permissions    - Get role permissions
 *   PUT    /api/roles.php?id={id}&action=permissions    - Update role permissions
 * 
 * All endpoints require 'users.manage_roles' permission.
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
require_once __DIR__ . '/../controllers/RolesController.php';

// Get parameters
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $controller = new RolesController();
    
    // Handle permission-specific endpoints
    if ($action === 'permissions' && $id) {
        switch ($method) {
            case 'GET':
                // GET /api/roles.php?id={id}&action=permissions
                $controller->permissions($id);
                break;
                
            case 'PUT':
                // PUT /api/roles.php?id={id}&action=permissions
                $controller->updatePermissions($id);
                break;
                
            default:
                methodNotAllowed('GET, PUT');
        }
        exit;
    }
    
    // Handle standard CRUD endpoints
    switch ($method) {
        case 'GET':
            if ($id) {
                // GET /api/roles.php?id={id}
                $controller->show($id);
            } else {
                // GET /api/roles.php
                $controller->index();
            }
            break;
            
        case 'POST':
            // POST /api/roles.php
            $controller->store();
            break;
            
        case 'PUT':
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Role ID is required for update'
                    ]
                ]);
                exit;
            }
            // PUT /api/roles.php?id={id}
            $controller->update($id);
            break;
            
        case 'DELETE':
            if (!$id) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'Role ID is required for deletion'
                    ]
                ]);
                exit;
            }
            // DELETE /api/roles.php?id={id}
            $controller->destroy($id);
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
