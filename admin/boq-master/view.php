<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/BoqMaster.php';
require_once __DIR__ . '/../../models/BoqMasterItem.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$boqMasterModel = new BoqMaster();
$boqMasterItemModel = new BoqMasterItem();
$boqId = (int)($_GET['id'] ?? 0);

// Validate BOQ ID
if (!$boqId) {
    header('Location: index.php?error=invalid_id');
    exit;
}

// Get BOQ master with items
$boqMaster = $boqMasterModel->getWithItems($boqId);
if (!$boqMaster) {
    header('Location: index.php?error=not_found');
    exit;
}

$title = 'View BOQ Master - ' . $boqMaster['boq_name'];
ob_start();
?>

<div class="max-w-6xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">BOQ Master Details</h1>
            <p class="mt-2 text-sm text-gray-700">View details and manage items for "<?php echo htmlspecialchars($boqMaster['boq_name']); ?>"</p>
        </div>
        <div class="flex space-x-2">
            <a href="edit.php?id=<?php echo $boqId; ?>" class="btn btn-primary">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                </svg>
                Edit BOQ Master
            </a>
            <a href="index.php" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <!-- Success Messages -->
    <?php if (isset($_GET['created']) && $_GET['created'] == '1'): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4" id="success-alert">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">BOQ Master created successfully!</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4" id="success-alert">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">BOQ Master updated successfully!</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- BOQ Master Information Card -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-start justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-16 w-16">
                        <div class="h-16 w-16 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <svg class="w-10 h-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-6">
                        <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($boqMaster['boq_name']); ?></h2>
                        <p class="text-sm text-gray-500 mt-1">BOQ ID: <?php echo $boqMaster['boq_id']; ?></p>
                        <?php if (!empty($boqMaster['description'])): ?>
                            <p class="text-sm text-gray-700 mt-2"><?php echo htmlspecialchars($boqMaster['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="flex flex-col items-end space-y-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $boqMaster['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($boqMaster['status']); ?>
                    </span>
                    <?php if ($boqMaster['is_serial_number_required']): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            Serial Required
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="card">
            <div class="card-body">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Items</p>
                        <p class="text-2xl font-semibold text-gray-900" id="total-items-count"><?php echo $boqMaster['item_count']; ?></p>
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
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Active Items</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count(array_filter($boqMaster['items'], function($item) { return $item['status'] === 'active'; })); ?></p>
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
                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Categories</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count(array_unique(array_filter(array_column($boqMaster['items'], 'category')))); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BOQ Items Section -->
    <div class="card">
        <div class="card-body">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900">Associated Items</h3>
                <button onclick="openAddItemModal()" class="btn btn-primary btn-sm">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                    </svg>
                    Add Item
                </button>
            </div>

            <div id="items-container">
                <?php if (empty($boqMaster['items'])): ?>
                <div class="text-center py-12" id="empty-state">
                    <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No items found</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by adding items to this BOQ master.</p>
                    <div class="mt-6">
                        <button onclick="openAddItemModal()" class="btn btn-primary">
                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                            </svg>
                            Add First Item
                        </button>
                    </div>
                </div>
                <?php else: ?>
                <div class="overflow-x-auto" id="items-table-container">
                    <table class="data-table" id="items-table">
                        <thead>
                            <tr>
                                <th>Item Code</th>
                                <th>Item Name</th>
                                <th>Unit</th>
                                <th>Quantity</th>
                                <th>Remarks</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="items-tbody">
                            <?php foreach ($boqMaster['items'] as $item): ?>
                            <tr id="item-row-<?php echo $item['id']; ?>">
                                <td>
                                    <span class="text-sm font-mono text-gray-900"><?php echo htmlspecialchars($item['item_code']); ?></span>
                                </td>
                                <td>
                                    <div class="flex items-center">
                                        <?php if (!empty($item['icon_class'])): ?>
                                            <div class="flex-shrink-0 h-8 w-8 mr-3">
                                                <div class="h-8 w-8 rounded-lg bg-gray-100 flex items-center justify-center">
                                                    <i class="<?php echo htmlspecialchars($item['icon_class']); ?> text-gray-600"></i>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['item_name']); ?></div>
                                            <?php if (!empty($item['category'])): ?>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600"><?php echo htmlspecialchars($item['category']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($item['unit']); ?></span>
                                </td>
                                <td>
                                    <span class="text-sm font-medium text-gray-900"><?php echo number_format($item['default_quantity'], 2); ?></span>
                                </td>
                                <td>
                                    <span class="text-sm text-gray-500"><?php echo htmlspecialchars($item['remarks'] ?? '-'); ?></span>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-2">
                                        <button onclick="openEditItemModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['item_name'])); ?>', <?php echo $item['default_quantity']; ?>, '<?php echo htmlspecialchars(addslashes($item['remarks'] ?? '')); ?>')" class="btn btn-sm btn-secondary" title="Edit">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="confirmDeleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['item_name'])); ?>')" class="btn btn-sm btn-danger" title="Remove">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
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
    </div>

    <!-- Audit Information -->
    <div class="card mt-6">
        <div class="card-body">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Audit Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Creation Details</h4>
                    <div class="text-sm text-gray-600">
                        <p><span class="font-medium">Date:</span> <?php echo date('M d, Y \a\t H:i', strtotime($boqMaster['created_at'])); ?></p>
                        <?php if (!empty($boqMaster['created_by_name'])): ?>
                            <p><span class="font-medium">Created by:</span> <?php echo htmlspecialchars($boqMaster['created_by_name']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if (!empty($boqMaster['updated_at']) && $boqMaster['updated_at'] !== $boqMaster['created_at']): ?>
                <div>
                    <h4 class="text-sm font-medium text-gray-700 mb-2">Last Update</h4>
                    <div class="text-sm text-gray-600">
                        <p><span class="font-medium">Date:</span> <?php echo date('M d, Y \a\t H:i', strtotime($boqMaster['updated_at'])); ?></p>
                        <?php if (!empty($boqMaster['updated_by_name'])): ?>
                            <p><span class="font-medium">Updated by:</span> <?php echo htmlspecialchars($boqMaster['updated_by_name']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Item Modal -->
<div id="addItemModal" class="modal">
    <div class="modal-content" style="top: 5rem;">
        <div class="modal-header">
            <h3 class="modal-title">Add Item to BOQ</h3>
            <button onclick="closeAddItemModal()" class="modal-close">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="addItemForm">
                <input type="hidden" name="boq_master_id" value="<?php echo $boqId; ?>">
                
                <div class="form-group">
                    <label for="item_search" class="form-label">Select Item <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="text" id="item_search" class="form-input" placeholder="Search items by name or code..." autocomplete="off">
                        <input type="hidden" id="boq_item_id" name="boq_item_id" required>
                        <div id="item_search_results" class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto hidden"></div>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Start typing to search for items, or <a href="#" onclick="openCreateItemModal(); return false;" class="text-indigo-600 hover:text-indigo-800 font-medium">create a new item</a></p>
                </div>
                
                <div class="form-group">
                    <label for="default_quantity" class="form-label">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" id="default_quantity" name="default_quantity" class="form-input" value="1" min="0.01" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea id="remarks" name="remarks" class="form-textarea" rows="2" placeholder="Optional remarks..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeAddItemModal()" class="btn btn-secondary">Cancel</button>
            <button type="button" onclick="submitAddItem()" class="btn btn-primary" id="addItemBtn">Add Item</button>
        </div>
    </div>
</div>

<!-- Create New Item Modal -->
<div id="createItemModal" class="modal">
    <div class="modal-content" style="top: 3rem; max-width: 600px;">
        <div class="modal-header">
            <h3 class="modal-title">Create New Item</h3>
            <button onclick="closeCreateItemModal()" class="modal-close">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="createItemForm">
                <input type="hidden" name="boq_master_id" value="<?php echo $boqId; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="new_item_name" class="form-label">Item Name <span class="text-red-500">*</span></label>
                        <input type="text" id="new_item_name" name="item_name" class="form-input" placeholder="Enter item name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_item_code" class="form-label">Item Code <span class="text-red-500">*</span></label>
                        <input type="text" id="new_item_code" name="item_code" class="form-input" placeholder="e.g., ITM-001" required>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="new_item_unit" class="form-label">Unit <span class="text-red-500">*</span></label>
                        <select id="new_item_unit" name="unit" class="form-select" required>
                            <option value="">Select Unit</option>
                            <option value="Nos">Nos (Numbers)</option>
                            <option value="Mtr">Mtr (Meters)</option>
                            <option value="Kg">Kg (Kilograms)</option>
                            <option value="Ltr">Ltr (Liters)</option>
                            <option value="Set">Set</option>
                            <option value="Pair">Pair</option>
                            <option value="Box">Box</option>
                            <option value="Roll">Roll</option>
                            <option value="Pkt">Pkt (Packet)</option>
                            <option value="Sqm">Sqm (Square Meters)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_item_category" class="form-label">Category</label>
                        <input type="text" id="new_item_category" name="category" class="form-input" placeholder="e.g., Electrical, Network">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="new_item_description" class="form-label">Description</label>
                    <textarea id="new_item_description" name="description" class="form-textarea" rows="2" placeholder="Optional description..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="flex items-center">
                        <input type="checkbox" id="new_item_serial" name="need_serial_number" value="1" class="form-checkbox h-4 w-4 text-indigo-600">
                        <span class="ml-2 text-sm text-gray-700">Requires Serial Number</span>
                    </label>
                </div>
                
                <hr class="my-4 border-gray-200">
                
                <p class="text-sm font-medium text-gray-700 mb-3">Add to Current BOQ</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="new_item_quantity" class="form-label">Quantity <span class="text-red-500">*</span></label>
                        <input type="number" id="new_item_quantity" name="default_quantity" class="form-input" value="1" min="0.01" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_item_remarks" class="form-label">Remarks</label>
                        <input type="text" id="new_item_remarks" name="remarks" class="form-input" placeholder="Optional remarks">
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeCreateItemModal()" class="btn btn-secondary">Cancel</button>
            <button type="button" onclick="submitCreateItem()" class="btn btn-primary" id="createItemBtn">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                </svg>
                Create & Add to BOQ
            </button>
        </div>
    </div>
</div>

<!-- Edit Item Modal -->
<div id="editItemModal" class="modal">
    <div class="modal-content" style="top: 5rem;">
        <div class="modal-header">
            <h3 class="modal-title">Edit Item</h3>
            <button onclick="closeEditItemModal()" class="modal-close">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <form id="editItemForm">
                <input type="hidden" id="edit_item_id" name="id">
                
                <div class="form-group">
                    <label class="form-label">Item Name</label>
                    <p id="edit_item_name" class="text-sm text-gray-900 font-medium py-2"></p>
                </div>
                
                <div class="form-group">
                    <label for="edit_quantity" class="form-label">Quantity <span class="text-red-500">*</span></label>
                    <input type="number" id="edit_quantity" name="default_quantity" class="form-input" min="0.01" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_remarks" class="form-label">Remarks</label>
                    <textarea id="edit_remarks" name="remarks" class="form-textarea" rows="2" placeholder="Optional remarks..."></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeEditItemModal()" class="btn btn-secondary">Cancel</button>
            <button type="button" onclick="submitEditItem()" class="btn btn-primary" id="editItemBtn">Update Item</button>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteItemModal" class="modal">
    <div class="modal-content" style="top: 10rem;">
        <div class="modal-header">
            <h3 class="modal-title">Confirm Removal</h3>
            <button onclick="closeDeleteItemModal()" class="modal-close">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="delete_item_id">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-12 w-12 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-700">Are you sure you want to remove <span id="delete_item_name" class="font-semibold"></span> from this BOQ?</p>
                    <p class="text-sm text-gray-500 mt-1">This action cannot be undone.</p>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" onclick="closeDeleteItemModal()" class="btn btn-secondary">Cancel</button>
            <button type="button" onclick="submitDeleteItem()" class="btn btn-danger" id="deleteItemBtn">Remove Item</button>
        </div>
    </div>
</div>

<script>
const boqMasterId = <?php echo $boqId; ?>;
let searchTimeout = null;

// Add Item Modal Functions
function openAddItemModal() {
    document.getElementById('addItemModal').classList.add('show');
    document.getElementById('item_search').value = '';
    document.getElementById('boq_item_id').value = '';
    document.getElementById('default_quantity').value = '1';
    document.getElementById('remarks').value = '';
    document.getElementById('item_search_results').classList.add('hidden');
    document.getElementById('item_search').focus();
}

function closeAddItemModal() {
    document.getElementById('addItemModal').classList.remove('show');
}

// Item Search with Debouncing
document.getElementById('item_search').addEventListener('input', function() {
    const query = this.value.trim();
    
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    
    if (query.length < 2) {
        document.getElementById('item_search_results').classList.add('hidden');
        return;
    }
    
    searchTimeout = setTimeout(() => {
        searchItems(query);
    }, 300);
});

function searchItems(query) {
    fetch(`items/search.php?boq_id=${boqMasterId}&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('item_search_results');
            
            // Create New Item option - always shown at the top
            const createNewOption = `
                <div class="px-4 py-2 hover:bg-indigo-50 cursor-pointer border-b border-gray-200 bg-gray-50" 
                     onclick="openCreateItemModalWithName('${escapeHtml(query)}')">
                    <div class="flex items-center text-indigo-600">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="font-medium">Create New Item</span>
                    </div>
                    <div class="text-sm text-gray-500 ml-6">Add "${escapeHtml(query)}" as a new item</div>
                </div>
            `;
            
            if (data.success && data.items.length > 0) {
                const itemsHtml = data.items.map(item => `
                    <div class="px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-0" 
                         onclick="selectItem(${item.id}, '${escapeHtml(item.item_name)}', '${escapeHtml(item.item_code)}')">
                        <div class="font-medium text-gray-900">${escapeHtml(item.item_name)}</div>
                        <div class="text-sm text-gray-500">${escapeHtml(item.item_code)} | ${escapeHtml(item.unit)}</div>
                    </div>
                `).join('');
                resultsDiv.innerHTML = createNewOption + itemsHtml;
                resultsDiv.classList.remove('hidden');
            } else {
                resultsDiv.innerHTML = createNewOption + '<div class="px-4 py-3 text-sm text-gray-500">No existing items found matching your search</div>';
                resultsDiv.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
        });
}

function selectItem(id, name, code) {
    document.getElementById('boq_item_id').value = id;
    document.getElementById('item_search').value = `${name} (${code})`;
    document.getElementById('item_search_results').classList.add('hidden');
    document.getElementById('default_quantity').focus();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Close search results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#item_search') && !e.target.closest('#item_search_results')) {
        document.getElementById('item_search_results').classList.add('hidden');
    }
});

function submitAddItem() {
    const boqItemId = document.getElementById('boq_item_id').value;
    const quantity = document.getElementById('default_quantity').value;
    const remarks = document.getElementById('remarks').value;
    
    if (!boqItemId) {
        showAlert('Please select an item', 'error');
        return;
    }
    
    if (!quantity || parseFloat(quantity) <= 0) {
        showAlert('Quantity must be greater than 0', 'error');
        return;
    }
    
    const btn = document.getElementById('addItemBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Adding...';
    
    const formData = new FormData();
    formData.append('boq_master_id', boqMasterId);
    formData.append('boq_item_id', boqItemId);
    formData.append('default_quantity', quantity);
    formData.append('remarks', remarks);
    
    fetch('items/add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeAddItemModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to add item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while adding the item', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Add Item';
    });
}

// Edit Item Modal Functions
function openEditItemModal(id, name, quantity, remarks) {
    document.getElementById('editItemModal').classList.add('show');
    document.getElementById('edit_item_id').value = id;
    document.getElementById('edit_item_name').textContent = name;
    document.getElementById('edit_quantity').value = quantity;
    document.getElementById('edit_remarks').value = remarks;
    document.getElementById('edit_quantity').focus();
}

function closeEditItemModal() {
    document.getElementById('editItemModal').classList.remove('show');
}

function submitEditItem() {
    const id = document.getElementById('edit_item_id').value;
    const quantity = document.getElementById('edit_quantity').value;
    const remarks = document.getElementById('edit_remarks').value;
    
    if (!quantity || parseFloat(quantity) <= 0) {
        showAlert('Quantity must be greater than 0', 'error');
        return;
    }
    
    const btn = document.getElementById('editItemBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Updating...';
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('default_quantity', quantity);
    formData.append('remarks', remarks);
    
    fetch('items/edit.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeEditItemModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to update item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while updating the item', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Update Item';
    });
}

// Delete Item Modal Functions
function confirmDeleteItem(id, name) {
    document.getElementById('deleteItemModal').classList.add('show');
    document.getElementById('delete_item_id').value = id;
    document.getElementById('delete_item_name').textContent = name;
}

function closeDeleteItemModal() {
    document.getElementById('deleteItemModal').classList.remove('show');
}

function submitDeleteItem() {
    const id = document.getElementById('delete_item_id').value;
    
    const btn = document.getElementById('deleteItemBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Removing...';
    
    fetch(`items/delete.php?id=${id}`, {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeDeleteItemModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to remove item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while removing the item', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Remove Item';
    });
}

// Alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-100 text-green-800 border-green-200' : 
                   type === 'info' ? 'bg-blue-100 text-blue-800 border-blue-200' : 
                   'bg-red-100 text-red-800 border-red-200';
    
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg border ${bgColor}`;
    alertDiv.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                ${type === 'success' ? 
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>' :
                    type === 'info' ?
                    '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>' :
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>'
                }
            </svg>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Auto-hide success alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('#success-alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease-out';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});

// Close modals on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddItemModal();
        closeEditItemModal();
        closeDeleteItemModal();
        closeCreateItemModal();
    }
});

// Create New Item Modal Functions
function openCreateItemModal() {
    closeAddItemModal();
    document.getElementById('createItemModal').classList.add('show');
    resetCreateItemForm();
    document.getElementById('new_item_name').focus();
}

function openCreateItemModalWithName(name) {
    closeAddItemModal();
    document.getElementById('createItemModal').classList.add('show');
    resetCreateItemForm();
    document.getElementById('new_item_name').value = name;
    document.getElementById('new_item_code').focus();
}

function closeCreateItemModal() {
    document.getElementById('createItemModal').classList.remove('show');
}

function resetCreateItemForm() {
    document.getElementById('createItemForm').reset();
    document.getElementById('new_item_quantity').value = '1';
}

function submitCreateItem() {
    const itemName = document.getElementById('new_item_name').value.trim();
    const itemCode = document.getElementById('new_item_code').value.trim();
    const unit = document.getElementById('new_item_unit').value;
    const quantity = document.getElementById('new_item_quantity').value;
    
    // Validate required fields
    if (!itemName) {
        showAlert('Item name is required', 'error');
        document.getElementById('new_item_name').focus();
        return;
    }
    
    if (!itemCode) {
        showAlert('Item code is required', 'error');
        document.getElementById('new_item_code').focus();
        return;
    }
    
    if (!unit) {
        showAlert('Unit is required', 'error');
        document.getElementById('new_item_unit').focus();
        return;
    }
    
    if (!quantity || parseFloat(quantity) <= 0) {
        showAlert('Quantity must be greater than 0', 'error');
        document.getElementById('new_item_quantity').focus();
        return;
    }
    
    const btn = document.getElementById('createItemBtn');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Creating...';
    
    const formData = new FormData(document.getElementById('createItemForm'));
    
    fetch('items/create-new.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeCreateItemModal();
            setTimeout(() => location.reload(), 1000);
        } else {
            showAlert(data.message || 'Failed to create item', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while creating the item', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path></svg>Create & Add to BOQ';
    });
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/admin_layout.php';
?>
