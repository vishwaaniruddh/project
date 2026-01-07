<?php
/**
 * Permissions API Routes
 * 
 * Handles permission listing endpoints for the RBAC system.
 * Endpoints:
 *   GET /api/permissions.php                  - List all permissions
 *   GET /api/permissions.php?action=grouped   - Get permissions grouped by module
 *   GET /api/permissions.php?module={module}  - Filter permissions by module
 * 
 * All endpoints require 'users.manage_roles' permission.
 * Requirements: 12.1, 12.2, 12.3
 */

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/PermissionsController.php';

// Get parameters
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Only allow GET requests
if ($method !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'METHOD_NOT_ALLOWED',
            'message' => 'Method not allowed. Use GET.'
        ]
    ]);
    exit;
}

try {
    $controller = new PermissionsController();
    
    switch ($action) {
        case 'grouped':
            // GET /api/permissions.php?action=grouped
            $controller->grouped();
            break;
            
        default:
            // GET /api/permissions.php or GET /api/permissions.php?module={module}
            $controller->index();
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
