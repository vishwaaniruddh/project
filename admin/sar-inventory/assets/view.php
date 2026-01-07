<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvAssetService.php';
require_once '../../../models/SarInvAsset.php';

Auth::requireRole(ADMIN_ROLE);

$currentUser = Auth::getCurrentUser();

$assetService = new SarInvAssetService();

// Handle scan lookup
$scan = trim($_GET['scan'] ?? '');
if ($scan) {
    $result = $assetService->scanAsset($scan);
    if ($result['success']) {
        header('Location: ' . url('/admin/sar-inventory/assets/view.php?id=' . $result['asset']['id']));
        exit;
    } else {
        $_SESSION['error'] = 'Asset not found with identifier: ' . $scan;
        header('Location: ' . url('/admin/sar-inventory/assets/'));
        exit;
    }
}

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

$title = 'Asset: ' . ($asset['serial_number'] ?? $asset['barcode'] ?? 'ID-' . $asset['id']);

// Get asset history
$history = $assetService->getAssetHistory($id, 50);

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Status badge colors
$statusColors = [
    'available' => 'badge-success',
    'dispatched' => 'badge-primary',
    'in_repair' => 'badge-warning',
    'retired' => 'badge-secondary',
    'lost' => 'badge-danger'
];
$statusClass = $statusColors[$asset['status']] ?? 'badge-secondary';

// Check warranty status
$warrantyExpired = !empty($asset['warranty_expiry']) && strtotime($asset['warranty_expiry']) < time();
$warrantyExpiring = !empty($asset['warranty_expiry']) && !$warrantyExpired && strtotime($asset['warranty_expiry']) < strtotime('+30 days');

// Action type labels
$actionLabels = [
    'created' => 'Asset Created',
    'moved' => 'Location Changed',
    'dispatched' => 'Dispatched',
    'received' => 'Received',
    'repair_start' => 'Repair Started',
    'repair_end' => 'Repair Completed',
    'retired' => 'Retired',
    'status_change' => 'Status Changed'
];

