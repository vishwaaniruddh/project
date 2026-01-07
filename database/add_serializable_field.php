<?php
/**
 * Add is_serializable field to products table
 */
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

echo "Adding is_serializable field to sar_inv_products...\n";

try {
    // Check if column exists
    $stmt = $db->query("SHOW COLUMNS FROM sar_inv_products LIKE 'is_serializable'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE sar_inv_products ADD COLUMN is_serializable TINYINT(1) DEFAULT 0 AFTER minimum_stock_level");
        echo "Added is_serializable column\n";
    } else {
        echo "Column already exists\n";
    }
    
    // Add warehouse_id to assets table if not exists
    $stmt = $db->query("SHOW COLUMNS FROM sar_inv_assets LIKE 'warehouse_id'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE sar_inv_assets ADD COLUMN warehouse_id INT(11) AFTER current_location_id");
        echo "Added warehouse_id to assets\n";
    }
    
    // Add notes to assets
    $stmt = $db->query("SHOW COLUMNS FROM sar_inv_assets LIKE 'notes'");
    if (!$stmt->fetch()) {
        $db->exec("ALTER TABLE sar_inv_assets ADD COLUMN notes TEXT AFTER warranty_expiry");
        echo "Added notes to assets\n";
    }
    
    echo "Done!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
