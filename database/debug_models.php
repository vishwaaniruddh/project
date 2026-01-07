<?php
/**
 * Debug script to identify model loading issues
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>\n";
echo "===========================================\n";
echo "SAR Inventory Model Loading Debug\n";
echo "===========================================\n\n";

$modelsDir = __DIR__ . '/../models/';

// Test loading each model in order
$models = [
    'BaseModel.php',
    'SarInvBaseModel.php',
    'SarInvWarehouse.php',
    'SarInvProductCategory.php',
    'SarInvProduct.php',
    'SarInvStock.php',
    'SarInvDispatch.php',
    'SarInvTransfer.php',
    'SarInvAsset.php',
    'SarInvRepair.php',
    'SarInvMaterialMaster.php',
    'SarInvMaterialRequest.php',
    'SarInvItemHistory.php',
    'SarInvAuditLog.php'
];

foreach ($models as $model) {
    $path = $modelsDir . $model;
    echo "Loading: $model ... ";
    
    if (!file_exists($path)) {
        echo "FILE NOT FOUND!\n";
        continue;
    }
    
    try {
        require_once $path;
        echo "OK\n";
    } catch (Error $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "  File: " . $e->getFile() . "\n";
        echo "  Line: " . $e->getLine() . "\n";
    } catch (Exception $e) {
        echo "EXCEPTION: " . $e->getMessage() . "\n";
    }
}

echo "\n-------------------------------------------\n";
echo "Checking if classes exist:\n";
echo "-------------------------------------------\n";

$classes = [
    'BaseModel',
    'SarInvBaseModel',
    'SarInvWarehouse',
    'SarInvProductCategory',
    'SarInvProduct',
    'SarInvStock',
    'SarInvDispatch',
    'SarInvTransfer',
    'SarInvAsset',
    'SarInvRepair',
    'SarInvMaterialMaster',
    'SarInvMaterialRequest',
    'SarInvItemHistory',
    'SarInvAuditLog'
];

foreach ($classes as $class) {
    echo "$class: " . (class_exists($class) ? "EXISTS" : "NOT FOUND") . "\n";
}

echo "\n-------------------------------------------\n";
echo "Testing database connection:\n";
echo "-------------------------------------------\n";

try {
    require_once __DIR__ . '/../config/database.php';
    $db = Database::getInstance()->getConnection();
    echo "Database connection: OK\n";
    
    // Check if tables exist
    $stmt = $db->query("SHOW TABLES LIKE 'sar_inv_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\nSAR Inventory tables found: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
    
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}

echo "\n</pre>\n";
?>
