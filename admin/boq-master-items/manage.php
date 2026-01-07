<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/BoqMaster.php';
require_once __DIR__ . '/../../models/BoqMasterItem.php';
require_once __DIR__ . '/../../models/BoqItem.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$boqMasterModel = new BoqMaster();
$boqMasterItemModel = new BoqMasterItem();
$boqItemModel = new BoqItem();

$boqId = (int)($_GET['boq_id'] ?? 0);

// Validate BOQ ID
if (!$boqId) {
    header('Location: ../boq-master/index.php?error=invalid_id');
    exit;
}

// Get BOQ master details
$boqMaster = $boqMasterModel->find($boqId);
if (!$boqMaster) {
    header('Location: ../boq-master/index.php?error=not_found');
    exit;
}

// Get current BOQ master items
$currentItems = $boqMasterItemModel->getByBoqMaster($boqId);
$currentItemIds = array_column($currentItems, 'boq_item_id');

// Get available BOQ items (not already in this BOQ master)
$availableItems = [];
$allBoqItems = $boqItemModel->getActive();
foreach ($allBoqItems as $item) {
    if (!in_array($item['id'], $currentItemIds)) {
        $availableItems[] = $item;
    }
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_items') {
        $selectedItems = $_POST['selected_items'] ?? [];
        $quantities = $_POST['quantities'] ?? [];
        $sortOrders = $_POST['sort_orders'] ?? [];
        
        if (!empty($selectedItems)) {
            $addedCount = 0;
            foreach ($selectedItems as $itemId) {
                $quantity = floatval($quantities[$itemId] ?? 1);
                $sortOrder = intval($sortOrders[$itemId] ?? 0);
                
                $data = [
                    'boq_master_id' => $boqId,
                    'boq_item_id' => $itemId,
                    'default_quantity' => $quantity,
                    'sort_order' => $sortOrder,
                    'status' => 'active'
                ];
                
                if ($boqMasterItemModel->create($data)) {
                    $addedCount++;
                }
            }
            
            if ($addedCount > 0) {
                $message = "Successfully added {$addedCount} item(s) to the BOQ master.";
                $messageType = 'success';
                
                // Refresh current items
                $currentItems = $boqMasterItemModel->getByBoqMaster($boqId);
                $currentItemIds = array_column($currentItems, 'boq_item_id');
                
                // Update available items
                $availableItems = [];
                foreach ($allBoqItems as $item) {
                    if (!in_array($item['id'], $currentItemIds)) {
                        $availableItems[] = $item;
                    }
                }
            } else {
                $message = "Failed to add items to the BOQ master.";
                $messageType = 'error';
            }
        } else {
            $message = "Please select at least one item to add.";
            $messageType = 'error';
        }
    }
    
    if ($action === 'update_item') {
        $itemId = intval($_POST['item_id'] ?? 0);
        $quantity = floatval($_POST['quantity'] ?? 1);
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        
        if ($itemId) {
            $data = [
                'default_quantity' => $quantity,
                'sort_order' => $sortOrder
            ];
            
            if ($boqMasterItemModel->updateByBoqMasterAndItem($boqId, $itemId, $data)) {
                $message = "Item updated successfully.";
                $messageType = 'success';
                
                // Refresh current items
                $currentItems = $boqMasterItemModel->getByBoqMaster($boqId);
            } else {
                $message = "Failed to update item.";
                $messageType = 'error';
            }
        }
    }
    
    if ($action === 'remove_item') {
        $itemId = intval($_POST['item_id'] ?? 0);
        
        if ($itemId && $boqMasterItemModel->removeFromBoqMaster($boqId, $itemId)) {
            $message = "Item removed successfully.";
            $messageType = 'success';
            
            // Refresh current items and available items
            $currentItems = $boqMasterItemModel->getByBoqMaster($boqId);
            $currentItemIds = array_column($currentItems, 'boq_item_id');
            
            $availableItems = [];
            foreach ($allBoqItems as $item) {
                if (!in_array($item['id'], $currentItemIds)) {
                    $availableItems[] = $item;
                }
            }
        } else {
            $message = "Failed to remove item.";
            $messageType = 'error';
        }
    }
}

$title = 'Manage BOQ Items - ' . $boqMaster['boq_name'];
ob_start();
?>

