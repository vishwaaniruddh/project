<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvStockService.php';
require_once '../../../services/SarInvProductService.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Stock Management';
$currentUser = Auth::getCurrentUser();

$stockService = new SarInvStockService();
$productService = new SarInvProductService();
$warehouseService = new SarInvWarehouseService();

// Get filter parameters
$search = trim($_GET['search'] ?? '');
$warehouseId = $_GET['warehouse_id'] ?? '';
$categoryId = $_GET['category_id'] ?? '';
$stockStatus = $_GET['stock_status'] ?? '';

// Get stock levels with filters
$stockLevels = $stockService->getStockLevels($search ?: null, $warehouseId ?: null, $categoryId ?: null);

// Filter by stock status
if ($stockStatus === 'in_stock') {
    $stockLevels = array_filter($stockLevels, fn($s) => $s['available_quantity'] > 0);
} elseif ($stockStatus === 'out_of_stock') {
    $stockLevels = array_filter($stockLevels, fn($s) => $s['available_quantity'] <= 0);
} elseif ($stockStatus === 'low_stock') {
    $stockLevels = array_filter($stockLevels, fn($s) => $s['available_quantity'] > 0 && $s['available_quantity'] <= ($s['minimum_stock_level'] ?? 10));
}

// Get dropdowns data
$warehouses = $warehouseService->getActiveWarehouses();
$categories = $productService->getFlatCategoryList();
$products = $productService->searchProducts();

// Get success/error messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Stock Levels</h1>
            <p class="text-gray-600">View and manage inventory stock across warehouses</p>
        </div>
        <div class="flex gap-2">
            <button onclick="openAddStockModal()" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Add Stock
            </button>
            <a href="<?php echo url('/admin/sar-inventory/stock-entry/adjust.php'); ?>" class="btn btn-secondary">
                Stock Adjustment
            </a>
        </div>
    </div>
</div>

