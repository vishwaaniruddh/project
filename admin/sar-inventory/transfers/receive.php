<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvTransferService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Receive Transfer';
$currentUser = Auth::getCurrentUser();

$transferService = new SarInvTransferService();

$id = intval($_GET['id'] ?? $_POST['id'] ?? 0);
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

if (!in_array($transfer['status'], ['approved', 'in_transit'])) {
    $_SESSION['error'] = 'Only approved or in-transit transfers can be received';
    header('Location: ' . url('/admin/sar-inventory/transfers/view.php?id=' . $id));
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'receive';
    
    if ($action === 'ship') {
        // Ship the transfer first
        $result = $transferService->shipTransfer($id);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header('Location: ' . url('/admin/sar-inventory/transfers/receive.php?id=' . $id));
            exit;
        } else {
            $errors = $result['errors'];
        }
    } else {
        // Receive the transfer
        $receivedItems = [];
        if (!empty($_POST['received_qty'])) {
            foreach ($_POST['received_qty'] as $itemId => $qty) {
                $receivedItems[intval($itemId)] = floatval($qty);
            }
        }
        
        $result = $transferService->receiveTransfer($id, $receivedItems);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header('Location: ' . url('/admin/sar-inventory/transfers/view.php?id=' . $id));
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
    
    // Refresh transfer data
    $transfer = $transferService->getTransferWithDetails($id);
}

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
            <h1 class="text-2xl font-bold text-gray-900">Receive Transfer</h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($transfer['transfer_number']); ?></p>
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

<?php if (!empty($errors)): ?>
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <ul class="list-disc list-inside">
        <?php foreach ($errors as $err): ?>
        <li><?php echo htmlspecialchars($err); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Transfer Details -->
    <div class="lg:col-span-2">
        <div class="card">
            <div class="card-body">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Transfer Details</h3>
                    <span class="badge <?php echo $transfer['status'] === 'in_transit' ? 'badge-primary' : 'badge-info'; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?>
                    </span>
                </div>
                
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
                        <div class="bg-green-50 rounded-lg p-4 border-2 border-green-200">
                            <div class="font-medium text-gray-900"><?php echo htmlspecialchars($transfer['destination_warehouse_name']); ?></div>
                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($transfer['destination_warehouse_code']); ?></div>
                            <div class="text-xs text-green-600 mt-1">← Receiving here</div>
                        </div>
                    </div>
                </div>
                
                <?php if ($transfer['status'] === 'approved'): ?>
                <!-- Ship First Option -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <h4 class="text-sm font-medium text-blue-800">Transfer Not Yet Shipped</h4>
                            <p class="text-sm text-blue-700 mt-1">This transfer has been approved but not shipped. You can either ship it first or receive it directly.</p>
                            <form method="POST" class="mt-3">
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                                <input type="hidden" name="action" value="ship">
                                <button type="submit" class="btn btn-sm btn-info">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"></path>
                                    </svg>
                                    Mark as Shipped First
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" id="receiveForm">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <input type="hidden" name="action" value="receive">
                    
                    <h4 class="text-sm font-medium text-gray-500 mb-3">Items to Receive</h4>
                    <div class="overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th class="text-right">Sent Qty</th>
                                    <th class="text-right">Received Qty</th>
                                    <th>Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalSent = 0;
                                foreach ($transfer['items'] as $item): 
                                    $totalSent += $item['quantity'];
                                ?>
                                <tr>
                                    <td class="font-medium"><?php echo htmlspecialchars($item['product_name']); ?></td>
                                    <td class="text-gray-500"><?php echo htmlspecialchars($item['sku']); ?></td>
                                    <td class="text-right"><?php echo number_format($item['quantity'], 2); ?> <?php echo htmlspecialchars($item['unit_of_measure'] ?? ''); ?></td>
                                    <td class="text-right">
                                        <input type="number" 
                                               name="received_qty[<?php echo $item['id']; ?>]" 
                                               value="<?php echo $item['quantity']; ?>"
                                               min="0" 
                                               max="<?php echo $item['quantity']; ?>"
                                               step="0.01"
                                               class="form-input w-24 text-right">
                                    </td>
                                    <td class="text-gray-500 text-sm"><?php echo htmlspecialchars($item['notes'] ?? '-'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="2" class="font-medium">Total Sent</td>
                                    <td class="text-right font-medium"><?php echo number_format($totalSent, 2); ?></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="mt-6 flex justify-end gap-3">
                        <a href="<?php echo url('/admin/sar-inventory/transfers/view.php?id=' . $id); ?>" class="btn btn-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Confirm Receipt
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="lg:col-span-1">
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Receipt Information</h3>
                
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-green-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-green-800">What happens next?</h4>
                            <ul class="text-sm text-green-700 mt-1 list-disc list-inside">
                                <li>Stock will be added to destination warehouse</li>
                                <li>Transfer will be marked as received</li>
                                <li>Item history will be updated</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="w-5 h-5 text-yellow-600 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div>
                            <h4 class="text-sm font-medium text-yellow-800">Partial Receipt</h4>
                            <p class="text-sm text-yellow-700 mt-1">You can adjust received quantities if items are damaged or missing.</p>
                        </div>
                    </div>
                </div>
                
                <dl class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Transfer Number</dt>
                        <dd class="font-medium"><?php echo htmlspecialchars($transfer['transfer_number']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Total Items</dt>
                        <dd class="font-medium"><?php echo count($transfer['items']); ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-gray-500">Transfer Date</dt>
                        <dd class="font-medium"><?php echo $transfer['transfer_date'] ? date('M j, Y', strtotime($transfer['transfer_date'])) : '-'; ?></dd>
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
