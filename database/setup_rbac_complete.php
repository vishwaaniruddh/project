<?php
/**
 * RBAC System - Complete Setup Script
 * 
 * This script runs all RBAC setup steps in sequence:
 * 1. Create RBAC tables (setup_rbac_system.php)
 * 2. Seed roles and permissions (seed_rbac_system.php)
 * 3. Assign permissions to roles (seed_role_permissions.php)
 * 
 * Usage:
 *   php setup_rbac_complete.php         - Run complete setup
 *   php setup_rbac_complete.php reset   - Drop and recreate everything
 *   php setup_rbac_complete.php check   - Check current status
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/setup_rbac_system.php';
require_once __DIR__ . '/seed_rbac_system.php';
require_once __DIR__ . '/seed_role_permissions.php';

class RBACCompleteSetup
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Run complete setup
     */
    public function run(): bool
    {
        echo "##############################################\n";
        echo "#  RBAC System - Complete Setup              #\n";
        echo "##############################################\n\n";
        
        $startTime = microtime(true);
        
        // Step 1: Create tables
        echo "STEP 1/3: Creating RBAC Tables\n";
        echo "----------------------------------------------\n";
        $setup = new RBACSetup();
        if (!$setup->run()) {
            echo "\n✗ Setup failed at Step 1. Aborting.\n";
            return false;
        }
        
        echo "\n";
        
        // Step 2: Seed roles and permissions
        echo "STEP 2/3: Seeding Roles and Permissions\n";
        echo "----------------------------------------------\n";
        $seed = new RBACSeed();
        if (!$seed->run()) {
            echo "\n✗ Setup failed at Step 2. Aborting.\n";
            return false;
        }
        
        echo "\n";
        
        // Step 3: Assign permissions to roles
        echo "STEP 3/3: Assigning Permissions to Roles\n";
        echo "----------------------------------------------\n";
        $rolePermSeed = new RolePermissionSeed();
        if (!$rolePermSeed->run()) {
            echo "\n✗ Setup failed at Step 3. Aborting.\n";
            return false;
        }
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo "\n##############################################\n";
        echo "#  RBAC Setup Complete!                      #\n";
        echo "#  Duration: {$duration} seconds                    #\n";
        echo "##############################################\n";
        
        return true;
    }

    
    /**
     * Reset everything - drop and recreate
     */
    public function reset(): bool
    {
        echo "##############################################\n";
        echo "#  RBAC System - Reset                       #\n";
        echo "##############################################\n\n";
        
        // Drop all tables
        echo "Dropping existing RBAC tables...\n";
        echo "----------------------------------------------\n";
        $setup = new RBACSetup();
        $setup->dropAllTables();
        
        echo "\n";
        
        // Run complete setup
        return $this->run();
    }
    
    /**
     * Check current status
     */
    public function check(): void
    {
        echo "##############################################\n";
        echo "#  RBAC System - Status Check                #\n";
        echo "##############################################\n\n";
        
        // Check tables
        echo "Tables:\n";
        echo "----------------------------------------------\n";
        $setup = new RBACSetup();
        $tableStatus = $setup->checkTables();
        foreach ($tableStatus as $table => $exists) {
            echo ($exists ? "✓" : "✗") . " $table\n";
        }
        
        echo "\n";
        
        // Check data
        echo "Data:\n";
        echo "----------------------------------------------\n";
        $seed = new RBACSeed();
        $stats = $seed->getStats();
        
        if (isset($stats['error'])) {
            echo "Error: " . $stats['error'] . "\n";
        } else {
            echo "Roles: " . ($stats['roles'] ?? 0) . "\n";
            echo "Permissions: " . ($stats['permissions'] ?? 0) . "\n";
            
            if (!empty($stats['permissions_by_module'])) {
                echo "\nPermissions by Module:\n";
                foreach ($stats['permissions_by_module'] as $row) {
                    echo "  - {$row['module']}: {$row['count']}\n";
                }
            }
        }
        
        echo "\n";
        
        // Check role-permission assignments
        echo "Role-Permission Assignments:\n";
        echo "----------------------------------------------\n";
        $rolePermSeed = new RolePermissionSeed();
        $rpStats = $rolePermSeed->getStats();
        
        if (isset($rpStats['error'])) {
            echo "Error: " . $rpStats['error'] . "\n";
        } else {
            echo "Total Assignments: " . ($rpStats['total_assignments'] ?? 0) . "\n";
            
            if (!empty($rpStats['role_permissions'])) {
                echo "\nPermissions by Role:\n";
                foreach ($rpStats['role_permissions'] as $row) {
                    echo "  - {$row['display_name']}: {$row['permission_count']} permissions\n";
                }
            }
        }
    }
}

// Run if executed directly
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    $completeSetup = new RBACCompleteSetup();
    
    // Check for command line arguments
    $action = isset($argv[1]) ? $argv[1] : 'setup';
    
    switch ($action) {
        case 'reset':
            $completeSetup->reset();
            break;
        case 'check':
            $completeSetup->check();
            break;
        case 'setup':
        default:
            $completeSetup->run();
            break;
    }
}
