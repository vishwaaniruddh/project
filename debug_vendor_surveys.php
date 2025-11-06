<?php
require_once 'config/auth.php';
require_once 'models/SiteSurvey.php';

// Simulate the vendor surveys page logic
echo "Debug: Vendor Surveys Query\n\n";

// Get current session info (if available)
if (isset($_SESSION['vendor_id'])) {
    echo "Session vendor_id: " . $_SESSION['vendor_id'] . "\n";
}
if (isset($_SESSION['user_id'])) {
    echo "Session user_id: " . $_SESSION['user_id'] . "\n";
}

// Test with vendor ID 2 (SAR Software solutions)
$vendorId = 2;
echo "Testing getVendorSurveys with vendor_id: $vendorId\n\n";

$surveyModel = new SiteSurvey();
$surveys = $surveyModel->getVendorSurveys($vendorId);

echo "Found " . count($surveys) . " surveys\n";

foreach ($surveys as $survey) {
    echo "- Survey ID: {$survey['id']}, Site: {$survey['site_code']}, Status: {$survey['survey_status']}, Vendor: {$survey['vendor_name']}, User: {$survey['survey_user_name']}\n";
}

// Check what's actually in the database
echo "\nDatabase contents:\n";
require_once 'config/database.php';
$db = Database::getInstance()->getConnection();

echo "Site surveys table:\n";
$stmt = $db->prepare('SELECT id, site_id, vendor_id, survey_status, submitted_date FROM site_surveys ORDER BY id');
$stmt->execute();
$surveys = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($surveys as $survey) {
    echo "- Survey ID: {$survey['id']}, Site: {$survey['site_id']}, Vendor_ID: {$survey['vendor_id']}, Status: {$survey['survey_status']}, Date: {$survey['submitted_date']}\n";
}

echo "\nUsers table (relevant IDs):\n";
$stmt = $db->prepare('SELECT id, username, vendor_id FROM users WHERE id IN (1, 2, 3, 9, 10) ORDER BY id');
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $user) {
    echo "- User ID: {$user['id']}, Username: {$user['username']}, Vendor_ID: {$user['vendor_id']}\n";
}

echo "\nJoin test:\n";
$sql = "SELECT ss.id, ss.vendor_id as survey_vendor_id, 
               u.id as user_id, u.username, u.vendor_id as user_vendor_id
        FROM site_surveys ss
        LEFT JOIN users u ON ss.vendor_id = u.id
        ORDER BY ss.id";
$stmt = $db->prepare($sql);
$stmt->execute();
$joinTest = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($joinTest as $row) {
    echo "- Survey {$row['id']}: survey.vendor_id={$row['survey_vendor_id']} -> user.id={$row['user_id']}, user.username={$row['username']}, user.vendor_id={$row['user_vendor_id']}\n";
}
?>