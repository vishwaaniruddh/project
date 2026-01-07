<?php
/**
 * Roles Controller
 * 
 * Handles role management API endpoints for the RBAC system.
 * Provides CRUD operations for roles and permission assignment.
 * Requirements: 1.2, 1.4, 1.5, 3.2, 3.3, 3.4, 10.1, 10.2
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../middleware/ApiPermissionMiddleware.php';
require_once __DIR__ . '/../services/AuditService.php';

class RolesController extends BaseController
{
    private $roleModel;
    private $permissionModel;
    private $auditService;
    
    public function __construct()
    {
        parent::__construct();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
        $this->auditService = new AuditService();
    }
    
    /**
     * List all roles with permission counts
     * GET /api/roles
     */
    public function index(): void
    {
        header('Content-Type: application/json');
        
        // Require permission to manage roles
        ApiPermissionMiddleware::require('users.manage_roles');
        
        try {
            $roles = $this->roleModel->getAllWithPermissionCount();
            
            $this->successResponse([
                'roles' => $roles,
                'total' => count($roles)
            ]);
        } catch (Exception $e) {
            $this->errorResponse('SERVER_ERROR', 'Failed to fetch roles', 500);
        }
    }
    
    /**
     * Get a single role with its permissions
     * GET /api/roles/{id}
     * 
     * @param int $id Role ID
     */
    public function show(int $id): void
    {
        header('Content-Type: application/json');
        
        // Require permission to manage roles
        ApiPermissionMiddleware::require('users.manage_roles');
        
        try {
            $role = $this->roleModel->getWithPermissions($id);
            
            if (!$role) {
                $this->errorResponse('ROLE_NOT_FOUND', 'Role not found', 404);
            }
            
            // Get permission keys for convenience
            $role['permission_keys'] = array_column($role['permissions'], 'permission_key');
            
            $this->successResponse(['role' => $role]);
        } catch (Exception $e) {
            $this->errorResponse('SERVER_ERROR', 'Failed to fetch role', 500);
        }
    }

    
    /**
     * Create a new role
     * POST /api/roles
     */
    public function store(): void
    {
        header('Content-Type: application/json');
        
        // Only superadmin can create roles
        ApiPermissionMiddleware::requireSuperAdmin();
        
        $input = $this->getJsonInput();
        
        // Validate required fields
        $errors = $this->validateRoleData($input);
        if (!empty($errors)) {
            $this->errorResponse('VALIDATION_ERROR', 'Validation failed', 422, $errors);
        }
        
        // Check if role name already exists
        $existingRole = $this->roleModel->findByName($input['name']);
        if ($existingRole) {
            $this->errorResponse('DUPLICATE_ROLE', 'A role with this name already exists', 409);
        }
        
        try {
            // Create the role
            $roleData = [
                'name' => strtolower(trim($input['name'])),
                'display_name' => trim($input['display_name']),
                'description' => trim($input['description'] ?? ''),
                'is_system_role' => false // Custom roles are not system roles
            ];
            
            $roleId = $this->roleModel->create($roleData);
            
            if (!$roleId) {
                $this->errorResponse('CREATE_FAILED', 'Failed to create role', 500);
            }
            
            // Assign permissions if provided
            if (!empty($input['permission_ids']) && is_array($input['permission_ids'])) {
                $this->roleModel->syncPermissions($roleId, $input['permission_ids']);
            }
            
            // Log role creation (Requirement 10.1)
            $this->auditService->logRoleCreate($roleId, $roleData);
            
            // Log permission assignment if permissions were provided (Requirement 10.2)
            if (!empty($input['permission_ids']) && is_array($input['permission_ids'])) {
                $this->auditService->logPermissionAssign($roleId, $input['permission_ids']);
            }
            
            // Fetch the created role with permissions
            $role = $this->roleModel->getWithPermissions($roleId);
            
            $this->successResponse(['role' => $role], 'Role created successfully', 201);
        } catch (Exception $e) {
            $this->errorResponse('SERVER_ERROR', 'Failed to create role', 500);
        }
    }
    
    /**
     * Update an existing role
     * PUT /api/roles/{id}
     * 
     * @param int $id Role ID
     */
    public function update(int $id): void
    {
        header('Content-Type: application/json');
        
        // Require permission to manage roles
        ApiPermissionMiddleware::require('users.manage_roles');
        
        // Check if role exists
        $role = $this->roleModel->find($id);
        if (!$role) {
            $this->errorResponse('ROLE_NOT_FOUND', 'Role not found', 404);
        }
        
        $input = $this->getJsonInput();
        
        // Validate input
        $errors = $this->validateRoleData($input, true);
        if (!empty($errors)) {
            $this->errorResponse('VALIDATION_ERROR', 'Validation failed', 422, $errors);
        }
        
        // Check if name is being changed and if it conflicts
        if (!empty($input['name']) && $input['name'] !== $role['name']) {
            $existingRole = $this->roleModel->findByName($input['name']);
            if ($existingRole && $existingRole['id'] != $id) {
                $this->errorResponse('DUPLICATE_ROLE', 'A role with this name already exists', 409);
            }
        }
        
        try {
            // Store old values for audit logging
            $oldData = [
                'name' => $role['name'],
                'display_name' => $role['display_name'],
                'description' => $role['description']
            ];
            
            // Prepare update data
            $updateData = [];
            
            if (isset($input['name'])) {
                $updateData['name'] = strtolower(trim($input['name']));
            }
            if (isset($input['display_name'])) {
                $updateData['display_name'] = trim($input['display_name']);
            }
            if (isset($input['description'])) {
                $updateData['description'] = trim($input['description']);
            }
            
            // Update the role
            if (!empty($updateData)) {
                $this->roleModel->update($id, $updateData);
                
                // Log role update (Requirement 10.1)
                $this->auditService->logRoleUpdate($id, $oldData, $updateData);
            }
            
            // Fetch updated role
            $role = $this->roleModel->getWithPermissions($id);
            
            $this->successResponse(['role' => $role], 'Role updated successfully');
        } catch (Exception $e) {
            $this->errorResponse('SERVER_ERROR', 'Failed to update role', 500);
        }
    }

    
    /**
     * Delete a role
     * DELETE /api/roles/{id}
     * 
     * @param int $id Role ID
     */
    public function destroy(int $id): void
    {
        header('Content-Type: application/json');
        
        // Only superadmin can delete roles
        ApiPermissionMiddleware::requireSuperAdmin();
        
        // Check if role exists
        $role = $this->roleModel->find($id);
        if (!$role) {
            $this->errorResponse('ROLE_NOT_FOUND', 'Role not found', 404);
        }
        
        // Check if it's a system role
        if ($this->roleModel->isSystemRole($id)) {
            $this->errorResponse('SYSTEM_ROLE_PROTECTED', 'System roles cannot be deleted', 400);
        }
        
        // Check if any users are assigned to this role
        $usersWithRole = $this->getUsersWithRole($id);
        if ($usersWithRole > 0) {
            $this->errorResponse(
                'ROLE_IN_USE', 
                "Cannot delete role. {$usersWithRole} user(s) are assigned to this role.", 
                400
            );
        }
        
        try {
            // Store role data for audit logging before deletion
            $roleData = [
                'name' => $role['name'],
                'display_name' => $role['display_name'],
                'description' => $role['description'],
                'is_system_role' => $role['is_system_role']
            ];
            
            $success = $this->roleModel->delete($id);
            
            if (!$success) {
                $this->errorResponse('DELETE_FAILED', 'Failed to delete role', 500);
            }
            
            // Log role deletion (Requirement 10.1)
            $this->auditService->logRoleDelete($id, $roleData);
            
            $this->successResponse(null, 'Role deleted successfully');
        } catch (Exception $e) {
            $this->errorResponse('SERVER_ERROR', 'Failed to delete role', 500);
        }
    }
    
    /**
     * Get permissions for a role
     * GET /api/roles/{id}/permissions
     * 
     * @param int $id Role ID
     */
    public function permissions(int $id): void
    {
        header('Content-Type: application/json');
        
        // Require permission to manage roles
        ApiPermissionMiddleware::require('users.manage_roles');
        
        // Check if role exists
        $role = $this->roleModel->find($id);
        if (!$role) {
            $this->errorResponse('ROLE_NOT_FOUND', 'Role not found', 404);
        }
        
        try {
            $permissions = $this->roleModel->getPermissions($id);
            $permissionKeys = array_column($permissions, 'permission_key');
            
            // Group permissions by module
            $grouped = [];
            foreach ($permissions as $permission) {
                $module = $permission['module'];
                if (!isset($grouped[$module])) {
                    $grouped[$module] = [];
                }
                $grouped[$module][] = $permission;
            }
            
            $this->successResponse([
                'role_id' => $id,
                'role_name' => $role['name'],
                'permissions' => $permissions,
                'permission_keys' => $permissionKeys,
                'permissions_grouped' => $grouped,
                'total' => count($permissions)
            ]);
        } catch (Exception $e) {
            $this->errorResponse('SERVER_ERROR', 'Failed to fetch permissions', 500);
        }
    }
    
    /**
     * Update permissions for a role
     * PUT /api/roles/{id}/permissions
     * 
     * @param int $id Role ID
     */
    public function updatePermissions(int $id): void
    {
        header('Content-Type: application/json');
        
        // Require permission to manage roles
        ApiPermissionMiddleware::require('users.manage_roles');
        
        // Check if role exists
        $role = $this->roleModel->find($id);
        if (!$role) {
            $this->errorResponse('ROLE_NOT_FOUND', 'Role not found', 404);
        }
        
        // Prevent modification of superadmin core permissions
        if ($role['name'] === 'superadmin') {
            $this->errorResponse(
                'SUPERADMIN_PROTECTED', 
                'Superadmin permissions cannot be modified', 
                400
            );
        }
        
        $input = $this->getJsonInput();
        
        // Validate permission_ids
        if (!isset($input['permission_ids']) || !is_array($input['permission_ids'])) {
            $this->errorResponse('VALIDATION_ERROR', 'permission_ids must be an array', 422);
        }
        
        // Validate that all permission IDs exist
        $invalidIds = $this->validatePermissionIds($input['permission_ids']);
        if (!empty($invalidIds)) {
            $this->errorResponse(
                'INVALID_PERMISSION', 
                'Some permission IDs are invalid', 
                400, 
                ['invalid_ids' => $invalidIds]
            );
        }
        
        try {
            // Get current permissions for audit logging
            $currentPermissions = $this->roleModel->getPermissions($id);
            $oldPermissionIds = array_column($currentPermissions, 'id');
            $newPermissionIds = array_map('intval', $input['permission_ids']);
            
            // Sync permissions
            $success = $this->roleModel->syncPermissions($id, $newPermissionIds);
            
            if (!$success) {
                $this->errorResponse('UPDATE_FAILED', 'Failed to update permissions', 500);
            }
            
            // Log permission sync (Requirement 10.2)
            $this->auditService->logPermissionSync($id, $oldPermissionIds, $newPermissionIds);
            
            // Fetch updated permissions
            $permissions = $this->roleModel->getPermissions($id);
            
            $this->successResponse([
                'role_id' => $id,
                'permissions' => $permissions,
                'permission_count' => count($permissions)
            ], 'Permissions updated successfully');
        } catch (Exception $e) {
            $this->errorResponse('SERVER_ERROR', 'Failed to update permissions', 500);
        }
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
            return $_POST;
        }
        
        return $input ?? [];
    }
    
    /**
     * Validate role data
     * 
     * @param array $data Input data
     * @param bool $isUpdate Whether this is an update operation
     * @return array Validation errors
     */
    private function validateRoleData(array $data, bool $isUpdate = false): array
    {
        $errors = [];
        
        if (!$isUpdate) {
            // Required fields for create
            if (empty($data['name'])) {
                $errors['name'] = 'Role name is required';
            } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', strtolower($data['name']))) {
                $errors['name'] = 'Role name must start with a letter and contain only lowercase letters, numbers, and underscores';
            } elseif (strlen($data['name']) > 50) {
                $errors['name'] = 'Role name must not exceed 50 characters';
            }
            
            if (empty($data['display_name'])) {
                $errors['display_name'] = 'Display name is required';
            } elseif (strlen($data['display_name']) > 100) {
                $errors['display_name'] = 'Display name must not exceed 100 characters';
            }
        } else {
            // Optional validation for update
            if (isset($data['name']) && !empty($data['name'])) {
                if (!preg_match('/^[a-z][a-z0-9_]*$/', strtolower($data['name']))) {
                    $errors['name'] = 'Role name must start with a letter and contain only lowercase letters, numbers, and underscores';
                } elseif (strlen($data['name']) > 50) {
                    $errors['name'] = 'Role name must not exceed 50 characters';
                }
            }
            
            if (isset($data['display_name']) && strlen($data['display_name']) > 100) {
                $errors['display_name'] = 'Display name must not exceed 100 characters';
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate permission IDs exist
     * 
     * @param array $permissionIds Array of permission IDs
     * @return array Invalid permission IDs
     */
    private function validatePermissionIds(array $permissionIds): array
    {
        if (empty($permissionIds)) {
            return [];
        }
        
        $invalidIds = [];
        
        foreach ($permissionIds as $id) {
            $permission = $this->permissionModel->find((int)$id);
            if (!$permission) {
                $invalidIds[] = $id;
            }
        }
        
        return $invalidIds;
    }
    
    /**
     * Get count of users with a specific role
     * 
     * @param int $roleId Role ID
     * @return int User count
     */
    private function getUsersWithRole(int $roleId): int
    {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE role_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$roleId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0;
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
