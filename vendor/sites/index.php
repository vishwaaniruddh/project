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
                                    <button onclick="viewSiteDetails(<?php echo $site['id']; ?>, '<?php echo htmlspecialchars($site['site_id']); ?>')" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" title="View Details">
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

<!-- Site Details Modal -->
<div id="siteDetailsModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <!-- Modal Header -->
            <div class="flex items-center justify-between pb-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900" id="modalSiteId">Site Details</h3>
                <button onclick="closeSiteModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <!-- Modal Content -->
            <div id="modalContent" class="mt-4">
                <div class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <span class="ml-2 text-gray-600">Loading site details...</span>
                </div>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex justify-end pt-4 border-t mt-6 space-x-2">
                <button onclick="closeSiteModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors">
                    Close
                </button>
                <button id="modalActionButton" onclick="" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors hidden">
                    Take Action
                </button>
            </div>
        </div>
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
const BASE_URL = '<?php echo BASE_URL; ?>';

function viewSiteDetails(id, siteId) {
    // Show modal and load site details
    const modal = document.getElementById('siteDetailsModal');
    const modalContent = document.getElementById('modalContent');
    const modalSiteId = document.getElementById('modalSiteId');
    
    // Show modal
    modal.classList.remove('hidden');
    modalSiteId.textContent = `Site Details - ${siteId}`;
    
    // Show loading state
    modalContent.innerHTML = `
        <div class="flex justify-center items-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading site details...</span>
        </div>
    `;
    
    // Fetch site details using the database ID
    fetch(`get-site-details.php?id=${encodeURIComponent(id)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    displaySiteDetails(data.site);
                } else {
                    modalContent.innerHTML = `
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-red-600">${data.message || 'Failed to load site details'}</p>
                        </div>
                    `;
                }
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Response text:', text);
                modalContent.innerHTML = `
                    <div class="text-center py-8">
                        <svg class="w-12 h-12 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="text-red-600">Invalid response format</p>
                        <p class="text-xs text-gray-500 mt-2">Check console for details</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            modalContent.innerHTML = `
                <div class="text-center py-8">
                    <svg class="w-12 h-12 mx-auto text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-red-600">Network error loading site details</p>
                </div>
            `;
        });
}

function displaySiteDetails(site) {
    const modalContent = document.getElementById('modalContent');
    const actionButton = document.getElementById('modalActionButton');
    
    modalContent.innerHTML = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Basic Information -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-900 border-b pb-2">Basic Information</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Site ID</label>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${site.site_id || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Store ID</label>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${site.store_id || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${site.location || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Branch</label>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${site.branch || 'N/A'}</p>
                    </div>
                </div>
            </div>
            
            <!-- Location Details -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-900 border-b pb-2">Location Details</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">City</label>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${site.city || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">State</label>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${site.state || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Country</label>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${site.country || 'N/A'}</p>
                    </div>
                </div>
            </div>
            
            <!-- Client Information -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-900 border-b pb-2">Client Information</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Customer</label>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${site.customer || 'N/A'}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Bank</label>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${site.bank || 'N/A'}</p>
                    </div>
                </div>
            </div>
            
            <!-- Progress Status -->
            <div class="space-y-4">
                <h4 class="font-semibold text-gray-900 border-b pb-2">Progress Status</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Survey Status</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${site.survey_status ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                            ${site.survey_status ? 'Completed' : 'Pending'}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Installation Status</label>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${site.installation_status ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}">
                            ${site.installation_status ? 'Completed' : 'Pending'}
                        </span>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Delegation Date</label>
                        <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${site.delegation_date ? new Date(site.delegation_date).toLocaleDateString() : 'N/A'}</p>
                    </div>
                </div>
            </div>
        </div>
        
        ${site.notes ? `
        <div class="mt-6 pt-4 border-t">
            <h4 class="font-semibold text-gray-900 mb-2">Special Instructions</h4>
            <div class="text-sm text-gray-900 bg-blue-50 p-3 rounded border-l-4 border-blue-400">
                ${site.notes}
            </div>
        </div>
        ` : ''}
        
        ${site.remarks ? `
        <div class="mt-4">
            <h4 class="font-semibold text-gray-900 mb-2">Remarks</h4>
            <div class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                ${site.remarks}
            </div>
        </div>
        ` : ''}
    `;
    
    // Show action button if needed
    if (site.delegation_status === 'active') {
        actionButton.classList.remove('hidden');
        if (!site.survey_status) {
            actionButton.textContent = 'Conduct Survey';
            actionButton.onclick = () => conductSurvey(site.delegation_id);
        } else {
            actionButton.textContent = 'Update Progress';
            actionButton.onclick = () => updateProgress(site.delegation_id);
        }
    } else {
        actionButton.classList.add('hidden');
    }
}

function closeSiteModal() {
    document.getElementById('siteDetailsModal').classList.add('hidden');
}

function updateProgress(delegationId) {
    window.location.href = `${BASE_URL}/vendor/update-progress.php?id=${delegationId}`;
}

function conductSurvey(delegationId) {
    window.location.href = `${BASE_URL}/vendor/site-survey.php?delegation_id=${delegationId}`;
}

function generateMaterialRequest(siteId) {
    window.location.href = `${BASE_URL}/vendor/material-request.php?site_id=${siteId}`;
}

// Add CSS for tabs and modal
const style = document.createElement('style');
style.textContent = `
    .tab-button {
        @apply py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700 hover:border-gray-300 transition-colors;
    }
    .tab-button.active {
        @apply border-blue-500 text-blue-600;
    }
    
    /* Modal animations */
    #siteDetailsModal {
        animation: fadeIn 0.3s ease-out;
    }
    
    #siteDetailsModal > div {
        animation: slideIn 0.3s ease-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    /* Badge styles */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    
    .badge-warning {
        background-color: #fef3c7;
        color: #92400e;
    }
    
    .badge-success {
        background-color: #d1fae5;
        color: #065f46;
    }
`;
document.head.appendChild(style);

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('siteDetailsModal');
    if (event.target === modal) {
        closeSiteModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modal = document.getElementById('siteDetailsModal');
        if (!modal.classList.contains('hidden')) {
            closeSiteModal();
        }
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/vendor_layout.php';
?>