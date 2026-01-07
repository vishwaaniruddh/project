<?php
/**
 * User RBAC Test
 * 
 * Tests the RBAC functionality added to the User model
 * Requirements: 4.1, 4.3, 4.4
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class UserRbacTest
{
    private $userModel;
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userModel = new User();
    }
    
    public function runTests()
    {
        echo "=== User RBAC Tests ===\n\n";
        
        $this->testGetRole();
        $this->testGetPermissions();
        $this->testValidateUserDataWithRoleId();
        $this->testGetAllWithPaginationIncludesRole();
        
        echo "\n=== All Tests Completed ===\n";
    }
    
    private function testGetRole()
    {
        echo "Test: getRole() method\n";
        
        // Find a user with a role_id
        $sql = "SELECT id FROM users WHERE role_id IS NOT NULL LIMIT 1";
        $stmt = $this->db->query($sql);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "  ⚠ No users with role_id found, skipping test\n\n";
            return;
        }
        
        $role = $this->userModel->getRole($user['id']);
        
        if ($role && isset($role['name']) && isset($role['display_name'])) {
            echo "  ✓ getRole() returns role data with name and display_name\n";
            echo "    Role: {$role['name']} ({$role['display_name']})\n";
        } else {
            echo "  ✗ getRole() did not return expected role data\n";
        }
        
        echo "\n";
    }
    
    private function testGetPermissions()
    {
        echo "Test: getPermissions() method\n";
        
        // Find a user with a role_id
        $sql = "SELECT id FROM users WHERE role_id IS NOT NULL LIMIT 1";
        $stmt = $this->db->query($sql);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            echo "  ⚠ No users with role_id found, skipping test\n\n";
            return;
        }
        
        $permissions = $this->userModel->getPermissions($user['id']);
        
        if (is_array($permissions)) {
            echo "  ✓ getPermissions() returns an array\n";
            echo "    Permission count: " . count($permissions) . "\n";
            
            if (count($permissions) > 0 && isset($permissions[0]['permission_key'])) {
                echo "    Sample permission: {$permissions[0]['permission_key']}\n";
            }
        } else {
            echo "  ✗ getPermissions() did not return an array\n";
        }
        
        echo "\n";
    }
    
    private function testValidateUserDataWithRoleId()
    {
        echo "Test: validateUserData() with role_id\n";
        
        // Test with valid role_id
        $validData = [
            'username' => 'testuser_' . time(),
            'email' => 'test_' . time() . '@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'role_id' => 2, // Admin role
            'role' => 'admin'
        ];
        
        $errors = $this->userModel->validateUserData($validData);
        
        if (empty($errors)) {
            echo "  ✓ Valid role_id passes validation\n";
        } else {
            echo "  ✗ Valid role_id failed validation\n";
            print_r($errors);
        }
        
        // Test with invalid role_id
        $invalidData = [
            'username' => 'testuser2_' . time(),
            'email' => 'test2_' . time() . '@example.com',
            'phone' => '+1234567891',
            'password' => 'password123',
            'role_id' => 999, // Non-existent role
            'role' => 'admin'
        ];
        
        $errors = $this->userModel->validateUserData($invalidData);
        
        if (isset($errors['role_id'])) {
            echo "  ✓ Invalid role_id fails validation with error\n";
        } else {
            echo "  ✗ Invalid role_id should fail validation\n";
        }
        
        echo "\n";
    }
    
    private function testGetAllWithPaginationIncludesRole()
    {
        echo "Test: getAllWithPagination() includes role info\n";
        
        $result = $this->userModel->getAllWithPagination(1, 5);
        
        if (isset($result['users']) && is_array($result['users'])) {
            echo "  ✓ getAllWithPagination() returns users array\n";
            
            if (count($result['users']) > 0) {
                $firstUser = $result['users'][0];
                
                if (isset($firstUser['role_name']) || isset($firstUser['role_display_name'])) {
                    echo "  ✓ User records include role information\n";
                    
                    if (isset($firstUser['role_name'])) {
                        echo "    Sample role_name: {$firstUser['role_name']}\n";
                    }
                    if (isset($firstUser['role_display_name'])) {
                        echo "    Sample role_display_name: {$firstUser['role_display_name']}\n";
                    }
                } else {
                    echo "  ⚠ User records do not include role_name or role_display_name\n";
                    echo "    (This is expected if users don't have role_id set)\n";
                }
            } else {
                echo "  ⚠ No users found in database\n";
            }
        } else {
            echo "  ✗ getAllWithPagination() did not return expected structure\n";
        }
        
        echo "\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli') {
    try {
        $test = new UserRbacTest();
        $test->runTests();
    } catch (Exception $e) {
        echo "Error running tests: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
    }
}
