<?php
require_once '../../../../config/auth.php';
require_once '../../../../config/database.php';
require_once '../../../../services/SarInvMaterialService.php';
require_once '../../../../services/SarInvProductService.php';
require_once '../../../../models/SarInvMaterialMaster.php';
require_once '../../../../models/SarInvMaterialRequest.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Create Material Request';
$currentUser = Auth::getCurrentUser();

$materialService = new SarInvMaterialService();
$productService = new SarInvProductService();

// Get pre-selected material if provided
$preSelectedMaterialId = $_GET['material_id'] ?? null;
$preSelectedMaterial = null;
if ($preSelectedMaterialId) {
    $preSelectedMaterial = $materialService->getMaterialMaster(intval($preSelectedMaterialId));
}

// Get active materials for dropdown
$materials = $materialService->getActiveMaterialMasters();

// Get active products for dropdown
$products = $productService->searchProducts(null, null, 'active');

$errors = [];
$formData = [
    'material_master_id' => $preSelectedMaterialId ?? '',
    'product_id' => '',
    'quantity' => $preSelectedMaterial['default_quantity'] ?? '',
    'notes' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'material_master_id' => $_POST['material_master_id'] ?? null,
        'product_id' => $_POST['product_id'] ?? null,
        'quantity' => $_POST['quantity'] ?? '',
        'notes' => trim($_POST['notes'] ?? '')
    ];
    
    // Validate required fields
    if (empty($formData['material_master_id']) && empty($formData['product_id'])) {
        $errors[] = 'Either material master or product must be selected';
    }
    
    if (empty($formData['quantity']) || floatval($formData['quantity']) <= 0) {
        $errors[] = 'Quantity must be greater than zero';
    } else {
        $formData['quantity'] = floatval($formData['quantity']);
    }
    
    // Convert empty strings to null
    if (empty($formData['material_master_id'])) {
        $formData['material_master_id'] = null;
    }
    if (empty($formData['product_id'])) {
        $formData['product_id'] = null;
    }
    
    if (empty($errors)) {
        $result = $materialService->createRequest($formData);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Material request ' . $result['request_number'] . ' created successfully';
            header('Location: ' . url('/admin/sar-inventory/materials/requests/view.php?id=' . $result['request_id']));
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
        <a href="<?php echo url('/admin/sar-inventory/materials/requests/'); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Material Request</h1>
            <p class="text-gray-600">Submit a new material request</p>
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
            <!-- Material Selection -->
            <div class="form-group">
                <label class="form-label">Material Master</label>
                <?php if ($preSelectedMaterial): ?>
                <input type="hidden" name="material_master_id" value="<?php echo $preSelectedMaterial['id']; ?>">
                <div class="p-4 bg-gray-50 rounded-lg border">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($preSelectedMaterial['name']); ?></p>
                            <p class="text-sm text-gray-600">
                                Code: <?php echo htmlspecialchars($preSelectedMaterial['code']); ?> |
                                Unit: <?php echo htmlspecialchars($preSelectedMaterial['unit_of_measure'] ?? '-'); ?>
                            </p>
                        </div>
                        <a href="<?php echo url('/admin/sar-inventory/materials/requests/create.php'); ?>" class="text-sm text-blue-600 hover:underline">Change</a>
                    </div>
                </div>
                <?php else: ?>
                <select name="material_master_id" id="material_master_id" class="form-select" onchange="updateQuantityDefault()">
                    <option value="">Select a material master (optional)</option>
                    <?php foreach ($materials as $material): ?>
                    <option value="<?php echo $material['id']; ?>" 
                            data-default-qty="<?php echo $material['default_quantity'] ?? 0; ?>"
                            data-unit="<?php echo htmlspecialchars($material['unit_of_measure'] ?? ''); ?>"
                            <?php echo $formData['material_master_id'] == $material['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($material['name']); ?> (<?php echo htmlspecialchars($material['code']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">Select from predefined material templates</p>
                <?php endif; ?>
            </div>

            <!-- OR Divider -->
            <?php if (!$preSelectedMaterial): ?>
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">OR</span>
                </div>
            </div>

            <!-- Product Selection -->
            <div class="form-group">
                <label class="form-label">Product</label>
                <select name="product_id" id="product_id" class="form-select">
                    <option value="">Select a product (optional)</option>
                    <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>" 
                            data-unit="<?php echo htmlspecialchars($product['unit_of_measure'] ?? ''); ?>"
                            <?php echo $formData['product_id'] == $product['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($product['name']); ?> (<?php echo htmlspecialchars($product['sku']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">Or select directly from inventory products</p>
            </div>
            <?php endif; ?>

            <!-- Quantity -->
            <div class="form-group">
                <label class="form-label required">Quantity</label>
                <div class="flex gap-2">
                    <input type="number" name="quantity" id="quantity" step="0.01" min="0.01" class="form-input flex-1" required
                           value="<?php echo htmlspecialchars($formData['quantity']); ?>"
                           placeholder="Enter quantity">
                    <span id="unit_display" class="flex items-center px-3 bg-gray-100 border rounded-lg text-gray-600">
                        <?php echo htmlspecialchars($preSelectedMaterial['unit_of_measure'] ?? 'Units'); ?>
                    </span>
                </div>
                <p class="text-sm text-gray-500 mt-1">Quantity of material requested</p>
            </div>

            <!-- Notes -->
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="4" class="form-input"
                          placeholder="Additional notes or justification for this request"><?php echo htmlspecialchars($formData['notes']); ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Optional notes or justification</p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-4 border-t">
                <a href="<?php echo url('/admin/sar-inventory/materials/requests/'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Submit Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateQuantityDefault() {
    const materialSelect = document.getElementById('material_master_id');
    const quantityInput = document.getElementById('quantity');
    const unitDisplay = document.getElementById('unit_display');
    
    if (materialSelect && materialSelect.selectedIndex > 0) {
        const selectedOption = materialSelect.options[materialSelect.selectedIndex];
        const defaultQty = selectedOption.dataset.defaultQty;
        const unit = selectedOption.dataset.unit || 'Units';
        
        if (defaultQty && parseFloat(defaultQty) > 0 && !quantityInput.value) {
            quantityInput.value = defaultQty;
        }
        unitDisplay.textContent = unit;
    }
}

// Also update unit when product is selected
document.getElementById('product_id')?.addEventListener('change', function() {
    const unitDisplay = document.getElementById('unit_display');
    if (this.selectedIndex > 0) {
        const selectedOption = this.options[this.selectedIndex];
        const unit = selectedOption.dataset.unit || 'Units';
        unitDisplay.textContent = unit;
    }
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', updateQuantityDefault);
</script>

<?php
$content = ob_get_clean();
include '../../../../includes/admin_layout.php';
?>
