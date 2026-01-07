<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvAssetService.php';
require_once '../../../services/SarInvProductService.php';
require_once '../../../services/SarInvWarehouseService.php';
require_once '../../../models/SarInvAsset.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Register Asset';
$currentUser = Auth::getCurrentUser();

$assetService = new SarInvAssetService();
$productService = new SarInvProductService();
$warehouseService = new SarInvWarehouseService();

// Get products for dropdown (only serializable products)
$products = $productService->searchProducts(null, null, 'active');

// Get warehouses for location dropdown
$warehouses = $warehouseService->getAllWarehouses();

$errors = [];
$data = [
    'product_id' => $_GET['product_id'] ?? '',
    'serial_number' => '',
    'barcode' => '',
    'status' => SarInvAsset::STATUS_AVAILABLE,
    'current_location_type' => SarInvAsset::LOCATION_WAREHOUSE,
    'current_location_id' => '',
    'purchase_date' => '',
    'warranty_expiry' => '',
    'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'product_id' => intval($_POST['product_id'] ?? 0),
        'serial_number' => trim($_POST['serial_number'] ?? ''),
        'barcode' => trim($_POST['barcode'] ?? ''),
        'status' => $_POST['status'] ?? SarInvAsset::STATUS_AVAILABLE,
        'current_location_type' => $_POST['current_location_type'] ?? SarInvAsset::LOCATION_WAREHOUSE,
        'current_location_id' => !empty($_POST['current_location_id']) ? intval($_POST['current_location_id']) : null,
        'purchase_date' => !empty($_POST['purchase_date']) ? $_POST['purchase_date'] : null,
        'warranty_expiry' => !empty($_POST['warranty_expiry']) ? $_POST['warranty_expiry'] : null,
        'notes' => trim($_POST['notes'] ?? '')
    ];
    
    $result = $assetService->registerAsset($data);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Asset registered successfully';
        header('Location: ' . url('/admin/sar-inventory/assets/view.php?id=' . $result['asset_id']));
        exit;
    } else {
        $errors = $result['errors'];
    }
}

// Status options
$statusOptions = [
    SarInvAsset::STATUS_AVAILABLE => 'Available',
    SarInvAsset::STATUS_DISPATCHED => 'Dispatched',
    SarInvAsset::STATUS_IN_REPAIR => 'In Repair',
    SarInvAsset::STATUS_RETIRED => 'Retired'
];

// Location type options
$locationTypes = [
    SarInvAsset::LOCATION_WAREHOUSE => 'Warehouse',
    SarInvAsset::LOCATION_SITE => 'Site',
    SarInvAsset::LOCATION_VENDOR => 'Vendor',
    SarInvAsset::LOCATION_CUSTOMER => 'Customer'
];

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/assets/'); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Register Asset</h1>
            <p class="text-gray-600">Register a new trackable asset with unique identifiers</p>
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

<div class="card max-w-3xl">
    <div class="card-body">
        <form method="POST" class="space-y-6">
            <!-- Product Selection -->
            <div class="form-group">
                <label class="form-label">Product <span class="text-red-500">*</span></label>
                <select name="product_id" class="form-select" required>
                    <option value="">— Select Product —</option>
                    <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>" 
                            <?php echo $data['product_id'] == $product['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['sku']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">Select the product type for this asset</p>
            </div>
            
            <!-- Identifiers -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Serial Number</label>
                    <input type="text" name="serial_number" value="<?php echo htmlspecialchars($data['serial_number']); ?>" 
                           class="form-input font-mono" placeholder="e.g., SN-2024-001234">
                    <p class="text-sm text-gray-500 mt-1">Unique serial number for this asset</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Barcode</label>
                    <div class="flex gap-2">
                        <input type="text" name="barcode" id="barcodeInput" value="<?php echo htmlspecialchars($data['barcode']); ?>" 
                               class="form-input font-mono flex-1" placeholder="Scan or enter barcode">
                        <button type="button" onclick="generateBarcode()" class="btn btn-secondary" title="Generate Barcode">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Unique barcode identifier</p>
                </div>
            </div>
            
            <!-- Status and Location -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach ($statusOptions as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $data['status'] === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Location Type</label>
                    <select name="current_location_type" id="locationTypeSelect" class="form-select" onchange="updateLocationOptions()">
                        <?php foreach ($locationTypes as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $data['current_location_type'] === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Location ID (Warehouse) -->
            <div class="form-group" id="warehouseLocationGroup">
                <label class="form-label">Warehouse</label>
                <select name="current_location_id" id="warehouseSelect" class="form-select">
                    <option value="">— Select Warehouse —</option>
                    <?php foreach ($warehouses as $warehouse): ?>
                    <option value="<?php echo $warehouse['id']; ?>" 
                            <?php echo $data['current_location_id'] == $warehouse['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($warehouse['name']); ?> (<?php echo htmlspecialchars($warehouse['code']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Location ID (Other) -->
            <div class="form-group hidden" id="otherLocationGroup">
                <label class="form-label">Location ID</label>
                <input type="number" id="otherLocationInput" class="form-input" placeholder="Enter location ID">
                <p class="text-sm text-gray-500 mt-1">Enter the ID of the site, vendor, or customer</p>
            </div>
            
            <!-- Purchase and Warranty -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Purchase Date</label>
                    <input type="date" name="purchase_date" value="<?php echo htmlspecialchars($data['purchase_date']); ?>" 
                           class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" value="<?php echo htmlspecialchars($data['warranty_expiry']); ?>" 
                           class="form-input">
                </div>
            </div>
            
            <!-- Notes -->
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-textarea" rows="3" 
                          placeholder="Additional notes about this asset"><?php echo htmlspecialchars($data['notes']); ?></textarea>
            </div>
            
            <div class="flex justify-end space-x-4 pt-4 border-t">
                <a href="<?php echo url('/admin/sar-inventory/assets/'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Register Asset</button>
            </div>
        </form>
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
        warehouseSelect.name = 'current_location_id';
        otherInput.name = '';
    } else {
        warehouseGroup.classList.add('hidden');
        otherGroup.classList.remove('hidden');
        warehouseSelect.name = '';
        otherInput.name = 'current_location_id';
    }
}

function generateBarcode() {
    // Generate a simple barcode based on timestamp and random number
    const timestamp = Date.now().toString(36).toUpperCase();
    const random = Math.random().toString(36).substring(2, 6).toUpperCase();
    const barcode = 'AST-' + timestamp + '-' + random;
    document.getElementById('barcodeInput').value = barcode;
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
