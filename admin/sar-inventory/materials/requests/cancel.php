<?php
require_once '../../../../config/auth.php';
require_once '../../../../config/database.php';
require_once '../../../../services/SarInvMaterialService.php';
require_once '../../../../models/SarInvMaterialRequest.php';

Auth::requireRole(ADMIN_ROLE);

$materialService = new SarInvMaterialService();

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('/admin/sar-inventory/materials/requests/'));
    exit;
}

// Get request ID
$requestId = isset($_POST['id']) ? intval($_POST['id']) : 0;
$reason = trim($_POST['reason'] ?? 'Cancelled by administrator');

if (!$requestId) {
    $_SESSION['error'] = 'Request ID is required';
    header('Location: ' . url('/admin/sar-inventory/materials/requests/'));
    exit;
}

// Get request details
$request = $materialService->getRequest($requestId);

if (!$request) {
    $_SESSION['error'] = 'Request not found';
    header('Location: ' . url('/admin/sar-inventory/materials/requests/'));
    exit;
}

// Check if request can be cancelled
if (in_array($request['status'], [SarInvMaterialRequest::STATUS_FULFILLED, SarInvMaterialRequest::STATUS_CANCELLED])) {
    $_SESSION['error'] = 'Cannot cancel fulfilled or already cancelled requests';
    header('Location: ' . url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId));
    exit;
}

// Cancel the request
$result = $materialService->cancelRequest($requestId, $reason);

if ($result['success']) {
    $_SESSION['success'] = 'Request cancelled successfully';
} else {
    $_SESSION['error'] = implode(', ', $result['errors']);
}

header('Location: ' . url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId));
exit;
?>
