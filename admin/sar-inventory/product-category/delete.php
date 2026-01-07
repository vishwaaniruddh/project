<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvProductService.php';

Auth::requireRole(ADMIN_ROLE);

$productService = new SarInvProductService();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'Invalid category ID';
    header('Location: ' . url('/admin/sar-inventory/product-category/'));
    exit;
}

$category = $productService->getCategory($id);
if (!$category) {
    $_SESSION['error'] = 'Category not found';
    header('Location: ' . url('/admin/sar-inventory/product-category/'));
    exit;
}

// Check if category can be deleted
$canDelete = $productService->canDeleteCategory($id);

if (!$canDelete['can_delete']) {
    $_SESSION['error'] = 'Cannot delete category: ' . implode(', ', $canDelete['reasons']);
    header('Location: ' . url('/admin/sar-inventory/product-category/'));
    exit;
}

// Perform deletion
$result = $productService->deleteCategory($id);

if ($result['success']) {
    $_SESSION['success'] = 'Category deleted successfully';
} else {
    $_SESSION['error'] = implode(', ', $result['errors']);
}

header('Location: ' . url('/admin/sar-inventory/product-category/'));
exit;
?>
