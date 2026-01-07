<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvRepairService.php';
require_once '../../../models/SarInvRepair.php';
require_once '../../../models/Vendor.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$currentUser = Auth::getCurrentUser();

$repairService = new SarInvRepairService();
$vendorModel = new Vendor();
$warehouseService = new SarInvWarehouseService();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'Invalid repair ID';
    header('Location: ' . url('/admin/sar-inventory/repairs/'));
    exit;
}

$repair = $repairService->getRepairWithDetails($id);
if (!$repair) {
    $_SESSION['error'] = 'Repair not found';
    header('Location: ' . url('/admin/sar-inventory/repairs/'));
    exit;
}

$title = 'Repair: ' . $repair['repair_number'];

// Get vendor info if assigned
$vendor = null;
if (!empty($repair['vendor_id'])) {
    $vendor = $vendorModel->find($repair['vendor_id']);
}

// Get warehouses for return location
$warehouses = $warehouseService->getAllWarehouses();

// Get valid status transitions
$validTransitions = $repairService->getValidStatusTransitions($id);

// Get audit history
$auditHistory = $repairService->getAuditHistory($id, 20);

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Status badge colors
$statusColors = [
    'pending' => 'badge-warning',
    'in_progress' => 'badge-primary',
    'completed' => 'badge-success',
    'cancelled' => 'badge-secondary'
];
$statusClass = $statusColors[$repair['status']] ?? 'badge-secondary';

