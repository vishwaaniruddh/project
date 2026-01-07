<?php
require_once __DIR__ . '/../config/database.php';

$db = Database::getInstance()->getConnection();
$stmt = $db->query('DESCRIBE users');

echo "Users table schema:\n";
echo str_repeat('-', 60) . "\n";
printf("%-20s %-20s %-10s\n", "Field", "Type", "Null");
echo str_repeat('-', 60) . "\n";

while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    printf("%-20s %-20s %-10s\n", $row['Field'], $row['Type'], $row['Null']);
}
