<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvRepairService.php';
require_once '../../../models/SarInvRepair.php';
require_once '../../../models/Vendor.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Repairs';
$currentUser = Auth::getCurrentUser();

$repairService = new SarInvRepairService();
$vendorModel = new Vendor();

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$vendorId = $_GET['vendor_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Get repairs with filters
$repairs = $repairService->searchRepairs(
    $search ?: null,
    $status ?: null,
    $vendorId ? intval($vendorId) : null,
    $dateFrom ?: null,
    $dateTo ?: null
);

// Get vendors for filter dropdown
$vendors = $vendorModel->getActiveVendors();

// Get statistics
$statistics = $repairService->getStatistics();
$pendingCount = 0;
$inProgressCount = 0;
$completedCount = 0;
$totalCost = 0;

foreach ($statistics as $stat) {
    if ($stat['status'] === SarInvRepair::STATUS_PENDING) {
        $pendingCount = $stat['count'];
    } elseif ($stat['status'] === SarInvRepair::STATUS_IN_PROGRESS) {
        $inProgressCount = $stat['count'];
    } elseif ($stat['status'] === SarInvRepair::STATUS_COMPLETED) {
        $completedCount = $stat['count'];
        $totalCost = $stat['total_cost'] ?? 0;
    }
}

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Status options
$statusOptions = [
    SarInvRepair::STATUS_PENDING => 'Pending',
    SarInvRepair::STATUS_IN_PROGRESS => 'In Progress',
    SarInvRepair::STATUS_COMPLETED => 'Completed',
    SarInvRepair::STATUS_CANCELLED => 'Cancelled'
];

// Status badge colors
$statusColors = [
    'pending' => 'badge-warning',
    'in_progress' => 'badge-primary',
    'completed' => 'badge-success',
    'cancelled' => 'badge-secondary'
];

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Repairs</h1>
            <p class="text-gray-600">Manage asset repair and maintenance operations</p>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo url('/admin/sar-inventory/repairs/create.php'); ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Repair
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Pending</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $pendingCount; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">In Progress</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $inProgressCount; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Completed</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $completedCount; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Cost</p>
                    <p class="text-2xl font-semibold text-gray-900">₹<?php echo number_format($totalCost, 2); ?></p>
                </div>
            </div>
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

<!-- Search and Filter -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-4">
            <div class="form-group mb-0">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="form-input" placeholder="Repair #, serial, product">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach ($statusOptions as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $status === $key ? 'selected' : ''; ?>>
                        <?php echo $label; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Vendor</label>
                <select name="vendor_id" class="form-select">
                    <option value="">All Vendors</option>
                    <?php foreach ($vendors as $vendor): ?>
                    <option value="<?php echo $vendor['id']; ?>" <?php echo $vendorId == $vendor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($vendor['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Date From</label>
                <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="form-input">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Date To</label>
                <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="form-input">
            </div>
            <div class="form-group mb-0 flex items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search
                </button>
                <a href="<?php echo url('/admin/sar-inventory/repairs/'); ?>" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Repairs Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Repair Number</th>
                    <th>Asset</th>
                    <th>Product</th>
                    <th>Issue</th>
                    <th>Status</th>
                    <th>Cost</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($repairs)): ?>
                <tr>
                    <td colspan="9" class="text-center py-8 text-gray-500">
                        No repairs found. <a href="<?php echo url('/admin/sar-inventory/repairs/create.php'); ?>" class="text-blue-600 hover:underline">Create your first repair</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php $serialNo = 1; ?>
                <?php foreach ($repairs as $repair): 
                    $statusClass = $statusColors[$repair['status']] ?? 'badge-secondary';
                ?>
                <tr>
                    <td class="text-center text-gray-500"><?php echo $serialNo++; ?></td>
                    <td class="font-mono text-sm">
                        <a href="<?php echo url('/admin/sar-inventory/repairs/view.php?id=' . $repair['id']); ?>" 
                           class="text-blue-600 hover:underline font-medium">
                            <?php echo htmlspecialchars($repair['repair_number']); ?>
                        </a>
                    </td>
                    <td class="font-mono text-sm"><?php echo htmlspecialchars($repair['serial_number'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($repair['product_name'] ?? '-'); ?></td>
                    <td class="max-w-xs truncate" title="<?php echo htmlspecialchars($repair['issue_description'] ?? ''); ?>">
                        <?php echo htmlspecialchars(substr($repair['issue_description'] ?? '-', 0, 50)); ?>
                        <?php if (strlen($repair['issue_description'] ?? '') > 50): ?>...<?php endif; ?>
                    </td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $repair['status'])); ?></span></td>
                    <td>
                        <?php if (!empty($repair['cost'])): ?>
                        ₹<?php echo number_format($repair['cost'], 2); ?>
                        <?php else: ?>
                        <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($repair['created_at'])); ?></td>
                    <td>
                        <div class="flex space-x-2">
                            <a href="<?php echo url('/admin/sar-inventory/repairs/view.php?id=' . $repair['id']); ?>" 
                               class="btn btn-sm btn-secondary" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <?php if (in_array($repair['status'], [SarInvRepair::STATUS_PENDING, SarInvRepair::STATUS_IN_PROGRESS])): ?>
                            <a href="<?php echo url('/admin/sar-inventory/repairs/update.php?id=' . $repair['id']); ?>" 
                               class="btn btn-sm btn-primary" title="Update">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
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
