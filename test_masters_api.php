<?php
require_once 'config/database.php';

echo "<h2>Masters API Test</h2>";

// Test countries API
echo "<h3>Testing Countries API:</h3>";
$countriesUrl = "http://localhost/project/api/masters.php?path=countries";
$countriesResponse = file_get_contents($countriesUrl);
$countriesData = json_decode($countriesResponse, true);

if ($countriesData && $countriesData['success']) {
    echo "✅ Countries API working<br>";
    echo "Found " . count($countriesData['data']['records']) . " countries<br>";
    foreach (array_slice($countriesData['data']['records'], 0, 3) as $country) {
        echo "- {$country['name']} (ID: {$country['id']})<br>";
    }
} else {
    echo "❌ Countries API failed<br>";
    echo "Response: " . $countriesResponse . "<br>";
}

echo "<br>";

// Test states API with country filter
echo "<h3>Testing States API with Country Filter:</h3>";
if ($countriesData && $countriesData['success'] && !empty($countriesData['data']['records'])) {
    $firstCountryId = $countriesData['data']['records'][0]['id'];
    $statesUrl = "http://localhost/project/api/masters.php?path=states&country_id={$firstCountryId}";
    $statesResponse = file_get_contents($statesUrl);
    $statesData = json_decode($statesResponse, true);
    
    if ($statesData && $statesData['success']) {
        echo "✅ States API with country filter working<br>";
        echo "Found " . count($statesData['data']['records']) . " states for country ID {$firstCountryId}<br>";
        foreach (array_slice($statesData['data']['records'], 0, 3) as $state) {
            echo "- {$state['name']} (Zone: {$state['zone_name']}) (ID: {$state['id']})<br>";
        }
    } else {
        echo "❌ States API with country filter failed<br>";
        echo "Response: " . $statesResponse . "<br>";
    }
} else {
    echo "❌ Cannot test states API - no countries available<br>";
}

echo "<br>";

// Test zones API
echo "<h3>Testing Zones API:</h3>";
$zonesUrl = "http://localhost/project/api/masters.php?path=zones";
$zonesResponse = file_get_contents($zonesUrl);
$zonesData = json_decode($zonesResponse, true);

if ($zonesData && $zonesData['success']) {
    echo "✅ Zones API working<br>";
    echo "Found " . count($zonesData['data']['records']) . " zones<br>";
    foreach (array_slice($zonesData['data']['records'], 0, 5) as $zone) {
        echo "- {$zone['name']} (ID: {$zone['id']})<br>";
    }
} else {
    echo "❌ Zones API failed<br>";
    echo "Response: " . $zonesResponse . "<br>";
}

echo "<br>";

// Test cities API
echo "<h3>Testing Cities API:</h3>";
$citiesUrl = "http://localhost/project/api/masters.php?path=cities";
$citiesResponse = file_get_contents($citiesUrl);
$citiesData = json_decode($citiesResponse, true);

if ($citiesData && $citiesData['success']) {
    echo "✅ Cities API working<br>";
    echo "Found " . count($citiesData['data']['records']) . " cities<br>";
    foreach (array_slice($citiesData['data']['records'], 0, 3) as $city) {
        echo "- {$city['name']} (State: {$city['state_name']}, Zone: {$city['zone_name']}, Country: {$city['country_name']}) (ID: {$city['id']})<br>";
    }
} else {
    echo "❌ Cities API failed<br>";
    echo "Response: " . $citiesResponse . "<br>";
}
?>