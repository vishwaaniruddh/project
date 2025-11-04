<?php
require_once 'config/database.php';

echo "<h2>Existing Cities Check</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    // Get existing cities with their states
    echo "<h3>Available Cities:</h3>";
    $stmt = $db->query("
        SELECT c.name as city_name, s.name as state_name, co.name as country_name 
        FROM cities c 
        JOIN states s ON c.state_id = s.id 
        JOIN countries co ON s.country_id = co.id 
        WHERE c.status = 'active' 
        ORDER BY co.name, s.name, c.name 
        LIMIT 20
    ");
    
    while ($row = $stmt->fetch()) {
        echo "- {$row['city_name']}, {$row['state_name']}, {$row['country_name']}<br>";
    }
    
    echo "<h3>Available Customers:</h3>";
    $stmt = $db->query("SELECT name FROM customers WHERE status = 'active' ORDER BY name LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "- {$row['name']}<br>";
    }
    
    echo "<h3>Available Banks:</h3>";
    $stmt = $db->query("SELECT name FROM banks WHERE status = 'active' ORDER BY name LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "- {$row['name']}<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>