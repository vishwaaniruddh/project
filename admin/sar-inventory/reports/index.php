<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvReportingService.php';
require_once '../../../services/SarInvWarehouseService.php';
require_once '../../../services/SarInvProductService.php';
require_once '../../../models/Vendor.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Reports';
$currentUser = Auth::getCurrentUser();

$reportingService = new SarInvReportingService();
$warehouseService = new SarInvWarehouseService();
$productService = new SarInvProductService();
$vendorModel = new Vendor();

// Get available reports
$availableReports = $reportingService->getAvailableReports();

// Get dropdown data
$warehouses = $warehouseService->getActiveWarehouses();
$products = $productService->searchProducts(null, null, 'active');
$vendors = $vendorModel->getActiveVendors();

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    $reportType = $_POST['report_type'] ?? '';
    $format = $_POST['format'] ?? 'csv';
    $filters = [];
    
    // Build filters based on report type
    if (!empty($_POST['warehouse_id'])) $filters['warehouse_id'] = $_POST['warehouse_id'];
    if (!empty($_POST['source_warehouse_id'])) $filters['source_warehouse_id'] = $_POST['source_warehouse_id'];
    if (!empty($_POST['destination_warehouse_id'])) $filters['destination_warehouse_id'] = $_POST['destination_warehouse_id'];
    if (!empty($_POST['product_id'])) $filters['product_id'] = $_POST['product_id'];
    if (!empty($_POST['status'])) $filters['status'] = $_POST['status'];
    if (!empty($_POST['location_type'])) $filters['location_type'] = $_POST['location_type'];
    if (!empty($_POST['vendor_id'])) $filters['vendor_id'] = $_POST['vendor_id'];
    if (!empty($_POST['transaction_type'])) $filters['transaction_type'] = $_POST['transaction_type'];
    if (!empty($_POST['date_from'])) $filters['date_from'] = $_POST['date_from'];
    if (!empty($_POST['date_to'])) $filters['date_to'] = $_POST['date_to'];
    
    $report = null;
    
    switch ($reportType) {
        case 'stock':
            $report = $reportingService->generateStockReport($filters, $format);
            break;
        case 'dispatch':
            $report = $reportingService->generateDispatchReport($filters, $format);
            break;
        case 'transfer':
            $report = $reportingService->generateTransferReport($filters, $format);
            break;
        case 'asset':
            $report = $reportingService->generateAssetReport($filters, $format);
            break;
        case 'repair':
            $report = $reportingService->generateRepairReport($filters, $format);
            break;
        case 'movement':
            $report = $reportingService->generateMovementReport($filters, $format);
            break;
        case 'warehouse_summary':
            $report = $reportingService->generateWarehouseSummaryReport($format);
            break;
    }
    
    if ($report) {
        header('Content-Type: ' . $report['mime_type']);
        header('Content-Disposition: attachment; filename="' . $report['filename'] . '"');
        echo $report['content'];
        exit;
    }
}

