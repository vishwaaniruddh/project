<?php
require_once '../config/auth.php';
require_once '../config/constants.php';
require_once '../config/database.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$title = 'Admin Dashboard';
$currentUser = Auth::getCurrentUser();

// Get dashboard statistics
try {
    $db = Database::getInstance()->getConnection();
    
    // Count total sites
    $stmt = $db->query("SELECT COUNT(*) as total FROM sites");
    $totalSites = $stmt->fetch()['total'];
    
    // Count sites by status
    $stmt = $db->query("SELECT status, COUNT(*) as count FROM sites GROUP BY status");
    $sitesByStatus = [];
    while ($row = $stmt->fetch()) {
        $sitesByStatus[$row['status']] = $row['count'];
    }
    
    // Count total vendors
    $stmt = $db->query("SELECT COUNT(*) as total FROM users WHERE role = 'vendor' AND status = 'active'");
    $totalVendors = $stmt->fetch()['total'];
    
    // Count pending material requests
    $stmt = $db->query("SELECT COUNT(*) as total FROM material_requests WHERE status = 'pending'");
    $pendingRequests = $stmt->fetch()['total'];
    
} catch (Exception $e) {
    $totalSites = 0;
    $sitesByStatus = [];
    $totalVendors = 0;
    $pendingRequests = 0;
}
ob_start();
?>
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
            <p class="mt-2 text-gray-600">Welcome back, <?php echo htmlspecialchars($currentUser['username']); ?>!</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                <span class="text-white font-bold">S</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Sites</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $totalSites; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                <span class="text-white font-bold">V</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Vendors</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $totalVendors; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                <span class="text-white font-bold">R</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending Requests</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $pendingRequests; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                <span class="text-white font-bold">C</span>
                            </div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed Sites</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $sitesByStatus['completed'] ?? 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Site Status Overview -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Sites by Status</h3>
                    <div class="space-y-3">
                        <?php
                        $statusLabels = [
                            'pending' => 'Pending Assignment',
                            'assigned' => 'Assigned to Vendor',
                            'surveyed' => 'Survey Completed',
                            'in_progress' => 'Installation in Progress',
                            'completed' => 'Completed'
                        ];
                        
                        $statusColors = [
                            'pending' => 'bg-gray-100 text-gray-800',
                            'assigned' => 'bg-blue-100 text-blue-800',
                            'surveyed' => 'bg-yellow-100 text-yellow-800',
                            'in_progress' => 'bg-orange-100 text-orange-800',
                            'completed' => 'bg-green-100 text-green-800'
                        ];
                        
                        foreach ($statusLabels as $status => $label):
                            $count = $sitesByStatus[$status] ?? 0;
                        ?>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600"><?php echo $label; ?></span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColors[$status]; ?>">
                                <?php echo $count; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="<?php echo BASE_URL; ?>/admin/sites/create.php" class="block w-full btn-primary text-center">
                            Add New Site
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/users/create.php" class="block w-full btn-secondary text-center">
                            Add New Vendor
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/inventory/" class="block w-full btn-secondary text-center">
                            Manage Inventory
                        </a>
                        <a href="<?php echo BASE_URL; ?>/admin/reports/" class="block w-full btn-secondary text-center">
                            View Reports
                        </a>
                    </div>
                </div>
            </div>
<?php
$content = ob_get_clean();
include '../includes/admin_layout.php';
?>