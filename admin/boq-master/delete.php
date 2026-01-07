<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/BoqMaster.php';
require_once __DIR__ . '/../../models/BoqMasterItem.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'BOQ master ID is required']);
    exit;
}

try {
    $boqMasterModel = new BoqMaster();
    $boqMasterItemModel = new BoqMasterItem();
    
    // Check if BOQ master exists
    $boqMaster = $boqMasterModel->find($id);
    if (!$boqMaster) {
        echo json_encode(['success' => false, 'message' => 'BOQ master not found']);
        exit;
    }
    
    // Get count of associated items for confirmation message
    $associatedItems = $boqMasterItemModel->getByBoqMaster($id);
    $itemCount = count($associatedItems);
    
    // Use database connection from model
    $db = Database::getInstance()->getConnection();
    $db->beginTransaction();
    
    try {
        // Delete associated items first (if any)
        if ($itemCount > 0) {
            $stmt = $db->prepare("DELETE FROM boq_master_items WHERE boq_master_id = ?");
            $stmt->execute([$id]);
        }
        
        // Delete the BOQ master
        $result = $boqMasterModel->delete($id);
        
        if ($result) {
            $db->commit();
            
            $message = "BOQ master '{$boqMaster['boq_name']}' has been deleted successfully";
            if ($itemCount > 0) {
                $message .= " along with {$itemCount} associated item" . ($itemCount > 1 ? 's' : '');
            }
            
            echo json_encode([
                'success' => true, 
                'message' => $message,
                'data' => [
                    'deleted_boq_id' => $id,
                    'deleted_items_count' => $itemCount
                ]
            ]);
        } else {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to delete BOQ master']);
        }
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error deleting BOQ master ID {$id}: " . $e->getMessage());
    
    // Provide more specific error messages
    $errorMessage = 'An error occurred while deleting the BOQ master';
    
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        $errorMessage = 'Cannot delete BOQ master as it is referenced by other records';
    } elseif (strpos($e->getMessage(), 'Deadlock') !== false) {
        $errorMessage = 'Database is busy. Please try again in a moment';
    }
    
    echo json_encode([
        'success' => false, 
        'message' => $errorMessage,
        'error_code' => 'DELETE_FAILED'
    ]);
}
?>