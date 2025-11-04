<?php
require_once 'config/database.php';

echo "<h2>Master Data Check</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h3>Countries:</h3>";
    $stmt = $db->query("SELECT name FROM countries WHERE status = 'active' ORDER BY name LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "- " . $row['name'] . "<br>";
    }
    
    echo "<h3>States:</h3>";
    $stmt = $db->query("SELECT name FROM states WHERE status = 'active' ORDER BY name LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "- " . $row['name'] . "<br>";
    }
    
    echo "<h3>Cities:</h3>";
    $stmt = $db->query("SELECT name FROM cities WHERE status = 'active' ORDER BY name LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "- " . $row['name'] . "<br>";
    }
    
    echo "<h3>Customers:</h3>";
    $stmt = $db->query("SELECT name FROM customers WHERE status = 'active' ORDER BY name LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "- " . $row['name'] . "<br>";
    }
    
    echo "<h3>Banks:</h3>";
    $stmt = $db->query("SELECT name FROM banks WHERE status = 'active' ORDER BY name LIMIT 10");
    while ($row = $stmt->fetch()) {
        echo "- " . $row['name'] . "<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>