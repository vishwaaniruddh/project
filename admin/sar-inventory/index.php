<?php
require_once '../../config/auth.php';
require_once '../../config/database.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$title = 'Inventory Dashboard';
$currentUser = Auth::getCurrentUser();

// Initialize dashboard data with defaults
$dashboardData = null;
$kpis = [];
$trends = [];
$loadError = null;

try {
    // Load the dashboard service
    require_once '../../services/SarInvDashboardService.php';
    
    // Initialize dashboard service
    $dashboardService = new SarInvDashboardService();
    $dashboardData = $dashboardService->getDashboardData();
    $kpis = $dashboardService->getKPIs();
    $trends = $dashboardService->getInventoryTrends(30);
} catch (Error $e) {
    // Handle class loading errors (tables might not exist)
    $loadError = $e->getMessage();
} catch (Exception $e) {
    $loadError = $e->getMessage();
}

// Set default data if loading failed
if ($dashboardData === null) {
    $dashboardData = [
        'summary' => [
            'warehouses' => ['total' => 0, 'active' => 0, 'inactive' => 0],
            'products' => ['total' => 0, 'active' => 0, 'low_stock' => 0],
            'stock' => ['products_in_stock' => 0, 'total_quantity' => 0, 'total_reserved' => 0, 'low_stock_items' => 0],
            'dispatches' => ['pending' => 0, 'approved' => 0, 'shipped' => 0, 'in_transit' => 0, 'total_active' => 0],
            'transfers' => ['pending' => 0, 'approved' => 0, 'in_transit' => 0, 'total_active' => 0],
            'assets' => ['available' => 0, 'dispatched' => 0, 'in_repair' => 0, 'expiring_warranty' => 0, 'total' => 0],
            'repairs' => ['pending' => 0, 'in_progress' => 0, 'overdue' => 0, 'total_active' => 0],
            'material_requests' => ['pending' => 0, 'approved' => 0, 'partially_fulfilled' => 0, 'total_active' => 0]
        ],
        'stock_alerts' => [],
        'pending_actions' => [],
        'recent_activity' => [],
        'warehouse_utilization' => []
    ];
}

$summary = $dashboardData['summary'];
ob_start();
?>

<?php if ($loadError): ?>
<!-- Setup Required Notice -->
<div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-lg">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-sm font-medium text-yellow-800">Database Setup Required</h3>
            <div class="mt-2 text-sm text-yellow-700">
                <p>The inventory system tables need to be created. Please run the migration:</p>
                <a href="<?php echo url('/database/run_sar_inventory_migration.php'); ?>" class="inline-block mt-2 px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700" target="_blank">
                    Run Migration
                </a>
                <p class="mt-2 text-xs">Error: <?php echo htmlspecialchars($loadError); ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Dashboard Header -->
