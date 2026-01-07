<?php
require_once '../../../../config/auth.php';
require_once '../../../../config/database.php';
require_once '../../../../services/SarInvMaterialService.php';
require_once '../../../../services/SarInvWarehouseService.php';
require_once '../../../../models/SarInvMaterialRequest.php';

Auth::requireRole(ADMIN_ROLE);

$materialService = new SarInvMaterialService();
$warehouseService = new SarInvWarehouseService();
$currentUser = Auth::getCurrentUser();

// Get request ID
$requestId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$requestId) {
    $_SESSION['error'] = 'Request ID is required';
    header('Location: ' . url('/admin/sar-inventory/materials/requests/'));
    exit;
}

// Get request details
$request = $materialService->getRequestWithDetails($requestId);

if (!$request) {
    $_SESSION['error'] = 'Request not found';
    header('Location: ' . url('/admin/sar-inventory/materials/requests/'));
    exit;
}

// Check if request can be fulfilled
if (!in_array($request['status'], [SarInvMaterialRequest::STATUS_APPROVED, SarInvMaterialRequest::STATUS_PARTIALLY_FULFILLED])) {
    $_SESSION['error'] = 'Only approved or partially fulfilled requests can be fulfilled';
    header('Location: ' . url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId));
    exit;
}

$title = 'Fulfill Request: ' . $request['request_number'];

// Get fulfillment progress
$progress = $materialService->getFulfillmentProgress($requestId);
$remainingQuantity = $progress['remaining'] ?? $request['quantity'];

// Get warehouses for selection
$warehouses = $warehouseService->getAllWarehouses();

$errors = [];
$formData = [
    'quantity' => $remainingQuantity,
    'warehouse_id' => '',
    'notes' => ''
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'quantity' => floatval($_POST['quantity'] ?? 0),
        'warehouse_id' => $_POST['warehouse_id'] ?? null,
        'notes' => trim($_POST['notes'] ?? '')
    ];
    
    if ($formData['quantity'] <= 0) {
        $errors[] = 'Quantity must be greater than zero';
    }
    
    if ($formData['quantity'] > $remainingQuantity) {
        $errors[] = "Quantity cannot exceed remaining quantity ({$remainingQuantity})";
    }
    
    // Convert empty warehouse to null
    if (empty($formData['warehouse_id'])) {
        $formData['warehouse_id'] = null;
    }
    
    if (empty($errors)) {
        $result = $materialService->fulfillRequest(
            $requestId,
            $formData['quantity'],
            $formData['warehouse_id'] ? intval($formData['warehouse_id']) : null,
            $formData['notes']
        );
        
        if ($result['success']) {
            $_SESSION['success'] = 'Request fulfilled successfully. Status: ' . ucfirst(str_replace('_', ' ', $result['status']));
            header('Location: ' . url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId));
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
}

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Fulfill Request</h1>
            <p class="text-gray-600 font-mono"><?php echo htmlspecialchars($request['request_number']); ?></p>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
    <ul class="list-disc list-inside">
        <?php foreach ($errors as $error): ?>
        <li><?php echo htmlspecialchars($error); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Request Summary -->
<div class="card mb-6">
    <div class="card-header">
        <h3 class="text-lg font-semibold">Request Summary</h3>
    </div>
    <div class="card-body">
        <dl class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <dt class="text-sm font-medium text-gray-500">Material/Product</dt>
                <dd class="mt-1 text-sm text-gray-900">
                    <?php if (!empty($request['material_name'])): ?>
                    <?php echo htmlspecialchars($request['material_name']); ?>
                    <?php elseif (!empty($request['product_name'])): ?>
                    <?php echo htmlspecialchars($request['product_name']); ?>
                    <?php else: ?>
                    <span class="text-gray-400">-</span>
                    <?php endif; ?>
                </dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Requested</dt>
                <dd class="mt-1 text-sm text-gray-900"><?php echo number_format($request['quantity']); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Already Fulfilled</dt>
                <dd class="mt-1 text-sm text-gray-900"><?php echo number_format($request['fulfilled_quantity'] ?? 0); ?></dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-gray-500">Remaining</dt>
                <dd class="mt-1 text-sm text-gray-900 font-bold text-orange-600"><?php echo number_format($remainingQuantity); ?></dd>
            </div>
        </dl>
        
        <!-- Progress Bar -->
        <?php if ($progress): ?>
        <div class="mt-4">
            <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600">Fulfillment Progress</span>
                <span class="font-medium"><?php echo $progress['percentage']; ?>%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo min(100, $progress['percentage']); ?>%"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Fulfillment Form -->
<div class="card">
    <div class="card-header">
        <h3 class="text-lg font-semibold">Fulfillment Details</h3>
    </div>
    <div class="card-body">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Quantity -->
                <div class="form-group">
                    <label class="form-label required">Quantity to Fulfill</label>
                    <input type="number" name="quantity" step="0.01" min="0.01" max="<?php echo $remainingQuantity; ?>" 
                           class="form-input" required
                           value="<?php echo htmlspecialchars($formData['quantity']); ?>">
                    <p class="text-sm text-gray-500 mt-1">Maximum: <?php echo number_format($remainingQuantity); ?></p>
                </div>

                <!-- Source Warehouse (Optional) -->
                <div class="form-group">
                    <label class="form-label">Source Warehouse</label>
                    <select name="warehouse_id" class="form-select">
                        <option value="">No warehouse (manual fulfillment)</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?php echo $warehouse['id']; ?>" <?php echo $formData['warehouse_id'] == $warehouse['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($warehouse['name']); ?> (<?php echo htmlspecialchars($warehouse['code']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="text-sm text-gray-500 mt-1">Select warehouse to deduct stock from (optional)</p>
                </div>
            </div>

            <!-- Notes -->
            <div class="form-group">
                <label class="form-label">Fulfillment Notes</label>
                <textarea name="notes" rows="3" class="form-input"
                          placeholder="Add any notes about this fulfillment"><?php echo htmlspecialchars($formData['notes']); ?></textarea>
            </div>

            <!-- Quick Fill Buttons -->
            <div class="flex gap-2">
                <button type="button" onclick="document.querySelector('input[name=quantity]').value = <?php echo $remainingQuantity; ?>" 
                        class="btn btn-secondary btn-sm">
                    Fill Remaining (<?php echo number_format($remainingQuantity); ?>)
                </button>
                <?php if ($remainingQuantity > 1): ?>
                <button type="button" onclick="document.querySelector('input[name=quantity]').value = <?php echo floor($remainingQuantity / 2); ?>" 
                        class="btn btn-secondary btn-sm">
                    Half (<?php echo number_format(floor($remainingQuantity / 2)); ?>)
                </button>
                <?php endif; ?>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-4 border-t">
                <a href="<?php echo url('/admin/sar-inventory/materials/requests/view.php?id=' . $requestId); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Fulfill Request
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../../includes/admin_layout.php';
?>