// Location type labels
$locationLabels = [
    'warehouse' => 'Warehouse',
    'dispatch' => 'Dispatch',
    'repair' => 'Repair',
    'site' => 'Site',
    'vendor' => 'Vendor',
    'customer' => 'Customer'
];

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="<?php echo url('/admin/sar-inventory/assets/'); ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">
                    <?php echo htmlspecialchars($asset['serial_number'] ?? $asset['barcode'] ?? 'Asset #' . $asset['id']); ?>
                </h1>
                <p class="text-gray-600"><?php echo htmlspecialchars($asset['product_name']); ?></p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo url('/admin/sar-inventory/assets/update-location.php?id=' . $id); ?>" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Update Location
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
    <!-- Asset Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Basic Information -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Asset Information</h3>
            </div>
            <div class="card-body">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Serial Number</dt>
                        <dd class="mt-1 font-mono text-gray-900"><?php echo htmlspecialchars($asset['serial_number'] ?? '-'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Barcode</dt>
                        <dd class="mt-1 font-mono text-gray-900"><?php echo htmlspecialchars($asset['barcode'] ?? '-'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Product</dt>
                        <dd class="mt-1 text-gray-900">
                            <a href="<?php echo url('/admin/sar-inventory/products/view.php?id=' . $asset['product_id']); ?>" 
                               class="text-blue-600 hover:underline">
                                <?php echo htmlspecialchars($asset['product_name']); ?>
                            </a>
                            <span class="text-gray-500 text-sm ml-1">(<?php echo htmlspecialchars($asset['sku']); ?>)</span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Unit of Measure</dt>
                        <dd class="mt-1 text-gray-900"><?php echo htmlspecialchars($asset['unit_of_measure'] ?? '-'); ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="mt-1">
                            <span class="badge <?php echo $statusClass; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $asset['status'])); ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Current Location</dt>
                        <dd class="mt-1 text-gray-900">
                            <?php echo $locationLabels[$asset['current_location_type']] ?? ucfirst($asset['current_location_type']); ?>
                            <?php if (!empty($asset['current_location_id'])): ?>
                            <span class="text-gray-500">#<?php echo $asset['current_location_id']; ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
        
        <!-- Purchase and Warranty -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Purchase & Warranty</h3>
            </div>
            <div class="card-body">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Purchase Date</dt>
                        <dd class="mt-1 text-gray-900">
                            <?php echo !empty($asset['purchase_date']) ? date('M j, Y', strtotime($asset['purchase_date'])) : '-'; ?>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Warranty Expiry</dt>
                        <dd class="mt-1">
                            <?php if (!empty($asset['warranty_expiry'])): ?>
                            <span class="<?php echo $warrantyExpired ? 'text-red-600' : ($warrantyExpiring ? 'text-orange-600' : 'text-gray-900'); ?>">
                                <?php echo date('M j, Y', strtotime($asset['warranty_expiry'])); ?>
                                <?php if ($warrantyExpired): ?>
                                <span class="badge badge-danger ml-2">Expired</span>
                                <?php elseif ($warrantyExpiring): ?>
                                <span class="badge badge-warning ml-2">Expiring Soon</span>
                                <?php else: ?>
                                <span class="badge badge-success ml-2">Active</span>
                                <?php endif; ?>
                            </span>
                            <?php else: ?>
                            <span class="text-gray-500">-</span>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
                
                <?php if ($warrantyExpired): ?>
                <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center text-red-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <span class="text-sm font-medium">Warranty has expired</span>
                    </div>
                </div>
                <?php elseif ($warrantyExpiring): ?>
                <div class="mt-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                    <div class="flex items-center text-orange-700">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium">Warranty expiring within 30 days</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Asset History -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Asset History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Action</th>
                            <th>From</th>
                            <th>To</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="5" class="text-center py-4 text-gray-500">
                                No history records found
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($history as $record): ?>
                        <tr>
                            <td class="text-sm">
                                <?php echo date('M j, Y H:i', strtotime($record['created_at'])); ?>
                            </td>
                            <td>
                                <span class="font-medium">
                                    <?php echo $actionLabels[$record['action_type']] ?? ucfirst(str_replace('_', ' ', $record['action_type'])); ?>
                                </span>
                            </td>
                            <td class="text-sm text-gray-600">
                                <?php if (!empty($record['from_location_type'])): ?>
                                <?php echo $locationLabels[$record['from_location_type']] ?? ucfirst($record['from_location_type']); ?>
                                <?php if (!empty($record['from_location_id'])): ?>
                                <span class="text-gray-400">#<?php echo $record['from_location_id']; ?></span>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm text-gray-600">
                                <?php if (!empty($record['to_location_type'])): ?>
                                <?php echo $locationLabels[$record['to_location_type']] ?? ucfirst($record['to_location_type']); ?>
                                <?php if (!empty($record['to_location_id'])): ?>
                                <span class="text-gray-400">#<?php echo $record['to_location_id']; ?></span>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="text-gray-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-sm text-gray-600 max-w-xs truncate">
                                <?php echo htmlspecialchars($record['notes'] ?? '-'); ?>
                            </td>
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
        <!-- Status Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Current Status</h3>
            </div>
            <div class="card-body">
                <div class="text-center py-4">
                    <span class="inline-flex items-center justify-center w-16 h-16 rounded-full <?php 
                        echo $asset['status'] === 'available' ? 'bg-green-100' : 
                            ($asset['status'] === 'dispatched' ? 'bg-blue-100' : 
                            ($asset['status'] === 'in_repair' ? 'bg-yellow-100' : 'bg-gray-100')); 
                    ?>">
                        <?php if ($asset['status'] === 'available'): ?>
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <?php elseif ($asset['status'] === 'dispatched'): ?>
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <?php elseif ($asset['status'] === 'in_repair'): ?>
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <?php else: ?>
                        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                        </svg>
                        <?php endif; ?>
                    </span>
                    <p class="mt-3 text-lg font-semibold <?php 
                        echo $asset['status'] === 'available' ? 'text-green-600' : 
                            ($asset['status'] === 'dispatched' ? 'text-blue-600' : 
                            ($asset['status'] === 'in_repair' ? 'text-yellow-600' : 'text-gray-600')); 
                    ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $asset['status'])); ?>
                    </p>
                    <p class="text-sm text-gray-500 mt-1">
                        <?php echo $locationLabels[$asset['current_location_type']] ?? ucfirst($asset['current_location_type']); ?>
                        <?php if (!empty($asset['current_location_id'])): ?>
                        #<?php echo $asset['current_location_id']; ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Quick Actions</h3>
            </div>
            <div class="card-body space-y-2">
                <a href="<?php echo url('/admin/sar-inventory/assets/update-location.php?id=' . $id); ?>" 
                   class="btn btn-secondary w-full justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Update Location
                </a>
                <?php if ($asset['status'] === 'available'): ?>
                <a href="<?php echo url('/admin/sar-inventory/dispatches/create.php?asset_id=' . $id); ?>" 
                   class="btn btn-secondary w-full justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    Create Dispatch
                </a>
                <a href="<?php echo url('/admin/sar-inventory/repairs/create.php?asset_id=' . $id); ?>" 
                   class="btn btn-secondary w-full justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Send for Repair
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Metadata -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Metadata</h3>
            </div>
            <div class="card-body">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Asset ID</dt>
                        <dd class="text-gray-900"><?php echo $asset['id']; ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Product ID</dt>
                        <dd class="text-gray-900"><?php echo $asset['product_id']; ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Created</dt>
                        <dd class="text-gray-900"><?php echo date('M j, Y', strtotime($asset['created_at'])); ?></dd>
                    </div>
                    <?php if (!empty($asset['updated_at'])): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Last Updated</dt>
                        <dd class="text-gray-900"><?php echo date('M j, Y H:i', strtotime($asset['updated_at'])); ?></dd>
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
