<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvProductService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Products';
$currentUser = Auth::getCurrentUser();

$productService = new SarInvProductService();

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$categoryId = $_GET['category_id'] ?? '';
$status = $_GET['status'] ?? '';

// Get products with filters
$products = $productService->searchProducts(
    $search ?: null,
    $categoryId ? intval($categoryId) : null,
    $status ?: null
);

// Get categories for filter dropdown
$categories = $productService->getFlatCategoryList();

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Products</h1>
            <p class="text-gray-600">Manage inventory products and their specifications</p>
        </div>
        <a href="<?php echo url('/admin/sar-inventory/products/create.php'); ?>" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Product
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

<!-- Search and Filter -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="form-group mb-0">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="form-input" placeholder="Name, SKU, or description">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $categoryId == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['display_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="discontinued" <?php echo $status === 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                </select>
            </div>
            <div class="form-group mb-0 flex items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search
                </button>
                <a href="<?php echo url('/admin/sar-inventory/products/'); ?>" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>SKU</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Type</th>
                    <th>Unit</th>
                    <th class="text-center">Min Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="9" class="text-center py-8 text-gray-500">
                        No products found. <a href="<?php echo url('/admin/sar-inventory/products/create.php'); ?>" class="text-blue-600 hover:underline">Add your first product</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php $serialNo = 1; ?>
                <?php foreach ($products as $product): 
                    $canDelete = $productService->canDeleteProduct($product['id']);
                    $statusColors = [
                        'active' => 'badge-success',
                        'inactive' => 'badge-secondary',
                        'discontinued' => 'badge-danger'
                    ];
                    $statusClass = $statusColors[$product['status']] ?? 'badge-secondary';
                    $isSerializable = !empty($product['is_serializable']);
                ?>
                <tr>
                    <td class="text-center text-gray-500"><?php echo $serialNo++; ?></td>
                    <td class="font-mono text-sm"><?php echo htmlspecialchars($product['sku']); ?></td>
                    <td>
                        <a href="<?php echo url('/admin/sar-inventory/products/view.php?id=' . $product['id']); ?>" 
                           class="font-medium text-blue-600 hover:underline">
                            <?php echo htmlspecialchars($product['name']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($product['category_name'] ?? '-'); ?></td>
                    <td>
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $isSerializable ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                            <?php echo $isSerializable ? 'Serializable' : 'Quantity'; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($product['unit_of_measure'] ?? '-'); ?></td>
                    <td class="text-center"><?php echo intval($product['minimum_stock_level']); ?></td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($product['status']); ?></span></td>
                    <td>
                        <div class="flex space-x-2">
                            <a href="<?php echo url('/admin/sar-inventory/products/view.php?id=' . $product['id']); ?>" 
                               class="btn btn-sm btn-secondary" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="<?php echo url('/admin/sar-inventory/products/edit.php?id=' . $product['id']); ?>" 
                               class="btn btn-sm btn-primary" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            <?php if ($canDelete['can_delete']): ?>
                            <a href="<?php echo url('/admin/sar-inventory/products/delete.php?id=' . $product['id']); ?>" 
                               class="btn btn-sm btn-danger" title="Delete"
                               onclick="return confirm('Are you sure you want to delete this product?');">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </a>
                            <?php else: ?>
                            <button class="btn btn-sm btn-secondary opacity-50 cursor-not-allowed" disabled 
                                    title="<?php echo implode(', ', $canDelete['reasons']); ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