// Status labels
$statusLabels = [
    'pending' => 'Pending',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="<?php echo url('/admin/sar-inventory/repairs/'); ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($repair['repair_number']); ?></h1>
                <p class="text-gray-600"><?php echo htmlspecialchars($repair['product_name'] ?? 'Unknown Product'); ?></p>
            </div>
        </div>
        <div class="flex gap-2">
            <?php if (in_array($repair['status'], [SarInvRepair::STATUS_PENDING, SarInvRepair::STATUS_IN_PROGRESS])): ?>
            <a href="<?php echo url('/admin/sar-inventory/repairs/update.php?id=' . $id); ?>" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Update Repair
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
    <!-- Repair Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Basic Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Repair Information</h3>
            </div>
            <div class="card-body">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Repair Number</dt>
                        <dd class="mt-1 font-mono text-gray-900"><?php echo htmlspecialchars($repair['repair_number']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="badge <?php echo $statusClass; ?>">
                                <?php echo $statusLabels[$repair['status']] ?? ucfirst($repair['status']); ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created Date</dt>
                        <dd class="mt-1 text-gray-900"><?php echo date('M j, Y H:i', strtotime($repair['created_at'])); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                        <dd class="mt-1 text-gray-900">
                            <?php echo !empty($repair['start_date']) ? date('M j, Y', strtotime($repair['start_date'])) : '-'; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Completion Date</dt>
                        <dd class="mt-1 text-gray-900">
                            <?php echo !empty($repair['completion_date']) ? date('M j, Y', strtotime($repair['completion_date'])) : '-'; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Cost</dt>
                        <dd class="mt-1 text-gray-900">
                            <?php if (!empty($repair['cost'])): ?>
                            <span class="font-semibold">₹<?php echo number_format($repair['cost'], 2); ?></span>
                            <?php else: ?>
                            <span class="text-gray-400">Not specified</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Asset Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Asset Information</h3>
            </div>
            <div class="card-body">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Product</dt>
                        <dd class="mt-1 text-gray-900">
                            <a href="<?php echo url('/admin/sar-inventory/products/view.php?id=' . ($repair['product_id'] ?? '')); ?>" 
                               class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($repair['product_name'] ?? 'Unknown'); ?>
                            </a>
                            <?php if (!empty($repair['sku'])): ?>
                            <span class="text-gray-500 text-sm ml-1">(<?php echo htmlspecialchars($repair['sku']); ?>)</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Serial Number</dt>
                        <dd class="mt-1 font-mono text-gray-900">
                            <a href="<?php echo url('/admin/sar-inventory/assets/view.php?id=' . $repair['asset_id']); ?>" 
                               class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($repair['serial_number'] ?? '-'); ?>
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Barcode</dt>
                        <dd class="mt-1 font-mono text-gray-900"><?php echo htmlspecialchars($repair['barcode'] ?? '-'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Asset Status</dt>
                        <dd class="mt-1">
                            <span class="badge <?php echo $repair['asset_status'] === 'in_repair' ? 'badge-warning' : 'badge-secondary'; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $repair['asset_status'] ?? 'unknown')); ?>
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Issue and Diagnosis -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Issue & Diagnosis</h3>
            </div>
            <div class="card-body space-y-4">
                <div>
                    <dt class="text-sm font-medium text-gray-500 mb-2">Issue Description</dt>
                    <dd class="p-3 bg-gray-50 rounded-lg text-gray-900">
                        <?php echo nl2br(htmlspecialchars($repair['issue_description'] ?? 'No description provided')); ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 mb-2">Diagnosis</dt>
                    <dd class="p-3 bg-gray-50 rounded-lg text-gray-900">
                        <?php echo !empty($repair['diagnosis']) ? nl2br(htmlspecialchars($repair['diagnosis'])) : '<span class="text-gray-400">No diagnosis recorded</span>'; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 mb-2">Repair Notes</dt>
                    <dd class="p-3 bg-gray-50 rounded-lg text-gray-900">
                        <?php echo !empty($repair['repair_notes']) ? nl2br(htmlspecialchars($repair['repair_notes'])) : '<span class="text-gray-400">No repair notes</span>'; ?>
                    </dd>
                </div>
            </div>
        </div>

        <!-- Vendor Information -->
        <?php if ($vendor): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Vendor Information</h3>
            </div>
            <div class="card-body">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Vendor Name</dt>
                        <dd class="mt-1 text-gray-900"><?php echo htmlspecialchars($vendor['name']); ?></dd>
                    </div>
                    <?php if (!empty($vendor['company_name'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Company</dt>
                        <dd class="mt-1 text-gray-900"><?php echo htmlspecialchars($vendor['company_name']); ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($vendor['email'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Email</dt>
                        <dd class="mt-1 text-gray-900">
                            <a href="mailto:<?php echo htmlspecialchars($vendor['email']); ?>" class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($vendor['email']); ?>
                            </a>
                        </dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($vendor['phone'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Phone</dt>
                        <dd class="mt-1 text-gray-900"><?php echo htmlspecialchars($vendor['phone']); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Status Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Current Status</h3>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <span class="inline-flex items-center justify-center w-16 h-16 rounded-full <?php 
                        echo $repair['status'] === 'completed' ? 'bg-green-100' : 
                            ($repair['status'] === 'in_progress' ? 'bg-blue-100' : 
                            ($repair['status'] === 'pending' ? 'bg-yellow-100' : 'bg-gray-100')); 
                    ?>">
                        <?php if ($repair['status'] === 'completed'): ?>
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <?php elseif ($repair['status'] === 'in_progress'): ?>
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <?php elseif ($repair['status'] === 'pending'): ?>
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php else: ?>
                        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <?php endif; ?>
                    </span>
                    <p class="mt-3 text-lg font-semibold <?php 
                        echo $repair['status'] === 'completed' ? 'text-green-600' : 
                            ($repair['status'] === 'in_progress' ? 'text-blue-600' : 
                            ($repair['status'] === 'pending' ? 'text-yellow-600' : 'text-gray-600')); 
                    ?>">
                        <?php echo $statusLabels[$repair['status']] ?? ucfirst($repair['status']); ?>
                    </p>
                    <?php if (!empty($repair['cost'])): ?>
                    <p class="text-sm text-gray-500 mt-1">Cost: ₹<?php echo number_format($repair['cost'], 2); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <?php if (!empty($validTransitions)): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Quick Actions</h3>
            </div>
            <div class="card-body space-y-2">
                <a href="<?php echo url('/admin/sar-inventory/repairs/update.php?id=' . $id); ?>" 
                   class="btn btn-primary w-full justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Update Repair
                </a>
                <?php if ($repair['status'] === SarInvRepair::STATUS_PENDING): ?>
                <form method="POST" action="<?php echo url('/admin/sar-inventory/repairs/update.php?id=' . $id); ?>" class="w-full">
                    <input type="hidden" name="action" value="start">
                    <button type="submit" class="btn btn-secondary w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Start Repair
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Timeline -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Timeline</h3>
            </div>
            <div class="card-body">
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Created</p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y H:i', strtotime($repair['created_at'])); ?></p>
                        </div>
                    </div>
                    <?php if (!empty($repair['start_date'])): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Started</p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($repair['start_date'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($repair['completion_date'])): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-green-100">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Completed</p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($repair['completion_date'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($repair['status'] === 'cancelled'): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-red-100">
                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </span>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Cancelled</p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y H:i', strtotime($repair['updated_at'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
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
                        <dt class="text-gray-500">Repair ID</dt>
                        <dd class="text-gray-900"><?php echo $repair['id']; ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Asset ID</dt>
                        <dd class="text-gray-900"><?php echo $repair['asset_id']; ?></dd>
                    </div>
                    <?php if (!empty($repair['created_by'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Created By</dt>
                        <dd class="text-gray-900">User #<?php echo $repair['created_by']; ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($repair['updated_at'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Last Updated</dt>
                        <dd class="text-gray-900"><?php echo date('M j, Y H:i', strtotime($repair['updated_at'])); ?></dd>
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