<div class="bg-gradient-to-r from-indigo-600 to-purple-700 rounded-lg shadow-lg p-6 mb-8 text-white">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold">Inventory Management</h1>
            <p class="mt-2 text-indigo-100">Real-time inventory metrics and operations dashboard</p>
            <p class="text-sm text-indigo-200 mt-1"><?php echo date('l, F j, Y'); ?></p>
        </div>
        <div class="mt-6 lg:mt-0 lg:ml-6">
            <div class="flex space-x-3">
                <button onclick="refreshDashboard()" class="bg-white bg-opacity-20 hover:bg-opacity-30 text-white px-4 py-2 rounded-lg transition-all duration-200">
                    <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Refresh
                </button>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards Row -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
    <?php foreach ($kpis as $key => $kpi): 
        $statusColors = [
            'good' => 'bg-green-100 text-green-800 border-green-200',
            'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
            'critical' => 'bg-red-100 text-red-800 border-red-200'
        ];
        $statusColor = $statusColors[$kpi['status']] ?? 'bg-gray-100 text-gray-800 border-gray-200';
    ?>
    <div class="bg-white rounded-xl shadow-sm border <?php echo $statusColor; ?> p-4">
        <div class="text-sm font-medium opacity-75"><?php echo htmlspecialchars($kpi['label']); ?></div>
        <div class="text-2xl font-bold mt-1"><?php echo htmlspecialchars($kpi['value'] . $kpi['unit']); ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Key Metrics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Warehouses Card -->
    <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100 hover:shadow-xl transition-shadow duration-300">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Warehouses</dt>
                        <dd class="text-2xl font-bold text-gray-900"><?php echo $summary['warehouses']['total']; ?></dd>
                        <dd class="text-sm text-gray-600 mt-1">
                            <span class="text-green-600"><?php echo $summary['warehouses']['active']; ?></span> active
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <a href="<?php echo url('/admin/sar-inventory/warehouses/'); ?>" class="text-sm text-blue-600 hover:text-blue-800">Manage Warehouses →</a>
        </div>
    </div>

    <!-- Products Card -->
    <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100 hover:shadow-xl transition-shadow duration-300">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-green-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Products</dt>
                        <dd class="text-2xl font-bold text-gray-900"><?php echo $summary['products']['total']; ?></dd>
                        <dd class="text-sm text-gray-600 mt-1">
                            <span class="text-red-600"><?php echo $summary['products']['low_stock']; ?></span> low stock
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <a href="<?php echo url('/admin/sar-inventory/products/'); ?>" class="text-sm text-blue-600 hover:text-blue-800">Manage Products →</a>
        </div>
    </div>

    <!-- Dispatches Card -->
    <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100 hover:shadow-xl transition-shadow duration-300">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Active Dispatches</dt>
                        <dd class="text-2xl font-bold text-gray-900"><?php echo $summary['dispatches']['total_active']; ?></dd>
                        <dd class="text-sm text-gray-600 mt-1">
                            <span class="text-yellow-600"><?php echo $summary['dispatches']['pending']; ?></span> pending
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <a href="<?php echo url('/admin/sar-inventory/dispatches/'); ?>" class="text-sm text-blue-600 hover:text-blue-800">Manage Dispatches →</a>
        </div>
    </div>

    <!-- Transfers Card -->
    <div class="bg-white overflow-hidden shadow-lg rounded-xl border border-gray-100 hover:shadow-xl transition-shadow duration-300">
        <div class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 truncate">Active Transfers</dt>
                        <dd class="text-2xl font-bold text-gray-900"><?php echo $summary['transfers']['total_active']; ?></dd>
                        <dd class="text-sm text-gray-600 mt-1">
                            <span class="text-purple-600"><?php echo $summary['transfers']['pending']; ?></span> pending
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="bg-gray-50 px-6 py-3">
            <a href="<?php echo url('/admin/sar-inventory/transfers/'); ?>" class="text-sm text-blue-600 hover:text-blue-800">Manage Transfers →</a>
        </div>
    </div>
</div>

<!-- Secondary Metrics Row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Stock Status -->
    <div class="bg-white shadow-lg rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Stock Status</h3>
            <div class="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Products in Stock</span>
                <span class="font-semibold text-gray-900"><?php echo number_format($summary['stock']['products_in_stock']); ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Total Quantity</span>
                <span class="font-semibold text-gray-900"><?php echo number_format($summary['stock']['total_quantity']); ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Reserved</span>
                <span class="font-semibold text-yellow-600"><?php echo number_format($summary['stock']['total_reserved']); ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Low Stock Items</span>
                <span class="font-semibold text-red-600"><?php echo number_format($summary['stock']['low_stock_items']); ?></span>
            </div>
        </div>
    </div>

    <!-- Assets Status -->
    <div class="bg-white shadow-lg rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Assets Status</h3>
            <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                </svg>
            </div>
        </div>
        <div class="space-y-3">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Total Assets</span>
                <span class="font-semibold text-gray-900"><?php echo number_format($summary['assets']['total']); ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Available</span>
                <span class="font-semibold text-green-600"><?php echo number_format($summary['assets']['available']); ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">Dispatched</span>
                <span class="font-semibold text-blue-600"><?php echo number_format($summary['assets']['dispatched']); ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">In Repair</span>
                <span class="font-semibold text-yellow-600"><?php echo number_format($summary['assets']['in_repair']); ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white shadow-lg rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Quick Actions</h3>
            <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
        </div>
        <div class="space-y-3">
            <a href="<?php echo url('/admin/sar-inventory/stock-entry/create.php'); ?>" class="block w-full bg-blue-50 hover:bg-blue-100 text-blue-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                New Stock Entry
            </a>
            <a href="<?php echo url('/admin/sar-inventory/dispatches/create.php'); ?>" class="block w-full bg-green-50 hover:bg-green-100 text-green-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                Create Dispatch
            </a>
            <a href="<?php echo url('/admin/sar-inventory/transfers/create.php'); ?>" class="block w-full bg-purple-50 hover:bg-purple-100 text-purple-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors duration-200">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                </svg>
                Create Transfer
            </a>
        </div>
    </div>
</div>

