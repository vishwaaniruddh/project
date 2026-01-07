<?php
require_once '../../../../config/auth.php';
require_once '../../../../config/database.php';
require_once '../../../../services/SarInvMaterialService.php';
require_once '../../../../models/SarInvMaterialRequest.php';

Auth::requireRole(ADMIN_ROLE);

$materialService = new SarInvMaterialService();
$currentUser = Auth::getCurrentUser();

// Get request ID
$requestId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$defaultAction = $_GET['action'] ?? 'approve';

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

// Check if request can be approved/rejected
if ($request['status'] !== SarInvMaterialRequest::STATUS_PENDING) {
    $_SESSION['error'] = 'Only pending requests can be approved or rejected';
    header('Location: ' . url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId));
    exit;
}

$title = 'Approve/Reject Request: ' . $request['request_number'];

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $notes = trim($_POST['notes'] ?? '');
    
    if ($action === 'approve') {
        $result = $materialService->approveRequest($requestId, $currentUser['id'], $notes);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Request approved successfully';
            header('Location: ' . url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId));
            exit;
        } else {
            $errors = $result['errors'];
        }
    } elseif ($action === 'reject') {
        if (empty($notes)) {
            $errors[] = 'Rejection reason is required';
        } else {
            $result = $materialService->rejectRequest($requestId, $currentUser['id'], $notes);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Request rejected';
                header('Location: ' . url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId));
                exit;
            } else {
                $errors = $result['errors'];
            }
        }
    } else {
        $errors[] = 'Invalid action';
    }
}

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Approve/Reject Request</h1>
            <p class="text-gray-600 font-mono"><?php echo htmlspecialchars($request['request_number']); ?></p>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <ul class="list-disc list-inside">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Request Summary -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold">Request Summary</h3>
    </div>
    <div class="card-body">
        <dl class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Material/Product</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <?php if (!empty($request['material_name'])): ?>
                    <?php echo htmlspecialchars($request['material_name']); ?>
                    <span class="text-xs text-gray-500 font-mono">(<?php echo htmlspecialchars($request['material_code']); ?>)</span>
                    <?php elseif (!empty($request['product_name'])): ?>
                    <?php echo htmlspecialchars($request['product_name']); ?>
                    <?php else: ?>
                    <span class="text-gray-400">-</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Quantity</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <?php echo number_format($request['quantity']); ?>
                    <?php echo htmlspecialchars($request['material_uom'] ?? $request['product_uom'] ?? ''); ?>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Requester</dt>
                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($request['requester_name'] ?? '-'); ?></dd>
            </div>
            <?php if (!empty($request['notes'])): ?>
            <div class="md:col-span-3">
                <dt class="text-sm font-medium text-gray-500">Request Notes</dt>
                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($request['notes']); ?></dd>
            </div>
            <?php endif; ?>
        </dl>
    </div>
</div>

<!-- Approval Form -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Approve Card -->
    <div class="card <?php echo $defaultAction === 'approve' ? 'ring-2 ring-green-500' : ''; ?>">
        <div class="card-header bg-green-50">
            <h3 class="text-lg font-semibold text-green-800">Approve Request</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="approve">
                
                <div class="form-group">
                    <label class="form-label">Approval Notes (Optional)</label>
                    <textarea name="notes" rows="4" class="form-input"
                              placeholder="Add any notes for the approval"></textarea>
                </div>
                
                <button type="submit" class="btn btn-success w-full">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Approve Request
                </button>
            </form>
        </div>
    </div>

    <!-- Reject Card -->
    <div class="card <?php echo $defaultAction === 'reject' ? 'ring-2 ring-red-500' : ''; ?>">
        <div class="card-header bg-red-50">
            <h3 class="text-lg font-semibold text-red-800">Reject Request</h3>
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                
                <div class="form-group">
                    <label class="form-label required">Rejection Reason</label>
                    <textarea name="notes" rows="4" class="form-input" required
                              placeholder="Provide a reason for rejection"></textarea>
                    <p class="text-sm text-gray-500 mt-1">A reason is required when rejecting a request</p>
                </div>
                
                <button type="submit" class="btn btn-danger w-full">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Reject Request
                </button>
            </form>
        </div>
    </div>
</div>

<div class="mt-6">
    <a href="<?php echo url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId); ?>" class="btn btn-secondary">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
        </svg>
        Back to Request
    </a>
</div>

<?php
$content = ob_get_clean();
include '../../../../includes/admin_layout.php';
?>
