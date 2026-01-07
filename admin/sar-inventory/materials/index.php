<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvMaterialService.php';
require_once '../../../models/SarInvMaterialMaster.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Material Masters';
$currentUser = Auth::getCurrentUser();

$materialService = new SarInvMaterialService();

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';

// Get materials with filters
$materials = $materialService->searchMaterialMasters(
    $search ?: null,
    $status ?: null
);

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Status options
$statusOptions = [
    SarInvMaterialMaster::STATUS_ACTIVE => 'Active',
    SarInvMaterialMaster::STATUS_INACTIVE => 'Inactive'
];

// Status badge colors
$statusColors = [
    'active' => 'badge-success',
    'inactive' => 'badge-secondary'
];

// Get statistics
$allMaterials = $materialService->getAllMaterialMasters();
$activeCount = count(array_filter($allMaterials, fn($m) => $m['status'] === 'active'));
$inactiveCount = count($allMaterials) - $activeCount;

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Material Masters</h1>
            <p class="text-gray-600">Manage material templates and specifications</p>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo url('/admin/sar-inventory/materials/requests/'); ?>" class="btn btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                View Requests
            </a>
            <a href="<?php echo url('/admin/sar-inventory/materials/create.php'); ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Material
            </a>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Materials</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo count($allMaterials); ?></p>
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
                    <p class="text-sm text-gray-500">Active</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $activeCount; ?></p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="p-3 rounded-full bg-gray-100 text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Inactive</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $inactiveCount; ?></p>
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
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="form-group mb-0 md:col-span-2">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="form-input" placeholder="Name, code, or description">
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
            <div class="form-group mb-0 flex items-end gap-2">
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Search
                </button>
                <a href="<?php echo url('/admin/sar-inventory/materials/'); ?>" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Materials Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Unit</th>
                    <th>Default Qty</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($materials)): ?>
                <tr>
                    <td colspan="8" class="text-center py-8 text-gray-500">
                        No materials found. <a href="<?php echo url('/admin/sar-inventory/materials/create.php'); ?>" class="text-blue-600 hover:underline">Create your first material</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php $serialNo = 1; ?>
                <?php foreach ($materials as $material): 
                    $statusClass = $statusColors[$material['status']] ?? 'badge-secondary';
                ?>
                <tr>
                    <td class="text-center text-gray-500"><?php echo $serialNo++; ?></td>
                    <td class="font-mono text-sm font-medium">
                        <a href="<?php echo url('/admin/sar-inventory/materials/view.php?id=' . $material['id']); ?>" 
                           class="text-blue-600 hover:underline">
                            <?php echo htmlspecialchars($material['code']); ?>
                        </a>
                    </td>
                    <td><?php echo htmlspecialchars($material['name']); ?></td>
                    <td><?php echo htmlspecialchars($material['unit_of_measure'] ?? '-'); ?></td>
                    <td class="text-right"><?php echo number_format($material['default_quantity'] ?? 0); ?></td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($material['status']); ?></span></td>
                    <td class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($material['created_at'])); ?></td>
                    <td>
                        <div class="flex space-x-2">
                            <a href="<?php echo url('/admin/sar-inventory/materials/view.php?id=' . $material['id']); ?>" 
                               class="btn btn-sm btn-secondary" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="<?php echo url('/admin/sar-inventory/materials/edit.php?id=' . $material['id']); ?>" 
                               class="btn btn-sm btn-primary" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
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
