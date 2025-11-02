<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/MaterialRequest.php';
require_once __DIR__ . '/../models/Inventory.php';
require_once __DIR__ . '/../models/BoqItem.php';

// Require vendor authentication
Auth::requireRole(VENDOR_ROLE);

$currentUser = Auth::getCurrentUser();
$vendorId = $currentUser['vendor_id'];

$materialRequestModel = new MaterialRequest();
$inventoryModel = new Inventory();
$boqModel = new BoqItem();

// Get received materials for this vendor (delivered dispatches)
$receivedMaterials = $inventoryModel->getReceivedMaterialsForVendor($vendorId);

$title = 'Material Received';
ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Material Received</h1>
        <p class="mt-2 text-sm text-gray-700">Track and manage received materials from admin</p>
    </div>
    <div class="flex items-center space-x-2">
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
            <?php echo count($receivedMaterials); ?> Materials
        </span>
    </div>
</div>

<!-- Received Status Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <?php
    $statusCounts = [
        'pending' => 0,
        'delivered' => 0,
        'confirmed' => 0,
        'total_items' => 0
    ];
    
    foreach ($receivedMaterials as $material) {
        $status = $material['dispatch_status'] ?? 'delivered';
        if ($status === 'dispatched') $statusCounts['pending']++;
        if ($status === 'delivered') $statusCounts['delivered']++;
        if ($status === 'confirmed') $statusCounts['confirmed']++;
        
        // Count items in stock
        $items = json_decode($material['item_confirmations'] ?? '[]', true);
        $statusCounts['total_items'] += count($items);
    }
    ?>
    
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Acceptance Pending</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $statusCounts['pending']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Delivered</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $statusCounts['delivered']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Confirmed</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $statusCounts['confirmed']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Confirmed</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $statusCounts['confirmed']; ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-body">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 2L3 7v11a1 1 0 001 1h12a1 1 0 001-1V7l-7-5zM8 15a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Items</p>
                    <p class="text-2xl font-semibold text-gray-900"><?php echo $statusCounts['total_items']; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Received Materials List -->
