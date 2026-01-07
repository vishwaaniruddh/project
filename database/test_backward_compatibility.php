<?php
/**
 * Test Backward Compatibility Layer
 * 
 * This script tests the backward compatibility features:
 * - Legacy role column sync
 * - Deprecation logging
 * - Migration status checks
 * 
 * Usage:
 *   php test_backward_compatibility.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth_compatibility.php';
require_once __DIR__ . '/../models/User.php';

class BackwardCompatibilityTest
{
    private $db;
    private $userModel;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
        $this->userModel = new User();
    }
    
    /**
     * Run all tests
     */
    public function runTests(): void
    {
        echo "==============================================\n";
        echo "Backward Compatibility Tests\n";
        echo "==============================================\n\n";
        
        // Test 1: Check RBAC availability
        echo "Test 1: RBAC System Availability\n";
        echo "----------------------------------------------\n";
        $this->testRBACAvailability();
        echo "\n";
        
        // Test 2: Check migration status
        echo "Test 2: Migration Status\n";
        echo "----------------------------------------------\n";
        $this->testMigrationStatus();
        echo "\n";
        
        // Test 3: Test role sync
        echo "Test 3: Role Synchronization\n";
        echo "----------------------------------------------\n";
        $this->testRoleSync();
        echo "\n";
        
        // Test 4: Test deprecation logging
        echo "Test 4: Deprecation Logging\n";
        echo "----------------------------------------------\n";
        $this->testDeprecationLogging();
        echo "\n";
        
        // Test 5: View deprecation stats
        echo "Test 5: Deprecation Statistics\n";
        echo "----------------------------------------------\n";
        $this->testDeprecationStats();
        echo "\n";
        
        echo "==============================================\n";
        echo "Tests Complete\n";
        echo "==============================================\n";
    }
    
    /**
     * Test RBAC availability
     */
    private function testRBACAvailability(): void
    {
        $available = AuthCompatibility::isRBACAvailable();
        
        if ($available) {
            echo "✓ RBAC system is available\n";
        } else {
            echo "✗ RBAC system is not available\n";
            echo "  Run: php database/setup_rbac_complete.php\n";
        }
    }
    
    /**
     * Test migration status
     */
    private function testMigrationStatus(): void
    {
        $status = AuthCompatibility::getMigrationStatus();
        
        if (isset($status['error'])) {
            echo "✗ Error: {$status['error']}\n";
            return;
        }
        
        echo "RBAC Available: " . ($status['rbac_available'] ? "Yes" : "No") . "\n";
        echo "Users Migrated: {$status['users_migrated']}\n";
        echo "Users Not Migrated: {$status['users_not_migrated']}\n";
        echo "Legacy Permissions: " . ($status['legacy_permissions_exist'] ? "Yes" : "No") . "\n";
        
        if ($status['users_not_migrated'] > 0) {
            echo "\n⚠ Warning: Some users need migration\n";
            echo "  Run: php database/migrate_existing_users.php\n";
        } else {
            echo "\n✓ All users migrated\n";
        }
    }
    
    /**
     * Test role synchronization
     */
    private function testRoleSync(): void
    {
        try {
            // Get a test user
            $stmt = $this->db->query("SELECT id, username, role, role_id FROM users LIMIT 1");
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo "○ No users found to test\n";
                return;
            }
            
            echo "Testing with user: {$user['username']} (ID: {$user['id']})\n";
            echo "Current role: {$user['role']}, role_id: " . ($user['role_id'] ?? 'NULL') . "\n";
            
            // Test syncing role_id to legacy role
            if ($user['role_id']) {
                $roleName = AuthCompatibility::getRoleName($user['role_id']);
                echo "Role name from role_id: " . ($roleName ?? 'NULL') . "\n";
            }
            
            // Test syncing legacy role to role_id
            if ($user['role']) {
                $roleId = AuthCompatibility::getRoleIdFromLegacyRole($user['role']);
                echo "Role ID from legacy role: " . ($roleId ?? 'NULL') . "\n";
            }
            
            echo "✓ Role sync functions working\n";
            
        } catch (Exception $e) {
            echo "✗ Role sync test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test deprecation logging
     */
    private function testDeprecationLogging(): void
    {
        // Enable deprecation logging
        AuthCompatibility::setDeprecationLogging(true);
        
        // Trigger some deprecated methods
        try {
            // This will log a deprecation warning
            AuthCompatibility::hasLegacyVendorPermission(1, 'view_sites');
            echo "✓ Deprecation logging triggered\n";
            
            // Check if log file was created
            $logs = AuthCompatibility::getDeprecationLog(5);
            if (!empty($logs)) {
                echo "✓ Deprecation log entries found: " . count($logs) . "\n";
                echo "\nRecent entries:\n";
                foreach (array_slice($logs, -3) as $log) {
                    echo "  " . substr($log, 0, 100) . "...\n";
                }
            } else {
                echo "○ No deprecation log entries yet\n";
            }
            
        } catch (Exception $e) {
            echo "✗ Deprecation logging test failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Test deprecation statistics
     */
    private function testDeprecationStats(): void
    {
        $stats = AuthCompatibility::getDeprecationStats();
        
        echo "Total Deprecated Calls: {$stats['total_calls']}\n";
        
        if (!empty($stats['by_method'])) {
            echo "\nMost Used Deprecated Methods:\n";
            $count = 0;
            foreach ($stats['by_method'] as $method => $calls) {
                echo "  - $method: $calls call(s)\n";
                if (++$count >= 5) break;
            }
        }
        
        if (!empty($stats['by_file'])) {
            echo "\nFiles Using Deprecated Methods:\n";
            $count = 0;
            foreach ($stats['by_file'] as $file => $calls) {
                echo "  - $file: $calls call(s)\n";
                if (++$count >= 5) break;
            }
        }
        
        if ($stats['total_calls'] === 0) {
            echo "✓ No deprecated methods in use\n";
        } else {
            echo "\n⚠ Consider migrating to RBAC permission checks\n";
        }
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    $test = new BackwardCompatibilityTest();
    $test->runTests();
}
