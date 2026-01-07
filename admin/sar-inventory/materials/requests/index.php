<?php
require_once '../../../../config/auth.php';
require_once '../../../../config/database.php';
require_once '../../../../services/SarInvMaterialService.php';
require_once '../../../../models/SarInvMaterialRequest.php';
require_once '../../../../models/User.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Material Requests';
$currentUser = Auth::getCurrentUser();

$materialService = new SarInvMaterialService();
$userModel = new User();

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$requesterId = $_GET['requester_id'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$materialId = $_GET['material_id'] ?? '';

// Get requests with filters
$requests = $materialService->searchRequests(
    $search ?: null,
    $status ?: null,
    $requesterId ? intval($requesterId) : null,
    $dateFrom ?: null,
    $dateTo ?: null
);

// Filter by material_id if provided
if ($materialId) {
    $requests = array_filter($requests, fn($r) => $r['material_master_id'] == $materialId);
}

// Get users for filter dropdown
$users = $userModel->findAll();

// Get statistics
$statistics = $materialService->getRequestStatistics();

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Status options
$statusOptions = [
    SarInvMaterialRequest::STATUS_PENDING => 'Pending',
    SarInvMaterialRequest::STATUS_APPROVED => 'Approved',
    SarInvMaterialRequest::STATUS_REJECTED => 'Rejected',
    SarInvMaterialRequest::STATUS_FULFILLED => 'Fulfilled',
    SarInvMaterialRequest::STATUS_PARTIALLY_FULFILLED => 'Partially Fulfilled',
    SarInvMaterialRequest::STATUS_CANCELLED => 'Cancelled'
];

// Status badge colors
$statusColors = [
    'pending' => 'badge-warning',
    'approved' => 'badge-primary',
    'rejected' => 'badge-danger',
    'fulfilled' => 'badge-success',
    'partially_fulfilled' => 'badge-info',
    'cancelled' => 'badge-secondary'
];

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Material Requests</h1>
            <p class="text-gray-600">Manage material requests and approvals</p>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo url('/admin/sar-inventory/materials/'); ?>" class="btn btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Material Masters
            </a>
            <a href="<?php echo url('/admin/sar-inventory/materials/requests/create.php'); ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Request
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
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
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $statistics['pending_count'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Approved</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $statistics['approved_count'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-green-100 text-green-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Fulfilled</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $statistics['fulfilled_count'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-red-100 text-red-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Rejected</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $statistics['rejected_count'] ?? 0; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $statistics['total_requests'] ?? 0; ?></p>
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
            <?php if ($materialId): ?>
            <input type="hidden" name="material_id" value="<?php echo htmlspecialchars($materialId); ?>">
            <?php endif; ?>
            <div class="form-group mb-0">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="form-input" placeholder="Request #, material">
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
                <label class="form-label">Requester</label>
                <select name="requester_id" class="form-select">
                    <option value="">All Requesters</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo $requesterId == $user['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
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
                <a href="<?php echo url('/admin/sar-inventory/materials/requests/'); ?>" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Request Number</th>
                    <th>Material</th>
                    <th>Quantity</th>
                    <th>Fulfilled</th>
                    <th>Status</th>
                    <th>Requester</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="9" class="text-center py-8 text-gray-500">
                        No requests found. <a href="<?php echo url('/admin/sar-inventory/materials/requests/create.php'); ?>" class="text-blue-600 hover:underline">Create your first request</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php $serialNo = 1; ?>
                <?php foreach ($requests as $request): 
                    $statusClass = $statusColors[$request['status']] ?? 'badge-secondary';
                    $progress = $request['quantity'] > 0 ? round(($request['fulfilled_quantity'] / $request['quantity']) * 100) : 0;
                ?>
                <tr>
                    <td class="text-center text-gray-500"><?php echo $serialNo++; ?></td>
                    <td class="font-mono text-sm">
                        <a href="<?php echo url('/admin/sar-inventory/materials/requests/view.php?id=' . $request['id']); ?>" 
                           class="text-blue-600 hover:underline font-medium">
                            <?php echo htmlspecialchars($request['request_number']); ?>
                        </a>
                    </td>
                    <td>
                        <?php if (!empty($request['material_name'])): ?>
                        <?php echo htmlspecialchars($request['material_name']); ?>
                        <span class="text-xs text-gray-500 font-mono">(<?php echo htmlspecialchars($request['material_code']); ?>)</span>
                        <?php elseif (!empty($request['product_name'])): ?>
                        <?php echo htmlspecialchars($request['product_name']); ?>
                        <?php else: ?>
                        <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right"><?php echo number_format($request['quantity']); ?></td>
                    <td class="text-right">
                        <?php echo number_format($request['fulfilled_quantity'] ?? 0); ?>
                        <?php if ($request['quantity'] > 0 && $request['fulfilled_quantity'] > 0): ?>
                        <span class="text-xs text-gray-500">(<?php echo $progress; ?>%)</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?></span></td>
                    <td><?php echo htmlspecialchars($request['requester_name'] ?? '-'); ?></td>
                    <td class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                    <td>
                        <div class="flex space-x-2">
                            <a href="<?php echo url('/admin/sar-inventory/materials/requests/view.php?id=' . $request['id']); ?>" 
                               class="btn btn-sm btn-secondary" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <?php if ($request['status'] === SarInvMaterialRequest::STATUS_PENDING): ?>
                            <a href="<?php echo url('/admin/sar-inventory/materials/requests/approve.php?id=' . $request['id']); ?>" 
                               class="btn btn-sm btn-primary" title="Approve/Reject">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
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
include '../../../../includes/admin_layout.php';
?>