<?php if ($success): ?>
<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="form-input w-full" placeholder="Search products...">
            </div>
            <div>
                <select name="warehouse_id" class="form-select w-full">
                    <option value="">All Warehouses</option>
                    <?php foreach ($warehouses as $w): ?>
                    <option value="<?php echo $w['id']; ?>" <?php echo $warehouseId == $w['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($w['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <select name="stock_status" class="form-select w-full">
                    <option value="">All Stock</option>
                    <option value="in_stock" <?php echo $stockStatus === 'in_stock' ? 'selected' : ''; ?>>In Stock</option>
                    <option value="low_stock" <?php echo $stockStatus === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                    <option value="out_of_stock" <?php echo $stockStatus === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary flex-1">Search</button>
                <a href="<?php echo url('/admin/sar-inventory/stock-entry/'); ?>" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Stock Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Product</th>
                    <th>Warehouse</th>
                    <th>Type</th>
                    <th class="text-center">Available</th>
                    <th class="text-center">Reserved</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stockLevels)): ?>
                <tr>
                    <td colspan="8" class="text-center py-8 text-gray-500">
                        No stock records found. <button onclick="openAddStockModal()" class="text-blue-600 hover:underline">Add your first stock entry</button>
                    </td>
                </tr>
                <?php else: ?>
                <?php $serialNo = 1; foreach ($stockLevels as $stock): 
                    $isSerializable = !empty($stock['is_serializable']);
                    $available = floatval($stock['available_quantity'] ?? $stock['quantity'] ?? 0);
                    $reserved = floatval($stock['reserved_quantity'] ?? 0);
                    $minLevel = intval($stock['minimum_stock_level'] ?? 0);
                    
                    if ($available <= 0) {
                        $statusClass = 'bg-red-100 text-red-800';
                        $statusText = 'Out of Stock';
                    } elseif ($minLevel > 0 && $available <= $minLevel) {
                        $statusClass = 'bg-yellow-100 text-yellow-800';
                        $statusText = 'Low Stock';
                    } else {
                        $statusClass = 'bg-green-100 text-green-800';
                        $statusText = 'In Stock';
                    }
                ?>
                <tr>
                    <td class="text-center text-gray-500"><?php echo $serialNo++; ?></td>
                    <td>
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded bg-<?php echo $isSerializable ? 'purple' : 'blue'; ?>-100 flex items-center justify-center mr-3">
                                <?php if ($isSerializable): ?>
                                <svg class="w-4 h-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20"><path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6z"></path></svg>
                                <?php else: ?>
                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z"></path><path fill-rule="evenodd" d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path></svg>
                                <?php endif; ?>
                            </div>
                            <div>
                                <div class="font-medium"><?php echo htmlspecialchars($stock['product_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($stock['sku'] ?? ''); ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($stock['warehouse_name'] ?? '-'); ?></td>
                    <td>
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $isSerializable ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                            <?php echo $isSerializable ? 'Serializable' : 'Quantity'; ?>
                        </span>
                    </td>
                    <td class="text-center font-medium text-blue-600">
                        <?php if ($isSerializable): ?>
                            <?php echo intval($available); ?> / <?php echo intval($available + $reserved); ?>
                        <?php else: ?>
                            <?php echo number_format($available); ?>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo number_format($reserved); ?></td>
                    <td><span class="px-2 py-1 text-xs rounded-full <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                    <td>
                        <div class="flex space-x-1">
                            <button onclick="openAddStockModal(<?php echo $stock['product_id']; ?>, <?php echo $stock['warehouse_id'] ?? 'null'; ?>)" 
                                    class="btn btn-sm btn-primary" title="Add Stock">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                            </button>
                            <?php if ($isSerializable): ?>
                            <button onclick="viewSerials(<?php echo $stock['product_id']; ?>, <?php echo $stock['warehouse_id'] ?? 'null'; ?>)" 
                                    class="btn btn-sm btn-secondary" title="View Serial Numbers">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Stock Modal -->
<div id="addStockModal" class="modal">
    <div class="modal-content" style="max-width: 500px; margin-top: 5%;">
        <div class="flex justify-between items-center mb-4 pb-4 border-b">
            <h3 class="text-lg font-semibold">Add Stock Entry</h3>
            <button onclick="closeAddStockModal()" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        
        <form id="addStockForm" method="POST" action="<?php echo url('/admin/sar-inventory/stock-entry/create.php'); ?>">
            <input type="hidden" name="action" value="add_stock">
            
            <div class="form-group">
                <label class="form-label">Product <span class="text-red-500">*</span></label>
                <select name="product_id" id="modal_product_id" class="form-select" required onchange="onProductChange()">
                    <option value="">Select Product</option>
                    <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>" data-serializable="<?php echo !empty($p['is_serializable']) ? '1' : '0'; ?>">
                        <?php echo htmlspecialchars($p['name']); ?> <?php echo !empty($p['is_serializable']) ? '[Serializable]' : ''; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Warehouse <span class="text-red-500">*</span></label>
                <select name="warehouse_id" id="modal_warehouse_id" class="form-select" required>
                    <option value="">Select Warehouse</option>
                    <?php foreach ($warehouses as $w): ?>
                    <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Quantity Fields (for non-serializable) -->
            <div id="quantityFields">
                <div class="form-group">
                    <label class="form-label">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" name="quantity" id="modal_quantity" class="form-input" min="1" value="1">
                </div>
            </div>
            
            <!-- Serial Number Fields (for serializable) -->
            <div id="serialFields" style="display: none;">
                <div class="form-group">
                    <label class="form-label">Serial Numbers <span class="text-red-500">*</span></label>
                    <div id="serialNumbersContainer">
                        <div class="flex gap-2 mb-2">
                            <input type="text" name="serial_numbers[]" class="form-input flex-1" placeholder="Enter serial number">
                            <button type="button" onclick="addSerialField()" class="btn btn-secondary">+</button>
                        </div>
                    </div>
                    <p class="text-xs text-gray-500">Add multiple serial numbers for batch entry</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Warranty Expiry</label>
                    <input type="date" name="warranty_expiry" class="form-input">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">PO Number</label>
                <input type="text" name="po_number" class="form-input" placeholder="Purchase Order Number">
            </div>
            
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-input" rows="2" placeholder="Optional notes"></textarea>
            </div>
            
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t">
                <button type="button" onclick="closeAddStockModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    Add Stock
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddStockModal(productId = null, warehouseId = null) {
    document.getElementById('addStockModal').classList.add('show');
    if (productId) {
        document.getElementById('modal_product_id').value = productId;
        onProductChange();
    }
    if (warehouseId) {
        document.getElementById('modal_warehouse_id').value = warehouseId;
    }
}

function closeAddStockModal() {
    document.getElementById('addStockModal').classList.remove('show');
    document.getElementById('addStockForm').reset();
    document.getElementById('quantityFields').style.display = 'block';
    document.getElementById('serialFields').style.display = 'none';
}

function onProductChange() {
    const select = document.getElementById('modal_product_id');
    const option = select.options[select.selectedIndex];
    const isSerializable = option.dataset.serializable === '1';
    
    document.getElementById('quantityFields').style.display = isSerializable ? 'none' : 'block';
    document.getElementById('serialFields').style.display = isSerializable ? 'block' : 'none';
    
    if (isSerializable) {
        document.getElementById('modal_quantity').removeAttribute('required');
    } else {
        document.getElementById('modal_quantity').setAttribute('required', 'required');
    }
}

function addSerialField() {
    const container = document.getElementById('serialNumbersContainer');
    const div = document.createElement('div');
    div.className = 'flex gap-2 mb-2';
    div.innerHTML = `
        <input type="text" name="serial_numbers[]" class="form-input flex-1" placeholder="Enter serial number">
        <button type="button" onclick="this.parentElement.remove()" class="btn btn-danger btn-sm">-</button>
    `;
    container.appendChild(div);
}

function viewSerials(productId, warehouseId) {
    window.location.href = '<?php echo url('/admin/sar-inventory/stock-entry/serials.php'); ?>?product_id=' + productId + (warehouseId ? '&warehouse_id=' + warehouseId : '');
}

// Close modal on outside click
document.getElementById('addStockModal').addEventListener('click', function(e) {
    if (e.target === this) closeAddStockModal();
});
</script>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
