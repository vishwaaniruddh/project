<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvTransferService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'View Transfer';
$currentUser = Auth::getCurrentUser();

$transferService = new SarInvTransferService();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'Invalid transfer ID';
    header('Location: ' . url('/admin/sar-inventory/transfers/'));
    exit;
}

$transfer = $transferService->getTransferWithDetails($id);
if (!$transfer) {
    $_SESSION['error'] = 'Transfer not found';
    header('Location: ' . url('/admin/sar-inventory/transfers/'));
    exit;
}

$validTransitions = $transferService->getValidStatusTransitions($id);

// Status badge colors
$statusColors = [
    'pending' => 'badge-secondary',
    'approved' => 'badge-info',
    'in_transit' => 'badge-primary',
    'received' => 'badge-success',
    'cancelled' => 'badge-danger'
];
$statusClass = $statusColors[$transfer['status']] ?? 'badge-secondary';

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

ob_start();
?>

<div class="mb-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="<?php echo url('/admin/sar-inventory/transfers/'); ?>" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($transfer['transfer_number']); ?></h1>
                <p class="text-gray-600">Transfer Details</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <?php if ($transfer['status'] === 'pending'): ?>
            <a href="<?php echo url('/admin/sar-inventory/transfers/approve.php?id=' . $id); ?>" class="btn btn-success">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                Approve
            </a>
            <?php endif; ?>
            <?php if (in_array($transfer['status'], ['approved', 'in_transit'])): ?>
            <a href="<?php echo url('/admin/sar-inventory/transfers/receive.php?id=' . $id); ?>" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                </svg>
                Receive
            </a>
            <?php endif; ?>
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
        <!-- Transfer Info -->
        <div class="card">
            <div class="card-body">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Transfer Information</h3>
                    <span class="badge <?php echo $statusClass; ?> text-sm"><?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?></span>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Source Warehouse</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($transfer['source_warehouse_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($transfer['source_warehouse_code']); ?></div>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-sm font-medium text-gray-500 mb-2">Destination Warehouse</h4>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($transfer['destination_warehouse_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($transfer['destination_warehouse_code']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Transfer Flow Visualization -->
                <div class="mt-6 flex items-center justify-center">
                    <div class="flex items-center space-x-4">
                        <div class="text-center">
                            <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div class="text-xs text-gray-500">Source</div>
                        </div>
                        <div class="flex-1 flex items-center">
                            <div class="flex-1 h-0.5 bg-gray-300"></div>
                            <svg class="w-6 h-6 text-gray-400 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                            <div class="flex-1 h-0.5 bg-gray-300"></div>
                        </div>
                        <div class="text-center">
                            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div class="text-xs text-gray-500">Destination</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Items -->
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Transfer Items</h3>
                
                <?php if (empty($transfer['items'])): ?>
                <div class="text-center py-8 text-gray-500">
                    No items in this transfer
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
                            foreach ($transfer['items'] as $item): 
                                $totalQty += $item['quantity'];
                                $totalReceived += $item['received_quantity'] ?? 0;
                                $itemStatusColors = [
                                    'pending' => 'badge-secondary',
                                    'in_transit' => 'badge-info',
                                    'received' => 'badge-success',
                                    'partial' => 'badge-warning',
                                    'cancelled' => 'badge-danger'
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
        <?php if (!empty($transfer['notes'])): ?>
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Notes</h3>
                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($transfer['notes'])); ?></p>
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
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($transfer['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($transfer['approved_by']): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Approved</p>
                            <p class="text-xs text-gray-500"><?php echo $transfer['transfer_date'] ? date('M j, Y', strtotime($transfer['transfer_date'])) : '-'; ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($transfer['status'] === 'in_transit'): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">In Transit</p>
                            <p class="text-xs text-gray-500">Items shipped</p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($transfer['received_date']): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Received</p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($transfer['received_date'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($transfer['status'] === 'cancelled'): ?>
                    <div class="flex items-start">
                        <div class="flex-shrink-0 w-8 h-8 bg-red-100 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">Cancelled</p>
                            <p class="text-xs text-gray-500"><?php echo date('M j, Y g:i A', strtotime($transfer['updated_at'])); ?></p>
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
                            'approved' => ['Approve Transfer', 'btn-success', 'M5 13l4 4L19 7', '/admin/sar-inventory/transfers/approve.php?id=' . $id],
                            'in_transit' => ['Mark In Transit', 'btn-primary', 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0', null],
                            'received' => ['Receive Transfer', 'btn-success', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', '/admin/sar-inventory/transfers/receive.php?id=' . $id],
                            'cancelled' => ['Cancel Transfer', 'btn-danger', 'M6 18L18 6M6 6l12 12', null]
                        ];
                        $label = $actionLabels[$nextStatus] ?? [ucfirst($nextStatus), 'btn-secondary', 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', null];
                        
                        if ($label[3]): // Has a dedicated page
                    ?>
                    <a href="<?php echo url($label[3]); ?>" class="btn <?php echo $label[1]; ?> w-full justify-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $label[2]; ?>"></path>
                        </svg>
                        <?php echo $label[0]; ?>
                    </a>
                    <?php else: // Quick action form ?>
                    <form method="POST" action="<?php echo url('/admin/sar-inventory/transfers/approve.php'); ?>" class="inline w-full">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="action" value="<?php echo $nextStatus; ?>">
                        <button type="submit" class="btn <?php echo $label[1]; ?> w-full justify-center"
                                onclick="return confirm('Are you sure you want to <?php echo strtolower($label[0]); ?>?');">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $label[2]; ?>"></path>
                            </svg>
                            <?php echo $label[0]; ?>
                        </button>
                    </form>
                    <?php endif; ?>
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
                        <dt class="text-gray-500">Transfer Number</dt>
                        <dd class="font-medium"><?php echo htmlspecialchars($transfer['transfer_number']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Total Items</dt>
                        <dd class="font-medium"><?php echo count($transfer['items']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Created By</dt>
                        <dd class="font-medium"><?php echo htmlspecialchars($transfer['created_by'] ?? '-'); ?></dd>
                    </div>
                    <?php if ($transfer['approved_by']): ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Approved By</dt>
                        <dd class="font-medium"><?php echo htmlspecialchars($transfer['approved_by']); ?></dd>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Last Updated</dt>
                        <dd class="font-medium"><?php echo date('M j, Y g:i A', strtotime($transfer['updated_at'])); ?></dd>
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
