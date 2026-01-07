<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvStockService.php';
require_once '../../../services/SarInvProductService.php';
require_once '../../../services/SarInvWarehouseService.php';
require_once '../../../services/SarInvAssetService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'New Stock Entry';
$currentUser = Auth::getCurrentUser();

$stockService = new SarInvStockService();
$productService = new SarInvProductService();
$warehouseService = new SarInvWarehouseService();
$assetService = new SarInvAssetService();

// Get products and warehouses for dropdowns
$products = $productService->searchProducts(null, null, 'active');
$warehouses = $warehouseService->getActiveWarehouses();

$errors = [];
$data = [
    'product_id' => $_GET['product_id'] ?? '',
    'warehouse_id' => $_GET['warehouse_id'] ?? '',
    'quantity' => '',
    'reference_type' => 'purchase',
    'reference_id' => '',
    'notes' => '',
    'register_assets' => false,
    'assets' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Handle modal form submission (add_stock action)
    if ($action === 'add_stock') {
        $productId = intval($_POST['product_id'] ?? 0);
        $warehouseId = intval($_POST['warehouse_id'] ?? 0);
        $quantity = floatval($_POST['quantity'] ?? 0);
        $serialNumbers = $_POST['serial_numbers'] ?? [];
        $warrantyExpiry = $_POST['warranty_expiry'] ?? null;
        $poNumber = trim($_POST['po_number'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        // Get product to check if serializable
        $product = $productService->getProduct($productId);
        $isSerializable = $product && !empty($product['is_serializable']);
        
        if ($isSerializable) {
            // Filter empty serial numbers
            $serialNumbers = array_filter($serialNumbers, fn($s) => !empty(trim($s)));
            $quantity = count($serialNumbers);
            
            if ($quantity === 0) {
                $_SESSION['error'] = 'Please enter at least one serial number';
                header('Location: ' . url('/admin/sar-inventory/stock-entry/'));
                exit;
            }
            
            // Add stock and register assets
            $result = $stockService->addStock($productId, $warehouseId, $quantity, 'purchase', null, $notes . ($poNumber ? " PO: $poNumber" : ''));
            
            if ($result['success']) {
                // Register each serial number as an asset
                foreach ($serialNumbers as $serial) {
                    $assetService->registerAsset([
                        'product_id' => $productId,
                        'serial_number' => trim($serial),
                        'status' => 'available',
                        'current_location_type' => 'warehouse',
                        'current_location_id' => $warehouseId,
                        'warehouse_id' => $warehouseId,
                        'purchase_date' => date('Y-m-d'),
                        'warranty_expiry' => $warrantyExpiry ?: null,
                        'notes' => $notes
                    ]);
                }
                $_SESSION['success'] = "Added $quantity serializable items to stock";
            } else {
                $_SESSION['error'] = implode(', ', $result['errors']);
            }
        } else {
            // Regular quantity-based stock
            if ($quantity <= 0) {
                $_SESSION['error'] = 'Quantity must be greater than zero';
                header('Location: ' . url('/admin/sar-inventory/stock-entry/'));
                exit;
            }
            
            $result = $stockService->addStock($productId, $warehouseId, $quantity, 'purchase', null, $notes . ($poNumber ? " PO: $poNumber" : ''));
            
            if ($result['success']) {
                $_SESSION['success'] = "Added $quantity units to stock";
            } else {
                $_SESSION['error'] = implode(', ', $result['errors']);
            }
        }
        
        header('Location: ' . url('/admin/sar-inventory/stock-entry/'));
        exit;
    }
    
    // Original form handling
    $data = [
        'product_id' => intval($_POST['product_id'] ?? 0),
        'warehouse_id' => intval($_POST['warehouse_id'] ?? 0),
        'quantity' => floatval($_POST['quantity'] ?? 0),
        'reference_type' => trim($_POST['reference_type'] ?? ''),
        'reference_id' => !empty($_POST['reference_id']) ? intval($_POST['reference_id']) : null,
        'notes' => trim($_POST['notes'] ?? ''),
        'register_assets' => isset($_POST['register_assets']),
        'assets' => $_POST['assets'] ?? []
    ];
    
    // Validate
    if ($data['product_id'] <= 0) {
        $errors[] = 'Please select a product';
    }
    if ($data['warehouse_id'] <= 0) {
        $errors[] = 'Please select a warehouse';
    }
    if ($data['quantity'] <= 0) {
        $errors[] = 'Quantity must be greater than zero';
    }
    
    // Validate assets if registering
    if ($data['register_assets'] && !empty($data['assets'])) {
        $assetCount = 0;
        foreach ($data['assets'] as $asset) {
            if (!empty($asset['serial_number']) || !empty($asset['barcode'])) {
                $assetCount++;
            }
        }
        if ($assetCount > 0 && $assetCount != $data['quantity']) {
            $errors[] = "Number of assets ({$assetCount}) must match quantity ({$data['quantity']})";
        }
    }
    
    if (empty($errors)) {
        // Add stock
        $result = $stockService->addStock(
            $data['product_id'],
            $data['warehouse_id'],
            $data['quantity'],
            $data['reference_type'] ?: null,
            $data['reference_id'],
            $data['notes'] ?: null
        );
        
        if ($result['success']) {
            // Register assets if requested
            if ($data['register_assets'] && !empty($data['assets'])) {
                foreach ($data['assets'] as $asset) {
                    if (!empty($asset['serial_number']) || !empty($asset['barcode'])) {
                        $assetService->registerAsset([
                            'product_id' => $data['product_id'],
                            'serial_number' => $asset['serial_number'] ?? null,
                            'barcode' => $asset['barcode'] ?? null,
                            'status' => 'available',
                            'current_location_type' => 'warehouse',
                            'current_location_id' => $data['warehouse_id'],
                            'purchase_date' => $asset['purchase_date'] ?? date('Y-m-d'),
                            'warranty_expiry' => $asset['warranty_expiry'] ?? null
                        ]);
                    }
                }
            }
            
            $_SESSION['success'] = 'Stock entry created successfully. New quantity: ' . number_format($result['new_quantity'], 2);
            header('Location: ' . url('/admin/sar-inventory/stock-entry/'));
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
        <a href="<?php echo url('/admin/sar-inventory/stock-entry/'); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">New Stock Entry</h1>
            <p class="text-gray-600">Add stock to warehouse inventory</p>
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
        <form method="POST" class="space-y-6" id="stockEntryForm">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Product <span class="text-red-500">*</span></label>
                    <select name="product_id" id="product_id" class="form-select" required>
                        <option value="">— Select Product —</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>" 
                                data-unit="<?php echo htmlspecialchars($product['unit_of_measure'] ?? 'units'); ?>"
                                <?php echo $data['product_id'] == $product['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($product['name'] . ' (' . $product['sku'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Warehouse <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" class="form-select" required>
                        <option value="">— Select Warehouse —</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?php echo $warehouse['id']; ?>" <?php echo $data['warehouse_id'] == $warehouse['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($warehouse['name'] . ' (' . $warehouse['code'] . ')'); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Quantity <span class="text-red-500">*</span></label>
                    <div class="flex">
                        <input type="number" name="quantity" id="quantity" 
                               value="<?php echo htmlspecialchars($data['quantity']); ?>" 
                               class="form-input rounded-r-none" required min="0.01" step="0.01" placeholder="0.00">
                        <span class="inline-flex items-center px-3 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-gray-600" id="unitLabel">
                            units
                        </span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Reference Type</label>
                    <select name="reference_type" class="form-select">
                        <option value="">— None —</option>
                        <option value="purchase" <?php echo $data['reference_type'] === 'purchase' ? 'selected' : ''; ?>>Purchase Order</option>
                        <option value="return" <?php echo $data['reference_type'] === 'return' ? 'selected' : ''; ?>>Return</option>
                        <option value="transfer" <?php echo $data['reference_type'] === 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                        <option value="production" <?php echo $data['reference_type'] === 'production' ? 'selected' : ''; ?>>Production</option>
                        <option value="other" <?php echo $data['reference_type'] === 'other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Reference ID</label>
                <input type="text" name="reference_id" value="<?php echo htmlspecialchars($data['reference_id']); ?>" 
                       class="form-input" placeholder="e.g., PO-12345">
                <p class="text-sm text-gray-500 mt-1">Optional reference number for tracking</p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-textarea" rows="3" 
                          placeholder="Additional notes about this stock entry"><?php echo htmlspecialchars($data['notes']); ?></textarea>
            </div>
            
            <!-- Asset Registration Section -->
            <div class="border-t pt-6">
                <div class="flex items-center mb-4">
                    <input type="checkbox" name="register_assets" id="register_assets" 
                           class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                           <?php echo $data['register_assets'] ? 'checked' : ''; ?>>
                    <label for="register_assets" class="ml-2 text-sm font-medium text-gray-700">
                        Register individual assets (for trackable items with serial numbers)
                    </label>
                </div>
                
                <div id="assetsSection" class="<?php echo $data['register_assets'] ? '' : 'hidden'; ?>">
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="font-medium text-gray-900">Asset Details</h3>
                            <button type="button" id="addAssetRow" class="btn btn-sm btn-secondary">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Asset
                            </button>
                        </div>
                        
                        <div id="assetRows" class="space-y-3">
                            <!-- Asset rows will be added here -->
                        </div>
                        
                        <p class="text-sm text-gray-500 mt-3">
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Number of assets must match the quantity entered above
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Current Stock Info -->
            <div id="currentStockInfo" class="hidden bg-blue-50 rounded-lg p-4">
                <h3 class="font-medium text-blue-900 mb-2">Current Stock Information</h3>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <span class="text-blue-600">Current Quantity:</span>
                        <span id="currentQty" class="font-medium">-</span>
                    </div>
                    <div>
                        <span class="text-blue-600">Reserved:</span>
                        <span id="reservedQty" class="font-medium">-</span>
                    </div>
                    <div>
                        <span class="text-blue-600">Available:</span>
                        <span id="availableQty" class="font-medium">-</span>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 pt-4 border-t">
                <a href="<?php echo url('/admin/sar-inventory/stock-entry/'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Stock
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const productSelect = document.getElementById('product_id');
    const warehouseSelect = document.getElementById('warehouse_id');
    const quantityInput = document.getElementById('quantity');
    const unitLabel = document.getElementById('unitLabel');
    const registerAssetsCheckbox = document.getElementById('register_assets');
    const assetsSection = document.getElementById('assetsSection');
    const assetRows = document.getElementById('assetRows');
    const addAssetBtn = document.getElementById('addAssetRow');
    const currentStockInfo = document.getElementById('currentStockInfo');
    
    let assetIndex = 0;
    
    // Update unit label when product changes
    productSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        unitLabel.textContent = selected.dataset.unit || 'units';
        updateStockInfo();
    });
    
    warehouseSelect.addEventListener('change', updateStockInfo);
    
    // Toggle assets section
    registerAssetsCheckbox.addEventListener('change', function() {
        assetsSection.classList.toggle('hidden', !this.checked);
        if (this.checked && assetRows.children.length === 0) {
            addAssetRow();
        }
    });
    
    // Add asset row
    addAssetBtn.addEventListener('click', addAssetRow);
    
    function addAssetRow() {
        const row = document.createElement('div');
        row.className = 'grid grid-cols-1 md:grid-cols-5 gap-3 p-3 bg-white rounded border';
        row.innerHTML = `
            <div>
                <input type="text" name="assets[${assetIndex}][serial_number]" 
                       class="form-input text-sm" placeholder="Serial Number">
            </div>
            <div>
                <input type="text" name="assets[${assetIndex}][barcode]" 
                       class="form-input text-sm" placeholder="Barcode">
            </div>
            <div>
                <input type="date" name="assets[${assetIndex}][purchase_date]" 
                       class="form-input text-sm" value="${new Date().toISOString().split('T')[0]}">
            </div>
            <div>
                <input type="date" name="assets[${assetIndex}][warranty_expiry]" 
                       class="form-input text-sm" placeholder="Warranty Expiry">
            </div>
            <div class="flex items-center">
                <button type="button" class="btn btn-sm btn-danger remove-asset">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        `;
        assetRows.appendChild(row);
        assetIndex++;
        
        // Add remove handler
        row.querySelector('.remove-asset').addEventListener('click', function() {
            row.remove();
        });
    }
    
    // Fetch current stock info
    function updateStockInfo() {
        const productId = productSelect.value;
        const warehouseId = warehouseSelect.value;
        
        if (productId && warehouseId) {
            fetch(`<?php echo url('/api/sar-inventory/stock.php'); ?>?product_id=${productId}&warehouse_id=${warehouseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('currentQty').textContent = parseFloat(data.data.quantity || 0).toFixed(2);
                        document.getElementById('reservedQty').textContent = parseFloat(data.data.reserved_quantity || 0).toFixed(2);
                        document.getElementById('availableQty').textContent = parseFloat(data.data.available_quantity || 0).toFixed(2);
                        currentStockInfo.classList.remove('hidden');
                    }
                })
                .catch(() => {
                    currentStockInfo.classList.add('hidden');
                });
        } else {
            currentStockInfo.classList.add('hidden');
        }
    }
    
    // Initialize
    if (productSelect.value) {
        const selected = productSelect.options[productSelect.selectedIndex];
        unitLabel.textContent = selected.dataset.unit || 'units';
    }
    updateStockInfo();
});
</script>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
