<?php
/**
 * Data Migration Script for Existing Users
 * 
 * This script migrates existing users to the new RBAC system:
 * 1. Maps existing 'admin' users to Admin role (role_id = 2)
 * 2. Maps existing 'vendor' users to Vendor role (role_id = 5)
 * 3. Creates default Superadmin user if not exists
 * 4. Migrates vendor_permissions to user_permissions table
 * 
 * Usage:
 *   php migrate_existing_users.php         - Run migration
 *   php migrate_existing_users.php check   - Check migration status
 *   php migrate_existing_users.php rollback - Rollback migration
 */

require_once __DIR__ . '/../config/database.php';

class UserMigration
{
    private $db;
    private $errors = [];
    private $successCount = 0;
    private $stats = [
        'admin_users_migrated' => 0,
        'vendor_users_migrated' => 0,
        'superadmin_created' => false,
        'permissions_migrated' => 0
    ];
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Run the complete migration process
     */
    public function run(): bool
    {
        echo "==============================================\n";
        echo "User Data Migration to RBAC System\n";
        echo "==============================================\n\n";
        
        try {
            // Start transaction for rollback support
            $this->db->beginTransaction();
            
            // Step 1: Verify RBAC tables exist
            echo "Step 1: Verifying RBAC tables...\n";
            if (!$this->verifyRBACTables()) {
                throw new Exception("RBAC tables not found. Please run setup_rbac_complete.php first.");
            }
            echo "✓ RBAC tables verified\n\n";
            
            // Step 2: Create default Superadmin user
            echo "Step 2: Creating default Superadmin user...\n";
            $this->createSuperadminUser();
            echo "\n";
            
            // Step 3: Map existing admin users to Admin role
            echo "Step 3: Mapping admin users to Admin role (role_id = 2)...\n";
            $this->mapAdminUsers();
            echo "\n";
            
            // Step 4: Map existing vendor users to Vendor role
            echo "Step 4: Mapping vendor users to Vendor role (role_id = 5)...\n";
            $this->mapVendorUsers();
            echo "\n";
            
            // Step 5: Migrate vendor_permissions to user_permissions
            echo "Step 5: Migrating vendor_permissions to user_permissions...\n";
            $this->migrateVendorPermissions();
            echo "\n";
            
            // Commit transaction if no errors
            if (empty($this->errors)) {
                $this->db->commit();
                $this->printSuccessSummary();
                return true;
            } else {
                // Rollback on errors
                $this->db->rollBack();
                $this->printErrorSummary();
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
     * Verify that all required RBAC tables exist
     */
    private function verifyRBACTables(): bool
    {
        $requiredTables = ['roles', 'permissions', 'role_permissions', 'user_permissions'];
        
        foreach ($requiredTables as $table) {
            $stmt = $this->db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() === 0) {
                echo "✗ Table '$table' not found\n";
                return false;
            }
        }
        
        // Check if role_id column exists in users table
        $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'role_id'");
        if ($stmt->rowCount() === 0) {
            echo "✗ Column 'role_id' not found in users table\n";
            return false;
        }
        
        return true;
    }
    
    /**
     * Create default Superadmin user if not exists
     */
    private function createSuperadminUser(): void
    {
        try {
            // Check if a user with role_id = 1 (Superadmin) already exists
            $stmt = $this->db->query("SELECT id, username FROM users WHERE role_id = 1 LIMIT 1");
            $existingSuperadmin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingSuperadmin) {
                echo "○ Superadmin user already exists: {$existingSuperadmin['username']} (ID: {$existingSuperadmin['id']})\n";
                $this->successCount++;
                return;
            }
            
            // Check if username 'superadmin' exists
            $stmt = $this->db->prepare("SELECT id, username, role FROM users WHERE username = ?");
            $stmt->execute(['superadmin']);
            $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingUser) {
                // Update existing user to Superadmin role
                $stmt = $this->db->prepare("UPDATE users SET role_id = 1 WHERE id = ?");
                $stmt->execute([$existingUser['id']]);
                echo "✓ Updated existing user 'superadmin' to Superadmin role (ID: {$existingUser['id']})\n";
                $this->stats['superadmin_created'] = true;
                $this->successCount++;
            } else {
                // Create new Superadmin user
                $passwordHash = password_hash('superadmin123', PASSWORD_DEFAULT);
                $stmt = $this->db->prepare("
                    INSERT INTO users (username, email, phone, password_hash, plain_password, role, role_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    'superadmin',
                    'superadmin@example.com',
                    '+1000000000',
                    $passwordHash,
                    'superadmin123',
                    'admin', // Keep legacy role for backward compatibility
                    1, // Superadmin role_id
                    'active'
                ]);
                
                $userId = $this->db->lastInsertId();
                echo "✓ Created new Superadmin user (ID: $userId)\n";
                echo "  Username: superadmin\n";
                echo "  Password: superadmin123\n";
                echo "  Email: superadmin@example.com\n";
                $this->stats['superadmin_created'] = true;
                $this->successCount++;
            }
            
        } catch (PDOException $e) {
            $this->errors[] = "Failed to create Superadmin user: " . $e->getMessage();
            echo "✗ Failed to create Superadmin user: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Map existing admin users to Admin role (role_id = 2)
     */
    private function mapAdminUsers(): void
    {
        try {
            // Get all admin users without role_id set
            $stmt = $this->db->query("
                SELECT id, username FROM users 
                WHERE role = 'admin' AND (role_id IS NULL OR role_id = 0)
            ");
            $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($adminUsers)) {
                echo "○ No admin users to migrate\n";
                $this->successCount++;
                return;
            }
            
            // Update all admin users to role_id = 2
            $stmt = $this->db->prepare("
                UPDATE users SET role_id = 2 
                WHERE role = 'admin' AND (role_id IS NULL OR role_id = 0)
            ");
            $stmt->execute();
            
            $count = $stmt->rowCount();
            $this->stats['admin_users_migrated'] = $count;
            echo "✓ Mapped $count admin user(s) to Admin role (role_id = 2)\n";
            
            // List migrated users
            foreach ($adminUsers as $user) {
                echo "  - {$user['username']} (ID: {$user['id']})\n";
            }
            
            $this->successCount++;
            
        } catch (PDOException $e) {
            $this->errors[] = "Failed to map admin users: " . $e->getMessage();
            echo "✗ Failed to map admin users: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Map existing vendor users to Vendor role (role_id = 5)
     */
    private function mapVendorUsers(): void
    {
        try {
            // Get all vendor users without role_id set
            $stmt = $this->db->query("
                SELECT id, username FROM users 
                WHERE role = 'vendor' AND (role_id IS NULL OR role_id = 0)
            ");
            $vendorUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($vendorUsers)) {
                echo "○ No vendor users to migrate\n";
                $this->successCount++;
                return;
            }
            
            // Update all vendor users to role_id = 5
            $stmt = $this->db->prepare("
                UPDATE users SET role_id = 5 
                WHERE role = 'vendor' AND (role_id IS NULL OR role_id = 0)
            ");
            $stmt->execute();
            
            $count = $stmt->rowCount();
            $this->stats['vendor_users_migrated'] = $count;
            echo "✓ Mapped $count vendor user(s) to Vendor role (role_id = 5)\n";
            
            // List migrated users
            foreach ($vendorUsers as $user) {
                echo "  - {$user['username']} (ID: {$user['id']})\n";
            }
            
            $this->successCount++;
            
        } catch (PDOException $e) {
            $this->errors[] = "Failed to map vendor users: " . $e->getMessage();
            echo "✗ Failed to map vendor users: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Migrate vendor_permissions to user_permissions table
     */
    private function migrateVendorPermissions(): void
    {
        try {
            // Check if vendor_permissions table exists
            $stmt = $this->db->query("SHOW TABLES LIKE 'vendor_permissions'");
            if ($stmt->rowCount() === 0) {
                echo "○ vendor_permissions table not found, skipping migration\n";
                $this->successCount++;
                return;
            }
            
            // Get all vendor permissions
            $stmt = $this->db->query("
                SELECT vp.*, u.id as user_id 
                FROM vendor_permissions vp
                INNER JOIN users u ON vp.user_id = u.id
                WHERE vp.permission_value = 1
            ");
            $vendorPermissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($vendorPermissions)) {
                echo "○ No vendor permissions to migrate\n";
                $this->successCount++;
                return;
            }
            
            $migratedCount = 0;
            $skippedCount = 0;
            
            foreach ($vendorPermissions as $vp) {
                // Map old permission keys to new permission IDs
                $permissionId = $this->mapVendorPermissionToNewSystem($vp['permission_key']);
                
                if ($permissionId === null) {
                    echo "  ○ Skipped unmapped permission: {$vp['permission_key']}\n";
                    $skippedCount++;
                    continue;
                }
                
                // Check if permission override already exists
                $stmt = $this->db->prepare("
                    SELECT id FROM user_permissions 
                    WHERE user_id = ? AND permission_id = ?
                ");
                $stmt->execute([$vp['user_id'], $permissionId]);
                
                if ($stmt->fetch()) {
                    $skippedCount++;
                    continue;
                }
                
                // Insert into user_permissions
                $stmt = $this->db->prepare("
                    INSERT INTO user_permissions (user_id, permission_id, is_granted, granted_by)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $vp['user_id'],
                    $permissionId,
                    1, // is_granted = true
                    $vp['granted_by'] ?? null
                ]);
                
                $migratedCount++;
            }
            
            $this->stats['permissions_migrated'] = $migratedCount;
            echo "✓ Migrated $migratedCount vendor permission(s) to user_permissions\n";
            
            if ($skippedCount > 0) {
                echo "  ○ Skipped $skippedCount permission(s) (already exist or unmapped)\n";
            }
            
            $this->successCount++;
            
        } catch (PDOException $e) {
            $this->errors[] = "Failed to migrate vendor permissions: " . $e->getMessage();
            echo "✗ Failed to migrate vendor permissions: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Map old vendor permission keys to new permission IDs
     */
    private function mapVendorPermissionToNewSystem(string $oldPermissionKey): ?int
    {
        // Mapping of old vendor permission keys to new permission keys
        $permissionMap = [
            'view_sites' => 'survey.read',
            'update_progress' => 'installation.update',
            'view_masters' => 'masters.bank.read',
            'view_reports' => 'inventory.reports.read',
            'view_inventory' => 'inventory.stock.read',
            'view_material_requests' => 'inventory.material_requests.view'
        ];
        
        $newPermissionKey = $permissionMap[$oldPermissionKey] ?? null;
        
        if ($newPermissionKey === null) {
            return null;
        }
        
        // Get permission ID from new system
        try {
            $stmt = $this->db->prepare("SELECT id FROM permissions WHERE permission_key = ?");
            $stmt->execute([$newPermissionKey]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? (int)$result['id'] : null;
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Check migration status
     */
    public function checkStatus(): void
    {
        echo "==============================================\n";
        echo "Migration Status Check\n";
        echo "==============================================\n\n";
        
        try {
            // Check Superadmin user
            echo "Superadmin User:\n";
            echo "----------------------------------------------\n";
            $stmt = $this->db->query("SELECT id, username, email FROM users WHERE role_id = 1");
            $superadmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($superadmins)) {
                echo "✗ No Superadmin user found\n";
            } else {
                foreach ($superadmins as $user) {
                    echo "✓ {$user['username']} (ID: {$user['id']}, Email: {$user['email']})\n";
                }
            }
            
            echo "\n";
            
            // Check Admin users
            echo "Admin Users (role_id = 2):\n";
            echo "----------------------------------------------\n";
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE role_id = 2");
            $adminCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "Total: $adminCount user(s)\n";
            
            echo "\n";
            
            // Check Vendor users
            echo "Vendor Users (role_id = 5):\n";
            echo "----------------------------------------------\n";
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM users WHERE role_id = 5");
            $vendorCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "Total: $vendorCount user(s)\n";
            
            echo "\n";
            
            // Check unmigrated users
            echo "Unmigrated Users:\n";
            echo "----------------------------------------------\n";
            $stmt = $this->db->query("
                SELECT id, username, role FROM users 
                WHERE role_id IS NULL OR role_id = 0
            ");
            $unmigrated = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($unmigrated)) {
                echo "✓ All users migrated\n";
            } else {
                echo "✗ " . count($unmigrated) . " user(s) not migrated:\n";
                foreach ($unmigrated as $user) {
                    echo "  - {$user['username']} (ID: {$user['id']}, Role: {$user['role']})\n";
                }
            }
            
            echo "\n";
            
            // Check user_permissions
            echo "User Permission Overrides:\n";
            echo "----------------------------------------------\n";
            $stmt = $this->db->query("SELECT COUNT(*) as count FROM user_permissions");
            $permCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "Total: $permCount override(s)\n";
            
        } catch (PDOException $e) {
            echo "✗ Error checking status: " . $e->getMessage() . "\n";
        }
    }
    
    /**
     * Rollback migration
     */
    public function rollback(): bool
    {
        echo "==============================================\n";
        echo "Rolling Back User Migration\n";
        echo "==============================================\n\n";
        
        try {
            $this->db->beginTransaction();
            
            // Clear role_id for all users except Superadmin
            echo "Clearing role_id from users...\n";
            $stmt = $this->db->exec("UPDATE users SET role_id = NULL WHERE role_id != 1");
            echo "✓ Cleared role_id from $stmt user(s)\n";
            
            // Delete Superadmin user if created by migration
            echo "\nRemoving Superadmin user...\n";
            $stmt = $this->db->prepare("DELETE FROM users WHERE username = 'superadmin' AND role_id = 1");
            $stmt->execute();
            $count = $stmt->rowCount();
            if ($count > 0) {
                echo "✓ Removed Superadmin user\n";
            } else {
                echo "○ No Superadmin user to remove\n";
            }
            
            // Clear user_permissions
            echo "\nClearing user_permissions...\n";
            $stmt = $this->db->exec("DELETE FROM user_permissions");
            echo "✓ Cleared $stmt permission override(s)\n";
            
            $this->db->commit();
            
            echo "\n==============================================\n";
            echo "✓ Rollback completed successfully!\n";
            echo "==============================================\n";
            
            return true;
            
        } catch (PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            echo "✗ Rollback failed: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Print success summary
     */
    private function printSuccessSummary(): void
    {
        echo "==============================================\n";
        echo "✓ Migration completed successfully!\n";
        echo "==============================================\n\n";
        echo "Summary:\n";
        echo "  - Superadmin created: " . ($this->stats['superadmin_created'] ? 'Yes' : 'No') . "\n";
        echo "  - Admin users migrated: {$this->stats['admin_users_migrated']}\n";
        echo "  - Vendor users migrated: {$this->stats['vendor_users_migrated']}\n";
        echo "  - Permissions migrated: {$this->stats['permissions_migrated']}\n";
        echo "  - Total operations: {$this->successCount}\n";
        echo "\n";
    }
    
    /**
     * Print error summary
     */
    private function printErrorSummary(): void
    {
        echo "==============================================\n";
        echo "✗ Migration failed with errors. Changes rolled back.\n";
        echo "==============================================\n\n";
        echo "Errors (" . count($this->errors) . "):\n";
        foreach ($this->errors as $error) {
            echo "  - $error\n";
        }
        echo "\n";
    }
}

// Run migration if executed directly
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    $migration = new UserMigration();
    
    // Check for command line arguments
    $action = isset($argv[1]) ? $argv[1] : 'migrate';
    
    switch ($action) {
        case 'check':
            $migration->checkStatus();
            break;
        case 'rollback':
            $migration->rollback();
            break;
        case 'migrate':
        default:
            $migration->run();
            break;
    }
}
