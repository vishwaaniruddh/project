<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvAssetService.php';
require_once '../../../services/SarInvWarehouseService.php';
require_once '../../../models/SarInvAsset.php';

Auth::requireRole(ADMIN_ROLE);

$currentUser = Auth::getCurrentUser();

$assetService = new SarInvAssetService();
$warehouseService = new SarInvWarehouseService();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'Invalid asset ID';
    header('Location: ' . url('/admin/sar-inventory/assets/'));
    exit;
}

$asset = $assetService->getAssetWithProduct($id);
if (!$asset) {
    $_SESSION['error'] = 'Asset not found';
    header('Location: ' . url('/admin/sar-inventory/assets/'));
    exit;
}

$title = 'Update Location: ' . ($asset['serial_number'] ?? $asset['barcode'] ?? 'Asset #' . $asset['id']);

// Get warehouses for location dropdown
$warehouses = $warehouseService->getAllWarehouses();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $locationType = $_POST['location_type'] ?? '';
    $locationId = !empty($_POST['location_id']) ? intval($_POST['location_id']) : null;
    $notes = trim($_POST['notes'] ?? '');
    
    // Also handle status update if provided
    $newStatus = $_POST['status'] ?? null;
    
    // Update location
    $result = $assetService->updateLocation($id, $locationType, $locationId, $notes);
    
    if ($result['success']) {
        // Update status if changed
        if ($newStatus && $newStatus !== $asset['status']) {
            $statusResult = $assetService->updateStatus($id, $newStatus, $notes);
            if (!$statusResult['success']) {
                $_SESSION['error'] = 'Location updated but status update failed: ' . implode(', ', $statusResult['errors']);
                header('Location: ' . url('/admin/sar-inventory/assets/view.php?id=' . $id));
                exit;
            }
        }
        
        $_SESSION['success'] = 'Asset location updated successfully';
        header('Location: ' . url('/admin/sar-inventory/assets/view.php?id=' . $id));
        exit;
    } else {
        $errors = $result['errors'];
    }
}

// Location type options
$locationTypes = [
    SarInvAsset::LOCATION_WAREHOUSE => 'Warehouse',
    SarInvAsset::LOCATION_SITE => 'Site',
    SarInvAsset::LOCATION_VENDOR => 'Vendor',
    SarInvAsset::LOCATION_CUSTOMER => 'Customer',
    SarInvAsset::LOCATION_REPAIR => 'Repair',
    SarInvAsset::LOCATION_DISPATCH => 'Dispatch'
];

// Status options
$statusOptions = [
    SarInvAsset::STATUS_AVAILABLE => 'Available',
    SarInvAsset::STATUS_DISPATCHED => 'Dispatched',
    SarInvAsset::STATUS_IN_REPAIR => 'In Repair',
    SarInvAsset::STATUS_RETIRED => 'Retired',
    SarInvAsset::STATUS_LOST => 'Lost'
];

// Status badge colors
$statusColors = [
    'available' => 'badge-success',
    'dispatched' => 'badge-primary',
    'in_repair' => 'badge-warning',
    'retired' => 'badge-secondary',
    'lost' => 'badge-danger'
];
$statusClass = $statusColors[$asset['status']] ?? 'badge-secondary';

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/assets/view.php?id=' . $id); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Update Asset Location</h1>
            <p class="text-gray-600">
                <?php echo htmlspecialchars($asset['serial_number'] ?? $asset['barcode'] ?? 'Asset #' . $asset['id']); ?>
                - <?php echo htmlspecialchars($asset['product_name']); ?>
            </p>
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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Update Form -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">New Location</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="space-y-6">
                    <!-- Location Type -->
                    <div class="form-group">
                        <label class="form-label">Location Type <span class="text-red-500">*</span></label>
                        <select name="location_type" id="locationTypeSelect" class="form-select" required onchange="updateLocationOptions()">
                            <?php foreach ($locationTypes as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $asset['current_location_type'] === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Warehouse Selection -->
                    <div class="form-group" id="warehouseLocationGroup">
                        <label class="form-label">Warehouse</label>
                        <select name="location_id" id="warehouseSelect" class="form-select">
                            <option value="">— Select Warehouse —</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?php echo $warehouse['id']; ?>" 
                                    <?php echo ($asset['current_location_type'] === 'warehouse' && $asset['current_location_id'] == $warehouse['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($warehouse['name']); ?> (<?php echo htmlspecialchars($warehouse['code']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Other Location ID -->
                    <div class="form-group hidden" id="otherLocationGroup">
                        <label class="form-label">Location ID</label>
                        <input type="number" id="otherLocationInput" class="form-input" 
                               value="<?php echo ($asset['current_location_type'] !== 'warehouse') ? htmlspecialchars($asset['current_location_id'] ?? '') : ''; ?>"
                               placeholder="Enter location ID">
                        <p class="text-sm text-gray-500 mt-1">Enter the ID of the site, vendor, customer, repair, or dispatch</p>
                    </div>
                    
                    <!-- Status Update -->
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach ($statusOptions as $key => $label): ?>
                            <option value="<?php echo $key; ?>" <?php echo $asset['status'] === $key ? 'selected' : ''; ?>>
                                <?php echo $label; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Optionally update the asset status</p>
                    </div>
                    
                    <!-- Notes -->
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-textarea" rows="3" 
                                  placeholder="Reason for location change or additional notes"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-4 pt-4 border-t">
                        <a href="<?php echo url('/admin/sar-inventory/assets/view.php?id=' . $id); ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Location</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Current Info Sidebar -->
    <div class="space-y-6">
        <!-- Current Location -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Current Location</h3>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </span>
                    <p class="mt-3 font-semibold text-gray-900">
                        <?php echo $locationTypes[$asset['current_location_type']] ?? ucfirst($asset['current_location_type']); ?>
                    </p>
                    <?php if (!empty($asset['current_location_id'])): ?>
                    <p class="text-sm text-gray-500">ID: <?php echo $asset['current_location_id']; ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Current Status -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Current Status</h3>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <span class="badge <?php echo $statusClass; ?> text-lg px-4 py-2">
                        <?php echo ucfirst(str_replace('_', ' ', $asset['status'])); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Asset Info -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Asset Info</h3>
            </div>
            <div class="card-body">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Serial Number</dt>
                        <dd class="text-gray-900 font-mono"><?php echo htmlspecialchars($asset['serial_number'] ?? '-'); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Barcode</dt>
                        <dd class="text-gray-900 font-mono"><?php echo htmlspecialchars($asset['barcode'] ?? '-'); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Product</dt>
                        <dd class="text-gray-900"><?php echo htmlspecialchars($asset['product_name']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">SKU</dt>
                        <dd class="text-gray-900 font-mono"><?php echo htmlspecialchars($asset['sku']); ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

<script>
function updateLocationOptions() {
    const locationType = document.getElementById('locationTypeSelect').value;
    const warehouseGroup = document.getElementById('warehouseLocationGroup');
    const otherGroup = document.getElementById('otherLocationGroup');
    const warehouseSelect = document.getElementById('warehouseSelect');
    const otherInput = document.getElementById('otherLocationInput');
    
    if (locationType === 'warehouse') {
        warehouseGroup.classList.remove('hidden');
        otherGroup.classList.add('hidden');
        warehouseSelect.name = 'location_id';
        otherInput.name = '';
    } else {
        warehouseGroup.classList.add('hidden');
        otherGroup.classList.remove('hidden');
        warehouseSelect.name = '';
        otherInput.name = 'location_id';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateLocationOptions();
});
</script>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
