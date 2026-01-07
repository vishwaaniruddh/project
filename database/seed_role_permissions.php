<?php
/**
 * RBAC System - Role Permission Assignments
 * 
 * This script assigns default permissions to each role.
 * Run this after seed_rbac_system.php has seeded roles and permissions.
 */

require_once __DIR__ . '/../config/database.php';

class RolePermissionSeed
{
    private $db;
    private $errors = [];
    private $successCount = 0;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Run the complete seed process
     */
    public function run(): bool
    {
        echo "==============================================\n";
        echo "RBAC Role-Permission Assignments\n";
        echo "==============================================\n\n";
        
        try {
            // Verify prerequisites
            if (!$this->verifyPrerequisites()) {
                return false;
            }
            
            // Start transaction for rollback support
            $this->db->beginTransaction();
            
            // Assign permissions to each role
            echo "Assigning permissions to roles...\n\n";
            
            $this->assignSuperadminPermissions();
            $this->assignAdminPermissions();
            $this->assignManagerPermissions();
            $this->assignEngineerPermissions();
            $this->assignVendorPermissions();
            
            // Commit transaction if no errors
            if (empty($this->errors)) {
                $this->db->commit();
                echo "\n==============================================\n";
                echo "✓ Role-Permission assignments completed!\n";
                echo "  Assignments created: {$this->successCount}\n";
                echo "==============================================\n";
                return true;
            } else {
                // Rollback on errors
                $this->db->rollBack();
                echo "\n==============================================\n";
                echo "✗ Assignment failed with errors. Changes rolled back.\n";
                echo "  Errors: " . count($this->errors) . "\n";
                echo "==============================================\n";
                foreach ($this->errors as $error) {
                    echo "  - $error\n";
                }
                return false;
            }
            
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo "✗ Fatal error: " . $e->getMessage() . "\n";
            return false;
        }
    }

    
    /**
     * Verify that roles and permissions tables have data
     */
    private function verifyPrerequisites(): bool
    {
        // Check roles
        $stmt = $this->db->query("SELECT COUNT(*) FROM roles");
        $roleCount = $stmt->fetchColumn();
        if ($roleCount == 0) {
            echo "✗ No roles found. Please run seed_rbac_system.php first.\n";
            return false;
        }
        echo "✓ Found {$roleCount} roles\n";
        
        // Check permissions
        $stmt = $this->db->query("SELECT COUNT(*) FROM permissions");
        $permCount = $stmt->fetchColumn();
        if ($permCount == 0) {
            echo "✗ No permissions found. Please run seed_rbac_system.php first.\n";
            return false;
        }
        echo "✓ Found {$permCount} permissions\n\n";
        
        return true;
    }
    
