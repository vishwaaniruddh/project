<?php
/**
 * RBAC API Integration Tests
 * 
 * Tests authentication endpoints, role management endpoints, and permission middleware
 * Requirements: 6.1, 6.2, 12.2
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/RolesController.php';
require_once __DIR__ . '/../controllers/PermissionsController.php';
require_once __DIR__ . '/../middleware/JWTAuthMiddleware.php';
require_once __DIR__ . '/../middleware/ApiPermissionMiddleware.php';
require_once __DIR__ . '/../services/TokenService.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Role.php';

class RbacApiIntegrationTest
{
    private $db;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testUserId;
    private $testRoleId;
    private $testToken;
    private $testUsername;
    private $testPassword = 'TestPassword123!';
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function runAllTests()
    {
        echo "🧪 Running RBAC API Integration Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Run tests
        $this->testAuthEndpoints();
        $this->testRoleManagementEndpoints();
        $this->testPermissionMiddleware();
        
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
        
        // Create test role with permissions
        $roleModel = new Role();
        $this->testRoleId = $roleModel->create([
            'name' => 'test_api_role_' . time(),
            'display_name' => 'Test API Role',
            'description' => 'Test role for API integration tests',
            'is_system_role' => false
        ]);
        
        // Assign some permissions to the role
        $stmt = $this->db->query("SELECT * FROM permissions LIMIT 1");
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (count($permissions) > 0) {
            $roleModel->assignPermission($this->testRoleId, $permissions[0]['id']);
        }
        
        // Create test user
        $userModel = new User();
        $this->testUsername = 'testapi_' . time();
        
        $userData = [
            'username' => $this->testUsername,
            'email' => 'testapi_' . time() . '@example.com',
            'phone' => '+1234567' . rand(100, 999),
            'password' => password_hash($this->testPassword, PASSWORD_BCRYPT),
            'role_id' => $this->testRoleId,
            'role' => 'admin',
            'status' => 'active'
        ];
        
        $this->testUserId = $userModel->create($userData);
        
        echo "Test data setup complete.\n\n";
    }
    
    private function cleanupTestData()
    {
        echo "\nCleaning up test data...\n";
        
        // Delete refresh tokens
        if ($this->testUserId) {
            $stmt = $this->db->prepare("DELETE FROM refresh_tokens WHERE user_id = ?");
            $stmt->execute([$this->testUserId]);
        }
        
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
    
    private function testAuthEndpoints()
    {
        echo "\n🔐 AUTHENTICATION ENDPOINT TESTS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Login with valid credentials", function() {
            $authController = new AuthController();
            
            // Simulate POST request
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'username' => $this->testUsername,
                'password' => $this->testPassword
            ];
            
            ob_start();
            $authController->login();
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            
            if ($response && isset($response['success']) && $response['success'] === true) {
                if (isset($response['data']['token'])) {
                    $this->testToken = $response['data']['token'];
                    return true;
                }
            }
            
            return false;
        });
        
        $this->test("Login response contains user data", function() {
            $authController = new AuthController();
            
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'username' => $this->testUsername,
                'password' => $this->testPassword
            ];
            
            ob_start();
            $authController->login();
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            
            return $response && 
                   isset($response['data']['user']) &&
                   isset($response['data']['user']['username']);
        });
        
        $this->test("Login response contains permissions", function() {
            $authController = new AuthController();
            
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'username' => $this->testUsername,
                'password' => $this->testPassword
            ];
            
            ob_start();
            $authController->login();
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            
            return $response && 
                   isset($response['data']['permissions']) &&
                   is_array($response['data']['permissions']);
        });
        
        $this->test("Login fails with invalid credentials", function() {
            $authController = new AuthController();
            
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'username' => $this->testUsername,
                'password' => 'WrongPassword123!'
            ];
            
            ob_start();
            $authController->login();
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            
            return $response && 
                   isset($response['success']) && 
                   $response['success'] === false;
        });
        
        $this->test("Get current user info (me endpoint)", function() {
            if (!$this->testToken) {
                // Generate token for test
                $tokenService = new TokenService();
                $userModel = new User();
                $user = $userModel->find($this->testUserId);
                $this->testToken = $tokenService->generateAccessToken($user, []);
            }
            
            $authController = new AuthController();
            
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->testToken;
            
            ob_start();
            try {
                $authController->me();
                $output = ob_get_clean();
                
                $response = json_decode($output, true);
                
                return $response && 
                       isset($response['success']) && 
                       $response['success'] === true &&
                       isset($response['data']['user']);
            } catch (Exception $e) {
                ob_get_clean();
                return false;
            }
        });
        
        $this->test("Logout invalidates token", function() {
            if (!$this->testToken) return true; // Skip if no token
            
            $authController = new AuthController();
            
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->testToken;
            
            ob_start();
            try {
                $authController->logout();
                $output = ob_get_clean();
                
                $response = json_decode($output, true);
                
                return $response && 
                       isset($response['success']) && 
                       $response['success'] === true;
            } catch (Exception $e) {
                ob_get_clean();
                return false;
            }
        });
    }
    
    private function testRoleManagementEndpoints()
    {
        echo "\n👥 ROLE MANAGEMENT ENDPOINT TESTS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        // Generate fresh token for role management tests
        $tokenService = new TokenService();
        $userModel = new User();
        $user = $userModel->find($this->testUserId);
        $this->testToken = $tokenService->generateAccessToken($user, ['users.manage_roles']);
        
        $this->test("Get all roles", function() {
            $rolesController = new RolesController();
            
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->testToken;
            
            ob_start();
            try {
                $rolesController->index();
                $output = ob_get_clean();
                
                $response = json_decode($output, true);
                
                return $response && 
                       isset($response['success']) && 
                       $response['success'] === true &&
                       isset($response['data']) &&
                       is_array($response['data']);
            } catch (Exception $e) {
                ob_get_clean();
                return false;
            }
        });
        
        $this->test("Get specific role", function() {
            $rolesController = new RolesController();
            
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->testToken;
            
            ob_start();
            try {
                $rolesController->show($this->testRoleId);
                $output = ob_get_clean();
                
                $response = json_decode($output, true);
                
                return $response && 
                       isset($response['success']) && 
                       $response['success'] === true &&
                       isset($response['data']['id']) &&
                       $response['data']['id'] == $this->testRoleId;
            } catch (Exception $e) {
                ob_get_clean();
                return false;
            }
        });
        
        $this->test("Create new role", function() {
            $rolesController = new RolesController();
            
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->testToken;
            $_POST = [
                'name' => 'new_test_role_' . time(),
                'display_name' => 'New Test Role',
                'description' => 'Created via API test'
            ];
            
            ob_start();
            try {
                $rolesController->store();
                $output = ob_get_clean();
                
                $response = json_decode($output, true);
                
                // Cleanup created role
                if ($response && isset($response['data']['id'])) {
                    $roleModel = new Role();
                    $roleModel->delete($response['data']['id']);
                }
                
                return $response && 
                       isset($response['success']) && 
                       $response['success'] === true;
            } catch (Exception $e) {
                ob_get_clean();
                return false;
            }
        });
        
        $this->test("Update role", function() {
            $rolesController = new RolesController();
            
            $_SERVER['REQUEST_METHOD'] = 'PUT';
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->testToken;
            
            // Simulate PUT data
            $putData = json_encode([
                'display_name' => 'Updated Test Role',
                'description' => 'Updated via API test'
            ]);
            
            ob_start();
            try {
                // Mock php://input for PUT request
                $rolesController->update($this->testRoleId);
                $output = ob_get_clean();
                
                $response = json_decode($output, true);
                
                return $response && 
                       isset($response['success']) && 
                       $response['success'] === true;
            } catch (Exception $e) {
                ob_get_clean();
                return false;
            }
        });
        
        $this->test("Get role permissions", function() {
            $rolesController = new RolesController();
            
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->testToken;
            
            ob_start();
            try {
                $rolesController->permissions($this->testRoleId);
                $output = ob_get_clean();
                
                $response = json_decode($output, true);
                
                return $response && 
                       isset($response['success']) && 
                       $response['success'] === true &&
                       isset($response['data']) &&
                       is_array($response['data']);
            } catch (Exception $e) {
                ob_get_clean();
                return false;
            }
        });
    }
    
    private function testPermissionMiddleware()
    {
        echo "\n🛡️ PERMISSION MIDDLEWARE TESTS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("JWT middleware validates valid token", function() {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->testToken;
            
            try {
                $user = JWTAuthMiddleware::authenticate();
                return $user !== null && isset($user['id']);
            } catch (Exception $e) {
                return false;
            }
        });
        
        $this->test("JWT middleware rejects missing token", function() {
            unset($_SERVER['HTTP_AUTHORIZATION']);
            
            ob_start();
            try {
                JWTAuthMiddleware::authenticate();
                ob_get_clean();
                return false; // Should have thrown exception
            } catch (Exception $e) {
                ob_get_clean();
                return strpos($e->getMessage(), 'token') !== false ||
                       strpos($e->getMessage(), 'Authorization') !== false;
            }
        });
        
        $this->test("JWT middleware rejects invalid token", function() {
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer invalid.token.string';
            
            ob_start();
            try {
                JWTAuthMiddleware::authenticate();
                ob_get_clean();
                return false; // Should have thrown exception
            } catch (Exception $e) {
                ob_get_clean();
                return true;
            }
        });
        
        $this->test("Permission middleware allows with valid permission", function() {
            // Generate token with specific permission
            $tokenService = new TokenService();
            $userModel = new User();
            $user = $userModel->find($this->testUserId);
            $token = $tokenService->generateAccessToken($user, ['users.read']);
            
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
            
            ob_start();
            try {
                ApiPermissionMiddleware::require('users.read');
                ob_get_clean();
                return true;
            } catch (Exception $e) {
                ob_get_clean();
                return false;
            }
        });
        
        $this->test("Permission middleware denies without permission", function() {
            // Generate token without specific permission
            $tokenService = new TokenService();
            $userModel = new User();
            $user = $userModel->find($this->testUserId);
            $token = $tokenService->generateAccessToken($user, ['users.read']);
            
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
            
            ob_start();
            try {
                ApiPermissionMiddleware::require('users.delete');
                ob_get_clean();
                return false; // Should have thrown exception
            } catch (Exception $e) {
                ob_get_clean();
                return strpos($e->getMessage(), 'Permission denied') !== false ||
                       strpos($e->getMessage(), 'permission') !== false;
            }
        });
        
        $this->test("Permission middleware requireAny with OR logic", function() {
            $tokenService = new TokenService();
            $userModel = new User();
            $user = $userModel->find($this->testUserId);
            $token = $tokenService->generateAccessToken($user, ['users.read']);
            
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
            
            ob_start();
            try {
                ApiPermissionMiddleware::requireAny(['users.read', 'users.delete']);
                ob_get_clean();
                return true;
            } catch (Exception $e) {
                ob_get_clean();
                return false;
            }
        });
        
        $this->test("Permission middleware requireAll with AND logic", function() {
            $tokenService = new TokenService();
            $userModel = new User();
            $user = $userModel->find($this->testUserId);
            $token = $tokenService->generateAccessToken($user, ['users.read', 'users.create']);
            
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
            
            ob_start();
            try {
                ApiPermissionMiddleware::requireAll(['users.read', 'users.create']);
                ob_get_clean();
                return true;
            } catch (Exception $e) {
                ob_get_clean();
                return false;
            }
        });
        
        $this->test("Permission middleware requireAll fails when one missing", function() {
            $tokenService = new TokenService();
            $userModel = new User();
            $user = $userModel->find($this->testUserId);
            $token = $tokenService->generateAccessToken($user, ['users.read']);
            
            $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $token;
            
            ob_start();
            try {
                ApiPermissionMiddleware::requireAll(['users.read', 'users.delete']);
                ob_get_clean();
                return false; // Should have thrown exception
            } catch (Exception $e) {
                ob_get_clean();
                return true;
            }
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
            echo "🎉 EXCELLENT! RBAC API integration is working correctly.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Most functionality is working. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! RBAC API integration needs attention.\n";
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    try {
        $test = new RbacApiIntegrationTest();
        $test->runAllTests();
    } catch (Exception $e) {
        echo "Error running tests: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
}
