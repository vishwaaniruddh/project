<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Warehouses Management';
$currentUser = Auth::getCurrentUser();

$warehouseService = new SarInvWarehouseService();

// Handle search and filters
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$warehouses = $warehouseService->searchWarehouses($search ?: null, $status ?: null);

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Warehouses</h1>
            <p class="text-gray-600">Manage inventory storage locations</p>
        </div>
        <a href="<?php echo url('/admin/sar-inventory/warehouses/create.php'); ?>" class="btn btn-primary">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add Warehouse
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
        <form method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       placeholder="Search by name, code, or location..." 
                       class="form-input w-full">
            </div>
            <div class="w-full md:w-48">
                <select name="status" class="form-select w-full">
                    <option value="">All Status</option>
                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="maintenance" <?php echo $status === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                </select>
            </div>
            <button type="submit" class="btn btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Search
            </button>
            <?php if ($search || $status): ?>
            <a href="<?php echo url('/admin/sar-inventory/warehouses/'); ?>" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Warehouses Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Capacity</th>
                    <th>Utilization</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($warehouses)): ?>
                <tr>
                    <td colspan="8" class="text-center py-8 text-gray-500">
                        No warehouses found. <a href="<?php echo url('/admin/sar-inventory/warehouses/create.php'); ?>" class="text-blue-600 hover:underline">Add your first warehouse</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php $serialNo = 1; ?>
                <?php foreach ($warehouses as $warehouse): 
                    $utilization = $warehouseService->getCapacityUtilization($warehouse['id']);
                    $statusColors = [
                        'active' => 'badge-success',
                        'inactive' => 'badge-secondary',
                        'maintenance' => 'badge-info'
                    ];
                    $statusClass = $statusColors[$warehouse['status']] ?? 'badge-secondary';
                ?>
                <tr>
                    <td class="text-center text-gray-500"><?php echo $serialNo++; ?></td>
                    <td class="font-medium"><?php echo htmlspecialchars($warehouse['code']); ?></td>
                    <td><?php echo htmlspecialchars($warehouse['name']); ?></td>
                    <td><?php echo htmlspecialchars($warehouse['location'] ?? '-'); ?></td>
                    <td><?php echo $warehouse['capacity'] ? number_format($warehouse['capacity']) : '-'; ?></td>
                    <td>
                        <?php if ($warehouse['capacity']): ?>
                        <div class="flex items-center">
                            <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="<?php echo $utilization['utilization_percentage'] >= 90 ? 'bg-red-500' : ($utilization['utilization_percentage'] >= 70 ? 'bg-yellow-500' : 'bg-green-500'); ?> h-2 rounded-full" 
                                     style="width: <?php echo min(100, $utilization['utilization_percentage']); ?>%"></div>
                            </div>
                            <span class="text-sm"><?php echo $utilization['utilization_percentage']; ?>%</span>
                        </div>
                        <?php else: ?>
                        -
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($warehouse['status']); ?></span></td>
                    <td>
                        <div class="flex space-x-2">
                            <a href="<?php echo url('/admin/sar-inventory/warehouses/view.php?id=' . $warehouse['id']); ?>" 
                               class="btn btn-sm btn-info" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="<?php echo url('/admin/sar-inventory/warehouses/edit.php?id=' . $warehouse['id']); ?>" 
                               class="btn btn-sm btn-primary" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            <a href="<?php echo url('/admin/sar-inventory/warehouses/delete.php?id=' . $warehouse['id']); ?>" 
                               class="btn btn-sm btn-danger" title="Delete"
                               onclick="return confirm('Are you sure you want to delete this warehouse?');">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