    /**
     * Assign all permissions to Superadmin role
     */
    private function assignSuperadminPermissions(): void
    {
        echo "Superadmin Role (ID: 1):\n";
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO role_permissions (role_id, permission_id)
                SELECT 1, id FROM permissions
                ON DUPLICATE KEY UPDATE role_id = role_id
            ");
            $stmt->execute();
            
            // Count assigned permissions
            $countStmt = $this->db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = 1");
            $count = $countStmt->fetchColumn();
            
            echo "  ✓ Assigned ALL permissions ({$count} total)\n";
            $this->successCount += $count;
            
        } catch (PDOException $e) {
            $this->errors[] = "Failed to assign Superadmin permissions: " . $e->getMessage();
            echo "  ✗ Failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Assign Admin role permissions
     */
    private function assignAdminPermissions(): void
    {
        echo "Admin Role (ID: 2):\n";
        
        $permissionKeys = [
            // All masters permissions
            'masters.bank.manage', 'masters.bank.read',
            'masters.customer.manage', 'masters.customer.read',
            'masters.country.manage', 'masters.country.read',
            'masters.cities.manage', 'masters.cities.read',
            'masters.states.manage', 'masters.states.read',
            'masters.zones.manage', 'masters.zones.read',
            // Users permissions (not delete or manage_roles)
            'users.read', 'users.create', 'users.update',
            // Survey permissions
            'survey.create', 'survey.read', 'survey.update', 'survey.delete', 'survey.approve',
            // Installation permissions
            'installation.create', 'installation.read', 'installation.update', 
            'installation.delete', 'installation.complete', 'installation.approve',
        ];
        
        $this->assignPermissionsByKeys(2, $permissionKeys);
        
        // Add all inventory permissions except audit.read
        try {
            $stmt = $this->db->prepare("
                INSERT INTO role_permissions (role_id, permission_id)
                SELECT 2, id FROM permissions 
                WHERE module = 'inventory' AND permission_key != 'inventory.audit.read'
                ON DUPLICATE KEY UPDATE role_id = role_id
            ");
            $stmt->execute();
            
            $countStmt = $this->db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = 2");
            $count = $countStmt->fetchColumn();
            echo "  ✓ Total permissions assigned: {$count}\n";
            
        } catch (PDOException $e) {
            $this->errors[] = "Failed to assign Admin inventory permissions: " . $e->getMessage();
        }
    }

    
    /**
     * Assign Manager role permissions
     */
    private function assignManagerPermissions(): void
    {
        echo "Manager Role (ID: 3):\n";
        
        $permissionKeys = [
            // Masters read permissions
            'masters.bank.read', 'masters.customer.read', 'masters.country.read',
            'masters.cities.read', 'masters.states.read', 'masters.zones.read',
            // Users read
            'users.read',
            // Inventory dashboard, reports, and approval permissions
            'inventory.dashboard.adv', 'inventory.dashboard.contractor', 'inventory.dashboard.engineer',
            'inventory.reports.read', 'inventory.reports.export',
            'inventory.material_requests.approve', 'inventory.material_requests.view',
            'inventory.dispatch.read', 'inventory.stock.read', 'inventory.warehouses.read',
            'inventory.products.read', 'inventory.assets.read', 'inventory.repairs.read',
            'inventory.transfer.read', 'inventory.alerts.read',
            // Survey permissions
            'survey.read', 'survey.approve',
            // Installation permissions
            'installation.read', 'installation.approve',
        ];
        
        $this->assignPermissionsByKeys(3, $permissionKeys);
        
        $countStmt = $this->db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = 3");
        $count = $countStmt->fetchColumn();
        echo "  ✓ Total permissions assigned: {$count}\n";
    }
    
    /**
     * Assign Engineer role permissions
     */
    private function assignEngineerPermissions(): void
    {
        echo "Engineer Role (ID: 4):\n";
        
        $permissionKeys = [
            // Survey permissions
            'survey.create', 'survey.read', 'survey.update',
            // Installation permissions
            'installation.create', 'installation.read', 'installation.update', 'installation.complete',
            // Inventory permissions
            'inventory.material_requests.create', 'inventory.material_requests.view',
            'inventory.stock.read', 'inventory.dispatch.read', 'inventory.dispatch.acknowledge',
            'inventory.dashboard.engineer', 'inventory.warehouses.read',
            'inventory.products.read', 'inventory.material_masters.view',
            // Masters read permissions (limited)
            'masters.cities.read', 'masters.states.read', 'masters.zones.read',
        ];
        
        $this->assignPermissionsByKeys(4, $permissionKeys);
        
        $countStmt = $this->db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = 4");
        $count = $countStmt->fetchColumn();
        echo "  ✓ Total permissions assigned: {$count}\n";
    }
    
    /**
     * Assign Vendor role permissions
     */
    private function assignVendorPermissions(): void
    {
        echo "Vendor Role (ID: 5):\n";
        
        $permissionKeys = [
            // Survey permissions
            'survey.create', 'survey.read', 'survey.update',
            // Installation permissions
            'installation.create', 'installation.read', 'installation.update', 'installation.complete',
            // Inventory permissions
            'inventory.material_requests.view', 'inventory.stock.read',
            'inventory.dispatch.acknowledge', 'inventory.dispatch.read',
            'inventory.dashboard.contractor', 'inventory.material_masters.view',
            // Masters read permissions (limited)
            'masters.cities.read', 'masters.states.read',
        ];
        
        $this->assignPermissionsByKeys(5, $permissionKeys);
        
        $countStmt = $this->db->query("SELECT COUNT(*) FROM role_permissions WHERE role_id = 5");
        $count = $countStmt->fetchColumn();
        echo "  ✓ Total permissions assigned: {$count}\n";
    }

    
    /**
     * Assign permissions to a role by permission keys
     */
    private function assignPermissionsByKeys(int $roleId, array $permissionKeys): void
    {
        if (empty($permissionKeys)) {
            return;
        }
        
        $placeholders = implode(',', array_fill(0, count($permissionKeys), '?'));
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO role_permissions (role_id, permission_id)
                SELECT ?, id FROM permissions WHERE permission_key IN ($placeholders)
                ON DUPLICATE KEY UPDATE role_id = role_id
            ");
            
            $params = array_merge([$roleId], $permissionKeys);
            $stmt->execute($params);
            
            $this->successCount += count($permissionKeys);
            
        } catch (PDOException $e) {
            $this->errors[] = "Failed to assign permissions to role {$roleId}: " . $e->getMessage();
            echo "  ✗ Failed: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Get statistics about role-permission assignments
     */
    public function getStats(): array
    {
        $stats = [];
        
        try {
            // Get role permission counts
            $stmt = $this->db->query("
                SELECT r.name, r.display_name, COUNT(rp.permission_id) as permission_count
                FROM roles r
                LEFT JOIN role_permissions rp ON r.id = rp.role_id
                GROUP BY r.id
                ORDER BY r.id
            ");
            $stats['role_permissions'] = $stmt->fetchAll();
            
            // Total assignments
            $stmt = $this->db->query("SELECT COUNT(*) FROM role_permissions");
            $stats['total_assignments'] = $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }
    
    /**
     * Clear all role-permission assignments
     */
    public function clearAssignments(): bool
    {
        echo "Clearing role-permission assignments...\n";
        
        try {
            $this->db->exec("TRUNCATE TABLE role_permissions");
            echo "✓ Cleared all role-permission assignments\n";
            return true;
        } catch (Exception $e) {
            echo "✗ Error clearing assignments: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run seed if executed directly
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    $seed = new RolePermissionSeed();
    
    // Check for command line arguments
    $action = isset($argv[1]) ? $argv[1] : 'seed';
    
    switch ($action) {
        case 'clear':
            $seed->clearAssignments();
            break;
        case 'reset':
            $seed->clearAssignments();
            echo "\n";
            $seed->run();
            break;
        case 'stats':
            echo "Role-Permission Assignment Statistics\n";
            echo "==============================================\n\n";
            $stats = $seed->getStats();
            if (isset($stats['error'])) {
                echo "Error: " . $stats['error'] . "\n";
            } else {
                echo "Total Assignments: " . $stats['total_assignments'] . "\n\n";
                echo "Permissions by Role:\n";
                foreach ($stats['role_permissions'] as $row) {
                    echo "  - {$row['display_name']}: {$row['permission_count']} permissions\n";
                }
            }
            break;
        case 'seed':
        default:
            $seed->run();
            break;
    }
}
