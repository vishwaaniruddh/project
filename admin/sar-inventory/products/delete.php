<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvProductService.php';

Auth::requireRole(ADMIN_ROLE);

$productService = new SarInvProductService();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'Invalid product ID';
    header('Location: ' . url('/admin/sar-inventory/products/'));
    exit;
}

$product = $productService->getProduct($id);
if (!$product) {
    $_SESSION['error'] = 'Product not found';
    header('Location: ' . url('/admin/sar-inventory/products/'));
    exit;
}

// Check if can delete
$canDelete = $productService->canDeleteProduct($id);
if (!$canDelete['can_delete']) {
    $_SESSION['error'] = 'Cannot delete product: ' . implode(', ', $canDelete['reasons']);
    header('Location: ' . url('/admin/sar-inventory/products/'));
    exit;
}

// Perform deletion
$result = $productService->deleteProduct($id);

if ($result['success']) {
    $_SESSION['success'] = 'Product deleted successfully';
} else {
    $_SESSION['error'] = 'Failed to delete product: ' . implode(', ', $result['errors']);
}

header('Location: ' . url('/admin/sar-inventory/products/'));
exit;
?>
