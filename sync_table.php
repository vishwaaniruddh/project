<?php
$localConn = new mysqli('localhost', 'reporting', 'reporting', 'site_installation_management');
$serverConn = new mysqli('193.203.184.112', 'u444388293_karvy_project', 'AVav@@2025', 'u444388293_karvy_project');

if (!$localConn || !$serverConn) {
    die("DB connection failed.");
}

$table = $_GET['table'] ?? '';
if (empty($table)) {
    die("No table specified.");
}

// Truncate local table
$localConn->query("TRUNCATE TABLE `$table`");

// Fetch server data
$result = $serverConn->query("SELECT * FROM `$table`");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $columns = array_keys($row);
        $values = array_values($row);
        $colList = "`" . implode("`, `", $columns) . "`";
        $valList = "'" . implode("', '", array_map([$localConn, 'real_escape_string'], $values)) . "'";
        $localConn->query("INSERT INTO `$table` ($colList) VALUES ($valList)");
    }
    echo "Sync complete for table: $table.";
} else {
    echo "Failed to fetch data from server.";
}
