<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$warehouseService = new SarInvWarehouseService();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'Invalid warehouse ID';
    header('Location: ' . url('/admin/sar-inventory/warehouses/'));
    exit;
}

$warehouse = $warehouseService->getWarehouse($id);
if (!$warehouse) {
    $_SESSION['error'] = 'Warehouse not found';
    header('Location: ' . url('/admin/sar-inventory/warehouses/'));
    exit;
}

// Check if warehouse can be deleted
$canDelete = $warehouseService->canDelete($id);

if (!$canDelete['can_delete']) {
    $_SESSION['error'] = 'Cannot delete warehouse: ' . implode(', ', $canDelete['reasons']);
    header('Location: ' . url('/admin/sar-inventory/warehouses/'));
    exit;
}

// Perform deletion
$result = $warehouseService->deleteWarehouse($id);

if ($result['success']) {
    $_SESSION['success'] = 'Warehouse deleted successfully';
} else {
    $_SESSION['error'] = implode(', ', $result['errors']);
}

header('Location: ' . url('/admin/sar-inventory/warehouses/'));
exit;
?>
