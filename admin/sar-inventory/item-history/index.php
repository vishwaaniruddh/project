<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../models/SarInvItemHistory.php';
require_once '../../../services/SarInvProductService.php';
require_once '../../../services/SarInvWarehouseService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Item History';
$currentUser = Auth::getCurrentUser();

$historyModel = new SarInvItemHistory();
$productService = new SarInvProductService();
$warehouseService = new SarInvWarehouseService();

// Handle export requests
if (isset($_GET['export'])) {
    $exportFilters = [
        'product_id' => $_GET['product_id'] ?? null,
        'warehouse_id' => $_GET['warehouse_id'] ?? null,
        'transaction_type' => $_GET['transaction_type'] ?? null,
        'date_from' => $_GET['date_from'] ?? null,
        'date_to' => $_GET['date_to'] ?? null,
        'keyword' => $_GET['keyword'] ?? null
    ];
    $exportFilters = array_filter($exportFilters);
    
    if ($_GET['export'] === 'csv') {
        $export = $historyModel->exportToCsv($exportFilters);
        header('Content-Type: ' . $export['mime_type']);
        header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
        echo $export['content'];
        exit;
    } elseif ($_GET['export'] === 'excel') {
        $export = $historyModel->exportToExcel($exportFilters);
        header('Content-Type: ' . $export['mime_type']);
        header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
        echo $export['content'];
        exit;
    }
}

