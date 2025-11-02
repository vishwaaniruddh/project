<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SiteDelegation.php';
require_once __DIR__ . '/../models/Vendor.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Require vendor authentication
Auth::requireVendor();

$vendorId = Auth::getVendorId();
$delegationModel = new SiteDelegation();
$vendorModel = new Vendor();

// Get vendor info
$vendor = $vendorModel->find($vendorId);
if (!$vendor) {
    Auth::logout();
}

// Get delegated sites
$delegatedSites = $delegationModel->getVendorDelegations($vendorId, 'active');
$completedSites = $delegationModel->getVendorDelegations($vendorId, 'completed');

$title = 'Dashboard - ' . $vendor['name'];
ob_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

?>

<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900">Welcome back!</h1>
            <p class="mt-2 text-lg text-gray-600"><?php echo htmlspecialchars($vendor['name']); ?></p>
            <p class="text-sm text-gray-500 mt-1">Manage your site installations and track progress</p>
        </div>
        <div class="mt-6 lg:mt-0 lg:ml-6">
            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3">
                <a href="sites/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"></path>
                    </svg>
                    View All Sites
                </a>
                <a href="profile.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                    </svg>
                    Profile
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="stats-card p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="stats-icon w-12 h-12 bg-blue-500 flex items-center justify-center text-white">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-5 flex-1">
                <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Active Delegations</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo count($delegatedSites); ?></div>
                <div class="text-xs text-blue-600 font-medium">Sites in progress</div>
            </div>
        </div>
    </div>
    
    <div class="stats-card p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="stats-icon w-12 h-12 bg-green-500 flex items-center justify-center text-white">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-5 flex-1">
                <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Completed</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo count($completedSites); ?></div>
                <div class="text-xs text-green-600 font-medium">Successfully finished</div>
            </div>
        </div>
    </div>
    
    <div class="stats-card p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="stats-icon w-12 h-12 bg-orange-500 flex items-center justify-center text-white">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-5 flex-1">
                <div class="text-sm font-medium text-gray-500 uppercase tracking-wide">Total Assigned</div>
                <div class="text-2xl font-bold text-gray-900"><?php echo count($delegatedSites) + count($completedSites); ?></div>
                <div class="text-xs text-orange-600 font-medium">All time projects</div>
            </div>
        </div>
    </div>
</div>

<!-- Active Delegations -->
<div class="professional-table bg-white mb-8">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Active Site Delegations</h3>
            <p class="text-sm text-gray-500 mt-1">Manage your assigned installation projects</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
            <?php echo count($delegatedSites); ?> Active Sites
        </span>
    </div>
    <div class="p-6">
        <?php if (empty($delegatedSites)): ?>
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No active delegations</h3>
                <p class="mt-1 text-sm text-gray-500">You don't have any active site delegations at the moment.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delegated Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delegated By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($delegatedSites as $site): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <svg class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($site['site_id']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($site['location']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($site['city'] . ', ' . $site['state']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($site['delegation_date'])); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('H:i A', strtotime($site['delegation_date'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center">
                                        <span class="text-xs font-medium text-gray-600"><?php echo strtoupper(substr($site['delegated_by_name'], 0, 1)); ?></span>
                                    </div>
                                    <div class="ml-2 text-sm text-gray-900"><?php echo htmlspecialchars($site['delegated_by_name']); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars($site['notes'] ?: 'No notes'); ?>">
                                    <?php echo htmlspecialchars($site['notes'] ?: 'No notes'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2">
                                    <button onclick="viewSiteDetails(<?php echo $site['site_id']; ?>)" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="View Details">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                    <button onclick="updateProgress(<?php echo $site['id']; ?>)" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="Update Progress">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Completed Sites -->
<?php if (!empty($completedSites)): ?>
<div class="professional-table bg-white">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Recently Completed Sites</h3>
            <p class="text-sm text-gray-500 mt-1">Your successfully completed projects</p>
        </div>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
            <?php echo count($completedSites); ?> Completed
        </span>
    </div>
    <div class="p-6">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Site Details</th>
                        <th>Location</th>
                        <th>Completed Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($completedSites, 0, 5) as $site): ?>
                    <tr>
                        <td>
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($site['site_id']); ?></div>
                                <div class="text-sm text-gray-500">Location: <?php echo htmlspecialchars($site['location']); ?></div>
                            </div>
                        </td>
                        <td>
                            <div class="text-sm text-gray-900"><?php echo htmlspecialchars($site['city'] . ', ' . $site['state']); ?></div>
                        </td>
                        <td>
                            <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($site['updated_at'])); ?></div>
                        </td>
                        <td>
                            <span class="badge badge-success">Completed</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (count($completedSites) > 5): ?>
        <div class="mt-4 text-center">
            <a href="completed.php" class="btn btn-secondary">View All Completed Sites</a>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
function viewSiteDetails(siteId) {
    window.location.href = `site-details.php?id=${siteId}`;
}

function updateProgress(delegationId) {
    window.location.href = `update-progress.php?id=${delegationId}`;
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/vendor_layout.php';
?>