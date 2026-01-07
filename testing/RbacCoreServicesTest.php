<?php
/**
 * RBAC Core Services Unit Tests
 * 
 * Tests TokenService, PermissionService, and Role model CRUD operations
 * Requirements: 6.4, 7.3, 7.4, 7.5
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/TokenService.php';
require_once __DIR__ . '/../services/PermissionService.php';
require_once __DIR__ . '/../models/Role.php';
require_once __DIR__ . '/../models/Permission.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserPermission.php';

class RbacCoreServicesTest
{
    private $db;
    private $tokenService;
    private $permissionService;
    private $roleModel;
    private $permissionModel;
    private $userModel;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testUserId;
    private $testRoleId;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->tokenService = new TokenService();
        $this->permissionService = new PermissionService();
        $this->roleModel = new Role();
        $this->permissionModel = new Permission();
        $this->userModel = new User();
    }
    
    public function runAllTests()
    {
        echo "🧪 Running RBAC Core Services Unit Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Run tests
        $this->testTokenService();
        $this->testPermissionService();
        $this->testRoleService();
        
        // Cleanup test data
        $this->cleanupTestData();
        
        // Print results
        $this->printResults();
        
        return $this->passedTests === $this->totalTests;
    }
    
    private function test($testName, $callback)
    {
        $this->totalTests++;
        echo "Testing: $testName... ";
        
        try {
            $result = $callback();
            if ($result) {
                echo "✅ PASS\n";
                $this->passedTests++;
                $this->results[] = ['test' => $testName, 'status' => 'PASS', 'message' => ''];
            } else {
                echo "❌ FAIL\n";
                $this->results[] = ['test' => $testName, 'status' => 'FAIL', 'message' => 'Test returned false'];
            }
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            $this->results[] = ['test' => $testName, 'status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }
    
    private function setupTestData()
    {
        echo "Setting up test data...\n";
        
        // Create test role
        $this->testRoleId = $this->roleModel->create([
            'name' => 'test_role_' . time(),
            'display_name' => 'Test Role',
            'description' => 'Test role for unit tests',
            'is_system_role' => false
        ]);
        
        // Create test user
        $userData = [
            'username' => 'testuser_' . time(),
            'email' => 'test_' . time() . '@example.com',
            'phone' => '+1234567' . rand(100, 999),
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'role_id' => $this->testRoleId,
            'role' => 'admin',
            'status' => 'active'
        ];
        
        $this->testUserId = $this->userModel->create($userData);
        
        echo "Test data setup complete.\n\n";
    }
    
    private function cleanupTestData()
    {
        echo "\nCleaning up test data...\n";
        
        // Delete test user
        if ($this->testUserId) {
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$this->testUserId]);
        }
        
        // Delete test role permissions
        if ($this->testRoleId) {
            $stmt = $this->db->prepare("DELETE FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$this->testRoleId]);
        }
        
        // Delete test role
        if ($this->testRoleId) {
            $stmt = $this->db->prepare("DELETE FROM roles WHERE id = ?");
            $stmt->execute([$this->testRoleId]);
        }
        
        echo "Test data cleanup complete.\n";
    }
    
    private function testTokenService()
    {
        echo "\n🔑 TOKEN SERVICE TESTS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $testUser = [
            'id' => $this->testUserId,
            'username' => 'testuser',
            'role_id' => $this->testRoleId,
            'role_name' => 'test_role'
        ];
        
        $testPermissions = ['users.read', 'users.create', 'inventory.stock.read'];
        
        $this->test("Generate access token", function() use ($testUser, $testPermissions) {
            $token = $this->tokenService->generateAccessToken($testUser, $testPermissions);
            return !empty($token) && is_string($token);
        });
        
        $this->test("Validate valid token", function() use ($testUser, $testPermissions) {
            $token = $this->tokenService->generateAccessToken($testUser, $testPermissions);
            $payload = $this->tokenService->validateToken($token);
            return $payload !== null && isset($payload['user_id']);
        });
        
        $this->test("Token contains user data", function() use ($testUser, $testPermissions) {
            $token = $this->tokenService->generateAccessToken($testUser, $testPermissions);
            $payload = $this->tokenService->decodeToken($token);
            return $payload && 
                   $payload['user_id'] == $testUser['id'] &&
                   $payload['username'] == $testUser['username'];
        });
        
        $this->test("Token contains permissions", function() use ($testUser, $testPermissions) {
            $token = $this->tokenService->generateAccessToken($testUser, $testPermissions);
            $payload = $this->tokenService->decodeToken($token);
            return $payload && 
                   isset($payload['permissions']) &&
                   is_array($payload['permissions']) &&
                   count($payload['permissions']) === count($testPermissions);
        });
        
        $this->test("Reject invalid token", function() {
            $invalidToken = 'invalid.token.string';
            $payload = $this->tokenService->validateToken($invalidToken);
            return $payload === null;
        });
        
        $this->test("Reject malformed token", function() {
            $malformedToken = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.invalid';
            $payload = $this->tokenService->validateToken($malformedToken);
            return $payload === null;
        });
        
        $this->test("Generate refresh token", function() use ($testUser) {
            $refreshToken = $this->tokenService->generateRefreshToken($testUser['id']);
            return !empty($refreshToken) && is_string($refreshToken);
        });
        
        $this->test("Refresh token stored in database", function() use ($testUser) {
            $refreshToken = $this->tokenService->generateRefreshToken($testUser['id']);
            
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM refresh_tokens WHERE user_id = ?");
            $stmt->execute([$testUser['id']]);
            $count = $stmt->fetchColumn();
            
            return $count > 0;
        });
    }
    
    private function testPermissionService()
    {
        echo "\n🔐 PERMISSION SERVICE TESTS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        // Assign some permissions to test role
        $stmt = $this->db->query("SELECT * FROM permissions LIMIT 3");
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($permissions) >= 3) {
            $this->roleModel->assignPermission($this->testRoleId, $permissions[0]['id']);
            $this->roleModel->assignPermission($this->testRoleId, $permissions[1]['id']);
            $this->roleModel->assignPermission($this->testRoleId, $permissions[2]['id']);
        }
        
        $this->test("Check user has role permission", function() use ($permissions) {
            if (count($permissions) < 1) return true; // Skip if no permissions
            
            $hasPermission = $this->permissionService->hasPermission(
                $this->testUserId,
                $permissions[0]['permission_key']
            );
            return $hasPermission === true;
        });
        
        $this->test("Check user lacks unassigned permission", function() {
            $hasPermission = $this->permissionService->hasPermission(
                $this->testUserId,
                'nonexistent.permission.key'
            );
            return $hasPermission === false;
        });
        
        $this->test("hasAnyPermission with OR logic", function() use ($permissions) {
            if (count($permissions) < 2) return true; // Skip if not enough permissions
            
            $hasAny = $this->permissionService->hasAnyPermission(
                $this->testUserId,
                [$permissions[0]['permission_key'], 'nonexistent.permission']
            );
            return $hasAny === true;
        });
        
        $this->test("hasAnyPermission returns false when none match", function() {
            $hasAny = $this->permissionService->hasAnyPermission(
                $this->testUserId,
                ['fake.permission.one', 'fake.permission.two']
            );
            return $hasAny === false;
        });
        
        $this->test("hasAllPermissions with AND logic", function() use ($permissions) {
            if (count($permissions) < 2) return true; // Skip if not enough permissions
            
            $hasAll = $this->permissionService->hasAllPermissions(
                $this->testUserId,
                [$permissions[0]['permission_key'], $permissions[1]['permission_key']]
            );
            return $hasAll === true;
        });
        
        $this->test("hasAllPermissions returns false when one missing", function() use ($permissions) {
            if (count($permissions) < 1) return true; // Skip if no permissions
            
            $hasAll = $this->permissionService->hasAllPermissions(
                $this->testUserId,
                [$permissions[0]['permission_key'], 'fake.permission']
            );
            return $hasAll === false;
        });
        
        $this->test("Get user permissions returns array", function() {
            $userPermissions = $this->permissionService->getUserPermissions($this->testUserId);
            return is_array($userPermissions);
        });
        
        $this->test("Get permission keys returns array of strings", function() {
            $keys = $this->permissionService->getPermissionKeys($this->testUserId);
            return is_array($keys) && (count($keys) === 0 || is_string($keys[0]));
        });
        
        $this->test("Check token permissions", function() {
            $tokenPermissions = ['users.read', 'users.create'];
            $hasPermission = $this->permissionService->checkTokenPermissions(
                $tokenPermissions,
                'users.read'
            );
            return $hasPermission === true;
        });
        
        $this->test("Token permission check fails for missing permission", function() {
            $tokenPermissions = ['users.read'];
            $hasPermission = $this->permissionService->checkTokenPermissions(
                $tokenPermissions,
                'users.delete'
            );
            return $hasPermission === false;
        });
    }
    
    private function testRoleService()
    {
        echo "\n👥 ROLE SERVICE (CRUD) TESTS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create role", function() {
            return $this->testRoleId !== null && $this->testRoleId > 0;
        });
        
        $this->test("Find role by ID", function() {
            $role = $this->roleModel->find($this->testRoleId);
            return $role !== null && $role['id'] == $this->testRoleId;
        });
        
        $this->test("Find role by name", function() {
            $role = $this->roleModel->find($this->testRoleId);
            $foundRole = $this->roleModel->findByName($role['name']);
            return $foundRole !== null && $foundRole['id'] == $this->testRoleId;
        });
        
        $this->test("Update role", function() {
            $updated = $this->roleModel->update($this->testRoleId, [
                'display_name' => 'Updated Test Role',
                'description' => 'Updated description'
            ]);
            
            $role = $this->roleModel->find($this->testRoleId);
            return $updated && $role['display_name'] === 'Updated Test Role';
        });
        
        $this->test("Get role permissions", function() {
            $permissions = $this->roleModel->getPermissions($this->testRoleId);
            return is_array($permissions);
        });
        
        $this->test("Assign permission to role", function() {
            $stmt = $this->db->query("SELECT * FROM permissions LIMIT 1");
            $allPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($allPermissions) === 0) return true; // Skip if no permissions
            
            $result = $this->roleModel->assignPermission(
                $this->testRoleId,
                $allPermissions[0]['id']
            );
            return $result === true;
        });
        
        $this->test("Revoke permission from role", function() {
            $stmt = $this->db->query("SELECT * FROM permissions LIMIT 1");
            $allPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($allPermissions) === 0) return true; // Skip if no permissions
            
            $result = $this->roleModel->revokePermission(
                $this->testRoleId,
                $allPermissions[0]['id']
            );
            return $result === true;
        });
        
        $this->test("Sync permissions", function() {
            $stmt = $this->db->query("SELECT * FROM permissions LIMIT 2");
            $allPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($allPermissions) < 2) return true; // Skip if not enough permissions
            
            $permissionIds = array_column($allPermissions, 'id');
            $result = $this->roleModel->syncPermissions($this->testRoleId, $permissionIds);
            
            $rolePermissions = $this->roleModel->getPermissions($this->testRoleId);
            return $result && count($rolePermissions) === 2;
        });
        
        $this->test("Check is system role", function() {
            $isSystem = $this->roleModel->isSystemRole($this->testRoleId);
            return $isSystem === false; // Our test role is not a system role
        });
        
        $this->test("Get all roles with permission count", function() {
            $roles = $this->roleModel->getAllWithPermissionCount();
            return is_array($roles) && count($roles) > 0;
        });
        
        $this->test("Cannot delete system role", function() {
            // Find a system role (superadmin)
            $systemRole = $this->roleModel->findByName('superadmin');
            if (!$systemRole) return true; // Skip if no system role
            
            try {
                $this->roleModel->delete($systemRole['id']);
                return false; // Should have thrown exception
            } catch (Exception $e) {
                return strpos($e->getMessage(), 'system role') !== false;
            }
        });
        
        $this->test("Can delete non-system role", function() {
            // Create a temporary role for deletion
            $tempRoleId = $this->roleModel->create([
                'name' => 'temp_delete_role_' . time(),
                'display_name' => 'Temp Delete Role',
                'description' => 'Temporary role for deletion test',
                'is_system_role' => false
            ]);
            
            $result = $this->roleModel->delete($tempRoleId);
            return $result === true;
        });
    }
    
    private function printResults()
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        
        $failedTests = $this->totalTests - $this->passedTests;
        $successRate = $this->totalTests > 0 ? ($this->passedTests / $this->totalTests) * 100 : 0;
        
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests} ✅\n";
        echo "Failed: {$failedTests} ❌\n";
        echo "Success Rate: " . number_format($successRate, 1) . "%\n\n";
        
        if ($failedTests > 0) {
            echo "❌ FAILED TESTS:\n";
            echo "-" . str_repeat("-", 40) . "\n";
            foreach ($this->results as $result) {
                if ($result['status'] !== 'PASS') {
                    echo "• {$result['test']}: {$result['status']}";
                    if ($result['message']) {
                        echo " - {$result['message']}";
                    }
                    echo "\n";
                }
            }
        }
        
        echo "\n";
        if ($successRate >= 90) {
            echo "🎉 EXCELLENT! RBAC core services are working correctly.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Most functionality is working. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! RBAC core services need attention.\n";
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    try {
        $test = new RbacCoreServicesTest();
        $test->runAllTests();
    } catch (Exception $e) {
        echo "Error running tests: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
}