<!-- Warehouse Utilization and Alerts Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Warehouse Utilization -->
    <div class="bg-white shadow-lg rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Warehouse Utilization</h3>
            <a href="<?php echo url('/admin/sar-inventory/warehouses/'); ?>" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
        </div>
        <div class="space-y-4">
            <?php if (empty($dashboardData['warehouse_utilization'])): ?>
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                    <p>No warehouses configured</p>
                </div>
            <?php else: ?>
                <?php foreach (array_slice($dashboardData['warehouse_utilization'], 0, 5) as $warehouse): 
                    $utilPercent = $warehouse['utilization_percentage'];
                    $barColor = $utilPercent >= 90 ? 'bg-red-500' : ($utilPercent >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                    $bgColor = $utilPercent >= 90 ? 'bg-red-100' : ($utilPercent >= 70 ? 'bg-yellow-100' : 'bg-green-100');
                ?>
                <div class="flex items-center justify-between">
                    <div class="flex items-center flex-1">
                        <span class="text-sm text-gray-700 flex-1"><?php echo htmlspecialchars($warehouse['warehouse_name']); ?></span>
                        <span class="text-sm font-semibold text-gray-900 mr-4"><?php echo $utilPercent; ?>%</span>
                    </div>
                    <div class="w-32 <?php echo $bgColor; ?> rounded-full h-2">
                        <div class="<?php echo $barColor; ?> h-2 rounded-full" style="width: <?php echo min(100, $utilPercent); ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stock Alerts -->
    <div class="bg-white shadow-lg rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Stock Alerts</h3>
            <span class="text-sm text-gray-500"><?php echo count($dashboardData['stock_alerts']); ?> alerts</span>
        </div>
        <div class="space-y-3 max-h-64 overflow-y-auto">
            <?php if (empty($dashboardData['stock_alerts'])): ?>
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>No stock alerts</p>
                </div>
            <?php else: ?>
                <?php foreach (array_slice($dashboardData['stock_alerts'], 0, 8) as $alert): 
                    $alertClass = $alert['severity'] === 'critical' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-yellow-50 border-yellow-200 text-yellow-800';
                ?>
                <div class="p-3 rounded-lg border <?php echo $alertClass; ?>">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium"><?php echo htmlspecialchars($alert['product_name']); ?></p>
                            <p class="text-xs opacity-75"><?php echo htmlspecialchars($alert['warehouse_name']); ?> - Qty: <?php echo $alert['available_quantity']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pending Actions and Recent Activity Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Pending Actions -->
    <div class="bg-white shadow-lg rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Pending Actions</h3>
            <span class="text-sm text-gray-500"><?php echo count($dashboardData['pending_actions']); ?> items</span>
        </div>
        <div class="space-y-3 max-h-80 overflow-y-auto">
            <?php if (empty($dashboardData['pending_actions'])): ?>
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>No pending actions</p>
                </div>
            <?php else: ?>
                <?php foreach ($dashboardData['pending_actions'] as $action): 
                    $typeColors = [
                        'dispatch_pending' => 'bg-blue-100 text-blue-800',
                        'transfer_pending' => 'bg-purple-100 text-purple-800',
                        'material_request_pending' => 'bg-green-100 text-green-800',
                        'repair_overdue' => 'bg-red-100 text-red-800'
                    ];
                    $typeColor = $typeColors[$action['type']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <div class="flex items-start space-x-3 p-3 bg-gray-50 rounded-lg">
                    <span class="px-2 py-1 text-xs font-medium rounded <?php echo $typeColor; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $action['type'])); ?>
                    </span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($action['message']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($action['created_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white shadow-lg rounded-xl border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
            <a href="<?php echo url('/admin/sar-inventory/item-history/'); ?>" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
        </div>
        <div class="space-y-3 max-h-80 overflow-y-auto">
            <?php if (empty($dashboardData['recent_activity'])): ?>
                <div class="text-center py-8 text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>No recent activity</p>
                </div>
            <?php else: ?>
                <?php foreach ($dashboardData['recent_activity'] as $activity): 
                    $typeColors = [
                        'stock_in' => 'bg-green-500',
                        'stock_out' => 'bg-red-500',
                        'adjustment' => 'bg-yellow-500',
                        'transfer_out' => 'bg-purple-500',
                        'transfer_in' => 'bg-blue-500',
                        'dispatch' => 'bg-orange-500',
                        'reservation' => 'bg-indigo-500',
                        'release' => 'bg-teal-500'
                    ];
                    $dotColor = $typeColors[$activity['type']] ?? 'bg-gray-500';
                ?>
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-2 h-2 rounded-full mt-2 <?php echo $dotColor; ?>"></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-900"><?php echo htmlspecialchars($activity['description']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto-refresh dashboard every 5 minutes
setInterval(refreshDashboard, 300000);

function refreshDashboard() {
    location.reload();
}

// Add hover effects to cards
document.addEventListener('DOMContentLoaded', function() {
    const cards = document.querySelectorAll('.hover\\:shadow-xl');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
});
</script>
<?php
$content = ob_get_clean();
include '../../includes/admin_layout.php';
?>