// Get filter parameters
$productId = $_GET['product_id'] ?? '';
$warehouseId = $_GET['warehouse_id'] ?? '';
$transactionType = $_GET['transaction_type'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$keyword = trim($_GET['keyword'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 50;

// Build filters array
$filters = [];
if ($productId) $filters['product_id'] = $productId;
if ($warehouseId) $filters['warehouse_id'] = $warehouseId;
if ($transactionType) $filters['transaction_type'] = $transactionType;
if ($dateFrom) $filters['date_from'] = $dateFrom;
if ($dateTo) $filters['date_to'] = $dateTo;
if ($keyword) $filters['keyword'] = $keyword;

// Get paginated history
$result = $historyModel->getPaginated($filters, $page, $perPage);
$historyRecords = $result['data'];
$pagination = $result['pagination'];

// Get summary by type for the current filters
$summary = $historyModel->getSummaryByType($filters);

// Get dropdown data
$products = $productService->searchProducts();
$warehouses = $warehouseService->getActiveWarehouses();
$transactionTypes = SarInvItemHistory::getTransactionTypes();

// Transaction type colors
$typeColors = [
    'stock_in' => 'bg-green-100 text-green-800',
    'stock_out' => 'bg-red-100 text-red-800',
    'adjustment' => 'bg-yellow-100 text-yellow-800',
    'transfer_out' => 'bg-orange-100 text-orange-800',
    'transfer_in' => 'bg-blue-100 text-blue-800',
    'dispatch' => 'bg-purple-100 text-purple-800',
    'return' => 'bg-teal-100 text-teal-800',
    'reservation' => 'bg-indigo-100 text-indigo-800',
    'release' => 'bg-pink-100 text-pink-800'
];

// Build query string for pagination and export links
$queryParams = array_filter([
    'product_id' => $productId,
    'warehouse_id' => $warehouseId,
    'transaction_type' => $transactionType,
    'date_from' => $dateFrom,
    'date_to' => $dateTo,
    'keyword' => $keyword
]);
$queryString = http_build_query($queryParams);

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Item History</h1>
            <p class="text-gray-600">View chronological transaction records for inventory items</p>
        </div>
        <div class="flex gap-2">
            <div class="relative" id="exportDropdown">
                <button type="button" onclick="toggleExportMenu()" class="btn btn-secondary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>
                <div id="exportMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                    <a href="?<?php echo $queryString ? $queryString . '&' : ''; ?>export=csv" 
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export as CSV
                    </a>
                    <a href="?<?php echo $queryString ? $queryString . '&' : ''; ?>export=excel" 
                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export as Excel
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Cards -->
<?php if (!empty($summary)): ?>
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4 mb-6">
    <?php foreach ($summary as $item): 
        $typeLabel = $transactionTypes[$item['transaction_type']] ?? ucfirst(str_replace('_', ' ', $item['transaction_type']));
        $typeColor = $typeColors[$item['transaction_type']] ?? 'bg-gray-100 text-gray-800';
    ?>
    <div class="card">
        <div class="card-body p-4">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium <?php echo $typeColor; ?> px-2 py-1 rounded-full">
                    <?php echo htmlspecialchars($typeLabel); ?>
                </span>
            </div>
            <div class="mt-2">
                <span class="text-2xl font-bold text-gray-900"><?php echo number_format($item['count']); ?></span>
                <span class="text-sm text-gray-500 ml-1">records</span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-6">
    <div class="card-body">
        <form method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-4">
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
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">All Warehouses</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?php echo $warehouse['id']; ?>" <?php echo $warehouseId == $warehouse['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($warehouse['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Transaction Type</label>
                    <select name="transaction_type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($transactionTypes as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $transactionType === $key ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($label); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" class="form-input">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" class="form-input">
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Search</label>
                    <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" 
                           class="form-input" placeholder="Product, SKU, notes...">
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <a href="<?php echo url('/admin/sar-inventory/item-history/'); ?>" class="btn btn-secondary">Clear Filters</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results Info -->
<div class="flex items-center justify-between mb-4">
    <div class="text-sm text-gray-600">
        Showing <?php echo number_format(($pagination['current_page'] - 1) * $pagination['per_page'] + 1); ?> 
        to <?php echo number_format(min($pagination['current_page'] * $pagination['per_page'], $pagination['total'])); ?> 
        of <?php echo number_format($pagination['total']); ?> records
    </div>
</div>

<!-- History Table -->
<div class="card">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-12">#</th>
                    <th>Date/Time</th>
                    <th>Product</th>
                    <th>Warehouse</th>
                    <th>Type</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Balance</th>
                    <th>Reference</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historyRecords)): ?>
                <tr>
                    <td colspan="9" class="text-center py-8 text-gray-500">
                        No history records found matching your criteria.
                    </td>
                </tr>
                <?php else: ?>
                <?php 
                $startNum = ($pagination['current_page'] - 1) * $pagination['per_page'] + 1;
                foreach ($historyRecords as $index => $record): 
                    $typeLabel = $transactionTypes[$record['transaction_type']] ?? ucfirst(str_replace('_', ' ', $record['transaction_type']));
                    $typeColor = $typeColors[$record['transaction_type']] ?? 'bg-gray-100 text-gray-800';
                    $quantity = floatval($record['quantity']);
                    $isPositive = $quantity >= 0;
                ?>
                <tr>
                    <td class="text-center text-gray-500"><?php echo $startNum + $index; ?></td>
                    <td class="whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo date('M j, Y', strtotime($record['created_at'])); ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?php echo date('h:i A', strtotime($record['created_at'])); ?>
                        </div>
                    </td>
                    <td>
                        <div class="font-medium text-gray-900"><?php echo htmlspecialchars($record['product_name'] ?? '-'); ?></div>
                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($record['sku'] ?? ''); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($record['warehouse_name'] ?? '-'); ?></td>
                    <td>
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $typeColor; ?>">
                            <?php echo htmlspecialchars($typeLabel); ?>
                        </span>
                    </td>
                    <td class="text-right font-mono <?php echo $isPositive ? 'text-green-600' : 'text-red-600'; ?>">
                        <?php echo $isPositive ? '+' : ''; ?><?php echo number_format($quantity); ?>
                    </td>
                    <td class="text-right font-mono text-gray-900">
                        <?php echo number_format($record['balance_after'] ?? 0); ?>
                    </td>
                    <td class="text-sm">
                        <?php if (!empty($record['reference_type'])): ?>
                        <span class="text-gray-600"><?php echo ucfirst($record['reference_type']); ?></span>
                        <?php if (!empty($record['reference_id'])): ?>
                        <span class="text-gray-400">#<?php echo $record['reference_id']; ?></span>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-gray-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-sm text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars($record['notes'] ?? ''); ?>">
                        <?php echo htmlspecialchars($record['notes'] ?? '-'); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- Pagination -->
<?php if ($pagination['total_pages'] > 1): ?>
<div class="mt-6 flex flex-col md:flex-row items-center justify-between gap-4">
    <div class="text-sm text-gray-600">
        Page <?php echo $pagination['current_page']; ?> of <?php echo $pagination['total_pages']; ?>
    </div>
    <nav class="flex items-center gap-1">
        <?php
        $baseUrl = url('/admin/sar-inventory/item-history/');
        $buildPageUrl = function($pageNum) use ($baseUrl, $queryParams) {
            $params = array_merge($queryParams, ['page' => $pageNum]);
            return $baseUrl . '?' . http_build_query($params);
        };
        
        // Previous button
        if ($pagination['current_page'] > 1): ?>
        <a href="<?php echo $buildPageUrl($pagination['current_page'] - 1); ?>" 
           class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </a>
        <?php endif; ?>
        
        <?php
        // Page numbers
        $startPage = max(1, $pagination['current_page'] - 2);
        $endPage = min($pagination['total_pages'], $pagination['current_page'] + 2);
        
        if ($startPage > 1): ?>
        <a href="<?php echo $buildPageUrl(1); ?>" 
           class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">1</a>
        <?php if ($startPage > 2): ?>
        <span class="px-2 text-gray-500">...</span>
        <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
        <a href="<?php echo $buildPageUrl($i); ?>" 
           class="px-3 py-2 text-sm font-medium <?php echo $i === $pagination['current_page'] 
               ? 'text-white bg-blue-600 border border-blue-600' 
               : 'text-gray-700 bg-white border border-gray-300 hover:bg-gray-50'; ?> rounded-md">
            <?php echo $i; ?>
        </a>
        <?php endfor; ?>
        
        <?php if ($endPage < $pagination['total_pages']): ?>
        <?php if ($endPage < $pagination['total_pages'] - 1): ?>
        <span class="px-2 text-gray-500">...</span>
        <?php endif; ?>
        <a href="<?php echo $buildPageUrl($pagination['total_pages']); ?>" 
           class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            <?php echo $pagination['total_pages']; ?>
        </a>
        <?php endif; ?>
        
        <?php // Next button
        if ($pagination['current_page'] < $pagination['total_pages']): ?>
        <a href="<?php echo $buildPageUrl($pagination['current_page'] + 1); ?>" 
           class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<script>
function toggleExportMenu() {
    const menu = document.getElementById('exportMenu');
    menu.classList.toggle('hidden');
}

// Close export menu when clicking outside
document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('exportDropdown');
    const menu = document.getElementById('exportMenu');
    if (!dropdown.contains(e.target)) {
        menu.classList.add('hidden');
    }
});

// Quick date range buttons
function setDateRange(range) {
    const today = new Date();
    let fromDate = new Date();
    
    switch(range) {
        case 'today':
            fromDate = today;
            break;
        case 'week':
            fromDate.setDate(today.getDate() - 7);
            break;
        case 'month':
            fromDate.setMonth(today.getMonth() - 1);
            break;
        case 'quarter':
            fromDate.setMonth(today.getMonth() - 3);
            break;
    }
    
    document.querySelector('input[name="date_from"]').value = fromDate.toISOString().split('T')[0];
    document.querySelector('input[name="date_to"]').value = today.toISOString().split('T')[0];
}
</script>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