// Status options for different reports
$stockStatuses = ['low_stock' => 'Low Stock', 'out_of_stock' => 'Out of Stock'];
$dispatchStatuses = ['pending' => 'Pending', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled'];
$transferStatuses = ['pending' => 'Pending', 'approved' => 'Approved', 'in_transit' => 'In Transit', 'received' => 'Received', 'cancelled' => 'Cancelled'];
$assetStatuses = ['available' => 'Available', 'dispatched' => 'Dispatched', 'in_repair' => 'In Repair', 'retired' => 'Retired'];
$repairStatuses = ['pending' => 'Pending', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
$locationTypes = ['warehouse' => 'Warehouse', 'dispatch' => 'Dispatch', 'repair' => 'Repair', 'site' => 'Site'];
$transactionTypes = ['stock_in' => 'Stock In', 'stock_out' => 'Stock Out', 'adjustment' => 'Adjustment', 'transfer_out' => 'Transfer Out', 'transfer_in' => 'Transfer In', 'dispatch' => 'Dispatch'];

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Reports</h1>
            <p class="text-gray-600">Generate and export inventory reports</p>
        </div>
    </div>
</div>

<!-- Report Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($availableReports as $key => $report): ?>
    <div class="card hover:shadow-lg transition-shadow cursor-pointer" onclick="showReportForm('<?php echo $key; ?>')">
        <div class="card-body">
            <div class="flex items-start justify-between">
                <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                    <?php if ($key === 'stock'): ?>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <?php elseif ($key === 'dispatch'): ?>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    <?php elseif ($key === 'transfer'): ?>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                    </svg>
                    <?php elseif ($key === 'asset'): ?>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                    <?php elseif ($key === 'repair'): ?>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    <?php elseif ($key === 'movement'): ?>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <?php else: ?>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <?php endif; ?>
                </div>
                <span class="text-xs text-gray-500"><?php echo count($report['filters']); ?> filters</span>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($report['name']); ?></h3>
            <p class="mt-1 text-sm text-gray-600"><?php echo htmlspecialchars($report['description']); ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Report Generation Modal -->
<div id="reportModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 id="modalTitle" class="text-lg font-semibold text-gray-900">Generate Report</h3>
            <button onclick="closeReportModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="POST" id="reportForm">
            <input type="hidden" name="generate" value="1">
            <input type="hidden" name="report_type" id="reportType" value="">
            
            <div class="space-y-4">
                <!-- Common Filters -->
                <div id="warehouseFilter" class="form-group hidden">
                    <label class="form-label">Warehouse</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">All Warehouses</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?php echo $warehouse['id']; ?>"><?php echo htmlspecialchars($warehouse['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="sourceWarehouseFilter" class="form-group hidden">
                    <label class="form-label">Source Warehouse</label>
                    <select name="source_warehouse_id" class="form-select">
                        <option value="">All Warehouses</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?php echo $warehouse['id']; ?>"><?php echo htmlspecialchars($warehouse['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="destinationWarehouseFilter" class="form-group hidden">
                    <label class="form-label">Destination Warehouse</label>
                    <select name="destination_warehouse_id" class="form-select">
                        <option value="">All Warehouses</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?php echo $warehouse['id']; ?>"><?php echo htmlspecialchars($warehouse['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="productFilter" class="form-group hidden">
                    <label class="form-label">Product</label>
                    <select name="product_id" class="form-select">
                        <option value="">All Products</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="statusFilter" class="form-group hidden">
                    <label class="form-label">Status</label>
                    <select name="status" id="statusSelect" class="form-select">
                        <option value="">All Statuses</option>
                    </select>
                </div>
                
                <div id="locationTypeFilter" class="form-group hidden">
                    <label class="form-label">Location Type</label>
                    <select name="location_type" class="form-select">
                        <option value="">All Locations</option>
                        <?php foreach ($locationTypes as $key => $label): ?>
                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="vendorFilter" class="form-group hidden">
                    <label class="form-label">Vendor</label>
                    <select name="vendor_id" class="form-select">
                        <option value="">All Vendors</option>
                        <?php foreach ($vendors as $vendor): ?>
                        <option value="<?php echo $vendor['id']; ?>"><?php echo htmlspecialchars($vendor['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="transactionTypeFilter" class="form-group hidden">
                    <label class="form-label">Transaction Type</label>
                    <select name="transaction_type" class="form-select">
                        <option value="">All Types</option>
                        <?php foreach ($transactionTypes as $key => $label): ?>
                        <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div id="dateFilters" class="grid grid-cols-2 gap-4 hidden">
                    <div class="form-group">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-input">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-input">
                    </div>
                </div>
                
                <!-- Format Selection -->
                <div class="form-group">
                    <label class="form-label">Export Format</label>
                    <div class="flex gap-4">
                        <label class="flex items-center">
                            <input type="radio" name="format" value="csv" checked class="mr-2">
                            <span>CSV</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="format" value="excel" class="mr-2">
                            <span>Excel</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="format" value="pdf" class="mr-2">
                            <span>PDF (HTML)</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end gap-4 mt-6 pt-4 border-t">
                <button type="button" onclick="closeReportModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Generate Report
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const reportConfigs = {
    stock: {
        title: 'Stock Report',
        filters: ['warehouseFilter', 'productFilter', 'statusFilter'],
        statuses: <?php echo json_encode($stockStatuses); ?>
    },
    dispatch: {
        title: 'Dispatch Report',
        filters: ['warehouseFilter', 'statusFilter', 'dateFilters'],
        statuses: <?php echo json_encode($dispatchStatuses); ?>
    },
    transfer: {
        title: 'Transfer Report',
        filters: ['sourceWarehouseFilter', 'destinationWarehouseFilter', 'statusFilter', 'dateFilters'],
        statuses: <?php echo json_encode($transferStatuses); ?>
    },
    asset: {
        title: 'Asset Report',
        filters: ['productFilter', 'statusFilter', 'locationTypeFilter'],
        statuses: <?php echo json_encode($assetStatuses); ?>
    },
    repair: {
        title: 'Repair Report',
        filters: ['vendorFilter', 'statusFilter', 'dateFilters'],
        statuses: <?php echo json_encode($repairStatuses); ?>
    },
    movement: {
        title: 'Inventory Movement Report',
        filters: ['warehouseFilter', 'productFilter', 'transactionTypeFilter', 'dateFilters'],
        statuses: {}
    },
    warehouse_summary: {
        title: 'Warehouse Summary Report',
        filters: [],
        statuses: {}
    }
};

function showReportForm(reportType) {
    const config = reportConfigs[reportType];
    if (!config) return;
    
    // Set title and report type
    document.getElementById('modalTitle').textContent = config.title;
    document.getElementById('reportType').value = reportType;
    
    // Hide all filters
    document.querySelectorAll('#reportForm .form-group').forEach(el => {
        if (el.id && el.id !== 'statusFilter') {
            el.classList.add('hidden');
        }
    });
    document.getElementById('dateFilters').classList.add('hidden');
    document.getElementById('statusFilter').classList.add('hidden');
    
    // Show relevant filters
    config.filters.forEach(filterId => {
        const el = document.getElementById(filterId);
        if (el) el.classList.remove('hidden');
    });
    
    // Update status options
    const statusSelect = document.getElementById('statusSelect');
    statusSelect.innerHTML = '<option value="">All Statuses</option>';
    Object.entries(config.statuses).forEach(([key, label]) => {
        statusSelect.innerHTML += `<option value="${key}">${label}</option>`;
    });
    
    // Show modal
    document.getElementById('reportModal').classList.remove('hidden');
}

function closeReportModal() {
    document.getElementById('reportModal').classList.add('hidden');
    document.getElementById('reportForm').reset();
}

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeReportModal();
});

// Close modal when clicking outside
document.getElementById('reportModal').addEventListener('click', function(e) {
    if (e.target === this) closeReportModal();
});
</script>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
