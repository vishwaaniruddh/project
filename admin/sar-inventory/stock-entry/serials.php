<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvProductService.php';
require_once '../../../services/SarInvWarehouseService.php';
require_once '../../../services/SarInvAssetService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Serial Numbers';
$currentUser = Auth::getCurrentUser();

$productService = new SarInvProductService();
$warehouseService = new SarInvWarehouseService();
$assetService = new SarInvAssetService();

$productId = intval($_GET['product_id'] ?? 0);
$warehouseId = intval($_GET['warehouse_id'] ?? 0);
$status = $_GET['status'] ?? '';

if (!$productId) {
    $_SESSION['error'] = 'Product ID is required';
    header('Location: ' . url('/admin/sar-inventory/stock-entry/'));
    exit;
}

$product = $productService->getProduct($productId);
if (!$product) {
    $_SESSION['error'] = 'Product not found';
    header('Location: ' . url('/admin/sar-inventory/stock-entry/'));
    exit;
}

$warehouse = $warehouseId ? $warehouseService->getWarehouse($warehouseId) : null;

// Get assets (serial numbers) for this product
$assets = $assetService->getAssetsByProduct($productId, $warehouseId ?: null, $status ?: null);

// Get all warehouses for filter
$warehouses = $warehouseService->getActiveWarehouses();

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/stock-entry/'); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Serial Numbers</h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['sku']); ?>)</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
            <div>
                <label class="form-label">Warehouse</label>
                <select name="warehouse_id" class="form-select">
                    <option value="">All Warehouses</option>
                    <?php foreach ($warehouses as $w): ?>
                    <option value="<?php echo $w['id']; ?>" <?php echo $warehouseId == $w['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($w['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="dispatched" <?php echo $status === 'dispatched' ? 'selected' : ''; ?>>Dispatched</option>
                    <option value="in_repair" <?php echo $status === 'in_repair' ? 'selected' : ''; ?>>In Repair</option>
                    <option value="retired" <?php echo $status === 'retired' ? 'selected' : ''; ?>>Retired</option>
                    <option value="lost" <?php echo $status === 'lost' ? 'selected' : ''; ?>>Lost</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="<?php echo url('/admin/sar-inventory/stock-entry/serials.php?product_id=' . $productId); ?>" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <?php
    $statusCounts = ['available' => 0, 'dispatched' => 0, 'in_repair' => 0, 'retired' => 0, 'lost' => 0];
    foreach ($assets as $asset) {
        if (isset($statusCounts[$asset['status']])) {
            $statusCounts[$asset['status']]++;
        }
    }
    ?>
    <div class="bg-white rounded-lg shadow p-4 text-center">
        <div class="text-2xl font-bold text-gray-900"><?php echo count($assets); ?></div>
        <div class="text-sm text-gray-500">Total</div>
    </div>
    <div class="bg-green-50 rounded-lg shadow p-4 text-center">
        <div class="text-2xl font-bold text-green-600"><?php echo $statusCounts['available']; ?></div>
        <div class="text-sm text-gray-500">Available</div>
    </div>
    <div class="bg-blue-50 rounded-lg shadow p-4 text-center">
        <div class="text-2xl font-bold text-blue-600"><?php echo $statusCounts['dispatched']; ?></div>
        <div class="text-sm text-gray-500">Dispatched</div>
    </div>
    <div class="bg-yellow-50 rounded-lg shadow p-4 text-center">
        <div class="text-2xl font-bold text-yellow-600"><?php echo $statusCounts['in_repair']; ?></div>
        <div class="text-sm text-gray-500">In Repair</div>
    </div>
    <div class="bg-red-50 rounded-lg shadow p-4 text-center">
        <div class="text-2xl font-bold text-red-600"><?php echo $statusCounts['retired'] + $statusCounts['lost']; ?></div>
        <div class="text-sm text-gray-500">Retired/Lost</div>
    </div>
</div>

<!-- Serial Numbers Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Serial Number</th>
                    <th>Barcode</th>
                    <th>Warehouse</th>
                    <th>Status</th>
                    <th>Warranty Expiry</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assets)): ?>
                <tr>
                    <td colspan="8" class="text-center py-8 text-gray-500">
                        No serial numbers found for this product.
                    </td>
                </tr>
                <?php else: ?>
                <?php $serialNo = 1; foreach ($assets as $asset): 
                    $statusColors = [
                        'available' => 'bg-green-100 text-green-800',
                        'dispatched' => 'bg-blue-100 text-blue-800',
                        'in_repair' => 'bg-yellow-100 text-yellow-800',
                        'retired' => 'bg-gray-100 text-gray-800',
                        'lost' => 'bg-red-100 text-red-800'
                    ];
                    $statusClass = $statusColors[$asset['status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <tr>
                    <td class="text-center text-gray-500"><?php echo $serialNo++; ?></td>
                    <td class="font-mono font-medium"><?php echo htmlspecialchars($asset['serial_number'] ?? '-'); ?></td>
                    <td class="font-mono text-sm text-gray-600"><?php echo htmlspecialchars($asset['barcode'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($asset['warehouse_name'] ?? '-'); ?></td>
                    <td>
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $statusClass; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $asset['status'])); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($asset['warranty_expiry']): ?>
                            <?php 
                            $warrantyDate = strtotime($asset['warranty_expiry']);
                            $isExpired = $warrantyDate < time();
                            $isExpiringSoon = !$isExpired && $warrantyDate < strtotime('+30 days');
                            ?>
                            <span class="<?php echo $isExpired ? 'text-red-600' : ($isExpiringSoon ? 'text-yellow-600' : 'text-gray-600'); ?>">
                                <?php echo date('M d, Y', $warrantyDate); ?>
                                <?php if ($isExpired): ?><span class="text-xs">(Expired)</span><?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm text-gray-500"><?php echo date('M d, Y', strtotime($asset['created_at'])); ?></td>
                    <td>
                        <div class="flex space-x-1">
                            <a href="<?php echo url('/admin/sar-inventory/assets/view.php?id=' . $asset['id']); ?>" 
                               class="btn btn-sm btn-secondary" title="View Details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
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
