<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/MaterialRequest.php';
require_once __DIR__ . '/../../models/BoqItem.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$requestId = $_GET['id'] ?? null;

if (!$requestId) {
    header('Location: index.php');
    exit;
}

$materialRequestModel = new MaterialRequest();
$boqModel = new BoqItem();

// Get request details
$request = $materialRequestModel->findWithDetails($requestId);

if (!$request) {
    header('Location: index.php');
    exit;
}

// Parse items
$items = json_decode($request['items'], true) ?: [];

// Get BOQ item details for each item
$boqItems = [];
foreach ($items as $item) {
    if (!empty($item['boq_item_id'])) {
        $boqItem = $boqModel->find($item['boq_item_id']);
        if ($boqItem) {
            $boqItems[$item['boq_item_id']] = $boqItem;
        }
    }
}

$title = 'Material Request #' . $request['id'];
ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Material Request #<?php echo $request['id']; ?></h1>
        <p class="mt-2 text-sm text-gray-700">View material request details</p>
    </div>
    <div class="flex space-x-2">
        <a href="index.php" class="btn btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
            </svg>
            Back to Requests
        </a>
        <?php if ($request['status'] === 'pending'): ?>
            <button onclick="approveRequest(<?php echo $request['id']; ?>)" class="btn btn-success">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                Approve Request
            </button>
            <button onclick="rejectRequest(<?php echo $request['id']; ?>)" class="btn btn-danger">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
                Reject Request
            </button>
        <?php endif; ?>
        <?php if ($request['status'] === 'approved'): ?>
            <a href="dispatch-material.php?request_id=<?php echo $request['id']; ?>" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                Create Dispatch
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Request Details -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Main Details -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Information</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500">Site</label>
                        <div class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($request['site_code']); ?></div>
                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($request['location']); ?></div>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Vendor</label>
                        <div class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($request['vendor_name']); ?></div>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Request Date</label>
                        <div class="mt-1 text-sm text-gray-900"><?php echo date('d M Y', strtotime($request['request_date'])); ?></div>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Required Date</label>
                        <div class="mt-1 text-sm text-gray-900">
                            <?php echo $request['required_date'] ? date('d M Y', strtotime($request['required_date'])) : 'Not specified'; ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Status</label>
                        <div class="mt-1">
                            <?php
                            $statusClasses = [
                                'draft' => 'bg-gray-100 text-gray-800',
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-green-100 text-green-800',
                                'dispatched' => 'bg-blue-100 text-blue-800',
                                'completed' => 'bg-purple-100 text-purple-800',
                                'rejected' => 'bg-red-100 text-red-800'
                            ];
                            $statusClass = $statusClasses[$request['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                <?php echo ucfirst($request['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <label class="text-sm font-medium text-gray-500">Created</label>
                        <div class="mt-1 text-sm text-gray-900"><?php echo date('d M Y H:i', strtotime($request['created_date'])); ?></div>
                    </div>
                </div>
                
                <?php if ($request['request_notes']): ?>
                <div class="mt-4">
                    <label class="text-sm font-medium text-gray-500">Notes</label>
                    <div class="mt-1 text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($request['request_notes'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Status Timeline -->
    <div>
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Timeline</h3>
                
                <div class="flow-root">
                    <ul class="-mb-8">
                        <li>
                            <div class="relative pb-8">
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                                            </svg>
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5">
                                        <div>
                                            <p class="text-sm text-gray-500">Request created</p>
                                            <p class="text-sm text-gray-900"><?php echo date('d M Y H:i', strtotime($request['created_date'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        
                        <?php if ($request['processed_date']): ?>
                        <li>
                            <div class="relative pb-8">
                                <div class="relative flex space-x-3">
                                    <div>
                                        <span class="h-8 w-8 rounded-full <?php echo $request['status'] === 'approved' ? 'bg-green-500' : 'bg-red-500'; ?> flex items-center justify-center ring-8 ring-white">
                                            <?php if ($request['status'] === 'approved'): ?>
                                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            <?php else: ?>
                                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1 pt-1.5">
                                        <div>
                                            <p class="text-sm text-gray-500">Request <?php echo $request['status']; ?></p>
                                            <p class="text-sm text-gray-900"><?php echo date('d M Y H:i', strtotime($request['processed_date'])); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Requested Items -->
<div class="card">
    <div class="card-body">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Requested Items</h3>
        
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item Details</th>
                        <th>Item Code</th>
                        <th>Quantity</th>
                        <th>Unit</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-4 text-gray-500">No items found</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?php if (isset($boqItems[$item['boq_item_id']])): ?>
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-lg bg-blue-100 flex items-center justify-center">
                                                <i class="<?php echo $boqItems[$item['boq_item_id']]['icon_class'] ?: 'fas fa-cube'; ?> text-blue-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($boqItems[$item['boq_item_id']]['item_name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($boqItems[$item['boq_item_id']]['description'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm text-gray-500">Item not found (ID: <?php echo $item['boq_item_id']; ?>)</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($item['item_code']); ?></div>
                            </td>
                            <td>
                                <div class="text-sm font-medium text-gray-900"><?php echo number_format($item['quantity']); ?></div>
                            </td>
                            <td>
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($item['unit']); ?></div>
                            </td>
                            <td>
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($item['notes'] ?? ''); ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function approveRequest(requestId) {
    if (confirm('Are you sure you want to approve this material request?')) {
        updateRequestStatus(requestId, 'approved');
    }
}

function rejectRequest(requestId) {
    if (confirm('Are you sure you want to reject this material request?')) {
        updateRequestStatus(requestId, 'rejected');
    }
}

function updateRequestStatus(requestId, status) {
    fetch('update-request-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            request_id: requestId,
            status: status
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(`Request ${status} successfully!`, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while updating the request.', 'error');
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/admin_layout.php';
?>