<div class="card">
    <div class="card-body">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Received Materials</h3>
        
        <?php if (empty($receivedMaterials)): ?>
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2M4 13h2m13-8l-4 4m0 0l-4-4m4 4V3"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No materials received</h3>
            <p class="mt-1 text-sm text-gray-500">No materials have been received from admin yet.</p>
        </div>
        <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($receivedMaterials as $material): ?>
            <div class="border border-gray-200 rounded-lg">
                <!-- Material Header -->
                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-lg bg-green-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <div class="text-lg font-medium text-gray-900">Request #<?php echo $material['material_request_id']; ?></div>
                                <div class="text-sm text-gray-500">Dispatch #<?php echo $material['dispatch_number']; ?></div>
                                <div class="text-sm text-gray-500">Received: <?php echo $material['delivery_date'] ? date('d M Y', strtotime($material['delivery_date'])) : 'N/A'; ?></div>
                            </div>
                        </div>
                        <div class="text-right">
                            <?php
                            $status = $material['dispatch_status'] ?? 'delivered';
                            $statusColors = [
                                'dispatched' => 'bg-yellow-100 text-yellow-800',
                                'delivered' => 'bg-green-100 text-green-800',
                                'confirmed' => 'bg-purple-100 text-purple-800'
                            ];
                            $statusLabels = [
                                'dispatched' => 'Acceptance Pending',
                                'delivered' => 'Delivered',
                                'confirmed' => 'Confirmed'
                            ];
                            $colorClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                            $statusLabel = $statusLabels[$status] ?? ucfirst($status);
                            ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $colorClass; ?>">
                                <?php echo $statusLabel; ?>
                            </span>
                            <div class="text-sm text-gray-500 mt-1">Site: <?php echo htmlspecialchars($material['site_code'] ?? 'N/A'); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Material Items -->
                <div class="px-4 py-3">
                    <?php if ($material['dispatch_status'] === 'dispatched'): ?>
                    <h4 class="text-md font-medium text-gray-900 mb-3">Request Summary</h4>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <p class="text-sm font-medium text-blue-800">
                                    Request #<?php echo $material['material_request_id']; ?> contains 
                                    <?php 
                                    $dispatchItems = $inventoryModel->getDispatchItems($material['id']);
                                    $totalItems = count($dispatchItems);
                                    $totalQuantity = array_sum(array_column($dispatchItems, 'quantity_dispatched'));
                                    echo $totalItems . ' different materials with total quantity of ' . number_format($totalQuantity) . ' units';
                                    ?>
                                </p>
                                <p class="text-xs text-blue-600 mt-1">Click "Accept Request" below to confirm receipt of all materials</p>
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="text-md font-medium text-gray-900 mb-3">Material Details</h4>
                    <?php else: ?>
                    <h4 class="text-md font-medium text-gray-900 mb-3">Received Items</h4>
                    <?php endif; ?>
                    
                    <?php
                    $itemConfirmations = json_decode($material['item_confirmations'] ?? '[]', true);
                    $showDispatchItems = ($material['dispatch_status'] === 'dispatched' && empty($itemConfirmations));
                    
                    if (!empty($itemConfirmations) || $showDispatchItems):
                        // For dispatched materials without confirmations, get dispatch items
                        if ($showDispatchItems) {
                            $dispatchItems = $inventoryModel->getDispatchItems($material['id']);
                        }
                    ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Material</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <?php echo $showDispatchItems ? 'Dispatched Qty' : 'Received Qty'; ?>
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <?php echo $showDispatchItems ? 'Status' : 'Condition'; ?>
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Individual Records</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php 
                                $itemsToShow = $showDispatchItems ? $dispatchItems : $itemConfirmations;
                                foreach ($itemsToShow as $item): 
                                    $boqItem = $boqModel->find($item['boq_item_id']); 
                                ?>
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <?php if ($boqItem): ?>
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8">
                                                    <div class="h-8 w-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                                        <i class="<?php echo $boqItem['icon_class'] ?: 'fas fa-cube'; ?> text-blue-600 text-xs"></i>
                                                    </div>
                                                </div>
                                                <div class="ml-3">
                                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($boqItem['item_name']); ?></div>
                                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($boqItem['item_code']); ?></div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-sm text-gray-500">Item not found</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php 
                                            $quantity = $showDispatchItems ? $item['quantity_dispatched'] : $item['received_quantity'];
                                            echo number_format($quantity); 
                                            ?>
                                        </div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($boqItem['unit'] ?? 'Units'); ?></div>
                                    </td>
                                    <td class="px-3 py-2 whitespace-nowrap">
                                        <?php if ($showDispatchItems): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Awaiting Acceptance
                                            </span>
                                        <?php else: ?>
                                            <?php
                                            $condition = $item['condition'] ?? 'good';
                                            $conditionColors = [
                                                'good' => 'bg-green-100 text-green-800',
                                                'damaged' => 'bg-red-100 text-red-800',
                                                'partial' => 'bg-yellow-100 text-yellow-800',
                                                'missing' => 'bg-gray-100 text-gray-800'
                                            ];
                                            $conditionClass = $conditionColors[$condition] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?php echo $conditionClass; ?>">
                                                <?php echo ucfirst($condition); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2">
                                        <?php
                                        // Check if there are individual records
                                        $individualRecords = null;
                                        if ($showDispatchItems) {
                                            $individualRecords = json_decode($item['serial_numbers'] ?? '[]', true);
                                        } else {
                                            $individualRecords = json_decode($material['individual_records'] ?? '[]', true);
                                        }
                                        
                                        if (!empty($individualRecords)):
                                        ?>
                                        <div class="text-xs space-y-1">
                                            <?php foreach ($individualRecords as $record): ?>
                                            <div class="bg-gray-50 px-2 py-1 rounded">
                                                <span class="font-medium">SN:</span> <?php echo htmlspecialchars($record['serial_number'] ?? 'N/A'); ?>
                                                <span class="ml-2 font-medium">Qty:</span> <?php echo $record['quantity'] ?? 1; ?>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php else: ?>
                                        <div class="text-sm text-gray-500">Cumulative</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="text-sm text-gray-900">
                                            <?php 
                                            $notes = $showDispatchItems ? ($item['remarks'] ?? 'No notes') : ($item['notes'] ?? 'No notes');
                                            echo htmlspecialchars($notes); 
                                            ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                    <div class="text-sm text-gray-500">No item details available</div>
                    <?php endif; ?>
                </div>
                
                <!-- Request-level Actions -->
                <?php if ($material['dispatch_status'] === 'dispatched'): ?>
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <strong>Request #<?php echo $material['material_request_id']; ?></strong> is ready for acceptance
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="acceptRequest(<?php echo $material['id']; ?>, <?php echo $material['material_request_id']; ?>)" 
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Accept Request
                            </button>
                            <button onclick="viewRequestDetails(<?php echo $material['material_request_id']; ?>)" 
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path>
                                </svg>
                                View Details
                            </button>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Delivery Information -->
                <?php if (!empty($material['delivery_notes']) || !empty($material['lr_copy_path'])): ?>
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Delivery Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php if (!empty($material['delivery_notes'])): ?>
                        <div>
                            <label class="text-xs font-medium text-gray-500">Delivery Notes</label>
                            <div class="text-sm text-gray-900"><?php echo nl2br(htmlspecialchars($material['delivery_notes'])); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($material['lr_copy_path'])): ?>
                        <div>
                            <label class="text-xs font-medium text-gray-500">LR Copy</label>
                            <div class="text-sm">
                                <a href="<?php echo BASE_URL . '/' . $material['lr_copy_path']; ?>" target="_blank" class="text-blue-600 hover:text-blue-800">
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                    View LR Copy
                                </a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function acceptRequest(dispatchId, requestId) {
    if (!confirm('Are you sure you want to accept Request #' + requestId + '? This will confirm receipt of all materials in this request.')) {
        return;
    }
    
    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Processing...';
    button.disabled = true;
    
    // Create form data
    const formData = new FormData();
    formData.append('dispatch_id', dispatchId);
    formData.append('request_id', requestId);
    formData.append('action', 'accept_request');
    
    fetch('process-request-acceptance.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Request #' + requestId + ' accepted successfully!', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showAlert('Error: ' + data.message, 'error');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while accepting the request.', 'error');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

function viewRequestDetails(requestId) {
    // Open request details in a new tab or modal
    window.open('material-requests-list.php?request_id=' + requestId, '_blank');
}

function showAlert(message, type) {
    // Create alert element
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${
        type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 
        'bg-red-100 text-red-800 border border-red-200'
    }`;
    alertDiv.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                ${type === 'success' ? 
                    '<path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>' :
                    '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>'
                }
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Remove alert after 5 seconds
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/vendor_layout.php';
?>