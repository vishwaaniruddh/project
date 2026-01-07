<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvProductService.php';

Auth::requireRole(ADMIN_ROLE);

$currentUser = Auth::getCurrentUser();

$productService = new SarInvProductService();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'Invalid product ID';
    header('Location: ' . url('/admin/sar-inventory/products/'));
    exit;
}

$product = $productService->getProductWithCategory($id);
if (!$product) {
    $_SESSION['error'] = 'Product not found';
    header('Location: ' . url('/admin/sar-inventory/products/'));
    exit;
}

$title = 'Product: ' . $product['name'];

// Get stock levels across warehouses
$stockLevels = $productService->getProductStockLevels($id);
$totalStock = $productService->getProductTotalStock($id);

// Get specifications
$specifications = [];
if (!empty($product['specifications'])) {
    $specifications = json_decode($product['specifications'], true) ?: [];
}

// Check if can delete
$canDelete = $productService->canDeleteProduct($id);

// Status colors
$statusColors = [
    'active' => 'badge-success',
    'inactive' => 'badge-secondary',
    'discontinued' => 'badge-danger'
];
$statusClass = $statusColors[$product['status']] ?? 'badge-secondary';

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="<?php echo url('/admin/sar-inventory/products/'); ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="text-gray-600">SKU: <span class="font-mono"><?php echo htmlspecialchars($product['sku']); ?></span></p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo url('/admin/sar-inventory/products/edit.php?id=' . $id); ?>" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit
            </a>
            <?php if ($canDelete['can_delete']): ?>
            <a href="<?php echo url('/admin/sar-inventory/products/delete.php?id=' . $id); ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this product?');">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Delete
            </a>
            <?php endif; ?>
        </div>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Product Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Basic Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Product Information</h3>
            </div>
            <div class="card-body">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Product Name</dt>
                        <dd class="mt-1 text-gray-900"><?php echo htmlspecialchars($product['name']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">SKU</dt>
                        <dd class="mt-1 font-mono text-gray-900"><?php echo htmlspecialchars($product['sku']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Category</dt>
                        <dd class="mt-1 text-gray-900"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Unit of Measure</dt>
                        <dd class="mt-1 text-gray-900"><?php echo htmlspecialchars($product['unit_of_measure'] ?? '-'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Minimum Stock Level</dt>
                        <dd class="mt-1 text-gray-900"><?php echo intval($product['minimum_stock_level']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1"><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($product['status']); ?></span></dd>
                    </div>
                </dl>
                
                <?php if (!empty($product['description'])): ?>
                <div class="mt-4 pt-4 border-t">
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-gray-900"><?php echo nl2br(htmlspecialchars($product['description'])); ?></dd>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Specifications -->
        <?php if (!empty($specifications)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Specifications</h3>
            </div>
            <div class="card-body">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($specifications as $key => $value): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $key))); ?></dt>
                        <dd class="mt-1 text-gray-900">
                            <?php 
                            if (is_array($value)) {
                                echo htmlspecialchars(json_encode($value));
                            } else {
                                echo htmlspecialchars($value);
                            }
                            ?>
                        </dd>
                    </div>
                    <?php endforeach; ?>
                </dl>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Stock by Warehouse -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Stock by Warehouse</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Warehouse</th>
                            <th class="text-right">Quantity</th>
                            <th class="text-right">Reserved</th>
                            <th class="text-right">Available</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($stockLevels)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-gray-500">
                                No stock records found for this product
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($stockLevels as $stock): 
                            $available = floatval($stock['quantity']) - floatval($stock['reserved_quantity']);
                        ?>
                        <tr>
                            <td>
                                <span class="font-medium"><?php echo htmlspecialchars($stock['warehouse_name']); ?></span>
                                <span class="text-gray-500 text-sm ml-1">(<?php echo htmlspecialchars($stock['warehouse_code']); ?>)</span>
                            </td>
                            <td class="text-right"><?php echo number_format($stock['quantity'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($stock['reserved_quantity'], 2); ?></td>
                            <td class="text-right font-medium <?php echo $available < 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                <?php echo number_format($available, 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Stock Summary -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Stock Summary</h3>
            </div>
            <div class="card-body">
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Quantity</span>
                        <span class="text-xl font-bold"><?php echo number_format($totalStock['total_quantity'], 2); ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Reserved</span>
                        <span class="text-xl font-bold text-orange-600"><?php echo number_format($totalStock['total_reserved'], 2); ?></span>
                    </div>
                    <div class="flex justify-between items-center pt-2 border-t">
                        <span class="text-gray-600">Available</span>
                        <span class="text-xl font-bold <?php echo $totalStock['available'] < $product['minimum_stock_level'] ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo number_format($totalStock['available'], 2); ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($totalStock['available'] < $product['minimum_stock_level']): ?>
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center text-red-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span class="text-sm font-medium">Low Stock Alert</span>
                    </div>
                    <p class="mt-1 text-sm text-red-600">
                        Stock is below minimum level (<?php echo intval($product['minimum_stock_level']); ?>)
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Quick Actions</h3>
            </div>
            <div class="card-body space-y-2">
                <a href="<?php echo url('/admin/sar-inventory/stock-entry/create.php?product_id=' . $id); ?>" 
                   class="btn btn-secondary w-full justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Stock Entry
                </a>
                <a href="<?php echo url('/admin/sar-inventory/item-history/?product_id=' . $id); ?>" 
                   class="btn btn-secondary w-full justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    View History
                </a>
            </div>
        </div>
        
        <!-- Metadata -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Metadata</h3>
            </div>
            <div class="card-body">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Product ID</dt>
                        <dd class="text-gray-900"><?php echo $product['id']; ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Created</dt>
                        <dd class="text-gray-900"><?php echo date('M j, Y', strtotime($product['created_at'])); ?></dd>
                    </div>
                    <?php if (!empty($product['updated_at'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Last Updated</dt>
                        <dd class="text-gray-900"><?php echo date('M j, Y H:i', strtotime($product['updated_at'])); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
