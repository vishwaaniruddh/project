<?php
/**
 * RBAC (Role-Based Access Control) System - Database Setup Script
 * 
 * This script creates all RBAC tables and adds role_id column to users table.
 * It includes proper error handling and rollback support.
 */

require_once __DIR__ . '/../config/database.php';

class RBACSetup
{
    private $db;
    private $errors = [];
    private $successCount = 0;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Run the complete setup process
     */
    public function run(): bool
    {
        echo "==============================================\n";
        echo "RBAC System Database Setup\n";
        echo "==============================================\n\n";
        
        try {
            // Start transaction for rollback support
            $this->db->beginTransaction();
            
            // Step 1: Create RBAC tables from SQL file
            echo "Step 1: Creating RBAC tables...\n";
            $this->createRBACTables();
            
            // Step 2: Add role_id column to users table
            echo "\nStep 2: Adding role_id column to users table...\n";
            $this->addRoleIdToUsers();
            
            // Commit transaction if no errors
            if (empty($this->errors)) {
                $this->db->commit();
                echo "\n==============================================\n";
                echo "✓ RBAC Setup completed successfully!\n";
                echo "  Operations completed: {$this->successCount}\n";
                echo "==============================================\n";
                return true;
            } else {
                // Rollback on errors
                $this->db->rollBack();
                echo "\n==============================================\n";
                echo "✗ Setup failed with errors. Changes rolled back.\n";
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
     * Create RBAC tables from SQL file
     */
    private function createRBACTables(): void
    {
        $sqlFile = __DIR__ . '/create_rbac_tables.sql';
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
     * Add role_id column to users table with foreign key constraint
     */
    private function addRoleIdToUsers(): void
    {
        // Check if role_id column already exists
        $stmt = $this->db->query("SHOW COLUMNS FROM users LIKE 'role_id'");
        if ($stmt->rowCount() > 0) {
            echo "○ Column role_id already exists in users table\n";
            $this->successCount++;
            return;
        }
        
        try {
            // Add role_id column after role column
            $this->db->exec("ALTER TABLE users ADD COLUMN role_id INT NULL AFTER role");
            echo "✓ Added role_id column to users table\n";
            $this->successCount++;
            
            // Add foreign key constraint
            $this->db->exec("ALTER TABLE users ADD CONSTRAINT fk_users_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE RESTRICT");
            echo "✓ Added foreign key constraint for role_id\n";
            $this->successCount++;
            
            // Add index for role_id
            $this->db->exec("ALTER TABLE users ADD INDEX idx_role_id (role_id)");
            echo "✓ Added index for role_id column\n";
            $this->successCount++;
            
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "○ Column or constraint already exists\n";
                $this->successCount++;
            } else {
                $this->errors[] = "Failed to modify users table: " . $e->getMessage();
                echo "✗ Failed to modify users table: " . $e->getMessage() . "\n";
            }
        }
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
        // Extract table name for logging
        $tableName = $this->extractTableName($statement);
        
        try {
            $this->db->exec($statement);
            $this->successCount++;
            
            if ($tableName) {
                echo "✓ Created table: $tableName\n";
            } else {
                echo "✓ Executed statement successfully\n";
            }
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') !== false) {
                if ($tableName) {
                    echo "○ Table already exists: $tableName\n";
                }
                $this->successCount++;
            } else {
                $errorMsg = $tableName 
                    ? "Failed to create table $tableName: " . $e->getMessage()
                    : "Statement failed: " . $e->getMessage();
                $this->errors[] = $errorMsg;
                echo "✗ $errorMsg\n";
            }
        }
    }
    
    /**
     * Extract table name from CREATE TABLE statement
     */
    private function extractTableName(string $statement): ?string
    {
        if (preg_match('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?(\w+)[`"]?/i', $statement, $matches)) {
            return $matches[1];
        }
        return null;
    }

    
    /**
     * Drop all RBAC tables (for cleanup/reset)
     */
    public function dropAllTables(): bool
    {
        echo "Dropping all RBAC tables...\n";
        
        $tables = [
            'user_permissions',
            'role_permissions',
            'refresh_tokens',
            'permissions',
            'roles'
        ];
        
        try {
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            // First remove the foreign key and column from users table
            try {
                $this->db->exec("ALTER TABLE users DROP FOREIGN KEY fk_users_role_id");
                echo "✓ Dropped foreign key fk_users_role_id\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "Can't DROP") === false) {
                    echo "○ Foreign key fk_users_role_id doesn't exist\n";
                }
            }
            
            try {
                $this->db->exec("ALTER TABLE users DROP INDEX idx_role_id");
                echo "✓ Dropped index idx_role_id\n";
            } catch (PDOException $e) {
                // Index might not exist
            }
            
            try {
                $this->db->exec("ALTER TABLE users DROP COLUMN role_id");
                echo "✓ Dropped column role_id from users table\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "Can't DROP") === false) {
                    echo "○ Column role_id doesn't exist\n";
                }
            }
            
            foreach ($tables as $table) {
                try {
                    $this->db->exec("DROP TABLE IF EXISTS `$table`");
                    echo "✓ Dropped table: $table\n";
                } catch (PDOException $e) {
                    echo "✗ Failed to drop $table: " . $e->getMessage() . "\n";
                }
            }
            
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
            echo "\n✓ All RBAC tables dropped successfully!\n";
            return true;
            
        } catch (Exception $e) {
            echo "✗ Error dropping tables: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Check if tables exist
     */
    public function checkTables(): array
    {
        $tables = [
            'roles',
            'permissions',
            'role_permissions',
            'user_permissions',
            'refresh_tokens'
        ];
        
        $status = [];
        
        foreach ($tables as $table) {
            try {
                $result = $this->db->query("SHOW TABLES LIKE '$table'");
                $status[$table] = $result->rowCount() > 0;
            } catch (PDOException $e) {
                $status[$table] = false;
            }
        }
        
        // Check role_id column in users table
        try {
            $result = $this->db->query("SHOW COLUMNS FROM users LIKE 'role_id'");
            $status['users.role_id'] = $result->rowCount() > 0;
        } catch (PDOException $e) {
            $status['users.role_id'] = false;
        }
        
        return $status;
    }
}

// Run setup if executed directly
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    $setup = new RBACSetup();
    
    // Check for command line arguments
    $action = isset($argv[1]) ? $argv[1] : 'setup';
    
    switch ($action) {
        case 'drop':
            $setup->dropAllTables();
            break;
        case 'reset':
            $setup->dropAllTables();
            echo "\n";
            $setup->run();
            break;
        case 'check':
            echo "Checking RBAC tables...\n\n";
            $status = $setup->checkTables();
            foreach ($status as $table => $exists) {
                echo ($exists ? "✓" : "✗") . " $table\n";
            }
            break;
        case 'setup':
        default:
            $setup->run();
            break;
    }
}
