<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();

$tables = ['roles', 'permissions', 'role_permissions', 'user_permissions'];

echo "Checking RBAC tables:\n";
echo str_repeat('-', 60) . "\n";

foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "✓ $table exists with $count records\n";
    } catch (Exception $e) {
        echo "✗ $table does not exist\n";
    }
}

echo "\nChecking users.role_id column:\n";
try {
    $stmt = $db->query("SELECT role_id FROM users LIMIT 1");
    echo "✓ users.role_id column exists\n";
} catch (Exception $e) {
    echo "✗ users.role_id column does not exist\n";
}
