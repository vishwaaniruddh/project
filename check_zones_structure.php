<?php
require_once 'config/database.php';

echo "<h2>Database Structure Check</h2>";

try {
    $db = Database::getInstance()->getConnection();
    
    echo "<h3>Zones Table Structure:</h3>";
    $stmt = $db->query('DESCRIBE zones');
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']} ({$row['Type']})<br>";
    }
    
    echo "<h3>Sample Zones:</h3>";
    $stmt = $db->query('SELECT id, name FROM zones WHERE status = "active" LIMIT 10');
    while ($row = $stmt->fetch()) {
        echo "- {$row['name']} (ID: {$row['id']})<br>";
    }
    
    echo "<h3>States Table Structure:</h3>";
    $stmt = $db->query('DESCRIBE states');
    while ($row = $stmt->fetch()) {
        echo "- {$row['Field']} ({$row['Type']})<br>";
    }
    
    echo "<h3>Sample States with Zone Info:</h3>";
    $stmt = $db->query('SELECT s.id, s.name, s.zone_id, z.name as zone_name FROM states s LEFT JOIN zones z ON s.zone_id = z.id WHERE s.status = "active" LIMIT 10');
    while ($row = $stmt->fetch()) {
        echo "- {$row['name']} (Zone: {$row['zone_name']})<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>