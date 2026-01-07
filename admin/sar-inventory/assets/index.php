<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvAssetService.php';
require_once '../../../services/SarInvProductService.php';
require_once '../../../models/SarInvAsset.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Assets';
$currentUser = Auth::getCurrentUser();

$assetService = new SarInvAssetService();
$productService = new SarInvProductService();

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$productId = $_GET['product_id'] ?? '';
$locationType = $_GET['location_type'] ?? '';

// Get assets with filters
$assets = $assetService->searchAssets(
    $search ?: null,
    $status ?: null,
    $productId ? intval($productId) : null,
    $locationType ?: null
);

// Get products for filter dropdown
$products = $productService->searchProducts(null, null, 'active');

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Status options
$statusOptions = [
    SarInvAsset::STATUS_AVAILABLE => 'Available',
    SarInvAsset::STATUS_DISPATCHED => 'Dispatched',
    SarInvAsset::STATUS_IN_REPAIR => 'In Repair',
    SarInvAsset::STATUS_RETIRED => 'Retired',
    SarInvAsset::STATUS_LOST => 'Lost'
];

// Location type options
$locationTypes = [
    SarInvAsset::LOCATION_WAREHOUSE => 'Warehouse',
    SarInvAsset::LOCATION_DISPATCH => 'Dispatch',
    SarInvAsset::LOCATION_REPAIR => 'Repair',
    SarInvAsset::LOCATION_SITE => 'Site',
    SarInvAsset::LOCATION_VENDOR => 'Vendor',
    SarInvAsset::LOCATION_CUSTOMER => 'Customer'
];

// Status badge colors
$statusColors = [
    'available' => 'badge-success',
    'dispatched' => 'badge-primary',
    'in_repair' => 'badge-warning',
    'retired' => 'badge-secondary',
    'lost' => 'badge-danger'
];

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Assets</h1>
            <p class="text-gray-600">Track individual assets with serial numbers and barcodes</p>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="openScanModal()" class="btn btn-secondary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                </svg>
                Scan Asset
            </button>
            <a href="<?php echo url('/admin/sar-inventory/assets/create.php'); ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Register Asset
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

<!-- Search and Filter -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="form-group mb-0">
                <label class="form-label">Search</label>
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="form-input" placeholder="Serial, barcode, or product">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Product</label>
                <select name="product_id" class="form-select">
                    <option value="">All Products</option>
                    <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>" <?php echo $productId == $product['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
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
                <label class="form-label">Location Type</label>
                <select name="location_type" class="form-select">
                    <option value="">All Locations</option>
                    <?php foreach ($locationTypes as $key => $label): ?>
                    <option value="<?php echo $key; ?>" <?php echo $locationType === $key ? 'selected' : ''; ?>>
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
                <a href="<?php echo url('/admin/sar-inventory/assets/'); ?>" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Assets Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Serial Number</th>
                    <th>Barcode</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Warranty</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assets)): ?>
                <tr>
                    <td colspan="8" class="text-center py-8 text-gray-500">
                        No assets found. <a href="<?php echo url('/admin/sar-inventory/assets/create.php'); ?>" class="text-blue-600 hover:underline">Register your first asset</a>
                    </td>
                </tr>
                <?php else: ?>
                <?php $serialNo = 1; ?>
                <?php foreach ($assets as $asset): 
                    $statusClass = $statusColors[$asset['status']] ?? 'badge-secondary';
                    $warrantyExpired = !empty($asset['warranty_expiry']) && strtotime($asset['warranty_expiry']) < time();
                    $warrantyExpiring = !empty($asset['warranty_expiry']) && !$warrantyExpired && strtotime($asset['warranty_expiry']) < strtotime('+30 days');
                ?>
                <tr>
                    <td class="text-center text-gray-500"><?php echo $serialNo++; ?></td>
                    <td class="font-mono text-sm">
                        <a href="<?php echo url('/admin/sar-inventory/assets/view.php?id=' . $asset['id']); ?>" 
                           class="text-blue-600 hover:underline">
                            <?php echo htmlspecialchars($asset['serial_number'] ?? '-'); ?>
                        </a>
                    </td>
                    <td class="font-mono text-sm"><?php echo htmlspecialchars($asset['barcode'] ?? '-'); ?></td>
                    <td><?php echo htmlspecialchars($asset['product_name'] ?? '-'); ?></td>
                    <td><span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst(str_replace('_', ' ', $asset['status'])); ?></span></td>
                    <td>
                        <span class="text-sm">
                            <?php echo ucfirst($asset['current_location_type'] ?? '-'); ?>
                            <?php if (!empty($asset['current_location_id'])): ?>
                            <span class="text-gray-500">#<?php echo $asset['current_location_id']; ?></span>
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($asset['warranty_expiry'])): ?>
                        <span class="text-sm <?php echo $warrantyExpired ? 'text-red-600' : ($warrantyExpiring ? 'text-orange-600' : 'text-gray-600'); ?>">
                            <?php echo date('M j, Y', strtotime($asset['warranty_expiry'])); ?>
                            <?php if ($warrantyExpired): ?>
                            <span class="text-xs">(Expired)</span>
                            <?php elseif ($warrantyExpiring): ?>
                            <span class="text-xs">(Expiring)</span>
                            <?php endif; ?>
                        </span>
                        <?php else: ?>
                        <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="flex space-x-2">
                            <a href="<?php echo url('/admin/sar-inventory/assets/view.php?id=' . $asset['id']); ?>" 
                               class="btn btn-sm btn-secondary" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="<?php echo url('/admin/sar-inventory/assets/update-location.php?id=' . $asset['id']); ?>" 
                               class="btn btn-sm btn-primary" title="Update Location">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
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

<!-- Scan Modal -->
<div id="scanModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Scan Asset</h3>
            <button onclick="closeScanModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <form id="scanForm" method="GET" action="<?php echo url('/admin/sar-inventory/assets/view.php'); ?>">
            <div class="form-group">
                <label class="form-label">Serial Number or Barcode</label>
                <input type="text" name="scan" id="scanInput" class="form-input" 
                       placeholder="Scan or enter identifier" autofocus>
                <p class="text-sm text-gray-500 mt-1">Enter serial number or barcode to find asset</p>
            </div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" onclick="closeScanModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    Find Asset
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openScanModal() {
    document.getElementById('scanModal').classList.remove('hidden');
    document.getElementById('scanInput').focus();
}

function closeScanModal() {
    document.getElementById('scanModal').classList.add('hidden');
    document.getElementById('scanInput').value = '';
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeScanModal();
    }
});

// Close modal when clicking outside
document.getElementById('scanModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeScanModal();
    }
});
</script>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
