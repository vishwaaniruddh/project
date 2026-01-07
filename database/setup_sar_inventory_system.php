<?php
/**
 * SAR Inventory Management System - Database Setup Script
 * 
 * This script creates all sar_inv_ prefixed tables for the new inventory system.
 * It includes proper error handling and rollback support.
 */

require_once __DIR__ . '/../config/database.php';

class SarInventorySetup
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
        echo "SAR Inventory Management System Setup\n";
        echo "==============================================\n\n";
        
        try {
            // Start transaction for rollback support
            $this->db->beginTransaction();
            
            // Read SQL file
            $sqlFile = __DIR__ . '/create_sar_inventory_tables.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("SQL file not found: $sqlFile");
            }
            
            $sql = file_get_contents($sqlFile);
            if ($sql === false) {
                throw new Exception("Failed to read SQL file");
            }
            
            // Execute SQL statements
            $this->executeSqlStatements($sql);
            
            // Commit transaction if no errors
            if (empty($this->errors)) {
                $this->db->commit();
                echo "\n==============================================\n";
                echo "✓ Setup completed successfully!\n";
                echo "  Tables created: {$this->successCount}\n";
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
     * Drop all sar_inv_ tables (for cleanup/reset)
     */
    public function dropAllTables(): bool
    {
        echo "Dropping all SAR Inventory tables...\n";
        
        $tables = [
            'sar_inv_audit_log',
            'sar_inv_material_requests',
            'sar_inv_material_masters',
            'sar_inv_repairs',
            'sar_inv_item_history',
            'sar_inv_asset_history',
            'sar_inv_assets',
            'sar_inv_transfer_items',
            'sar_inv_transfers',
            'sar_inv_dispatch_items',
            'sar_inv_dispatches',
            'sar_inv_stock_entries',
            'sar_inv_stock',
            'sar_inv_products',
            'sar_inv_product_categories',
            'sar_inv_warehouses'
        ];
        
        try {
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 0');
            
            foreach ($tables as $table) {
                try {
                    $this->db->exec("DROP TABLE IF EXISTS `$table`");
                    echo "✓ Dropped table: $table\n";
                } catch (PDOException $e) {
                    echo "✗ Failed to drop $table: " . $e->getMessage() . "\n";
                }
            }
            
            $this->db->exec('SET FOREIGN_KEY_CHECKS = 1');
            echo "\n✓ All tables dropped successfully!\n";
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
            'sar_inv_warehouses',
            'sar_inv_product_categories',
            'sar_inv_products',
            'sar_inv_stock',
            'sar_inv_stock_entries',
            'sar_inv_dispatches',
            'sar_inv_dispatch_items',
            'sar_inv_transfers',
            'sar_inv_transfer_items',
            'sar_inv_assets',
            'sar_inv_asset_history',
            'sar_inv_item_history',
            'sar_inv_repairs',
            'sar_inv_material_masters',
            'sar_inv_material_requests',
            'sar_inv_audit_log'
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
        
        return $status;
    }
}

// Run setup if executed directly
if (php_sapi_name() === 'cli' || !isset($_SERVER['HTTP_HOST'])) {
    $setup = new SarInventorySetup();
    
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
            echo "Checking SAR Inventory tables...\n\n";
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
