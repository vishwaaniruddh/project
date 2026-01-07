<?php
require_once '../../../../config/auth.php';
require_once '../../../../config/database.php';
require_once '../../../../services/SarInvMaterialService.php';
require_once '../../../../models/SarInvMaterialRequest.php';

Auth::requireRole(ADMIN_ROLE);

$materialService = new SarInvMaterialService();

// Get request ID
$requestId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$requestId) {
    $_SESSION['error'] = 'Request ID is required';
    header('Location: ' . url('/admin/sar-inventory/materials/requests/'));
    exit;
}

// Get request details
$request = $materialService->getRequestWithDetails($requestId);

if (!$request) {
    $_SESSION['error'] = 'Request not found';
    header('Location: ' . url('/admin/sar-inventory/materials/requests/'));
    exit;
}

$title = 'Request: ' . $request['request_number'];
$currentUser = Auth::getCurrentUser();

// Get fulfillment progress
$progress = $materialService->getFulfillmentProgress($requestId);

// Status badge colors
$statusColors = [
    'pending' => 'badge-warning',
    'approved' => 'badge-primary',
    'rejected' => 'badge-danger',
    'fulfilled' => 'badge-success',
    'partially_fulfilled' => 'badge-info',
    'cancelled' => 'badge-secondary'
];

// Get success/error messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="<?php echo url('/admin/sar-inventory/materials/requests/'); ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Material Request</h1>
                <p class="text-gray-600 font-mono"><?php echo htmlspecialchars($request['request_number']); ?></p>
            </div>
        </div>
        <div class="flex gap-2">
            <?php if ($request['status'] === SarInvMaterialRequest::STATUS_PENDING): ?>
            <a href="<?php echo url('/admin/sar-inventory/materials/requests/approve.php?id=' . $request['id']); ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Approve/Reject
            </a>
            <?php elseif (in_array($request['status'], [SarInvMaterialRequest::STATUS_APPROVED, SarInvMaterialRequest::STATUS_PARTIALLY_FULFILLED])): ?>
            <a href="<?php echo url('/admin/sar-inventory/materials/requests/fulfill.php?id=' . $request['id']); ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Fulfill
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
    <!-- Main Details -->
    <div class="lg:col-span-2 space-y-6">
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h3 class="text-lg font-semibold">Request Details</h3>
                <span class="badge <?php echo $statusColors[$request['status']] ?? 'badge-secondary'; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                </span>
            </div>
            <div class="card-body">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Request Number</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($request['request_number']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Material/Product</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php if (!empty($request['material_name'])): ?>
                            <?php echo htmlspecialchars($request['material_name']); ?>
                            <span class="text-xs text-gray-500 font-mono">(<?php echo htmlspecialchars($request['material_code']); ?>)</span>
                            <?php elseif (!empty($request['product_name'])): ?>
                            <?php echo htmlspecialchars($request['product_name']); ?>
                            <span class="text-xs text-gray-500 font-mono">(<?php echo htmlspecialchars($request['product_sku']); ?>)</span>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Requested Quantity</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo number_format($request['quantity']); ?>
                            <?php echo htmlspecialchars($request['material_uom'] ?? $request['product_uom'] ?? ''); ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Fulfilled Quantity</dt>
                        <dd class="mt-1 text-sm text-gray-900">
                            <?php echo number_format($request['fulfilled_quantity'] ?? 0); ?>
                            <?php echo htmlspecialchars($request['material_uom'] ?? $request['product_uom'] ?? ''); ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Requester</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($request['requester_name'] ?? '-'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Approver</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($request['approver_name'] ?? '-'); ?></dd>
                    </div>
                    <?php if (!empty($request['notes'])): ?>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Notes</dt>
                        <dd class="mt-1 text-sm text-gray-900 whitespace-pre-line"><?php echo htmlspecialchars($request['notes']); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <!-- Fulfillment Progress -->
        <?php if ($progress && $request['quantity'] > 0): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Fulfillment Progress</h3>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-600">Progress</span>
                        <span class="font-medium"><?php echo $progress['percentage']; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-3">
                        <div class="bg-blue-600 h-3 rounded-full transition-all duration-300" 
                             style="width: <?php echo min(100, $progress['percentage']); ?>%"></div>
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <p class="text-2xl font-bold text-gray-900"><?php echo number_format($progress['requested']); ?></p>
                        <p class="text-sm text-gray-500">Requested</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($progress['fulfilled']); ?></p>
                        <p class="text-sm text-gray-500">Fulfilled</p>
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-orange-600"><?php echo number_format($progress['remaining']); ?></p>
                        <p class="text-sm text-gray-500">Remaining</p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Quick Actions</h3>
            </div>
            <div class="card-body space-y-3">
                <?php if ($request['status'] === SarInvMaterialRequest::STATUS_PENDING): ?>
                <a href="<?php echo url('/admin/sar-inventory/materials/requests/approve.php?id=' . $request['id']); ?>" class="btn btn-success w-full">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Approve Request
                </a>
                <a href="<?php echo url('/admin/sar-inventory/materials/requests/approve.php?id=' . $request['id'] . '&action=reject'); ?>" class="btn btn-danger w-full">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Reject Request
                </a>
                <?php elseif (in_array($request['status'], [SarInvMaterialRequest::STATUS_APPROVED, SarInvMaterialRequest::STATUS_PARTIALLY_FULFILLED])): ?>
                <a href="<?php echo url('/admin/sar-inventory/materials/requests/fulfill.php?id=' . $request['id']); ?>" class="btn btn-primary w-full">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Fulfill Request
                </a>
                <?php endif; ?>
                
                <?php if (!in_array($request['status'], [SarInvMaterialRequest::STATUS_FULFILLED, SarInvMaterialRequest::STATUS_CANCELLED])): ?>
                <form method="POST" action="<?php echo url('/admin/sar-inventory/materials/requests/cancel.php'); ?>" 
                      onsubmit="return confirm('Are you sure you want to cancel this request?');">
                    <input type="hidden" name="id" value="<?php echo $request['id']; ?>">
                    <button type="submit" class="btn btn-secondary w-full">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                        Cancel Request
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Timestamps</h3>
            </div>
            <div class="card-body">
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created At</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($request['created_at'])); ?></dd>
                    </div>
                    <?php if (!empty($request['updated_at'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($request['updated_at'])); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <?php if (!empty($request['material_master_id'])): ?>
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Material Info</h3>
            </div>
            <div class="card-body">
                <a href="<?php echo url('/admin/sar-inventory/materials/view.php?id=' . $request['material_master_id']); ?>" 
                   class="text-blue-600 hover:underline">
                    View Material Master →
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../../includes/admin_layout.php';
?>
