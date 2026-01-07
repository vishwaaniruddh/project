<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvStockService.php';
require_once '../../../services/SarInvProductService.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Stock Adjustment';
$currentUser = Auth::getCurrentUser();

$stockService = new SarInvStockService();
$productService = new SarInvProductService();
$warehouseService = new SarInvWarehouseService();

// Get products and warehouses for dropdowns
$products = $productService->searchProducts(null, null, 'active');
$warehouses = $warehouseService->getActiveWarehouses();

$errors = [];
$data = [
    'product_id' => $_GET['product_id'] ?? '',
    'warehouse_id' => $_GET['warehouse_id'] ?? '',
    'adjustment_type' => 'increase',
    'quantity' => '',
    'notes' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'product_id' => intval($_POST['product_id'] ?? 0),
        'warehouse_id' => intval($_POST['warehouse_id'] ?? 0),
        'adjustment_type' => $_POST['adjustment_type'] ?? 'increase',
        'quantity' => floatval($_POST['quantity'] ?? 0),
        'notes' => trim($_POST['notes'] ?? '')
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
    if (empty($data['notes'])) {
        $errors[] = 'Please provide a reason for the adjustment';
    }
    
    if (empty($errors)) {
        // Calculate adjustment value (negative for decrease)
        $adjustment = $data['adjustment_type'] === 'decrease' ? -$data['quantity'] : $data['quantity'];
        
        $result = $stockService->adjustStock(
            $data['product_id'],
            $data['warehouse_id'],
            $adjustment,
            $data['notes']
        );
        
        if ($result['success']) {
            $_SESSION['success'] = 'Stock adjusted successfully. New quantity: ' . number_format($result['new_quantity'], 2);
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
            <h1 class="text-2xl font-bold text-gray-900">Stock Adjustment</h1>
            <p class="text-gray-600">Adjust stock levels for inventory corrections</p>
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
            
            <div class="form-group">
                <label class="form-label">Adjustment Type <span class="text-red-500">*</span></label>
                <div class="grid grid-cols-2 gap-4">
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 <?php echo $data['adjustment_type'] === 'increase' ? 'border-green-500 bg-green-50' : ''; ?>">
                        <input type="radio" name="adjustment_type" value="increase" 
                               class="h-4 w-4 text-green-600" 
                               <?php echo $data['adjustment_type'] === 'increase' ? 'checked' : ''; ?>>
                        <div class="ml-3">
                            <span class="block font-medium text-green-700">Increase Stock</span>
                            <span class="text-sm text-gray-500">Add to current quantity</span>
                        </div>
                    </label>
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 <?php echo $data['adjustment_type'] === 'decrease' ? 'border-red-500 bg-red-50' : ''; ?>">
                        <input type="radio" name="adjustment_type" value="decrease" 
                               class="h-4 w-4 text-red-600"
                               <?php echo $data['adjustment_type'] === 'decrease' ? 'checked' : ''; ?>>
                        <div class="ml-3">
                            <span class="block font-medium text-red-700">Decrease Stock</span>
                            <span class="text-sm text-gray-500">Subtract from current quantity</span>
                        </div>
                    </label>
                </div>
            </div>
            
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
                <p id="newQuantityPreview" class="text-sm mt-2 hidden"></p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Reason for Adjustment <span class="text-red-500">*</span></label>
                <textarea name="notes" class="form-textarea" rows="3" required
                          placeholder="Please provide a detailed reason for this stock adjustment (e.g., physical count correction, damaged goods, etc.)"><?php echo htmlspecialchars($data['notes']); ?></textarea>
                <p class="text-sm text-gray-500 mt-1">This will be recorded in the audit log</p>
            </div>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <div class="flex">
                    <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div>
                        <h4 class="font-medium text-yellow-800">Important</h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            Stock adjustments are logged and audited. Please ensure you have proper authorization 
                            before making adjustments. All changes are permanent and cannot be undone.
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end space-x-4 pt-4 border-t">
                <a href="<?php echo url('/admin/sar-inventory/stock-entry/'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Apply Adjustment
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
    const currentStockInfo = document.getElementById('currentStockInfo');
    const newQuantityPreview = document.getElementById('newQuantityPreview');
    const adjustmentTypeInputs = document.querySelectorAll('input[name="adjustment_type"]');
    
    let currentStock = 0;
    
    // Update unit label when product changes
    productSelect.addEventListener('change', function() {
        const selected = this.options[this.selectedIndex];
        unitLabel.textContent = selected.dataset.unit || 'units';
        updateStockInfo();
    });
    
    warehouseSelect.addEventListener('change', updateStockInfo);
    quantityInput.addEventListener('input', updatePreview);
    adjustmentTypeInputs.forEach(input => input.addEventListener('change', updatePreview));
    
    // Fetch current stock info
    function updateStockInfo() {
        const productId = productSelect.value;
        const warehouseId = warehouseSelect.value;
        
        if (productId && warehouseId) {
            fetch(`<?php echo url('/api/sar-inventory/stock.php'); ?>?product_id=${productId}&warehouse_id=${warehouseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentStock = parseFloat(data.data.quantity || 0);
                        document.getElementById('currentQty').textContent = currentStock.toFixed(2);
                        document.getElementById('reservedQty').textContent = parseFloat(data.data.reserved_quantity || 0).toFixed(2);
                        document.getElementById('availableQty').textContent = parseFloat(data.data.available_quantity || 0).toFixed(2);
                        currentStockInfo.classList.remove('hidden');
                        updatePreview();
                    }
                })
                .catch(() => {
                    currentStockInfo.classList.add('hidden');
                    currentStock = 0;
                });
        } else {
            currentStockInfo.classList.add('hidden');
            currentStock = 0;
        }
    }
    
    function updatePreview() {
        const quantity = parseFloat(quantityInput.value) || 0;
        const isIncrease = document.querySelector('input[name="adjustment_type"]:checked').value === 'increase';
        
        if (quantity > 0 && currentStock >= 0) {
            const newQty = isIncrease ? currentStock + quantity : currentStock - quantity;
            const color = newQty >= 0 ? 'text-green-600' : 'text-red-600';
            newQuantityPreview.innerHTML = `New quantity will be: <span class="font-medium ${color}">${newQty.toFixed(2)}</span>`;
            newQuantityPreview.classList.remove('hidden');
        } else {
            newQuantityPreview.classList.add('hidden');
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