<div class="max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Manage BOQ Items</h1>
            <p class="mt-2 text-sm text-gray-700">Add and manage items for "<?php echo htmlspecialchars($boqMaster['boq_name']); ?>"</p>
        </div>
        <div class="flex space-x-2">
            <a href="../boq-master/view.php?id=<?php echo $boqId; ?>" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                </svg>
                View BOQ Master
            </a>
            <a href="../boq-master/index.php" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <!-- Messages -->
    <?php if ($message): ?>
    <div class="mb-6 p-4 rounded-md <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 <?php echo $messageType === 'success' ? 'text-green-400' : 'text-red-400'; ?>" fill="currentColor" viewBox="0 0 20 20">
                    <?php if ($messageType === 'success'): ?>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    <?php else: ?>
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    <?php endif; ?>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium <?php echo $messageType === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- BOQ Master Info -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-12 w-12">
                        <div class="h-12 w-12 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($boqMaster['boq_name']); ?></h3>
                        <p class="text-sm text-gray-500">BOQ ID: <?php echo $boqMaster['boq_id']; ?> • <?php echo count($currentItems); ?> items</p>
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $boqMaster['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($boqMaster['status']); ?>
                    </span>
                    <?php if ($boqMaster['is_serial_number_required']): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            Serial Required
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Current Items -->
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Current Items (<?php echo count($currentItems); ?>)</h3>
                
                <?php if (empty($currentItems)): ?>
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No items added</h3>
                    <p class="mt-1 text-sm text-gray-500">Start by adding items from the available items list.</p>
                </div>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($currentItems as $item): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center flex-1">
                                <?php if (!empty($item['icon_class'])): ?>
                                    <div class="flex-shrink-0 h-8 w-8">
                                        <div class="h-8 w-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                            <i class="<?php echo htmlspecialchars($item['icon_class']); ?> text-gray-600"></i>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="<?php echo !empty($item['icon_class']) ? 'ml-3' : ''; ?> flex-1">
                                    <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($item['item_code']); ?> • <?php echo htmlspecialchars($item['category']); ?></p>
                                    <?php if (!empty($item['item_description'])): ?>
                                        <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars(substr($item['item_description'], 0, 80)) . (strlen($item['item_description']) > 80 ? '...' : ''); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="ml-4 flex items-center space-x-2">
                                <button onclick="editItem(<?php echo $item['boq_item_id']; ?>, <?php echo $item['default_quantity']; ?>, <?php echo $item['sort_order']; ?>, '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>')" class="btn btn-sm btn-secondary" title="Edit">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                    </svg>
                                </button>
                                <button onclick="removeItem(<?php echo $item['boq_item_id']; ?>, '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>')" class="btn btn-sm btn-danger" title="Remove">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8 7a1 1 0 012 0v4a1 1 0 11-2 0V7zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V7a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-3 gap-4 text-xs text-gray-600">
                            <div>
                                <span class="font-medium">Quantity:</span> <?php echo number_format($item['default_quantity'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?>
                            </div>
                            <div>
                                <span class="font-medium">Sort Order:</span> <?php echo $item['sort_order']; ?>
                            </div>
                            <div>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo $item['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Available Items -->
        <div class="card">
            <div class="card-body">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Available Items (<?php echo count($availableItems); ?>)</h3>
                
                <?php if (empty($availableItems)): ?>
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">All items added</h3>
                    <p class="mt-1 text-sm text-gray-500">All available BOQ items have been added to this BOQ master.</p>
                </div>
                <?php else: ?>
                <form method="POST" id="addItemsForm">
                    <input type="hidden" name="action" value="add_items">
                    
                    <div class="mb-4">
                        <div class="flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-700">Select items to add:</label>
                            <div class="flex space-x-2">
                                <button type="button" onclick="selectAllItems()" class="text-xs text-blue-600 hover:text-blue-800">Select All</button>
                                <button type="button" onclick="clearAllItems()" class="text-xs text-gray-600 hover:text-gray-800">Clear All</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        <?php foreach ($availableItems as $item): ?>
                        <div class="border border-gray-200 rounded-lg p-3">
                            <div class="flex items-start">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" 
                                           name="selected_items[]" 
                                           value="<?php echo $item['id']; ?>" 
                                           class="item-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                </div>
                                <div class="ml-3 flex-1">
                                    <div class="flex items-center">
                                        <?php if (!empty($item['icon_class'])): ?>
                                            <div class="flex-shrink-0 h-6 w-6">
                                                <div class="h-6 w-6 rounded bg-gray-100 flex items-center justify-center">
                                                    <i class="<?php echo htmlspecialchars($item['icon_class']); ?> text-gray-600 text-xs"></i>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div class="<?php echo !empty($item['icon_class']) ? 'ml-2' : ''; ?>">
                                            <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($item['item_code']); ?> • <?php echo htmlspecialchars($item['category']); ?> • <?php echo htmlspecialchars($item['unit']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-2 grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Quantity</label>
                                            <input type="number" 
                                                   name="quantities[<?php echo $item['id']; ?>]" 
                                                   value="1" 
                                                   min="0.01" 
                                                   step="0.01" 
                                                   class="mt-1 block w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        </div>
                                        <div>
                                            <label class="block text-xs font-medium text-gray-700">Sort Order</label>
                                            <input type="number" 
                                                   name="sort_orders[<?php echo $item['id']; ?>]" 
                                                   value="0" 
                                                   min="0" 
                                                   class="mt-1 block w-full text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <button type="submit" class="btn btn-primary w-full">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            Add Selected Items
                        </button>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="modal">
    <div class="modal-content">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Edit Item</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="POST" id="editItemForm">
            <input type="hidden" name="action" value="update_item">
            <input type="hidden" name="item_id" id="editItemId">
            
            <div class="p-4 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
                    <p id="editItemName" class="text-sm text-gray-900 bg-gray-50 p-2 rounded"></p>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="editQuantity" class="block text-sm font-medium text-gray-700">Quantity</label>
                        <input type="number" 
                               id="editQuantity" 
                               name="quantity" 
                               min="0.01" 
                               step="0.01" 
                               required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </div>
                    <div>
                        <label for="editSortOrder" class="block text-sm font-medium text-gray-700">Sort Order</label>
                        <input type="number" 
                               id="editSortOrder" 
                               name="sort_order" 
                               min="0" 
                               required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-end space-x-3 p-4 border-t border-gray-200">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Item</button>
            </div>
        </form>
    </div>
</div>

<!-- Remove Item Modal -->
<div id="removeItemModal" class="modal">
    <div class="modal-content">
        <div class="flex items-center justify-between p-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Remove Item</h3>
            <button onclick="closeRemoveModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <form method="POST" id="removeItemForm">
            <input type="hidden" name="action" value="remove_item">
            <input type="hidden" name="item_id" id="removeItemId">
            
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-gray-900">Remove Item</h3>
                        <div class="mt-2 text-sm text-gray-500">
                            <p>Are you sure you want to remove "<span id="removeItemName"></span>" from this BOQ master? This action cannot be undone.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex items-center justify-end space-x-3 p-4 border-t border-gray-200">
                <button type="button" onclick="closeRemoveModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-danger">Remove Item</button>
            </div>
        </form>
    </div>
</div>

<script>
// Modal functions
function editItem(itemId, quantity, sortOrder, itemName) {
    document.getElementById('editItemId').value = itemId;
    document.getElementById('editItemName').textContent = itemName;
    document.getElementById('editQuantity').value = quantity;
    document.getElementById('editSortOrder').value = sortOrder;
    document.getElementById('editItemModal').classList.add('show');
}

function closeEditModal() {
    document.getElementById('editItemModal').classList.remove('show');
}

function removeItem(itemId, itemName) {
    document.getElementById('removeItemId').value = itemId;
    document.getElementById('removeItemName').textContent = itemName;
    document.getElementById('removeItemModal').classList.add('show');
}

function closeRemoveModal() {
    document.getElementById('removeItemModal').classList.remove('show');
}

// Item selection functions
function selectAllItems() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
}

function clearAllItems() {
    const checkboxes = document.querySelectorAll('.item-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
}

// Form validation
document.getElementById('addItemsForm').addEventListener('submit', function(e) {
    const checkedBoxes = document.querySelectorAll('.item-checkbox:checked');
    if (checkedBoxes.length === 0) {
        e.preventDefault();
        alert('Please select at least one item to add.');
        return false;
    }
});

// Close modals when clicking outside
window.addEventListener('click', function(e) {
    const editModal = document.getElementById('editItemModal');
    const removeModal = document.getElementById('removeItemModal');
    
    if (e.target === editModal) {
        closeEditModal();
    }
    if (e.target === removeModal) {
        closeRemoveModal();
    }
});

// Auto-hide success messages
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.querySelector('.bg-green-50');
    if (successAlert) {
        setTimeout(() => {
            successAlert.style.transition = 'opacity 0.5s ease-out';
            successAlert.style.opacity = '0';
            setTimeout(() => {
                successAlert.remove();
            }, 500);
        }, 5000);
    }
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/admin_layout.php';
?>