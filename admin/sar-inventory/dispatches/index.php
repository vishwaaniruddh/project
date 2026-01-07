<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvDispatchService.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Dispatches Management';
$currentUser = Auth::getCurrentUser();

$dispatchService = new SarInvDispatchService();
$warehouseService = new SarInvWarehouseService();

// Handle search and filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$warehouseId = $_GET['warehouse_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$dispatches = $dispatchService->searchDispatches(
    $search ?: null,
    $status ?: null,
    $warehouseId ? intval($warehouseId) : null,
    $dateFrom ?: null,
    $dateTo ?: null
);

$warehouses = $warehouseService->getActiveWarehouses();

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Status badge colors
$statusColors = [
    'pending' => 'badge-secondary',
    'approved' => 'badge-info',
    'shipped' => 'badge-info',
    'in_transit' => 'badge-info',
    'delivered' => 'badge-success',
    'cancelled' => 'badge-danger'
];

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dispatches</h1>
            <p class="text-gray-600">Manage product dispatches and shipments</p>
        </div>
        <a href="<?php echo url('/admin/sar-inventory/dispatches/create.php'); ?>" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            New Dispatch
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

<!-- Search and Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="md:col-span-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search dispatch number or address..." 
                       class="form-input w-full">
            </div>
            <div>
                <select name="status" class="form-select w-full">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                    <option value="in_transit" <?php echo $status === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div>
                <select name="warehouse_id" class="form-select w-full">
                    <option value="">All Warehouses</option>
                    <?php foreach ($warehouses as $warehouse): ?>
                    <option value="<?php echo $warehouse['id']; ?>" <?php echo $warehouseId == $warehouse['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($warehouse['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" 
                       class="form-input w-full" placeholder="From Date">
            </div>
            <div class="flex gap-2">
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" 
                       class="form-input flex-1" placeholder="To Date">
                <button type="submit" class="btn btn-secondary">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </button>
                <?php if ($search || $status || $warehouseId || $dateFrom || $dateTo): ?>
                <a href="<?php echo url('/admin/sar-inventory/dispatches/'); ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php
    $pendingCount = count(array_filter($dispatches, fn($d) => $d['status'] === 'pending'));
    $shippedCount = count(array_filter($dispatches, fn($d) => in_array($d['status'], ['shipped', 'in_transit'])));
    $deliveredCount = count(array_filter($dispatches, fn($d) => $d['status'] === 'delivered'));
    $totalCount = count($dispatches);
    ?>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-gray-600"><?php echo $pendingCount; ?></div>
            <div class="text-sm text-gray-500">Pending</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-blue-600"><?php echo $shippedCount; ?></div>
            <div class="text-sm text-gray-500">In Transit</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-green-600"><?php echo $deliveredCount; ?></div>
            <div class="text-sm text-gray-500">Delivered</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-gray-900"><?php echo $totalCount; ?></div>
            <div class="text-sm text-gray-500">Total</div>
        </div>
    </div>
</div>

<!-- Dispatches Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Dispatch #</th>
                    <th>Source Warehouse</th>
                    <th>Destination</th>
                    <th>Items</th>
                    <th>Dispatch Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dispatches)): ?>
                <tr>
                    <td colspan="7" class="text-center py-8 text-gray-500">
                        No dispatches found. <a href="<?php echo url('/admin/sar-inventory/dispatches/create.php'); ?>" class="text-blue-600 hover:underline">Create your first dispatch</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($dispatches as $dispatch): 
                    $statusClass = $statusColors[$dispatch['status']] ?? 'badge-secondary';
                ?>
                <tr>
                    <td class="font-medium">
                        <a href="<?php echo url('/admin/sar-inventory/dispatches/view.php?id=' . $dispatch['id']); ?>" class="text-blue-600 hover:underline">
                            <?php echo htmlspecialchars($dispatch['dispatch_number']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($dispatch['source_warehouse_name'] ?? '-'); ?></td>
                    <td>
                        <span class="text-xs text-gray-500 uppercase"><?php echo htmlspecialchars($dispatch['destination_type']); ?></span><br>
                        <?php echo htmlspecialchars(substr($dispatch['destination_address'] ?? '-', 0, 30)); ?>
                        <?php if (strlen($dispatch['destination_address'] ?? '') > 30): ?>...<?php endif; ?>
                    </td>
                    <td>
                        <?php 
                        $items = $dispatchService->getDispatchItems($dispatch['id']);
                        echo count($items) . ' item(s)';
                        ?>
                    </td>
                    <td>
                        <?php echo $dispatch['dispatch_date'] ? date('M j, Y', strtotime($dispatch['dispatch_date'])) : '-'; ?>
                    </td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $dispatch['status'])); ?></span></td>
                    <td>
                        <div class="flex space-x-2">
                            <a href="<?php echo url('/admin/sar-inventory/dispatches/view.php?id=' . $dispatch['id']); ?>" 
                               class="btn btn-sm btn-info" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <?php if (in_array($dispatch['status'], ['pending', 'approved'])): ?>
                            <a href="<?php echo url('/admin/sar-inventory/dispatches/update-status.php?id=' . $dispatch['id']); ?>" 
                               class="btn btn-sm btn-primary" title="Update Status">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </a>
                            <?php endif; ?>
                            <a href="<?php echo url('/admin/sar-inventory/dispatches/shipping-label.php?id=' . $dispatch['id']); ?>" 
                               class="btn btn-sm btn-secondary" title="Shipping Label" target="_blank">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
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
