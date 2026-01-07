<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvTransferService.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Transfers Management';
$currentUser = Auth::getCurrentUser();

$transferService = new SarInvTransferService();
$warehouseService = new SarInvWarehouseService();

// Handle search and filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$sourceWarehouseId = $_GET['source_warehouse_id'] ?? '';
$destWarehouseId = $_GET['dest_warehouse_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$transfers = $transferService->searchTransfers(
    $search ?: null,
    $status ?: null,
    $sourceWarehouseId ? intval($sourceWarehouseId) : null,
    $destWarehouseId ? intval($destWarehouseId) : null,
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
    'in_transit' => 'badge-primary',
    'received' => 'badge-success',
    'cancelled' => 'badge-danger'
];

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Transfers</h1>
            <p class="text-gray-600">Manage inter-warehouse inventory transfers</p>
        </div>
        <a href="<?php echo url('/admin/sar-inventory/transfers/create.php'); ?>" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            New Transfer
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
            <div>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search transfer #..." 
                       class="form-input w-full">
            </div>
            <div>
                <select name="status" class="form-select w-full">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="in_transit" <?php echo $status === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                    <option value="received" <?php echo $status === 'received' ? 'selected' : ''; ?>>Received</option>
                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div>
                <select name="source_warehouse_id" class="form-select w-full">
                    <option value="">Source Warehouse</option>
                    <?php foreach ($warehouses as $warehouse): ?>
                    <option value="<?php echo $warehouse['id']; ?>" <?php echo $sourceWarehouseId == $warehouse['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($warehouse['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <select name="dest_warehouse_id" class="form-select w-full">
                    <option value="">Dest. Warehouse</option>
                    <?php foreach ($warehouses as $warehouse): ?>
                    <option value="<?php echo $warehouse['id']; ?>" <?php echo $destWarehouseId == $warehouse['id'] ? 'selected' : ''; ?>>
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
                <?php if ($search || $status || $sourceWarehouseId || $destWarehouseId || $dateFrom || $dateTo): ?>
                <a href="<?php echo url('/admin/sar-inventory/transfers/'); ?>" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <?php
    $pendingCount = count(array_filter($transfers, fn($t) => $t['status'] === 'pending'));
    $approvedCount = count(array_filter($transfers, fn($t) => $t['status'] === 'approved'));
    $inTransitCount = count(array_filter($transfers, fn($t) => $t['status'] === 'in_transit'));
    $receivedCount = count(array_filter($transfers, fn($t) => $t['status'] === 'received'));
    $totalCount = count($transfers);
    ?>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-gray-600"><?php echo $pendingCount; ?></div>
            <div class="text-sm text-gray-500">Pending</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-blue-600"><?php echo $approvedCount; ?></div>
            <div class="text-sm text-gray-500">Approved</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-indigo-600"><?php echo $inTransitCount; ?></div>
            <div class="text-sm text-gray-500">In Transit</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-green-600"><?php echo $receivedCount; ?></div>
            <div class="text-sm text-gray-500">Received</div>
        </div>
    </div>
    <div class="card">
        <div class="card-body text-center">
            <div class="text-2xl font-bold text-gray-900"><?php echo $totalCount; ?></div>
            <div class="text-sm text-gray-500">Total</div>
        </div>
    </div>
</div>

<!-- Transfers Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Transfer #</th>
                    <th>Source Warehouse</th>
                    <th>Destination Warehouse</th>
                    <th>Items</th>
                    <th>Transfer Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transfers)): ?>
                <tr>
                    <td colspan="7" class="text-center py-8 text-gray-500">
                        No transfers found. <a href="<?php echo url('/admin/sar-inventory/transfers/create.php'); ?>" class="text-blue-600 hover:underline">Create your first transfer</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($transfers as $transfer): 
                    $statusClass = $statusColors[$transfer['status']] ?? 'badge-secondary';
                ?>
                <tr>
                    <td class="font-medium">
                        <a href="<?php echo url('/admin/sar-inventory/transfers/view.php?id=' . $transfer['id']); ?>" class="text-blue-600 hover:underline">
                            <?php echo htmlspecialchars($transfer['transfer_number']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($transfer['source_warehouse_name'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($transfer['destination_warehouse_name'] ?? '-'); ?></td>
                    <td>
                        <?php 
                        $items = $transferService->getTransferItems($transfer['id']);
                        echo count($items) . ' item(s)';
                        ?>
                    </td>
                    <td>
                        <?php echo $transfer['transfer_date'] ? date('M j, Y', strtotime($transfer['transfer_date'])) : '-'; ?>
                    </td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?></span></td>
                    <td>
                        <div class="flex space-x-2">
                            <a href="<?php echo url('/admin/sar-inventory/transfers/view.php?id=' . $transfer['id']); ?>" 
                               class="btn btn-sm btn-info" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <?php if ($transfer['status'] === 'pending'): ?>
                            <a href="<?php echo url('/admin/sar-inventory/transfers/approve.php?id=' . $transfer['id']); ?>" 
                               class="btn btn-sm btn-success" title="Approve">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </a>
                            <?php endif; ?>
                            <?php if (in_array($transfer['status'], ['approved', 'in_transit'])): ?>
                            <a href="<?php echo url('/admin/sar-inventory/transfers/receive.php?id=' . $transfer['id']); ?>" 
                               class="btn btn-sm btn-primary" title="Receive">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                            </a>
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
