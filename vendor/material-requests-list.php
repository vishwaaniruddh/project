<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/MaterialRequest.php';
require_once __DIR__ . '/../models/Site.php';

// Require vendor authentication
Auth::requireVendor();

$vendorId = Auth::getVendorId();

// Get all material requests for this vendor
$materialRequestModel = new MaterialRequest();
$materialRequests = $materialRequestModel->getVendorRequests($vendorId);

$title = 'My Material Requests';
ob_start();
?>

<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900">My Material Requests</h1>
            <p class="mt-2 text-lg text-gray-600">View and track your submitted material requests</p>
        </div>
        <div class="mt-6 lg:mt-0 lg:ml-6">
            <a href="material-request.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                </svg>
                New Request
            </a>
        </div>
    </div>
</div>

<!-- Material Requests Table -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <div class="p-6">
        <?php if (empty($materialRequests)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Material Requests</h3>
                <p class="text-gray-500 mb-4">You haven't submitted any material requests yet.</p>
                <a href="material-request.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    Create Your First Request
                </a>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dates</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($materialRequests as $request): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            Request #<?php echo htmlspecialchars($request['id']); ?>
                                        </div>
                                        <?php if ($request['request_notes']): ?>
                                            <div class="text-sm text-gray-500 max-w-xs truncate" title="<?php echo htmlspecialchars($request['request_notes']); ?>">
                                                <?php echo htmlspecialchars($request['request_notes']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($request['site_id'] ?? 'N/A'); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($request['location'] ?? 'Location not specified'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo $request['total_items'] ?? 0; ?> items
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Total Qty: <?php echo $request['total_quantity'] ?? 0; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-800',
                                        'submitted' => 'bg-blue-100 text-blue-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        'partially_fulfilled' => 'bg-yellow-100 text-yellow-800',
                                        'fulfilled' => 'bg-green-100 text-green-800'
                                    ];
                                    $statusColor = $statusColors[$request['status']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>Requested: <?php echo date('M d, Y', strtotime($request['request_date'])); ?></div>
                                    <div>Required: <?php echo date('M d, Y', strtotime($request['required_date'])); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewRequest(<?php echo $request['id']; ?>)" 
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                        View
                                    </button>
                                    <?php if ($request['status'] === 'draft'): ?>
                                        <a href="material-request.php?site_id=<?php echo $request['site_id']; ?>&edit=<?php echo $request['id']; ?>" 
                                           class="text-green-600 hover:text-green-900">
                                            Edit
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Request Modal -->
<div id="viewRequestModal" class="modal hidden">
    <div class="modal-content-large">
        <div class="modal-header-fixed">
            <h3 class="modal-title">Material Request Details</h3>
            <button type="button" class="modal-close" onclick="closeModal('viewRequestModal')">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <div class="modal-body-scrollable" id="requestDetailsContent">
            <!-- Content will be loaded here -->
        </div>
    </div>
</div>

<script>
function viewRequest(requestId) {
    // Show loading state
    document.getElementById('requestDetailsContent').innerHTML = `
        <div class="flex items-center justify-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-2 text-gray-600">Loading request details...</span>
        </div>
    `;
    
    // Open modal
    document.getElementById('viewRequestModal').classList.remove('hidden');
    document.getElementById('viewRequestModal').style.display = 'flex';
    
    // Fetch request details
    fetch(`get-request-details.php?id=${requestId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayRequestDetails(data.request);
            } else {
                document.getElementById('requestDetailsContent').innerHTML = `
                    <div class="text-center py-8">
                        <div class="text-red-600 mb-2">Error loading request details</div>
                        <div class="text-gray-500">${data.message || 'Unknown error occurred'}</div>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('requestDetailsContent').innerHTML = `
                <div class="text-center py-8">
                    <div class="text-red-600 mb-2">Error loading request details</div>
                    <div class="text-gray-500">Please try again later</div>
                </div>
            `;
        });
}

function displayRequestDetails(request) {
    const statusColors = {
        'draft': 'bg-gray-100 text-gray-800',
        'submitted': 'bg-blue-100 text-blue-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800',
        'partially_fulfilled': 'bg-yellow-100 text-yellow-800',
        'fulfilled': 'bg-green-100 text-green-800'
    };
    
    const statusColor = statusColors[request.status] || 'bg-gray-100 text-gray-800';
    
    let itemsHtml = '';
    if (request.items && request.items.length > 0) {
        itemsHtml = request.items.map(item => `
            <tr class="border-b border-gray-200">
                <td class="py-2 px-3 text-sm">${item.item_name || 'N/A'}</td>
                <td class="py-2 px-3 text-sm">${item.item_code || 'N/A'}</td>
                <td class="py-2 px-3 text-sm text-center">${item.quantity}</td>
                <td class="py-2 px-3 text-sm">${item.unit || 'N/A'}</td>
                <td class="py-2 px-3 text-sm">${item.notes || '-'}</td>
            </tr>
        `).join('');
    } else {
        itemsHtml = '<tr><td colspan="5" class="py-4 text-center text-gray-500">No items found</td></tr>';
    }
    
    document.getElementById('requestDetailsContent').innerHTML = `
        <!-- Request Information -->
        <div class="form-section">
            <h4 class="form-section-title">Request Information</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Request ID</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">#${request.id}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusColor}">
                        ${request.status.charAt(0).toUpperCase() + request.status.slice(1).replace('_', ' ')}
                    </span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${request.site_id || 'N/A'}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${request.location || 'N/A'}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Request Date</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${new Date(request.request_date).toLocaleDateString()}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Required Date</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${new Date(request.required_date).toLocaleDateString()}</p>
                </div>
                ${request.request_notes ? `
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded">${request.request_notes}</p>
                </div>
                ` : ''}
            </div>
        </div>
        
        <!-- Material Items -->
        <div class="form-section">
            <h4 class="form-section-title">Material Items</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Item Name</th>
                            <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                            <th class="py-2 px-3 text-center text-xs font-medium text-gray-500 uppercase">Quantity</th>
                            <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                            <th class="py-2 px-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                </table>
            </div>
        </div>
    `;
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    modal.classList.add('hidden');
    modal.style.display = 'none';
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/vendor_layout.php';
?>