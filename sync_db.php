<?php
// Connection details for local and server DB
$localConn = new mysqli('localhost', 'reporting', 'reporting', 'site_installation_management');
$serverConn = new mysqli('193.203.184.112', 'u444388293_karvy_project', 'AVav@@2025', 'u444388293_karvy_project');

// Fetch table lists
$localTables = getTables($localConn);
$serverTables = getTables($serverConn);

$matchingTables = array_intersect($localTables, $serverTables);
$uniqueToLocal = array_diff($localTables, $serverTables);
$uniqueToServer = array_diff($serverTables, $localTables);

function getTables($conn) {
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    return $tables;
}

function getColumns($conn, $table) {
    $columns = [];
    $result = $conn->query("SHOW FULL COLUMNS FROM `$table`");
    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = [
            'Type' => $row['Type'],
            'Collation' => $row['Collation'] ?? 'NULL'
        ];
    }
    return $columns;
}

function compareColumns($table, $localConn, $serverConn) {
    $cols1 = getColumns($localConn, $table);
    $cols2 = getColumns($serverConn, $table);

    $typeMismatch = [];
    $collationMismatch = [];

    foreach ($cols1 as $col => $attr1) {
        if (isset($cols2[$col])) {
            if ($attr1['Type'] !== $cols2[$col]['Type']) {
                $typeMismatch[$col] = [
                    'Local' => $attr1['Type'], 
                    'Server' => $cols2[$col]['Type']
                ];
            }
            if ($attr1['Collation'] !== $cols2[$col]['Collation']) {
                $collationMismatch[$col] = [
                    'Local' => $attr1['Collation'], 
                    'Server' => $cols2[$col]['Collation']
                ];
            }
        }
    }

    return [
        'onlyLocal' => array_diff_key($cols1, $cols2),
        'onlyServer' => array_diff_key($cols2, $cols1),
        'typeMismatch' => $typeMismatch,
        'collationMismatch' => $collationMismatch,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>DB Difference Viewer</title>
<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f8f9fa; }
    h1, h2 { color: #333; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 30px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
    th, td { padding: 10px 15px; border-bottom: 1px solid #ddd; text-align: left; }
    th { background: #343a40; color: white; }
    .highlight { background: #ffeeba; }
    .section-title { background: #007bff; color: #fff; padding: 8px 10px; font-weight: bold; }
    .cell-danger { background-color: #f8d7da; }
    .cell-warning { background-color: #fff3cd; }
    .cell-success { background-color: #d4edda; }
</style>
</head>
<body>

<h1>Database Schema Comparison</h1>

<div class="section-title">Tables Only in Local DB</div>
<table>
    <tr><th>Table Name</th></tr>
    <?php foreach ($uniqueToLocal as $table): ?>
        <tr><td><?php echo $table; ?></td></tr>
    <?php endforeach; ?>
</table>

<div class="section-title">Tables Only in Server DB</div>
<table>
    <tr><th>Table Name</th></tr>
    <?php foreach ($uniqueToServer as $table): ?>
        <tr><td><?php echo $table; ?></td></tr>
    <?php endforeach; ?>
</table>

<div class="section-title">Differences in Matching Tables</div>
<table>
    <tr>
        <th>Table Name</th>
        <th>Columns Only in Local</th>
        <th>Columns Only in Server</th>
        <th>Column Type Mismatches</th>
        <th>Collation Mismatches</th>
    </tr>
    <?php foreach ($matchingTables as $table): ?>
        <?php
        $colDiff = compareColumns($table, $localConn, $serverConn);
        if (empty($colDiff['onlyLocal']) && empty($colDiff['onlyServer']) && empty($colDiff['typeMismatch']) && empty($colDiff['collationMismatch'])) {
            continue;
        }
        ?>
        <tr class="highlight">
            <td><?php echo $table; ?></td>
            <td class="cell-warning">
                <?php echo implode('<br>', array_keys($colDiff['onlyLocal'])) ?: 'None'; ?>
            </td>
            <td class="cell-danger">
                <?php echo implode('<br>', array_keys($colDiff['onlyServer'])) ?: 'None'; ?>
            </td>
            <td class="cell-warning">
                <?php 
                if (!empty($colDiff['typeMismatch'])) {
                    foreach ($colDiff['typeMismatch'] as $col => $types) {
                        echo "$col (Local: {$types['Local']}, Server: {$types['Server']})<br>";
                    }
                } else {
                    echo "None";
                }
                ?>
            </td>
            <td class="cell-warning">
                <?php 
                if (!empty($colDiff['collationMismatch'])) {
                    foreach ($colDiff['collationMismatch'] as $col => $collations) {
                        echo "$col (Local: {$collations['Local']}, Server: {$collations['Server']})<br>";
                    }
                } else {
                    echo "None";
                }
                ?>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
