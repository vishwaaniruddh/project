<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'View Warehouse';
$currentUser = Auth::getCurrentUser();

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

$utilization = $warehouseService->getCapacityUtilization($id);
$stockSummary = $warehouseService->getStockSummary($id);
$canDelete = $warehouseService->canDelete($id);

$statusColors = [
    'active' => 'badge-success',
    'inactive' => 'badge-secondary',
    'maintenance' => 'badge-info'
];
$statusClass = $statusColors[$warehouse['status']] ?? 'badge-secondary';

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="<?php echo url('/admin/sar-inventory/warehouses/'); ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($warehouse['name']); ?></h1>
                <p class="text-gray-600">Warehouse Code: <?php echo htmlspecialchars($warehouse['code']); ?></p>
            </div>
        </div>
        <div class="flex space-x-3">
            <a href="<?php echo url('/admin/sar-inventory/warehouses/edit.php?id=' . $id); ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit
            </a>
            <?php if ($canDelete['can_delete']): ?>
            <a href="<?php echo url('/admin/sar-inventory/warehouses/delete.php?id=' . $id); ?>" 
               class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this warehouse?');">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                Delete
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Warehouse Details -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Basic Info -->
    <div class="card">
        <div class="card-body">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm text-gray-500">Status</dt>
                    <dd><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($warehouse['status']); ?></span></dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Location</dt>
                    <dd class="text-gray-900"><?php echo htmlspecialchars($warehouse['location'] ?? '-'); ?></dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Address</dt>
                    <dd class="text-gray-900"><?php echo nl2br(htmlspecialchars($warehouse['address'] ?? '-')); ?></dd>
                </div>
                <div>
                    <dt class="text-sm text-gray-500">Created</dt>
                    <dd class="text-gray-900"><?php echo date('M j, Y g:i A', strtotime($warehouse['created_at'])); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Capacity Visualization -->
    <div class="card lg:col-span-2">
        <div class="card-body">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Capacity Utilization</h3>
            <?php if ($warehouse['capacity']): ?>
            <div class="mb-6">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-600">Used: <?php echo number_format($utilization['used']); ?></span>
                    <span class="text-gray-600">Available: <?php echo number_format($utilization['available']); ?></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-6">
                    <?php 
                    $barColor = $utilization['utilization_percentage'] >= 90 ? 'bg-red-500' : 
                               ($utilization['utilization_percentage'] >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                    ?>
                    <div class="<?php echo $barColor; ?> h-6 rounded-full flex items-center justify-center text-white text-sm font-medium" 
                         style="width: <?php echo max(10, min(100, $utilization['utilization_percentage'])); ?>%">
                        <?php echo $utilization['utilization_percentage']; ?>%
                    </div>
                </div>
                <div class="text-center mt-2 text-sm text-gray-500">
                    Total Capacity: <?php echo number_format($utilization['capacity']); ?>
                </div>
            </div>
            <?php else: ?>
            <div class="text-center py-8 text-gray-500">
                <p>No capacity limit set for this warehouse</p>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-3 gap-4 pt-4 border-t">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo count($stockSummary); ?></div>
                    <div class="text-sm text-gray-500">Products</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo number_format(array_sum(array_column($stockSummary, 'quantity'))); ?></div>
                    <div class="text-sm text-gray-500">Total Qty</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600"><?php echo number_format(array_sum(array_column($stockSummary, 'reserved_quantity'))); ?></div>
                    <div class="text-sm text-gray-500">Reserved</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Summary -->
<div class="card">
    <div class="card-body">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Stock Summary</h3>
            <a href="<?php echo url('/admin/sar-inventory/stock-entry/create.php?warehouse_id=' . $id); ?>" class="btn btn-sm btn-primary">
                Add Stock
            </a>
        </div>
        
        <?php if (empty($stockSummary)): ?>
        <div class="text-center py-8 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <p>No stock in this warehouse</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product Name</th>
                        <th class="text-right">Quantity</th>
                        <th class="text-right">Reserved</th>
                        <th class="text-right">Available</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stockSummary as $item): ?>
                    <tr>
                        <td class="font-medium"><?php echo htmlspecialchars($item['sku']); ?></td>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td class="text-right"><?php echo number_format($item['quantity']); ?></td>
                        <td class="text-right text-yellow-600"><?php echo number_format($item['reserved_quantity']); ?></td>
                        <td class="text-right text-green-600"><?php echo number_format($item['available_quantity']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!$canDelete['can_delete']): ?>
<div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
    <div class="flex">
        <svg class="w-5 h-5 text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
        </svg>
        <div>
            <h4 class="text-sm font-medium text-yellow-800">This warehouse cannot be deleted</h4>
            <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside">
                <?php foreach ($canDelete['reasons'] as $reason): ?>
                <li><?php echo htmlspecialchars($reason); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
