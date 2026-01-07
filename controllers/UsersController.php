<?php
/**
 * Users Controller
 * 
 * Handles user management API endpoints including RBAC functionality.
 * Provides CRUD operations for users, role assignment, and permission overrides.
 * Requirements: 4.2, 4.5, 5.2, 5.3, 5.5, 10.3
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/UserPermission.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../middleware/ApiPermissionMiddleware.php';
require_once __DIR__ . '/../includes/error_handler.php';
require_once __DIR__ . '/../includes/logger.php';

class UsersController extends BaseController {
    private $userModel;
    private $roleModel;
    private $permissionModel;
    private $userPermissionModel;
    private $permissionService;
    private $auditService;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
        $this->userPermissionModel = new UserPermission();
        $this->permissionService = new PermissionService();
        $this->auditService = new AuditService();
    }
    
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $role = isset($_GET['role']) ? trim($_GET['role']) : '';
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';
        
        $result = $this->userModel->getAllWithPagination($page, 20, $search, $role, $status);
        
        return [
            'users' => $result['users'],
            'pagination' => [
                'current_page' => $result['page'],
                'total_pages' => $result['pages'],
                'total_records' => $result['total'],
                'limit' => $result['limit']
            ],
            'search' => $search,
            'role' => $role,
            'status' => $status
        ];
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->store();
        }
        
        // Return empty user for form
        return ['user' => null];
    }
    
    public function store() {
        try {
            $data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? '',
                'role_id' => !empty($_POST['role_id']) ? (int)$_POST['role_id'] : null,
                'status' => $_POST['status'] ?? 'active'
            ];
            
            // Add vendor_id if role is vendor
            if ($data['role'] === 'vendor' && !empty($_POST['vendor_id'])) {
                $data['vendor_id'] = (int)$_POST['vendor_id'];
            } else {
                $data['vendor_id'] = null;
            }
            
            // Validate role_id if provided
            if (!empty($data['role_id'])) {
                $role = $this->roleModel->find($data['role_id']);
                if (!$role) {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => ['role_id' => 'Invalid role selected']
                    ], 400);
                }
                // Set role name from role_id for backward compatibility
                $data['role'] = $role['name'];
            }
            
            // Validate data
            $errors = $this->userModel->validateUserData($data);
            
            if (!empty($errors)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
            }
            
            // Create user
            $userId = $this->userModel->create($data);
            
            if ($userId) {
                // Log the action
                ErrorHandler::logUserAction('CREATE_USER', 'users', $userId, null, $data);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'User created successfully',
                    'user_id' => $userId
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to create user'
                ], 500);
            }
            
        } catch (Exception $e) {
            Logger::error('User creation failed', ['error' => $e->getMessage()]);
            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while creating the user'
            ], 500);
        }
    }
    
    public function show($id) {
        $user = $this->userModel->findWithVendor($id);
        
        if (!$user) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        // Remove password hash from response
        unset($user['password_hash']);
        
        return $this->jsonResponse([
            'success' => true,
            'user' => $user
        ]);
    }
    
    public function edit($id) {
        $user = $this->userModel->findWithVendor($id);
        
        if (!$user) {
            return $this->jsonResponse([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            return $this->update($id);
        }
        
        // Remove password hash from response
        unset($user['password_hash']);
        
        return $this->jsonResponse([
            'success' => true,
            'user' => $user
        ]);
    }
    
    public function update($id) {
        try {
            $user = $this->userModel->find($id);
            
            if (!$user) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            $data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'role' => $_POST['role'] ?? '',
                'role_id' => !empty($_POST['role_id']) ? (int)$_POST['role_id'] : $user['role_id'],
                'status' => $_POST['status'] ?? 'active'
            ];
            
            // Add vendor_id if role is vendor
            if ($data['role'] === 'vendor' && !empty($_POST['vendor_id'])) {
                $data['vendor_id'] = (int)$_POST['vendor_id'];
            } else {
                $data['vendor_id'] = null;
            }
            
            // Validate role_id if provided
            if (!empty($data['role_id'])) {
                $role = $this->roleModel->find($data['role_id']);
                if (!$role) {
                    return $this->jsonResponse([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => ['role_id' => 'Invalid role selected']
                    ], 400);
                }
                // Set role name from role_id for backward compatibility
                $data['role'] = $role['name'];
            }
            
            // Validate data
            $errors = $this->userModel->validateUserData($data, true, $id);
            
            if (!empty($errors)) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ], 400);
            }
            
            // Track role change for logging
            $oldRoleId = $user['role_id'] ?? null;
            $newRoleId = $data['role_id'] ?? null;
            $roleChanged = $oldRoleId != $newRoleId;
            
            // Update user
            $success = $this->userModel->update($id, $data);
            
            if ($success) {
                // Log the action
                ErrorHandler::logUserAction('UPDATE_USER', 'users', $id, $user, $data);
                
                // Log role change separately if applicable using AuditService (Requirement 10.3)
                if ($roleChanged) {
                    $this->auditService->logUserRoleChange($id, $oldRoleId, $newRoleId);
                    
                    // Also log via ErrorHandler for backward compatibility
                    ErrorHandler::logUserAction('CHANGE_USER_ROLE', 'users', $id, 
                        ['role_id' => $oldRoleId], 
                        ['role_id' => $newRoleId]
                    );
                    
                    // Clear permission cache for this user
                    $this->permissionService->clearUserCache($id);
                }
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'User updated successfully'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to update user'
                ], 500);
            }
            
        } catch (Exception $e) {
            Logger::error('User update failed', ['error' => $e->getMessage(), 'user_id' => $id]);
            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while updating the user'
            ], 500);
        }
    }
    
    public function delete($id) {
        try {
            $user = $this->userModel->find($id);
            
            if (!$user) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Prevent deletion of current user
            $currentUser = Auth::getCurrentUser();
            if ($currentUser['id'] == $id) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'You cannot delete your own account'
                ], 400);
            }
            
            // Check if user has assigned sites
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM sites WHERE vendor = ?");
            $stmt->execute([$id]);
            $assignedSites = $stmt->fetchColumn();
            
            if ($assignedSites > 0) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Cannot delete user with assigned sites. Please reassign sites first.'
                ], 400);
            }
            
            // Delete user
            $success = $this->userModel->delete($id);
            
            if ($success) {
                // Log the action
                ErrorHandler::logUserAction('DELETE_USER', 'users', $id, $user, null);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'User deleted successfully'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to delete user'
                ], 500);
            }
            
        } catch (Exception $e) {
            Logger::error('User deletion failed', ['error' => $e->getMessage(), 'user_id' => $id]);
            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while deleting the user'
            ], 500);
        }
    }
    
    public function toggleStatus($id) {
        try {
            $user = $this->userModel->find($id);
            
            if (!$user) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Prevent deactivation of current user
            $currentUser = Auth::getCurrentUser();
            if ($currentUser['id'] == $id && $user['status'] === 'active') {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'You cannot deactivate your own account'
                ], 400);
            }
            
            $newStatus = $user['status'] === 'active' ? 'inactive' : 'active';
            
            $success = $this->userModel->update($id, ['status' => $newStatus]);
            
            if ($success) {
                // Log the action
                ErrorHandler::logUserAction('TOGGLE_USER_STATUS', 'users', $id, 
                    ['status' => $user['status']], 
                    ['status' => $newStatus]
                );
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => "User {$newStatus} successfully",
                    'new_status' => $newStatus
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to update user status'
                ], 500);
            }
            
        } catch (Exception $e) {
            Logger::error('User status toggle failed', ['error' => $e->getMessage(), 'user_id' => $id]);
            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while updating user status'
            ], 500);
        }
    }
    
    /**
     * Change a user's role
     * PUT /api/users/{id}/role
     * 
     * @param int $id User ID
     */
    public function changeRole($id) {
        header('Content-Type: application/json');
        
        // Require permission to manage roles
        ApiPermissionMiddleware::require('users.manage_roles');
        
        try {
            $user = $this->userModel->find($id);
            
            if (!$user) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Get JSON input
            $input = $this->getJsonInput();
            
            if (empty($input['role_id'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'role_id is required'
                ], 422);
            }
            
            $newRoleId = (int)$input['role_id'];
            
            // Validate role exists
            $role = $this->roleModel->find($newRoleId);
            if (!$role) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Invalid role selected'
                ], 400);
            }
            
            // Prevent changing own role (unless superadmin)
            $currentUserId = ApiPermissionMiddleware::getCurrentUserId();
            if ($currentUserId == $id && !JWTAuthMiddleware::isSuperAdmin()) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'You cannot change your own role'
                ], 400);
            }
            
            $oldRoleId = $user['role_id'];
            
            // Update user role
            $success = $this->userModel->update($id, [
                'role_id' => $newRoleId,
                'role' => $role['name']
            ]);
            
            if ($success) {
                // Log the role change using AuditService (Requirement 10.3)
                $this->auditService->logUserRoleChange($id, $oldRoleId, $newRoleId);
                
                // Also log via ErrorHandler for backward compatibility
                ErrorHandler::logUserAction('CHANGE_USER_ROLE', 'users', $id, 
                    ['role_id' => $oldRoleId], 
                    ['role_id' => $newRoleId]
                );
                
                // Clear permission cache for this user
                $this->permissionService->clearUserCache($id);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'User role updated successfully',
                    'data' => [
                        'user_id' => $id,
                        'old_role_id' => $oldRoleId,
                        'new_role_id' => $newRoleId,
                        'new_role_name' => $role['name']
                    ]
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to update user role'
                ], 500);
            }
            
        } catch (Exception $e) {
            Logger::error('User role change failed', ['error' => $e->getMessage(), 'user_id' => $id]);
            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while changing user role'
            ], 500);
        }
    }
    
    /**
     * Get user's effective permissions
     * GET /api/users/{id}/permissions
     * 
     * @param int $id User ID
     */
    public function getPermissions($id) {
        header('Content-Type: application/json');
        
        // Require permission to read users
        ApiPermissionMiddleware::require('users.read');
        
        try {
            $user = $this->userModel->find($id);
            
            if (!$user) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Get effective permissions
            $permissions = $this->permissionService->getUserPermissions($id);
            $permissionKeys = $this->permissionService->getPermissionKeys($id);
            $permissionsGrouped = $this->permissionService->getUserPermissionsGrouped($id);
            
            // Get user-specific overrides
            $overrides = $this->userPermissionModel->getUserOverrides($id);
            
            // Get role info
            $role = null;
            if ($user['role_id']) {
                $role = $this->roleModel->find($user['role_id']);
            }
            
            return $this->jsonResponse([
                'success' => true,
                'data' => [
                    'user_id' => $id,
                    'role' => $role ? [
                        'id' => $role['id'],
                        'name' => $role['name'],
                        'display_name' => $role['display_name']
                    ] : null,
                    'permissions' => $permissions,
                    'permission_keys' => $permissionKeys,
                    'permissions_grouped' => $permissionsGrouped,
                    'overrides' => $overrides,
                    'total_permissions' => count($permissionKeys)
                ]
            ]);
            
        } catch (Exception $e) {
            Logger::error('Get user permissions failed', ['error' => $e->getMessage(), 'user_id' => $id]);
            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while fetching user permissions'
            ], 500);
        }
    }
    
    /**
     * Set permission overrides for a user
     * PUT /api/users/{id}/permissions
     * 
     * @param int $id User ID
     */
    public function setPermissionOverrides($id) {
        header('Content-Type: application/json');
        
        // Require permission to manage roles
        ApiPermissionMiddleware::require('users.manage_roles');
        
        try {
            $user = $this->userModel->find($id);
            
            if (!$user) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Get JSON input
            $input = $this->getJsonInput();
            
            if (!isset($input['overrides']) || !is_array($input['overrides'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'overrides array is required'
                ], 422);
            }
            
            $currentUserId = ApiPermissionMiddleware::getCurrentUserId();
            $successCount = 0;
            $errors = [];
            
            foreach ($input['overrides'] as $override) {
                if (!isset($override['permission_id']) || !isset($override['is_granted'])) {
                    $errors[] = 'Each override must have permission_id and is_granted';
                    continue;
                }
                
                $permissionId = (int)$override['permission_id'];
                $isGranted = (bool)$override['is_granted'];
                
                // Validate permission exists
                $permission = $this->permissionModel->find($permissionId);
                if (!$permission) {
                    $errors[] = "Permission ID {$permissionId} not found";
                    continue;
                }
                
                // Set the override
                $success = $this->userPermissionModel->setOverride(
                    $id, 
                    $permissionId, 
                    $isGranted, 
                    $currentUserId
                );
                
                if ($success) {
                    $successCount++;
                    
                    // Log the override using AuditService (Requirement 5.5)
                    $this->auditService->logUserPermissionOverride($id, $permissionId, $isGranted);
                    
                    // Also log via ErrorHandler for backward compatibility
                    ErrorHandler::logUserAction('SET_PERMISSION_OVERRIDE', 'user_permissions', $id, 
                        null, 
                        [
                            'permission_id' => $permissionId,
                            'permission_key' => $permission['permission_key'],
                            'is_granted' => $isGranted
                        ]
                    );
                } else {
                    $errors[] = "Failed to set override for permission ID {$permissionId}";
                }
            }
            
            // Clear permission cache for this user
            $this->permissionService->clearUserCache($id);
            
            // Get updated overrides
            $updatedOverrides = $this->userPermissionModel->getUserOverrides($id);
            
            return $this->jsonResponse([
                'success' => true,
                'message' => "{$successCount} permission override(s) set successfully",
                'data' => [
                    'user_id' => $id,
                    'overrides_set' => $successCount,
                    'errors' => $errors,
                    'current_overrides' => $updatedOverrides
                ]
            ]);
            
        } catch (Exception $e) {
            Logger::error('Set permission overrides failed', ['error' => $e->getMessage(), 'user_id' => $id]);
            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while setting permission overrides'
            ], 500);
        }
    }
    
    /**
     * Remove a permission override for a user
     * DELETE /api/users/{id}/permissions/{permissionId}
     * 
     * @param int $id User ID
     * @param int $permissionId Permission ID
     */
    public function removePermissionOverride($id, $permissionId) {
        header('Content-Type: application/json');
        
        // Require permission to manage roles
        ApiPermissionMiddleware::require('users.manage_roles');
        
        try {
            $user = $this->userModel->find($id);
            
            if (!$user) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }
            
            // Validate permission exists
            $permission = $this->permissionModel->find($permissionId);
            if (!$permission) {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Permission not found'
                ], 404);
            }
            
            // Remove the override
            $success = $this->userPermissionModel->removeOverride($id, $permissionId);
            
            if ($success) {
                // Log the removal using AuditService (Requirement 5.5)
                $this->auditService->logUserPermissionOverrideRemove($id, $permissionId);
                
                // Also log via ErrorHandler for backward compatibility
                ErrorHandler::logUserAction('REMOVE_PERMISSION_OVERRIDE', 'user_permissions', $id, 
                    [
                        'permission_id' => $permissionId,
                        'permission_key' => $permission['permission_key']
                    ], 
                    null
                );
                
                // Clear permission cache for this user
                $this->permissionService->clearUserCache($id);
                
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'Permission override removed successfully'
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Failed to remove permission override'
                ], 500);
            }
            
        } catch (Exception $e) {
            Logger::error('Remove permission override failed', ['error' => $e->getMessage(), 'user_id' => $id]);
            return $this->jsonResponse([
                'success' => false,
                'message' => 'An error occurred while removing permission override'
            ], 500);
        }
    }
    
    /**
     * Get JSON input from request body
     * 
     * @return array Parsed JSON data
     */
    private function getJsonInput(): array {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $_POST;
        }
        
        return $input ?? [];
    }
}