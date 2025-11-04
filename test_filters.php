<?php
require_once 'controllers/SitesController.php';

echo "<h2>Filter Test</h2>";

echo "<h3>Current GET Parameters:</h3>";
echo "<pre>" . print_r($_GET, true) . "</pre>";

echo "<h3>Testing SitesController:</h3>";
try {
    $controller = new SitesController();
    $data = $controller->index();
    
    echo "✅ SitesController loaded successfully<br>";
    echo "Search term: '" . $data['search'] . "'<br>";
    echo "Active filters:<br>";
    foreach ($data['filters'] as $key => $value) {
        if (!empty($value)) {
            echo "- $key: $value<br>";
        }
    }
    
    echo "<br>Filter options available:<br>";
    echo "Cities: " . count($data['filter_options']['cities']) . "<br>";
    echo "States: " . count($data['filter_options']['states']) . "<br>";
    echo "Activity Statuses: " . count($data['filter_options']['activity_statuses']) . "<br>";
    
    echo "<br>Sites found: " . count($data['sites']) . "<br>";
    echo "Total records: " . $data['pagination']['total_records'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "<h3>Test Filter Links:</h3>";
echo '<a href="?search=SITE">Search for "SITE"</a><br>';
echo '<a href="?city=Mumbai">Filter by Mumbai</a><br>';
echo '<a href="?state=Maharashtra">Filter by Maharashtra</a><br>';
echo '<a href="?activity_status=active">Filter by Active Status</a><br>';
echo '<a href="?search=SITE&city=Mumbai">Search + City Filter</a><br>';
?>