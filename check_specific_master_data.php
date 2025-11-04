<?php
require_once 'config/database.php';

echo "<h2>Specific Master Data Check</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Check specific entries needed for our test data
    $checks = [
        ['table' => 'countries', 'name' => 'India'],
        ['table' => 'states', 'name' => 'Maharashtra'],
        ['table' => 'states', 'name' => 'Karnataka'],
        ['table' => 'cities', 'name' => 'Mumbai'],
        ['table' => 'cities', 'name' => 'Bangalore'],
        ['table' => 'customers', 'name' => 'Customer A'],
        ['table' => 'banks', 'name' => 'Axis Bank']
    ];
    
    foreach ($checks as $check) {
        $stmt = $db->prepare("SELECT id, name FROM {$check['table']} WHERE name = ? AND status = 'active'");
        $stmt->execute([$check['name']]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo "✅ {$check['table']}: {$check['name']} (ID: {$result['id']})<br>";
        } else {
            echo "❌ {$check['table']}: {$check['name']} - NOT FOUND<br>";
        }
    }
    
    // Check if Customer A and Axis Bank exist, if not create them
    echo "<h3>Creating Missing Master Data:</h3>";
    
    // Check/Create Customer A
    $stmt = $db->prepare("SELECT id FROM customers WHERE name = 'Customer A'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO customers (name, status, created_at) VALUES ('Customer A', 'active', NOW())");
        if ($stmt->execute()) {
            echo "✅ Created Customer A<br>";
        } else {
            echo "❌ Failed to create Customer A<br>";
        }
    }
    
    // Check/Create Axis Bank
    $stmt = $db->prepare("SELECT id FROM banks WHERE name = 'Axis Bank'");
    $stmt->execute();
    if (!$stmt->fetch()) {
        $stmt = $db->prepare("INSERT INTO banks (name, status, created_at) VALUES ('Axis Bank', 'active', NOW())");
        if ($stmt->execute()) {
            echo "✅ Created Axis Bank<br>";
        } else {
            echo "❌ Failed to create Axis Bank<br>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>