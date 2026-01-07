<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvMaterialService.php';
require_once '../../../models/SarInvMaterialMaster.php';

Auth::requireRole(ADMIN_ROLE);

$materialService = new SarInvMaterialService();

// Get material ID
$materialId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$materialId) {
    $_SESSION['error'] = 'Material ID is required';
    header('Location: ' . url('/admin/sar-inventory/materials/'));
    exit;
}

// Get material details
$material = $materialService->getMaterialMaster($materialId);

if (!$material) {
    $_SESSION['error'] = 'Material not found';
    header('Location: ' . url('/admin/sar-inventory/materials/'));
    exit;
}

$title = 'Material: ' . $material['code'];
$currentUser = Auth::getCurrentUser();

// Get related requests
$requests = $materialService->searchRequests(null, null, null, null, null, 10, 0);
$relatedRequests = array_filter($requests, fn($r) => $r['material_master_id'] == $materialId);

// Parse specifications
$specifications = [];
if (!empty($material['specifications'])) {
    $specifications = json_decode($material['specifications'], true) ?: [];
}

// Status badge colors
$statusColors = [
    'active' => 'badge-success',
    'inactive' => 'badge-secondary'
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
            <a href="<?php echo url('/admin/sar-inventory/materials/'); ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($material['name']); ?></h1>
                <p class="text-gray-600 font-mono"><?php echo htmlspecialchars($material['code']); ?></p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo url('/admin/sar-inventory/materials/requests/create.php?material_id=' . $material['id']); ?>" class="btn btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                Create Request
            </a>
            <a href="<?php echo url('/admin/sar-inventory/materials/edit.php?id=' . $material['id']); ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit
            </a>
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
            <div class="card-header">
                <h3 class="text-lg font-semibold">Material Details</h3>
            </div>
            <div class="card-body">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Code</dt>
                        <dd class="mt-1 text-sm text-gray-900 font-mono"><?php echo htmlspecialchars($material['code']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Name</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($material['name']); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Unit of Measure</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($material['unit_of_measure'] ?? '-'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Default Quantity</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo number_format($material['default_quantity'] ?? 0); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="badge <?php echo $statusColors[$material['status']] ?? 'badge-secondary'; ?>">
                                <?php echo ucfirst($material['status']); ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($material['created_at'])); ?></dd>
                    </div>
                    <?php if (!empty($material['description'])): ?>
                    <div class="md:col-span-2">
                        <dt class="text-sm font-medium text-gray-500">Description</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($material['description'])); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

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
                        <dd class="mt-1 text-sm text-gray-900">
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

        <!-- Related Requests -->
        <div class="card">
            <div class="card-header flex justify-between items-center">
                <h3 class="text-lg font-semibold">Recent Requests</h3>
                <a href="<?php echo url('/admin/sar-inventory/materials/requests/?material_id=' . $material['id']); ?>" class="text-sm text-blue-600 hover:underline">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Request #</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Requester</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($relatedRequests)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500">No requests found for this material</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach (array_slice($relatedRequests, 0, 5) as $request): ?>
                        <tr>
                            <td>
                                <a href="<?php echo url('/admin/sar-inventory/materials/requests/view.php?id=' . $request['id']); ?>" class="text-blue-600 hover:underline font-mono text-sm">
                                    <?php echo htmlspecialchars($request['request_number']); ?>
                                </a>
                            </td>
                            <td><?php echo number_format($request['quantity']); ?></td>
                            <td><span class="badge badge-<?php echo $request['status'] === 'fulfilled' ? 'success' : ($request['status'] === 'pending' ? 'warning' : 'secondary'); ?>"><?php echo ucfirst($request['status']); ?></span></td>
                            <td><?php echo htmlspecialchars($request['requester_name'] ?? '-'); ?></td>
                            <td class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
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
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Quick Actions</h3>
            </div>
            <div class="card-body space-y-3">
                <a href="<?php echo url('/admin/sar-inventory/materials/requests/create.php?material_id=' . $material['id']); ?>" class="btn btn-primary w-full">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Request
                </a>
                <a href="<?php echo url('/admin/sar-inventory/materials/edit.php?id=' . $material['id']); ?>" class="btn btn-secondary w-full">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Material
                </a>
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
                        <dd class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($material['created_at'])); ?></dd>
                    </div>
                    <?php if (!empty($material['updated_at'])): ?>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($material['updated_at'])); ?></dd>
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
