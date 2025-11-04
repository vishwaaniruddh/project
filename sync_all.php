<?php
// Note: You must ensure your PHP environment's mysqli is configured to throw
// exceptions on errors for this catch block to work perfectly with mysqli.
// If not, you may need to manually set mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$localConn = new mysqli('localhost', 'reporting', 'reporting', 'site_installation_management');
$serverConn = new mysqli('193.203.184.112', 'u444388293_karvy_project', 'AVav@@2025', 'u444388293_karvy_project');

if ($localConn->connect_errno || $serverConn->connect_errno) {
    die("DB connection failed: " . $localConn->connect_error . " / " . $serverConn->connect_error);
}

function getTables($conn) {
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }
    return $tables;
}

$localTables = getTables($localConn);
$serverTables = getTables($serverConn);
$commonTables = array_intersect($localTables, $serverTables);

// Disable foreign key checks
$localConn->query("SET foreign_key_checks = 0");

foreach ($commonTables as $table) {
    echo "<strong>Syncing table:</strong> $table<br>";

    // --- START OF THE FIX ---
    try {
        // Attempt the TRUNCATE query
        if (!$localConn->query("TRUNCATE TABLE `$table`")) {
            // Check for standard failure
            echo "<span style='color:red;'>⛔ Failed to truncate local table '$table': " . $localConn->error . "</span><br><br>";
            continue; // Skip and move to next table
        }
    } catch (mysqli_sql_exception $e) {
        // Catch the FATAL 'Table doesn't exist' or other MySQL exceptions
        echo "<span style='color:red;'>⛔ Failed to truncate local table '$table' (Exception): " . $e->getMessage() . "</span><br><br>";
        continue; // Skip and move to next table
    }
    // --- END OF THE FIX ---

    $result = $serverConn->query("SELECT * FROM `$table`");
    
    if (!$result) {
        echo "<span style='color:red;'>⛔ Failed to fetch remote data for '$table': " . $serverConn->error . "</span><br><br>";
        continue;
    }

    $rowCount = 0;
    while ($row = $result->fetch_assoc()) {
        $columns = array_keys($row);
        $colList = "`" . implode("`, `", $columns) . "`";

        $vals = [];
        foreach ($row as $val) {
            if ($val === null || $val === '') {
                $vals[] = "NULL";
            } else {
                $vals[] = "'" . $localConn->real_escape_string($val) . "'";
            }
        }
        $valList = implode(", ", $vals);

        $insertSQL = "INSERT INTO `$table` ($colList) VALUES ($valList)";
        if (!$localConn->query($insertSQL)) {
            echo "<span style='color:orange;'>⚠️ Failed to insert row in '$table': " . $localConn->error . "</span><br>";
        } else {
            $rowCount++;
        }
    }

    echo "<span style='color:green;'>✅ Completed $table – $rowCount rows synced</span><br><br>";
}

// Re-enable FK checks
$localConn->query("SET foreign_key_checks = 1");

echo "<h3>✅ Sync complete for all existing tables.</h3>";