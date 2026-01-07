<?php
error_reporting(E_ERROR | E_PARSE);
header('Content-Type: application/json');

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/SiteSurvey.php';

try {
    Auth::requireVendor();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$vendorId = Auth::getVendorId();
$delegationId = $_POST['delegation_id'] ?? null;
$siteId = $_POST['site_id'] ?? null;

if (!$delegationId || !$siteId) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$data = [
    'vendor_id' => $vendorId,
    'site_id' => $siteId,
    'delegation_id' => $delegationId,
    'store_model' => $_POST['store_model'] ?? '',
    'floor_height' => $_POST['floor_height'] ?? '',
    'ceiling_type' => $_POST['ceiling_type'] ?? '',
    'total_cameras' => $_POST['total_cameras'] ?? '',
    'technical_remarks' => $_POST['technical_remarks'] ?? '',
    'recommendations' => $_POST['recommendations'] ?? '',
    'working_hours' => $_POST['working_hours'] ?? '',
    'checkin_datetime' => $_POST['checkin_datetime'] ?? '',
    'checkout_datetime' => $_POST['checkout_datetime'] ?? '',
    'submitted_date' => date('Y-m-d H:i:s'),
];

$surveyModel = new SiteSurvey();
$surveyId = $surveyModel->create($data);

// 🔹 Image upload example
if (!empty($_FILES['floor_height_photo'])) {
    $surveyModel->uploadImages($surveyId, $_FILES['floor_height_photo'], 'floor_height');
}

echo json_encode([
    'success' => true,
    'message' => 'Survey submitted successfully'
]);
