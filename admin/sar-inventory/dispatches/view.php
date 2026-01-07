<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvDispatchService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'View Dispatch';
$currentUser = Auth::getCurrentUser();

$dispatchService = new SarInvDispatchService();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'Invalid dispatch ID';
    header('Location: ' . url('/admin/sar-inventory/dispatches/'));
    exit;
}

$dispatch = $dispatchService->getDispatchWithDetails($id);
if (!$dispatch) {
    $_SESSION['error'] = 'Dispatch not found';
    header('Location: ' . url('/admin/sar-inventory/dispatches/'));
    exit;
}

$validTransitions = $dispatchService->getValidStatusTransitions($id);

// Status badge colors
$statusColors = [
    'pending' => 'badge-secondary',
    'approved' => 'badge-info',
    'shipped' => 'badge-info',
    'in_transit' => 'badge-info',
    'delivered' => 'badge-success',
    'cancelled' => 'badge-danger'
];
$statusClass = $statusColors[$dispatch['status']] ?? 'badge-secondary';

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="<?php echo url('/admin/sar-inventory/dispatches/'); ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($dispatch['dispatch_number']); ?></h1>
                <p class="text-gray-600">Dispatch Details</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if (!empty($validTransitions)): ?>
            <a href="<?php echo url('/admin/sar-inventory/dispatches/update-status.php?id=' . $id); ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Update Status
            </a>
            <?php endif; ?>
            <a href="<?php echo url('/admin/sar-inventory/dispatches/shipping-label.php?id=' . $id); ?>" class="btn btn-secondary" target="_blank">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Label
            </a>
        </div>
    </div>
</div>

<?php if ($success): ?>
<div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg">
    <?php echo htmlspecialchars($success); ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Details -->
    <div class="lg:col-span-2 space-y-6">
        <!-- Dispatch Info -->
        <div class="card">
            <div class="card-body">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Dispatch Information</h3>
                    <span class="badge <?php echo $statusClass; ?> text-sm"><?php echo ucfirst(str_replace('_', ' ', $dispatch['status'])); ?></span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Source</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($dispatch['source_warehouse_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($dispatch['source_warehouse_code']); ?></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Destination</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="text-xs text-gray-400 uppercase mb-1"><?php echo htmlspecialchars($dispatch['destination_type']); ?></div>
                            <div class="text-gray-900"><?php echo nl2br(htmlspecialchars($dispatch['destination_address'] ?? '-')); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Items -->
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Dispatch Items</h3>
                
                <?php if (empty($dispatch['items'])): ?>
                <div class="text-center py-8 text-gray-500">
                    No items in this dispatch
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-right">Quantity</th>
                                <th class="text-right">Received</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalQty = 0;
                            $totalReceived = 0;
                            foreach ($dispatch['items'] as $item): 
                                $totalQty += $item['quantity'];
                                $totalReceived += $item['received_quantity'] ?? 0;
                                $itemStatusColors = [
                                    'pending' => 'badge-secondary',
                                    'shipped' => 'badge-info',
                                    'received' => 'badge-success',
                                    'partial' => 'badge-info'
                                ];
                                $itemStatusClass = $itemStatusColors[$item['status']] ?? 'badge-secondary';
                            ?>
                            <tr>
                                <td class="font-medium"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td class="text-gray-500"><?php echo htmlspecialchars($item['sku']); ?></td>
                                <td class="text-right"><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure'] ?? ''); ?></td>
                                <td class="text-right"><?php echo number_format($item['received_quantity'] ?? 0, 2); ?></td>
                                <td><span class="badge <?php echo $itemStatusClass; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                                <td class="text-gray-500 text-sm"><?php echo htmlspecialchars($item['notes'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="2" class="font-medium">Total</td>
                                <td class="text-right font-medium"><?php echo number_format($totalQty, 2); ?></td>
                                <td class="text-right font-medium"><?php echo number_format($totalReceived, 2); ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Notes -->
        <?php if (!empty($dispatch['notes'])): ?>
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($dispatch['notes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Sidebar -->
    <div class="lg:col-span-1 space-y-6">
        <!-- Timeline -->
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
                
                <div class="space-y-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Created</p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($dispatch['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($dispatch['dispatch_date']): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Dispatched</p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($dispatch['dispatch_date'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($dispatch['received_date']): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Delivered</p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($dispatch['received_date'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <?php if (!empty($validTransitions)): ?>
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                
                <div class="space-y-2">
                    <?php foreach ($validTransitions as $nextStatus): 
                        $actionLabels = [
                            'approved' => ['Approve', 'btn-success', 'M5 13l4 4L19 7'],
                            'shipped' => ['Mark as Shipped', 'btn-primary', 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4'],
                            'in_transit' => ['Mark In Transit', 'btn-info', 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0'],
                            'delivered' => ['Mark Delivered', 'btn-success', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
                            'cancelled' => ['Cancel Dispatch', 'btn-danger', 'M6 18L18 6M6 6l12 12']
                        ];
                        $label = $actionLabels[$nextStatus] ?? [ucfirst($nextStatus), 'btn-secondary', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15'];
                    ?>
                    <form method="POST" action="<?php echo url('/admin/sar-inventory/dispatches/update-status.php'); ?>" class="inline w-full">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="status" value="<?php echo $nextStatus; ?>">
                        <button type="submit" class="btn <?php echo $label[1]; ?> w-full justify-center"
                                onclick="return confirm('Are you sure you want to <?php echo strtolower($label[0]); ?>?');">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $label[2]; ?>"></path>
                            </svg>
                            <?php echo $label[0]; ?>
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Details -->
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Details</h3>
                
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Dispatch Number</dt>
                        <dd class="font-medium"><?php echo htmlspecialchars($dispatch['dispatch_number']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Total Items</dt>
                        <dd class="font-medium"><?php echo count($dispatch['items']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Created By</dt>
                        <dd class="font-medium"><?php echo htmlspecialchars($dispatch['created_by'] ?? '-'); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Last Updated</dt>
                        <dd class="font-medium"><?php echo date('M j, Y g:i A', strtotime($dispatch['updated_at'])); ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
