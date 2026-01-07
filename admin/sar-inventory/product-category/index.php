<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvProductService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Product Categories';
$currentUser = Auth::getCurrentUser();

$productService = new SarInvProductService();

// Get category tree for hierarchical display
$categoryTree = $productService->getCategoryTree();
$flatCategories = $productService->getFlatCategoryList();

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

ob_start();

// Recursive function to render category tree
$GLOBALS['categorySerialNo'] = 1;
function renderCategoryTree($categories, $productService, $level = 0) {
    foreach ($categories as $category):
        $canDelete = $productService->canDeleteCategory($category['id']);
        $statusColors = [
            'active' => 'badge-success',
            'inactive' => 'badge-secondary'
        ];
        $statusClass = $statusColors[$category['status'] ?? 'active'] ?? 'badge-secondary';
        $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level);
?>
        <tr class="<?php echo $level > 0 ? 'bg-gray-50' : ''; ?>">
            <td class="text-center text-gray-500"><?php echo $GLOBALS['categorySerialNo']++; ?></td>
            <td>
                <?php echo $indent; ?>
                <?php if ($level > 0): ?>
                <span class="text-gray-400">└─</span>
                <?php endif; ?>
                <span class="font-medium"><?php echo htmlspecialchars($category['name']); ?></span>
            </td>
            <td><?php echo htmlspecialchars($category['description'] ?? '-'); ?></td>
            <td class="text-center"><?php echo $category['level']; ?></td>
            <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($category['status'] ?? 'active'); ?></span></td>
            <td>
                <div class="flex space-x-2">
                    <a href="<?php echo url('/admin/sar-inventory/product-category/edit.php?id=' . $category['id']); ?>" 
                       class="btn btn-sm btn-primary" title="Edit">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                    <?php if ($canDelete['can_delete']): ?>
                    <a href="<?php echo url('/admin/sar-inventory/product-category/delete.php?id=' . $category['id']); ?>" 
                       class="btn btn-sm btn-danger" title="Delete"
                       onclick="return confirm('Are you sure you want to delete this category?');">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </a>
                    <?php else: ?>
                    <button class="btn btn-sm btn-secondary opacity-50 cursor-not-allowed" disabled title="<?php echo implode(', ', $canDelete['reasons']); ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
<?php
        if (!empty($category['children'])) {
            renderCategoryTree($category['children'], $productService, $level + 1);
        }
    endforeach;
}
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Product Categories</h1>
            <p class="text-gray-600">Organize products into hierarchical categories</p>
        </div>
        <a href="<?php echo url('/admin/sar-inventory/product-category/create.php'); ?>" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Category
        </a>
    </div>
</div>

<?php if ($success): ?>
<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<!-- Categories Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th class="text-center">Level</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categoryTree)): ?>
                <tr>
                    <td colspan="6" class="text-center py-8 text-gray-500">
                        No categories found. <a href="<?php echo url('/admin/sar-inventory/product-category/create.php'); ?>" class="text-blue-600 hover:underline">Add your first category</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php renderCategoryTree($categoryTree, $productService); ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
