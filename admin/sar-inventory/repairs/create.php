<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvRepairService.php';
require_once '../../../services/SarInvAssetService.php';
require_once '../../../models/SarInvAsset.php';
require_once '../../../models/Vendor.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Create Repair';
$currentUser = Auth::getCurrentUser();

$repairService = new SarInvRepairService();
$assetService = new SarInvAssetService();
$vendorModel = new Vendor();

// Get pre-selected asset if provided
$preSelectedAssetId = $_GET['asset_id'] ?? null;
$preSelectedAsset = null;
if ($preSelectedAssetId) {
    $preSelectedAsset = $assetService->getAssetWithProduct(intval($preSelectedAssetId));
}

// Get available assets (not already in repair or retired)
$availableAssets = $assetService->searchAssets(null, null, null, null);
$availableAssets = array_filter($availableAssets, function($asset) {
    return !in_array($asset['status'], [SarInvAsset::STATUS_IN_REPAIR, SarInvAsset::STATUS_RETIRED]);
});

// Get vendors for dropdown
$vendors = $vendorModel->getActiveVendors();

$errors = [];
$formData = [
    'asset_id' => $preSelectedAssetId ?? '',
    'vendor_id' => '',
    'issue_description' => '',
    'diagnosis' => '',
    'cost' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'asset_id' => $_POST['asset_id'] ?? '',
        'vendor_id' => $_POST['vendor_id'] ?? null,
        'issue_description' => trim($_POST['issue_description'] ?? ''),
        'diagnosis' => trim($_POST['diagnosis'] ?? ''),
        'cost' => $_POST['cost'] ?? null
    ];
    
    // Validate required fields
    if (empty($formData['asset_id'])) {
        $errors[] = 'Asset is required';
    }
    
    if (empty($formData['issue_description'])) {
        $errors[] = 'Issue description is required';
    }
    
    // Convert empty strings to null
    if (empty($formData['vendor_id'])) {
        $formData['vendor_id'] = null;
    }
    
    if ($formData['cost'] !== '' && $formData['cost'] !== null) {
        $formData['cost'] = floatval($formData['cost']);
        if ($formData['cost'] < 0) {
            $errors[] = 'Cost cannot be negative';
        }
    } else {
        $formData['cost'] = null;
    }
    
    if (empty($errors)) {
        $result = $repairService->createRepair($formData);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Repair ' . $result['repair_number'] . ' created successfully';
            header('Location: ' . url('/admin/sar-inventory/repairs/view.php?id=' . $result['repair_id']));
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
}

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/repairs/'); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Repair</h1>
            <p class="text-gray-600">Register a new repair for an asset</p>
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

<div class="card">
    <div class="card-body">
        <form method="POST" class="space-y-6">
            <!-- Asset Selection -->
            <div class="form-group">
                <label class="form-label required">Asset</label>
                <?php if ($preSelectedAsset): ?>
                <input type="hidden" name="asset_id" value="<?php echo $preSelectedAsset['id']; ?>">
                <div class="p-4 bg-gray-50 rounded-lg border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($preSelectedAsset['product_name'] ?? 'Unknown Product'); ?></p>
                            <p class="text-sm text-gray-600">
                                Serial: <?php echo htmlspecialchars($preSelectedAsset['serial_number'] ?? '-'); ?> |
                                Barcode: <?php echo htmlspecialchars($preSelectedAsset['barcode'] ?? '-'); ?>
                            </p>
                        </div>
                        <a href="<?php echo url('/admin/sar-inventory/repairs/create.php'); ?>" class="text-sm text-blue-600 hover:underline">Change</a>
                    </div>
                </div>
                <?php else: ?>
                <select name="asset_id" id="asset_id" class="form-select" required>
                    <option value="">Select an asset</option>
                    <?php foreach ($availableAssets as $asset): ?>
                    <option value="<?php echo $asset['id']; ?>" <?php echo $formData['asset_id'] == $asset['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($asset['product_name'] ?? 'Unknown'); ?> - 
                        <?php echo htmlspecialchars($asset['serial_number'] ?? $asset['barcode'] ?? 'No ID'); ?>
                        (<?php echo ucfirst($asset['status']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">Select the asset that needs repair</p>
                <?php endif; ?>
            </div>

            <!-- Issue Description -->
            <div class="form-group">
                <label class="form-label required">Issue Description</label>
                <textarea name="issue_description" rows="4" class="form-input" required
                          placeholder="Describe the issue or problem with the asset"><?php echo htmlspecialchars($formData['issue_description']); ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Provide a detailed description of the issue</p>
            </div>

            <!-- Diagnosis (Optional) -->
            <div class="form-group">
                <label class="form-label">Initial Diagnosis</label>
                <textarea name="diagnosis" rows="3" class="form-input"
                          placeholder="Initial diagnosis or assessment (optional)"><?php echo htmlspecialchars($formData['diagnosis']); ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Optional initial diagnosis or assessment</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Vendor Selection -->
                <div class="form-group">
                    <label class="form-label">Repair Vendor</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">Select vendor (optional)</option>
                        <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo $vendor['id']; ?>" <?php echo $formData['vendor_id'] == $vendor['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($vendor['name']); ?>
                            <?php if (!empty($vendor['company_name'])): ?>
                            (<?php echo htmlspecialchars($vendor['company_name']); ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Vendor handling the repair</p>
                </div>

                <!-- Estimated Cost -->
                <div class="form-group">
                    <label class="form-label">Estimated Cost (₹)</label>
                    <input type="number" name="cost" step="0.01" min="0" class="form-input"
                           value="<?php echo htmlspecialchars($formData['cost']); ?>"
                           placeholder="0.00">
                    <p class="text-sm text-gray-500 mt-1">Estimated repair cost (optional)</p>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-4 border-t">
                <a href="<?php echo url('/admin/sar-inventory/repairs/'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Repair
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
