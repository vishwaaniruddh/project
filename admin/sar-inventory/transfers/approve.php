<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvTransferService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Approve Transfer';
$currentUser = Auth::getCurrentUser();

$transferService = new SarInvTransferService();

// Handle POST request for quick actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'approved';
    
    if (!$id) {
        $_SESSION['error'] = 'Invalid transfer ID';
        header('Location: ' . url('/admin/sar-inventory/transfers/'));
        exit;
    }
    
    switch ($action) {
        case 'approved':
            $result = $transferService->approveTransfer($id, $currentUser['id']);
            break;
        case 'in_transit':
            $result = $transferService->shipTransfer($id);
            break;
        case 'cancelled':
            $reason = trim($_POST['reason'] ?? '');
            $result = $transferService->cancelTransfer($id, $reason);
            break;
        default:
            $result = ['success' => false, 'errors' => ['Invalid action']];
    }
    
    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = implode(', ', $result['errors']);
    }
    
    header('Location: ' . url('/admin/sar-inventory/transfers/view.php?id=' . $id));
    exit;
}

// Handle GET request - show approval page
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

if ($transfer['status'] !== 'pending') {
    $_SESSION['error'] = 'Only pending transfers can be approved';
    header('Location: ' . url('/admin/sar-inventory/transfers/view.php?id=' . $id));
    exit;
}

$errors = [];

// Get success/error messages from session
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/transfers/view.php?id=' . $id); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Approve Transfer</h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($transfer['transfer_number']); ?></p>
        </div>
    </div>
</div>

<?php if ($error): ?>
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <?php echo htmlspecialchars($error); ?>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Transfer Summary -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Transfer Summary</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
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
                
                <h4 class="text-sm font-medium text-gray-500 mb-3">Items to Transfer</h4>
                <div class="overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="text-right">Quantity</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $totalQty = 0;
                            foreach ($transfer['items'] as $item): 
                                $totalQty += $item['quantity'];
                            ?>
                            <tr>
                                <td class="font-medium"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                <td class="text-gray-500"><?php echo htmlspecialchars($item['sku']); ?></td>
                                <td class="text-right"><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure'] ?? ''); ?></td>
                                <td class="text-gray-500 text-sm"><?php echo htmlspecialchars($item['notes'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="2" class="font-medium">Total</td>
                                <td class="text-right font-medium"><?php echo number_format($totalQty, 2); ?></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <?php if (!empty($transfer['notes'])): ?>
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-500 mb-2">Notes</h4>
                    <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($transfer['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Approval Actions -->
    <div class="lg:col-span-1">
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Approval Actions</h3>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Important</h4>
                            <p class="text-sm text-yellow-700 mt-1">Approving this transfer will reserve stock at the source warehouse.</p>
                        </div>
                    </div>
                </div>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="action" value="approved">
                    
                    <button type="submit" class="btn btn-success w-full justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Approve Transfer
                    </button>
                </form>
                
                <div class="border-t my-4"></div>
                
                <form method="POST" class="space-y-4" id="cancelForm">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="action" value="cancelled">
                    
                    <div class="form-group">
                        <label class="form-label text-sm">Cancellation Reason</label>
                        <textarea name="reason" class="form-textarea" rows="2" placeholder="Enter reason for cancellation..."></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger w-full justify-center"
                            onclick="return confirm('Are you sure you want to cancel this transfer?');">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel Transfer
                    </button>
                </form>
                
                <div class="mt-4">
                    <a href="<?php echo url('/admin/sar-inventory/transfers/view.php?id=' . $id); ?>" class="btn btn-secondary w-full justify-center">
                        Back to Transfer
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
