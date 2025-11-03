<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Installation.php';
require_once __DIR__ . '/../../models/Vendor.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$installationModel = new Installation();
$vendorModel = new Vendor();

// Get filter parameters
$statusFilter = $_GET['status'] ?? '';
$vendorFilter = $_GET['vendor'] ?? '';

// Get all installations with filters
$installations = $installationModel->getAllInstallations($statusFilter ?: null, $vendorFilter ?: null);
$activeVendors = $vendorModel->getActiveVendors();
$stats = $installationModel->getInstallationStats();

$title = 'Installation Management';
ob_start();
?>

<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900">Installation Management</h1>
            <p class="mt-2 text-lg text-gray-600">Manage and track all installation delegations and progress</p>
            <p class="text-sm text-gray-500 mt-1">Monitor installation status from delegation to completion</p>
        </div>
        <div class="mt-6 lg:mt-0 lg:ml-6">
            <div class="flex flex-wrap gap-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    <?php echo $stats['assigned'] ?? 0; ?> Assigned
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                    <?php echo $stats['in_progress'] ?? 0; ?> In Progress
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <?php echo $stats['completed'] ?? 0; ?> Completed
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                    <?php echo $stats['overdue'] ?? 0; ?> Overdue
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                    <?php echo $stats['total'] ?? 0; ?> Total
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Filters Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div class="flex-1 min-w-48">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" class="form-select w-full">
                <option value="">All Statuses</option>
                <option value="assigned" <?php echo $statusFilter === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                <option value="acknowledged" <?php echo $statusFilter === 'acknowledged' ? 'selected' : ''; ?>>Acknowledged</option>
                <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                <option value="on_hold" <?php echo $statusFilter === 'on_hold' ? 'selected' : ''; ?>>On Hold</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>
        <div class="flex-1 min-w-48">
            <label for="vendor" class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
            <select name="vendor" id="vendor" class="form-select w-full">
                <option value="">All Vendors</option>
                <?php foreach ($activeVendors as $vendor): ?>
                    <option value="<?php echo $vendor['id']; ?>" <?php echo $vendorFilter == $vendor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($vendor['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="btn btn-primary">Filter</button>
            <a href="?" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Installations Table -->
<div class="professional-table bg-white">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Installation Delegations</h3>
            <p class="text-sm text-gray-500 mt-1">All delegated installations with current status and progress</p>
        </div>
        <div class="flex gap-2">
            <button onclick="refreshInstallations()" class="btn btn-secondary btn-sm">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Refresh
            </button>
        </div>
    </div>
    <div class="p-6">
        <?php if (empty($installations)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No installations found</h3>
                <p class="mt-1 text-sm text-gray-500">No installation delegations match your current filters.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Delegated</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Expected Completion</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($installations as $installation): ?>
                        <tr class="hover:bg-gray-50 transition-colors" id="installation_row_<?php echo $installation['id']; ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo $installation['id']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <div class="font-medium"><?php echo htmlspecialchars($installation['site_code'] ?? 'N/A'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($installation['location'] ?? '', 0, 30)); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <div class="font-medium"><?php echo htmlspecialchars($installation['vendor_name'] ?? 'Unknown'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($installation['vendor_phone'] ?? ''); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                switch($installation['status']) {
                                    case 'assigned':
                                        $statusClass = 'bg-blue-100 text-blue-800';
                                        $statusText = 'Assigned';
                                        break;
                                    case 'acknowledged':
                                        $statusClass = 'bg-indigo-100 text-indigo-800';
                                        $statusText = 'Acknowledged';
                                        break;
                                    case 'in_progress':
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        $statusText = 'In Progress';
                                        break;
                                    case 'on_hold':
                                        $statusClass = 'bg-orange-100 text-orange-800';
                                        $statusText = 'On Hold';
                                        break;
                                    case 'completed':
                                        $statusClass = 'bg-green-100 text-green-800';
                                        $statusText = 'Completed';
                                        break;
                                    case 'cancelled':
                                        $statusClass = 'bg-red-100 text-red-800';
                                        $statusText = 'Cancelled';
                                        break;
                                    default:
                                        $statusClass = 'bg-gray-100 text-gray-800';
                                        $statusText = ucfirst($installation['status']);
                                }
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>" id="installation_status_<?php echo $installation['id']; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php
                                $priorityClass = '';
                                switch($installation['priority']) {
                                    case 'urgent':
                                        $priorityClass = 'bg-red-100 text-red-800';
                                        break;
                                    case 'high':
                                        $priorityClass = 'bg-orange-100 text-orange-800';
                                        break;
                                    case 'medium':
                                        $priorityClass = 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'low':
                                        $priorityClass = 'bg-green-100 text-green-800';
                                        break;
                                    default:
                                        $priorityClass = 'bg-gray-100 text-gray-800';
                                }
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $priorityClass; ?>">
                                    <?php echo ucfirst($installation['priority']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <div><?php echo date('M j, Y', strtotime($installation['delegation_date'])); ?></div>
                                <div class="text-xs text-gray-500">by <?php echo htmlspecialchars($installation['delegated_by_name'] ?? 'Unknown'); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php if ($installation['expected_completion_date']): ?>
                                    <div><?php echo date('M j, Y', strtotime($installation['expected_completion_date'])); ?></div>
                                    <?php 
                                    $isOverdue = strtotime($installation['expected_completion_date']) < time() && 
                                                !in_array($installation['status'], ['completed', 'cancelled']);
                                    if ($isOverdue): ?>
                                        <div class="text-xs text-red-600 font-medium">Overdue</div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-gray-400">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="view.php?id=<?php echo $installation['id']; ?>" class="text-blue-600 hover:text-blue-900 text-xs">View</a>
                                    
                                    <?php if ($installation['status'] !== 'completed' && $installation['status'] !== 'cancelled'): ?>
                                        <select class="form-control form-control-sm status-dropdown" data-id="<?php echo $installation['id']; ?>" style="width:auto; display:inline-block; font-size: 11px;">
                                            <option value="">Update Status</option>
                                            <?php if ($installation['status'] === 'assigned'): ?>
                                                <option value="acknowledged">Mark Acknowledged</option>
                                                <option value="in_progress">Start Progress</option>
                                            <?php endif; ?>
                                            <?php if ($installation['status'] === 'acknowledged' || $installation['status'] === 'in_progress'): ?>
                                                <option value="in_progress">In Progress</option>
                                                <option value="on_hold">Put On Hold</option>
                                                <option value="completed">Mark Completed</option>
                                            <?php endif; ?>
                                            <?php if ($installation['status'] === 'on_hold'): ?>
                                                <option value="in_progress">Resume Progress</option>
                                                <option value="completed">Mark Completed</option>
                                            <?php endif; ?>
                                            <option value="cancelled">Cancel</option>
                                        </select>
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
// Auto-refresh every 30 seconds
setInterval(refreshInstallations, 30000);

function refreshInstallations() {
    // Simple page refresh for now - can be enhanced with AJAX later
    const url = new URL(window.location);
    const params = new URLSearchParams(url.search);
    
    // Add a timestamp to prevent caching
    params.set('_t', Date.now());
    
    // Reload with current filters
    window.location.search = params.toString();
}

// Handle status updates
document.addEventListener('DOMContentLoaded', function() {
    const statusDropdowns = document.querySelectorAll('.status-dropdown');
    
    statusDropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            const installationId = this.dataset.id;
            const newStatus = this.value;
            
            if (newStatus && installationId) {
                const notes = prompt(`Please enter notes for changing status to ${newStatus}:`);
                if (notes !== null) {
                    updateInstallationStatus(installationId, newStatus, notes);
                }
                this.value = ''; // Reset dropdown
            }
        });
    });
});

function updateInstallationStatus(installationId, status, notes) {
    const formData = new FormData();
    formData.append('installation_id', installationId);
    formData.append('status', status);
    formData.append('notes', notes);
    
    fetch('update-status.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update status badge
            const statusBadge = document.getElementById(`installation_status_${installationId}`);
            if (statusBadge) {
                // Update badge class and text based on new status
                const statusClasses = {
                    'assigned': 'bg-blue-100 text-blue-800',
                    'acknowledged': 'bg-indigo-100 text-indigo-800',
                    'in_progress': 'bg-yellow-100 text-yellow-800',
                    'on_hold': 'bg-orange-100 text-orange-800',
                    'completed': 'bg-green-100 text-green-800',
                    'cancelled': 'bg-red-100 text-red-800'
                };
                
                statusBadge.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClasses[status] || 'bg-gray-100 text-gray-800'}`;
                statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' ');
            }
            
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while updating status.', 'error');
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/admin_layout.php';
?>