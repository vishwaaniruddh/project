<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Site.php';
require_once __DIR__ . '/../../models/SiteDelegation.php';

// Require vendor authentication and permission
Auth::requireVendorPermission('view_sites');

$vendorId = Auth::getVendorId();
$siteModel = new Site();
$delegationModel = new SiteDelegation();

// Get vendor's delegated sites
$delegatedSites = $delegationModel->getVendorDelegations($vendorId, 'active');
$completedSites = $delegationModel->getVendorDelegations($vendorId, 'completed');

// Combine and format for display
$sites = [];
foreach ($delegatedSites as $site) {
    $site['delegation_status'] = 'active';
    $sites[] = $site;
}
foreach ($completedSites as $site) {
    $site['delegation_status'] = 'completed';
    $sites[] = $site;
}

$title = 'My Sites';
ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">My Sites</h1>
        <p class="mt-2 text-sm text-gray-700">Sites delegated to you for installation</p>
    </div>
    <div class="flex space-x-2">
        <div class="flex items-center space-x-2">
            <span class="badge badge-warning"><?php echo count($delegatedSites); ?> Active</span>
            <span class="badge badge-success"><?php echo count($completedSites); ?> Completed</span>
        </div>
    </div>
</div>

<!-- Filter Tabs -->
<div class="card mb-6">
    <div class="card-body">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button onclick="filterSites('all')" id="tab-all" class="tab-button active">
                    All Sites (<?php echo count($sites); ?>)
                </button>
                <button onclick="filterSites('active')" id="tab-active" class="tab-button">
                    Active (<?php echo count($delegatedSites); ?>)
                </button>
                <button onclick="filterSites('completed')" id="tab-completed" class="tab-button">
                    Completed (<?php echo count($completedSites); ?>)
                </button>
            </nav>
        </div>
    </div>
</div>

<!-- Sites Table -->
<div class="professional-table bg-white">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Site Assignments</h3>
            <p class="text-sm text-gray-500 mt-1">Manage your delegated installation projects</p>
        </div>
    </div>
    <div class="p-6">
        <?php if (empty($sites)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No sites assigned</h3>
                <p class="mt-1 text-sm text-gray-500">You don't have any sites assigned to you yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200" id="sitesTable">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer/Bank</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($sites as $site): ?>
                        <tr class="site-row hover:bg-gray-50 transition-colors" data-status="<?php echo $site['delegation_status']; ?>">
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
                                        <?php if (!empty($site['notes'])): ?>
                                            <div class="text-xs text-blue-600 mt-1 flex items-center">
                                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                                </svg>
                                                Special instructions
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($site['city'] . ', ' . $site['state']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($site['country']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($site['customer'] ?? 'N/A'); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($site['bank'] ?? 'N/A'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col space-y-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $site['delegation_status'] === 'active' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo ucfirst($site['delegation_status']); ?>
                                    </span>
                                    <div class="text-xs text-gray-500">
                                        Since: <?php echo date('M d, Y', strtotime($site['delegation_date'])); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="space-y-2">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500">Survey:</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo ($site['survey_status'] ?? false) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo ($site['survey_status'] ?? false) ? 'Done' : 'Pending'; ?>
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500">Install:</span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo ($site['installation_status'] ?? false) ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                            <?php echo ($site['installation_status'] ?? false) ? 'Done' : 'Pending'; ?>
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex items-center space-x-2">
                                    <button onclick="viewSiteDetails(<?php echo $site['site_id']; ?>)" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="View Details">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                    <?php if ($site['delegation_status'] === 'active'): ?>
                                        <?php if (!($site['survey_status'] ?? false)): ?>
                                            <button onclick="conductSurvey(<?php echo $site['id']; ?>)" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" title="Site Survey">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        <?php else: ?>
                                            <button onclick="generateMaterialRequest(<?php echo $site['id']; ?>)" class="inline-flex items-center px-3 py-1 border border-blue-300 text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="Generate Material Request">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zM8 6a2 2 0 114 0v1H8V6zM6 9a1 1 0 012 0v1a1 1 0 11-2 0V9zm8 0a1 1 0 012 0v1a1 1 0 11-2 0V9z" clip-rule="evenodd"></path>
                                                </svg>
                                            </button>
                                        <?php endif; ?>
                                        <button onclick="updateProgress(<?php echo $site['id']; ?>)" class="inline-flex items-center px-3 py-1 border border-transparent text-xs font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="Update Progress">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    <?php endif; ?>
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

<script>
// Tab filtering
function filterSites(status) {
    const rows = document.querySelectorAll('.site-row');
    const tabs = document.querySelectorAll('.tab-button');
    
    // Update tab styles
    tabs.forEach(tab => tab.classList.remove('active'));
    document.getElementById(`tab-${status}`).classList.add('active');
    
    // Filter rows
    rows.forEach(row => {
        if (status === 'all') {
            row.style.display = '';
        } else {
            const rowStatus = row.getAttribute('data-status');
            row.style.display = rowStatus === status ? '' : 'none';
        }
    });
}

// Navigation functions
function viewSiteDetails(siteId) {
    window.location.href = `../site-details.php?id=${siteId}`;
}

function updateProgress(delegationId) {
    window.location.href = `../update-progress.php?id=${delegationId}`;
}

function conductSurvey(delegationId) {
    window.location.href = `../site-survey.php?delegation_id=${delegationId}`;
}

function generateMaterialRequest(siteId) {
    window.location.href = `material-request.php?site_id=${siteId}`;
}

// Add CSS for tabs
const style = document.createElement('style');
style.textContent = `
    .tab-button {
        @apply py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors;
    }
    .tab-button.active {
        @apply border-blue-500 text-blue-600;
    }
`;
document.head.appendChild(style);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/vendor_layout.php';
?>