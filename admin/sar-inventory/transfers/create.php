<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvTransferService.php';
require_once '../../../services/SarInvProductService.php';
require_once '../../../services/SarInvWarehouseService.php';
require_once '../../../services/SarInvStockService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Create Transfer';
$currentUser = Auth::getCurrentUser();

$transferService = new SarInvTransferService();
$productService = new SarInvProductService();
$warehouseService = new SarInvWarehouseService();
$stockService = new SarInvStockService();

// Get products and warehouses for dropdowns
$products = $productService->searchProducts(null, null, 'active');
$warehouses = $warehouseService->getActiveWarehouses();

$errors = [];
$data = [
    'source_warehouse_id' => $_GET['source_warehouse_id'] ?? '',
    'destination_warehouse_id' => $_GET['destination_warehouse_id'] ?? '',
    'notes' => '',
    'items' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'source_warehouse_id' => intval($_POST['source_warehouse_id'] ?? 0),
        'destination_warehouse_id' => intval($_POST['destination_warehouse_id'] ?? 0),
        'notes' => trim($_POST['notes'] ?? '')
    ];
    
    // Parse items
    $items = [];
    if (!empty($_POST['items'])) {
        foreach ($_POST['items'] as $item) {
            if (!empty($item['product_id']) && !empty($item['quantity'])) {
                $items[] = [
                    'product_id' => intval($item['product_id']),
                    'quantity' => floatval($item['quantity']),
                    'notes' => trim($item['notes'] ?? '')
                ];
            }
        }
    }
    
    if (empty($items)) {
        $errors[] = 'At least one item is required';
    }
    
    if (empty($errors)) {
        $result = $transferService->createTransfer($data, $items);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Transfer ' . $result['transfer_number'] . ' created successfully';
            header('Location: ' . url('/admin/sar-inventory/transfers/view.php?id=' . $result['transfer_id']));
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
        <a href="<?php echo url('/admin/sar-inventory/transfers/'); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Create Transfer</h1>
            <p class="text-gray-600">Create a new inter-warehouse transfer</p>
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

<form method="POST" id="transferForm">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Transfer Details -->
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-body">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Transfer Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="form-group">
                            <label class="form-label">Source Warehouse <span class="text-red-500">*</span></label>
                            <select name="source_warehouse_id" id="source_warehouse_id" class="form-select" required>
                                <option value="">— Select Source Warehouse —</option>
                                <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo $warehouse['id']; ?>" <?php echo $data['source_warehouse_id'] == $warehouse['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($warehouse['name'] . ' (' . $warehouse['code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Destination Warehouse <span class="text-red-500">*</span></label>
                            <select name="destination_warehouse_id" id="destination_warehouse_id" class="form-select" required>
                                <option value="">— Select Destination Warehouse —</option>
                                <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo $warehouse['id']; ?>" <?php echo $data['destination_warehouse_id'] == $warehouse['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($warehouse['name'] . ' (' . $warehouse['code'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-textarea" rows="2" 
                                  placeholder="Additional notes for this transfer"><?php echo htmlspecialchars($data['notes']); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Items Section -->
            <div class="card mt-6">
                <div class="card-body">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Transfer Items</h3>
                        <button type="button" id="addItemBtn" class="btn btn-sm btn-secondary">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add Item
                        </button>
                    </div>
                    
                    <div id="itemsContainer" class="space-y-4">
                        <!-- Items will be added here dynamically -->
                    </div>
                    
                    <div id="noItemsMessage" class="text-center py-8 text-gray-500">
                        <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                        <p>No items added yet. Click "Add Item" to start.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Summary Sidebar -->
        <div class="lg:col-span-1">
            <div class="card sticky top-4">
                <div class="card-body">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Summary</h3>
                    
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Total Items:</span>
                            <span id="totalItems" class="font-medium">0</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Total Quantity:</span>
                            <span id="totalQuantity" class="font-medium">0</span>
                        </div>
                    </div>
                    
                    <div class="border-t pt-4">
                        <button type="submit" class="btn btn-primary w-full mb-3">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Create Transfer
                        </button>
                        <a href="<?php echo url('/admin/sar-inventory/transfers/'); ?>" class="btn btn-secondary w-full">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Item Template -->
<template id="itemTemplate">
    <div class="item-row bg-gray-50 rounded-lg p-4 border">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
            <div class="md:col-span-5">
                <label class="form-label text-sm">Product <span class="text-red-500">*</span></label>
                <select name="items[INDEX][product_id]" class="form-select product-select" required>
                    <option value="">— Select Product —</option>
                    <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['id']; ?>" 
                            data-unit="<?php echo htmlspecialchars($product['unit_of_measure'] ?? 'units'); ?>">
                        <?php echo htmlspecialchars($product['name'] . ' (' . $product['sku'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <div class="stock-info text-xs text-gray-500 mt-1 hidden">
                    Available: <span class="available-qty">-</span>
                </div>
            </div>
            <div class="md:col-span-2">
                <label class="form-label text-sm">Quantity <span class="text-red-500">*</span></label>
                <div class="flex">
                    <input type="number" name="items[INDEX][quantity]" class="form-input quantity-input rounded-r-none" 
                           required min="0.01" step="0.01" placeholder="0">
                    <span class="unit-label inline-flex items-center px-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-md text-gray-600 text-xs">
                        units
                    </span>
                </div>
            </div>
            <div class="md:col-span-4">
                <label class="form-label text-sm">Notes</label>
                <input type="text" name="items[INDEX][notes]" class="form-input" placeholder="Item notes">
            </div>
            <div class="md:col-span-1 flex items-end">
                <button type="button" class="btn btn-sm btn-danger remove-item-btn w-full">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsContainer = document.getElementById('itemsContainer');
    const noItemsMessage = document.getElementById('noItemsMessage');
    const addItemBtn = document.getElementById('addItemBtn');
    const itemTemplate = document.getElementById('itemTemplate');
    const sourceWarehouseSelect = document.getElementById('source_warehouse_id');
    const destWarehouseSelect = document.getElementById('destination_warehouse_id');
    
    let itemIndex = 0;
    
    function updateSummary() {
        const items = itemsContainer.querySelectorAll('.item-row');
        let totalQty = 0;
        
        items.forEach(item => {
            const qty = parseFloat(item.querySelector('.quantity-input').value) || 0;
            totalQty += qty;
        });
        
        document.getElementById('totalItems').textContent = items.length;
        document.getElementById('totalQuantity').textContent = totalQty.toFixed(2);
        
        noItemsMessage.classList.toggle('hidden', items.length > 0);
    }
    
    function addItem() {
        const clone = itemTemplate.content.cloneNode(true);
        const row = clone.querySelector('.item-row');
        
        // Update indices
        row.innerHTML = row.innerHTML.replace(/INDEX/g, itemIndex);
        itemIndex++;
        
        // Add event listeners
        const productSelect = row.querySelector('.product-select');
        const quantityInput = row.querySelector('.quantity-input');
        const removeBtn = row.querySelector('.remove-item-btn');
        const unitLabel = row.querySelector('.unit-label');
        const stockInfo = row.querySelector('.stock-info');
        const availableQty = row.querySelector('.available-qty');
        
        productSelect.addEventListener('change', function() {
            const selected = this.options[this.selectedIndex];
            unitLabel.textContent = selected.dataset.unit || 'units';
            
            // Fetch stock info
            const productId = this.value;
            const warehouseId = sourceWarehouseSelect.value;
            
            if (productId && warehouseId) {
                fetch(`<?php echo url('/api/sar-inventory/stock.php'); ?>?product_id=${productId}&warehouse_id=${warehouseId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            availableQty.textContent = parseFloat(data.data.available_quantity || 0).toFixed(2);
                            stockInfo.classList.remove('hidden');
                        }
                    })
                    .catch(() => stockInfo.classList.add('hidden'));
            } else {
                stockInfo.classList.add('hidden');
            }
        });
        
        quantityInput.addEventListener('input', updateSummary);
        
        removeBtn.addEventListener('click', function() {
            row.remove();
            updateSummary();
        });
        
        itemsContainer.appendChild(row);
        updateSummary();
    }
    
    addItemBtn.addEventListener('click', addItem);
    
    // Update stock info when source warehouse changes
    sourceWarehouseSelect.addEventListener('change', function() {
        itemsContainer.querySelectorAll('.product-select').forEach(select => {
            select.dispatchEvent(new Event('change'));
        });
    });
    
    // Validate that source and destination are different
    destWarehouseSelect.addEventListener('change', function() {
        if (this.value && this.value === sourceWarehouseSelect.value) {
            alert('Source and destination warehouses must be different');
            this.value = '';
        }
    });
    
    sourceWarehouseSelect.addEventListener('change', function() {
        if (this.value && this.value === destWarehouseSelect.value) {
            alert('Source and destination warehouses must be different');
            destWarehouseSelect.value = '';
        }
    });
    
    // Add first item by default
    addItem();
});
</script>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
