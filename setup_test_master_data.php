<?php
require_once 'config/database.php';

echo "<h2>Setting up Test Master Data</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Get state IDs
    $stmt = $db->prepare("SELECT id FROM states WHERE name = 'Maharashtra' AND status = 'active'");
    $stmt->execute();
    $maharashtraId = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT id FROM states WHERE name = 'Karnataka' AND status = 'active'");
    $stmt->execute();
    $karnatakaId = $stmt->fetchColumn();
    
    echo "Maharashtra ID: $maharashtraId<br>";
    echo "Karnataka ID: $karnatakaId<br>";
    
    // Create cities if they don't exist
    $cities = [
        ['name' => 'Mumbai', 'state_id' => $maharashtraId],
        ['name' => 'Bangalore', 'state_id' => $karnatakaId]
    ];
    
    foreach ($cities as $city) {
        $stmt = $db->prepare("SELECT id FROM cities WHERE name = ? AND state_id = ?");
        $stmt->execute([$city['name'], $city['state_id']]);
        
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO cities (name, state_id, status, created_at) VALUES (?, ?, 'active', NOW())");
            if ($stmt->execute([$city['name'], $city['state_id']])) {
                echo "✅ Created city: {$city['name']}<br>";
            } else {
                echo "❌ Failed to create city: {$city['name']}<br>";
            }
        } else {
            echo "ℹ️ City already exists: {$city['name']}<br>";
        }
    }
    
    // Create customers if they don't exist
    $customers = ['Customer A', 'Customer B'];
    foreach ($customers as $customer) {
        $stmt = $db->prepare("SELECT id FROM customers WHERE name = ?");
        $stmt->execute([$customer]);
        
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO customers (name, status, created_at) VALUES (?, 'active', NOW())");
            if ($stmt->execute([$customer])) {
                echo "✅ Created customer: $customer<br>";
            } else {
                echo "❌ Failed to create customer: $customer<br>";
            }
        } else {
            echo "ℹ️ Customer already exists: $customer<br>";
        }
    }
    
    // Create banks if they don't exist
    $banks = ['Axis Bank', 'HDFC Bank', 'SBI Bank'];
    foreach ($banks as $bank) {
        $stmt = $db->prepare("SELECT id FROM banks WHERE name = ?");
        $stmt->execute([$bank]);
        
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO banks (name, status, created_at) VALUES (?, 'active', NOW())");
            if ($stmt->execute([$bank])) {
                echo "✅ Created bank: $bank<br>";
            } else {
                echo "❌ Failed to create bank: $bank<br>";
            }
        } else {
            echo "ℹ️ Bank already exists: $bank<br>";
        }
    }
    
    echo "<h3>Final Verification:</h3>";
    
    // Verify all required data exists
    $verifications = [
        ['table' => 'countries', 'name' => 'India'],
        ['table' => 'states', 'name' => 'Maharashtra'],
        ['table' => 'states', 'name' => 'Karnataka'],
        ['table' => 'cities', 'name' => 'Mumbai'],
        ['table' => 'cities', 'name' => 'Bangalore'],
        ['table' => 'customers', 'name' => 'Customer A'],
        ['table' => 'banks', 'name' => 'Axis Bank']
    ];
    
    $allGood = true;
    foreach ($verifications as $check) {
        $stmt = $db->prepare("SELECT id FROM {$check['table']} WHERE name = ? AND status = 'active'");
        $stmt->execute([$check['name']]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo "✅ {$check['table']}: {$check['name']}<br>";
        } else {
            echo "❌ {$check['table']}: {$check['name']} - STILL MISSING<br>";
            $allGood = false;
        }
    }
    
    if ($allGood) {
        echo "<h3 style='color: green;'>🎉 All master data is ready for bulk upload test!</h3>";
    } else {
        echo "<h3 style='color: red;'>❌ Some master data is still missing</h3>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>