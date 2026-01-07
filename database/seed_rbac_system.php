<?php
/**
 * RBAC System - Seed Script
 * 
 * This script seeds default roles and permissions into the RBAC tables.
 * Run this after setup_rbac_system.php has created the tables.
 */

require_once __DIR__ . '/../config/database.php';

class RBACSeed
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
        echo "RBAC System Data Seeding\n";
        echo "==============================================\n\n";
        
        try {
            // Start transaction for rollback support
            $this->db->beginTransaction();
            
            // Step 1: Seed roles
            echo "Step 1: Seeding default roles...\n";
            $this->seedRoles();
            
            // Step 2: Seed permissions
            echo "\nStep 2: Seeding permissions...\n";
            $this->seedPermissions();
            
            // Commit transaction if no errors
            if (empty($this->errors)) {
                $this->db->commit();
                echo "\n==============================================\n";
                echo "✓ RBAC Seeding completed successfully!\n";
                echo "  Records inserted/updated: {$this->successCount}\n";
                echo "==============================================\n";
                return true;
            } else {
                // Rollback on errors
                $this->db->rollBack();
                echo "\n==============================================\n";
                echo "✗ Seeding failed with errors. Changes rolled back.\n";
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
     * Seed default roles
     */
    private function seedRoles(): void
    {
        $roles = [
            ['id' => 1, 'name' => 'superadmin', 'display_name' => 'Super Admin', 'description' => 'Full system access, can manage all users and permissions', 'is_system_role' => true],
            ['id' => 2, 'name' => 'admin', 'display_name' => 'Administrator', 'description' => 'Administrative access, can manage most operations', 'is_system_role' => true],
            ['id' => 3, 'name' => 'manager', 'display_name' => 'Manager', 'description' => 'Managerial access, can approve and oversee operations', 'is_system_role' => true],
            ['id' => 4, 'name' => 'engineer', 'display_name' => 'Engineer', 'description' => 'Technical access for surveys and installations', 'is_system_role' => true],
            ['id' => 5, 'name' => 'vendor', 'display_name' => 'Vendor', 'description' => 'External partner with limited site access', 'is_system_role' => true],
        ];
        
        $stmt = $this->db->prepare("
            INSERT INTO roles (id, name, display_name, description, is_system_role) 
            VALUES (:id, :name, :display_name, :description, :is_system_role)
            ON DUPLICATE KEY UPDATE 
                display_name = VALUES(display_name), 
                description = VALUES(description)
        ");
        
        foreach ($roles as $role) {
            try {
                $stmt->execute([
                    ':id' => $role['id'],
                    ':name' => $role['name'],
                    ':display_name' => $role['display_name'],
                    ':description' => $role['description'],
                    ':is_system_role' => $role['is_system_role'] ? 1 : 0
                ]);
                echo "✓ Role: {$role['display_name']}\n";
                $this->successCount++;
            } catch (PDOException $e) {
                $this->errors[] = "Failed to insert role {$role['name']}: " . $e->getMessage();
                echo "✗ Failed to insert role {$role['name']}: " . $e->getMessage() . "\n";
            }
        }
    }
    
    /**
     * Seed permissions
     */
    private function seedPermissions(): void
    {
        // Read SQL file and execute
        $sqlFile = __DIR__ . '/seed_rbac_data.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("SQL file not found: $sqlFile");
        }
        
        $sql = file_get_contents($sqlFile);
        if ($sql === false) {
            throw new Exception("Failed to read SQL file");
        }
        
        $this->executeSqlStatements($sql);
    }

    
    /**
     * Execute SQL statements from the SQL file
     */
    private function executeSqlStatements(string $sql): void
    {
        // Remove comments and split into statements
        $sql = $this->removeComments($sql);
        $statements = $this->splitStatements($sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement)) {
                continue;
            }
            
            $this->executeStatement($statement);
        }
    }
    
    /**
     * Remove SQL comments
     */
    private function removeComments(string $sql): string
    {
        // Remove single-line comments
        $sql = preg_replace('/--.*$/m', '', $sql);
        // Remove multi-line comments
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        return $sql;
    }
    
    /**
     * Split SQL into individual statements
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inString = false;
        $stringChar = '';
        
        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];
            
            if ($inString) {
                $current .= $char;
                if ($char === $stringChar && ($i === 0 || $sql[$i - 1] !== '\\')) {
                    $inString = false;
                }
            } else {
                if ($char === "'" || $char === '"') {
                    $inString = true;
                    $stringChar = $char;
                    $current .= $char;
                } elseif ($char === ';') {
                    $statements[] = $current;
                    $current = '';
                } else {
                    $current .= $char;
                }
            }
        }
        
        if (trim($current) !== '') {
            $statements[] = $current;
        }
        
        return $statements;
    }
    
    /**
     * Execute a single SQL statement
     */
    private function executeStatement(string $statement): void
    {
        try {
            $rowCount = $this->db->exec($statement);
            
            // Count affected rows for INSERT statements
            if (stripos($statement, 'INSERT') !== false) {
                // Extract module name for logging
                if (preg_match("/INTO\s+(\w+)/i", $statement, $matches)) {
                    $table = $matches[1];
                    echo "✓ Inserted/updated records in: $table\n";
                }
                $this->successCount++;
            }
        } catch (PDOException $e) {
            // Ignore duplicate key errors for ON DUPLICATE KEY UPDATE
            if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                $this->errors[] = "Statement failed: " . $e->getMessage();
                echo "✗ Statement failed: " . $e->getMessage() . "\n";
            }
        }
    }

    
    /**
     * Get statistics about seeded data
     */
    public function getStats(): array
    {
        $stats = [];
        
        try {
            // Count roles
            $stmt = $this->db->query("SELECT COUNT(*) FROM roles");
            $stats['roles'] = $stmt->fetchColumn();
            
            // Count permissions
            $stmt = $this->db->query("SELECT COUNT(*) FROM permissions");
            $stats['permissions'] = $stmt->fetchColumn();
            
            // Count permissions by module
            $stmt = $this->db->query("SELECT module, COUNT(*) as count FROM permissions GROUP BY module ORDER BY module");
            $stats['permissions_by_module'] = $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }
    
    /**
     * Clear all seeded data
     */
    public function clearData(): bool
    {
        echo "Clearing RBAC seed data...\n";
        
        try {
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            $this->db->exec("TRUNCATE TABLE role_permissions");
            echo "✓ Cleared role_permissions\n";
            
            $this->db->exec("TRUNCATE TABLE user_permissions");
            echo "✓ Cleared user_permissions\n";
            
            $this->db->exec("TRUNCATE TABLE permissions");
            echo "✓ Cleared permissions\n";
            
            $this->db->exec("TRUNCATE TABLE roles");
            echo "✓ Cleared roles\n";
            
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
            
            echo "\n✓ All seed data cleared!\n";
            return true;
            
        } catch (Exception $e) {
            echo "✗ Error clearing data: " . $e->getMessage() . "\n";
            return false;
        }
    }
}

// Run seed if executed directly
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    $seed = new RBACSeed();
    
    // Check for command line arguments
    $action = isset($argv[1]) ? $argv[1] : 'seed';
    
    switch ($action) {
        case 'clear':
            $seed->clearData();
            break;
        case 'reset':
            $seed->clearData();
            echo "\n";
            $seed->run();
            break;
        case 'stats':
            echo "RBAC Data Statistics\n";
            echo "==============================================\n\n";
            $stats = $seed->getStats();
            if (isset($stats['error'])) {
                echo "Error: " . $stats['error'] . "\n";
            } else {
                echo "Roles: " . $stats['roles'] . "\n";
                echo "Total Permissions: " . $stats['permissions'] . "\n";
                echo "\nPermissions by Module:\n";
                foreach ($stats['permissions_by_module'] as $row) {
                    echo "  - {$row['module']}: {$row['count']}\n";
                }
            }
            break;
        case 'seed':
        default:
            $seed->run();
            break;
    }
}
