<?php
/**
 * Permissions Controller
 * 
 * Handles permission listing API endpoints for the RBAC system.
 * Provides read-only access to permission definitions.
 * Requirements: 2.1, 3.3
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../middleware/ApiPermissionMiddleware.php';

class PermissionsController extends BaseController
{
    private $permissionModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->permissionModel = new Permission();
    }
    
    /**
     * List all permissions
     * GET /api/permissions
     */
    public function index(): void
    {
        header('Content-Type: application/json');
        
        // Require permission to manage roles
        ApiPermissionMiddleware::require('users.manage_roles');
        
        try {
            // Get optional module filter
            $module = $_GET['module'] ?? null;
            
            if ($module) {
                $permissions = $this->permissionModel->getByModule($module);
            } else {
                $permissions = $this->permissionModel->findAll();
            }
            
            // Get module counts for summary
            $moduleCounts = $this->permissionModel->getCountByModule();
            
            $this->successResponse([
                'permissions' => $permissions,
                'total' => count($permissions),
                'modules' => $moduleCounts
            ]);
        } catch (Exception $e) {
            $this->errorResponse('SERVER_ERROR', 'Failed to fetch permissions', 500);
        }
    }
    
    /**
     * Get permissions grouped by module
     * GET /api/permissions/grouped
     */
    public function grouped(): void
    {
        header('Content-Type: application/json');
        
        // Require permission to manage roles
        ApiPermissionMiddleware::require('users.manage_roles');
        
        try {
            $grouped = $this->permissionModel->getAllGroupedByModule();
            $modules = $this->permissionModel->getModules();
            
            // Calculate totals
            $totalPermissions = 0;
            foreach ($grouped as $modulePermissions) {
                $totalPermissions += count($modulePermissions);
            }
            
            $this->successResponse([
                'permissions_grouped' => $grouped,
                'modules' => $modules,
                'module_count' => count($modules),
                'total_permissions' => $totalPermissions
            ]);
        } catch (Exception $e) {
            $this->errorResponse('SERVER_ERROR', 'Failed to fetch permissions', 500);
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
