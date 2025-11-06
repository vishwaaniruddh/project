<?php
require_once __DIR__ . '/../../../config/auth.php';
require_once __DIR__ . '/../../../models/Inventory.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$dispatchId = $_GET['id'] ?? null;

if (!$dispatchId) {
    header('Location: index.php');
    exit;
}

try {
    $inventoryModel = new Inventory();
    $dispatch = $inventoryModel->getDispatchDetails($dispatchId);

    if (!$dispatch) {
        header('Location: index.php?error=dispatch_not_found');
        exit;
    }
} catch (Exception $e) {
    error_log("Error in view-dispatch.php: " . $e->getMessage());
    header('Location: index.php?error=database_error');
    exit;
}

$title = 'Dispatch Details - ' . $dispatch['dispatch_number'];
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Dispatch Details</h1>
            <p class="mt-2 text-sm text-gray-700">View complete dispatch information</p>
        </div>
        <div class="flex space-x-2">
            <button onclick="window.print()" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 4v3H4a2 2 0 00-2 2v3a2 2 0 002 2h1v2a2 2 0 002 2h6a2 2 0 002-2v-2h1a2 2 0 002-2V9a2 2 0 00-2-2h-1V4a2 2 0 00-2-2H7a2 2 0 00-2 2zm8 0H7v3h6V4zm0 8H7v4h6v-4z" clip-rule="evenodd"></path>
                </svg>
                Print
            </button>
            <button onclick="window.close()" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
                Close
            </button>
        </div>
    </div>

    <!-- Dispatch Information -->
    <div class="card mb-6">
        <div class="card-header">
            <h3 class="card-title">Dispatch Information</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dispatch Number</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($dispatch['dispatch_number']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <?php
                    $statusClasses = [
                        'prepared' => 'bg-blue-100 text-blue-800',
                        'dispatched' => 'bg-yellow-100 text-yellow-800',
                        'in_transit' => 'bg-purple-100 text-purple-800',
                        'delivered' => 'bg-green-100 text-green-800',
                        'returned' => 'bg-red-100 text-red-800'
                    ];
                    $statusClass = $statusClasses[$dispatch['dispatch_status']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $statusClass; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $dispatch['dispatch_status'])); ?>
                    </span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dispatch Date</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo date('d M Y', strtotime($dispatch['dispatch_date'])); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expected Delivery</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded">
                        <?php echo $dispatch['expected_delivery_date'] ? date('d M Y', strtotime($dispatch['expected_delivery_date'])) : 'Not specified'; ?>
                    </p>
                </div>
                <?php if ($dispatch['site_code']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($dispatch['site_code']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($dispatch['vendor_name']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($dispatch['vendor_name']); ?></p>
                </div>
                <?php endif; ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($dispatch['contact_person_name']); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Phone</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($dispatch['contact_person_phone'] ?: 'Not provided'); ?></p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Address</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($dispatch['delivery_address']); ?></p>
                </div>
                <?php if ($dispatch['courier_name']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Courier</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($dispatch['courier_name']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($dispatch['tracking_number']): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tracking Number</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($dispatch['tracking_number']); ?></p>
                </div>
                <?php endif; ?>
                <?php if ($dispatch['delivery_remarks']): ?>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Delivery Remarks</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-3 rounded"><?php echo htmlspecialchars($dispatch['delivery_remarks']); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dispatch Items -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Dispatch Items</h3>
        </div>
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item Details</th>
                            <th>Quantity</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                            <th>Batch/Serial</th>
                            <th>Condition</th>
                            <th>Warranty</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($dispatch['items'])): ?>
                            <?php 
                            $totalValue = 0;
                            foreach ($dispatch['items'] as $item): 
                                $totalValue += $item['total_cost'];
                            ?>
                            <tr>
                                <td>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['item_code']); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm text-gray-900"><?php echo number_format($item['quantity_dispatched'], 2); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['unit']); ?></div>
                                </td>
                                <td class="text-sm text-gray-900">₹<?php echo number_format($item['unit_cost'] ?? 0, 2); ?></td>
                                <td class="text-sm font-medium text-gray-900">₹<?php echo number_format($item['total_cost'] ?? 0, 2); ?></td>
                                <td class="text-sm text-gray-900"><?php echo htmlspecialchars($item['batch_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <?php echo ucfirst($item['item_condition'] ?? 'new'); ?>
                                    </span>
                                </td>
                                <td class="text-sm text-gray-900"><?php echo htmlspecialchars($item['warranty_period'] ?? 'N/A'); ?></td>
                                <td class="text-sm text-gray-900"><?php echo htmlspecialchars($item['remarks'] ?? '-'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-8 text-gray-500">No items found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <?php if (!empty($dispatch['items'])): ?>
                    <tfoot>
                        <tr class="bg-gray-50">
                            <td colspan="3" class="text-right font-medium text-gray-900">Total Value:</td>
                            <td class="font-bold text-gray-900">₹<?php echo number_format($totalValue, 2); ?></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .btn, .card-header {
        display: none !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
    
    body {
        background: white !important;
    }
}
</style>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../../includes/admin_layout.php';
?>