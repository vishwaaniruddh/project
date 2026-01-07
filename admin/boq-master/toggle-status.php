<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/BoqMaster.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);
$newStatus = $_GET['status'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'BOQ master ID is required']);
    exit;
}

if (!$newStatus || !in_array($newStatus, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Valid status (active/inactive) is required']);
    exit;
}

try {
    $boqMasterModel = new BoqMaster();
    
    // Check if BOQ master exists
    $boqMaster = $boqMasterModel->find($id);
    if (!$boqMaster) {
        echo json_encode(['success' => false, 'message' => 'BOQ master not found']);
        exit;
    }
    
    // Check if status is already the same
    if ($boqMaster['status'] === $newStatus) {
        echo json_encode([
            'success' => false, 
            'message' => "BOQ master is already {$newStatus}",
            'current_status' => $boqMaster['status']
        ]);
        exit;
    }
    
    // Update the status
    $result = $boqMasterModel->update($id, ['status' => $newStatus]);
    
    if ($result) {
        $action = $newStatus === 'active' ? 'activated' : 'deactivated';
        echo json_encode([
            'success' => true, 
            'message' => "BOQ master '{$boqMaster['boq_name']}' has been {$action} successfully",
            'data' => [
                'boq_id' => $id,
                'boq_name' => $boqMaster['boq_name'],
                'old_status' => $boqMaster['status'],
                'new_status' => $newStatus,
                'action' => $action
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to update BOQ master status',
            'error_code' => 'UPDATE_FAILED'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error toggling BOQ master status for ID {$id}: " . $e->getMessage());
    
    // Provide more specific error messages
    $errorMessage = 'An error occurred while updating the status';
    
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        $errorMessage = 'Cannot change status due to related records';
    } elseif (strpos($e->getMessage(), 'Deadlock') !== false) {
        $errorMessage = 'Database is busy. Please try again in a moment';
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $errorMessage,
        'error_code' => 'STATUS_UPDATE_FAILED'
    ]);
}
?>