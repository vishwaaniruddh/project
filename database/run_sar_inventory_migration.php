<?php
/**
 * SAR Inventory Tables Migration Script
 * Run this script to create all SAR Inventory tables
 * 
 * Usage: php database/run_sar_inventory_migration.php
 * Or access via browser: http://localhost/project/database/run_sar_inventory_migration.php
 */

require_once __DIR__ . '/../config/database.php';

echo "<pre>\n";
echo "===========================================\n";
echo "SAR Inventory Tables Migration\n";
echo "===========================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    echo "✓ Database connection successful\n\n";
    
    // Read the SQL file
    $sqlFile = __DIR__ . '/create_sar_inventory_tables.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    echo "✓ SQL file loaded\n\n";
    
    // Split SQL into individual statements
    // Remove comments and split by semicolon
    $statements = [];
    $lines = explode("\n", $sql);
    $currentStatement = '';
    
    foreach ($lines as $line) {
        $trimmedLine = trim($line);
        
        // Skip empty lines and comments
        if (empty($trimmedLine) || strpos($trimmedLine, '--') === 0) {
            continue;
        }
        
        $currentStatement .= $line . "\n";
        
        // Check if statement is complete (ends with semicolon)
        if (substr($trimmedLine, -1) === ';') {
            $statements[] = trim($currentStatement);
            $currentStatement = '';
        }
    }
    
    echo "Found " . count($statements) . " SQL statements to execute\n\n";
    echo "-------------------------------------------\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $index => $statement) {
        if (empty(trim($statement))) {
            continue;
        }
        
        // Extract table name for display
        $tableName = 'Unknown';
        if (preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches)) {
            $tableName = $matches[1];
        }
        
        try {
            $db->exec($statement);
            echo "✓ Created/verified table: $tableName\n";
            $successCount++;
        } catch (PDOException $e) {
            // Check if it's just a "table already exists" error
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "○ Table already exists: $tableName\n";
                $successCount++;
            } else {
                echo "✗ Error with $tableName: " . $e->getMessage() . "\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n-------------------------------------------\n";
    echo "Migration Complete!\n";
    echo "Success: $successCount | Errors: $errorCount\n";
    echo "===========================================\n";
    
    // List all SAR inventory tables
    echo "\nVerifying SAR Inventory Tables:\n";
    echo "-------------------------------------------\n";
    
    $stmt = $db->query("SHOW TABLES LIKE 'sar_inv_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($tables)) {
        echo "⚠ No SAR inventory tables found!\n";
    } else {
        foreach ($tables as $table) {
            $countStmt = $db->query("SELECT COUNT(*) FROM `$table`");
            $count = $countStmt->fetchColumn();
            echo "  • $table ($count rows)\n";
        }
        echo "\nTotal: " . count($tables) . " tables created\n";
    }
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

echo "</pre>\n";
?>
