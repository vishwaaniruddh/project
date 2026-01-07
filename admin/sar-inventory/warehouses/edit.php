<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Edit Warehouse';
$currentUser = Auth::getCurrentUser();

$warehouseService = new SarInvWarehouseService();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'Invalid warehouse ID';
    header('Location: ' . url('/admin/sar-inventory/warehouses/'));
    exit;
}

$warehouse = $warehouseService->getWarehouse($id);
if (!$warehouse) {
    $_SESSION['error'] = 'Warehouse not found';
    header('Location: ' . url('/admin/sar-inventory/warehouses/'));
    exit;
}

$errors = [];
$data = $warehouse;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'code' => strtoupper(trim($_POST['code'] ?? '')),
        'location' => trim($_POST['location'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'capacity' => $_POST['capacity'] !== '' ? floatval($_POST['capacity']) : null,
        'status' => $_POST['status'] ?? 'active'
    ];
    
    $result = $warehouseService->updateWarehouse($id, $data);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Warehouse updated successfully';
        header('Location: ' . url('/admin/sar-inventory/warehouses/'));
        exit;
    } else {
        $errors = $result['errors'];
    }
}

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/warehouses/'); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Warehouse</h1>
            <p class="text-gray-600">Update warehouse information</p>
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

<div class="card max-w-2xl">
    <div class="card-body">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Warehouse Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>" 
                           class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Warehouse Code <span class="text-red-500">*</span></label>
                    <input type="text" name="code" value="<?php echo htmlspecialchars($data['code']); ?>" 
                           class="form-input" required placeholder="e.g., WH001"
                           style="text-transform: uppercase;">
                    <p class="text-sm text-gray-500 mt-1">Unique identifier for the warehouse</p>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Location</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($data['location'] ?? ''); ?>" 
                       class="form-input" placeholder="City, State">
            </div>
            
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-textarea" rows="3" 
                          placeholder="Full address"><?php echo htmlspecialchars($data['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" value="<?php echo htmlspecialchars($data['capacity'] ?? ''); ?>" 
                           class="form-input" min="0" step="0.01" placeholder="Maximum storage capacity">
                    <p class="text-sm text-gray-500 mt-1">Leave empty for unlimited capacity</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo ($data['status'] ?? '') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($data['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="maintenance" <?php echo ($data['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    </select>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 pt-4 border-t">
                <a href="<?php echo url('/admin/sar-inventory/warehouses/'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Warehouse</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
