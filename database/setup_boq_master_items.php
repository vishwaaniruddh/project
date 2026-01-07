<?php
/**
 * Setup BOQ Master Items Junction Table
 * This migration creates the boq_master_items table and adds description column to boq_master
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Setting up BOQ Master Items table...\n\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/create_boq_master_items.sql');
    
    // Remove SQL comments (lines starting with --)
    $lines = explode("\n", $sql);
    $cleanedLines = array_filter($lines, function($line) {
        $trimmed = trim($line);
        return !empty($trimmed) && !preg_match('/^--/', $trimmed);
    });
    $cleanedSQL = implode("\n", $cleanedLines);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $cleanedSQL)));
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($statements as $statement) {
        // Skip empty statements
        if (empty(trim($statement))) {
            continue;
        }
        
        try {
            $db->exec($statement);
            $preview = substr(preg_replace('/\s+/', ' ', $statement), 0, 60);
            echo "✓ Executed: {$preview}...\n";
            $successCount++;
        } catch (PDOException $e) {
            // Ignore error if column already exists (error code 1060)
            if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⚠ Column already exists, skipping...\n";
                $successCount++;
            } else {
                echo "✗ Error executing statement: " . $e->getMessage() . "\n";
                echo "Statement: " . substr($statement, 0, 100) . "...\n\n";
                $errorCount++;
            }
        }
    }
    
    echo "\n=== Migration Summary ===\n";
    echo "Successful statements: {$successCount}\n";
    echo "Failed statements: {$errorCount}\n";
    
    // Verify the setup
    echo "\n=== Verification ===\n";
    
    // Check if description column was added to boq_master
    echo "\n1. Checking boq_master table structure:\n";
    $stmt = $db->query("DESCRIBE boq_master");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $hasDescription = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'description') {
            $hasDescription = true;
            echo "   ✓ Description column exists ({$column['Type']})\n";
        }
    }
    if (!$hasDescription) {
        echo "   ✗ Description column not found\n";
    }
    
    // Check boq_master_items table structure
    echo "\n2. Checking boq_master_items table structure:\n";
    try {
        $stmt = $db->query("DESCRIBE boq_master_items");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "   Table columns:\n";
        foreach ($columns as $column) {
            echo "   - {$column['Field']} ({$column['Type']})\n";
        }
        
        // Check indexes
        echo "\n3. Checking indexes:\n";
        $stmt = $db->query("SHOW INDEX FROM boq_master_items");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $indexNames = array_unique(array_column($indexes, 'Key_name'));
        foreach ($indexNames as $indexName) {
            echo "   - {$indexName}\n";
        }
        
        // Check foreign keys
        echo "\n4. Checking foreign key constraints:\n";
        $stmt = $db->query("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'boq_master_items'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($foreignKeys as $fk) {
            echo "   - {$fk['CONSTRAINT_NAME']}: {$fk['COLUMN_NAME']} -> {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}\n";
        }
        
        // Check data count
        $stmt = $db->query("SELECT COUNT(*) as total FROM boq_master_items");
        $total = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\n5. Total records in boq_master_items: {$total['total']}\n";
        
    } catch (PDOException $e) {
        echo "   ✗ Error checking table: " . $e->getMessage() . "\n";
    }
    
    echo "\n✅ BOQ Master Items setup completed successfully!\n";
    echo "The boq_master_items junction table is now ready for use.\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up BOQ Master Items: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
