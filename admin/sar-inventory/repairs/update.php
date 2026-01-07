<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvRepairService.php';
require_once '../../../services/SarInvWarehouseService.php';
require_once '../../../models/SarInvRepair.php';
require_once '../../../models/Vendor.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Update Repair';
$currentUser = Auth::getCurrentUser();

$repairService = new SarInvRepairService();
$warehouseService = new SarInvWarehouseService();
$vendorModel = new Vendor();

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['error'] = 'Invalid repair ID';
    header('Location: ' . url('/admin/sar-inventory/repairs/'));
    exit;
}

$repair = $repairService->getRepairWithDetails($id);
if (!$repair) {
    $_SESSION['error'] = 'Repair not found';
    header('Location: ' . url('/admin/sar-inventory/repairs/'));
    exit;
}

// Check if repair can be updated
if (in_array($repair['status'], [SarInvRepair::STATUS_COMPLETED, SarInvRepair::STATUS_CANCELLED])) {
    $_SESSION['error'] = 'This repair cannot be updated as it is already ' . $repair['status'];
    header('Location: ' . url('/admin/sar-inventory/repairs/view.php?id=' . $id));
    exit;
}

// Get valid status transitions
$validTransitions = $repairService->getValidStatusTransitions($id);

// Get warehouses for return location
$warehouses = $warehouseService->getAllWarehouses();

// Get vendors for dropdown
$vendors = $vendorModel->getActiveVendors();

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'start':
            $result = $repairService->startRepair($id);
            if ($result['success']) {
                $_SESSION['success'] = 'Repair started successfully';
                header('Location: ' . url('/admin/sar-inventory/repairs/view.php?id=' . $id));
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;
            
        case 'complete':
            $repairNotes = trim($_POST['repair_notes'] ?? '');
            $cost = $_POST['cost'] ?? null;
            $returnWarehouseId = $_POST['return_warehouse_id'] ?? null;
            
            if ($cost !== '' && $cost !== null) {
                $cost = floatval($cost);
                if ($cost < 0) {
                    $errors[] = 'Cost cannot be negative';
                }
            } else {
                $cost = null;
            }
            
            if (empty($errors)) {
                $result = $repairService->completeRepair(
                    $id, 
                    $repairNotes ?: null, 
                    $cost, 
                    $returnWarehouseId ? intval($returnWarehouseId) : null
                );
                
                if ($result['success']) {
                    $_SESSION['success'] = 'Repair completed successfully. Asset status updated to available.';
                    header('Location: ' . url('/admin/sar-inventory/repairs/view.php?id=' . $id));
                    exit;
                } else {
                    $errors = $result['errors'];
                }
            }
            break;
            
        case 'cancel':
            $reason = trim($_POST['cancel_reason'] ?? '');
            $result = $repairService->cancelRepair($id, $reason ?: null);
            
            if ($result['success']) {
                $_SESSION['success'] = 'Repair cancelled successfully. Asset status restored.';
                header('Location: ' . url('/admin/sar-inventory/repairs/view.php?id=' . $id));
                exit;
            } else {
                $errors = $result['errors'];
            }
            break;
            
        case 'update_diagnosis':
            $diagnosis = trim($_POST['diagnosis'] ?? '');
            if (empty($diagnosis)) {
                $errors[] = 'Diagnosis is required';
            } else {
                $result = $repairService->updateDiagnosis($id, $diagnosis);
                if ($result['success']) {
                    $_SESSION['success'] = 'Diagnosis updated successfully';
                    header('Location: ' . url('/admin/sar-inventory/repairs/view.php?id=' . $id));
                    exit;
                } else {
                    $errors = $result['errors'];
                }
            }
            break;
            
        case 'update_cost':
            $cost = $_POST['cost'] ?? '';
            if ($cost === '' || $cost === null) {
                $errors[] = 'Cost is required';
            } else {
                $cost = floatval($cost);
                if ($cost < 0) {
                    $errors[] = 'Cost cannot be negative';
                } else {
                    $result = $repairService->updateCost($id, $cost);
                    if ($result['success']) {
                        $_SESSION['success'] = 'Cost updated successfully';
                        header('Location: ' . url('/admin/sar-inventory/repairs/view.php?id=' . $id));
                        exit;
                    } else {
                        $errors = $result['errors'];
                    }
                }
            }
            break;
            
        default:
            $errors[] = 'Invalid action';
    }
    
    // Refresh repair data after failed action
    $repair = $repairService->getRepairWithDetails($id);
}

// Status labels
$statusLabels = [
    'pending' => 'Pending',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled'
];

// Status badge colors
$statusColors = [
    'pending' => 'badge-warning',
    'in_progress' => 'badge-primary',
    'completed' => 'badge-success',
    'cancelled' => 'badge-secondary'
];
$statusClass = $statusColors[$repair['status']] ?? 'badge-secondary';

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/repairs/view.php?id=' . $id); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Update Repair</h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($repair['repair_number']); ?> - <?php echo htmlspecialchars($repair['product_name'] ?? 'Unknown Product'); ?></p>
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

<!-- Current Status -->
<div class="card mb-6">
    <div class="card-body">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div>
                    <p class="text-sm text-gray-500">Current Status</p>
                    <span class="badge <?php echo $statusClass; ?> text-lg">
                        <?php echo $statusLabels[$repair['status']] ?? ucfirst($repair['status']); ?>
                    </span>
                </div>
                <div class="border-l pl-4">
                    <p class="text-sm text-gray-500">Asset</p>
                    <p class="font-medium"><?php echo htmlspecialchars($repair['serial_number'] ?? $repair['barcode'] ?? 'N/A'); ?></p>
                </div>
                <div class="border-l pl-4">
                    <p class="text-sm text-gray-500">Current Cost</p>
                    <p class="font-medium">
                        <?php echo !empty($repair['cost']) ? '₹' . number_format($repair['cost'], 2) : 'Not set'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Status Actions -->
    <div class="space-y-6">
        <!-- Start Repair (if pending) -->
        <?php if ($repair['status'] === SarInvRepair::STATUS_PENDING): ?>
        <div class="card">
            <div class="card-header bg-blue-50">
                <h3 class="text-lg font-semibold text-blue-800">Start Repair</h3>
            </div>
            <div class="card-body">
                <p class="text-gray-600 mb-4">Mark this repair as in progress. This will set the start date to today.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="start">
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Start Repair
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Complete Repair -->
        <?php if (in_array(SarInvRepair::STATUS_COMPLETED, $validTransitions)): ?>
        <div class="card">
            <div class="card-header bg-green-50">
                <h3 class="text-lg font-semibold text-green-800">Complete Repair</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="complete">
                    
                    <div class="form-group">
                        <label class="form-label">Repair Notes</label>
                        <textarea name="repair_notes" rows="3" class="form-input"
                                  placeholder="Summary of work performed"><?php echo htmlspecialchars($repair['repair_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Final Cost (₹)</label>
                        <input type="number" name="cost" step="0.01" min="0" class="form-input"
                               value="<?php echo htmlspecialchars($repair['cost'] ?? ''); ?>"
                               placeholder="0.00">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Return to Warehouse</label>
                        <select name="return_warehouse_id" class="form-select">
                            <option value="">Select warehouse (optional)</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?php echo $warehouse['id']; ?>">
                                <?php echo htmlspecialchars($warehouse['name']); ?>
                                <?php if (!empty($warehouse['code'])): ?>
                                (<?php echo htmlspecialchars($warehouse['code']); ?>)
                                <?php endif; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-sm text-gray-500 mt-1">Select warehouse to return the asset to after repair</p>
                    </div>
                    
                    <button type="submit" class="btn btn-success">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Complete Repair
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <!-- Cancel Repair -->
        <?php if (in_array(SarInvRepair::STATUS_CANCELLED, $validTransitions)): ?>
        <div class="card">
            <div class="card-header bg-red-50">
                <h3 class="text-lg font-semibold text-red-800">Cancel Repair</h3>
            </div>
            <div class="card-body">
                <p class="text-gray-600 mb-4">Cancel this repair. The asset status will be restored to available.</p>
                <form method="POST" class="space-y-4" onsubmit="return confirm('Are you sure you want to cancel this repair?');">
                    <input type="hidden" name="action" value="cancel">
                    
                    <div class="form-group">
                        <label class="form-label">Cancellation Reason</label>
                        <textarea name="cancel_reason" rows="2" class="form-input"
                                  placeholder="Reason for cancellation (optional)"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel Repair
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Update Details -->
    <div class="space-y-6">
        <!-- Update Diagnosis -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Update Diagnosis</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_diagnosis">
                    
                    <div class="form-group">
                        <label class="form-label">Diagnosis</label>
                        <textarea name="diagnosis" rows="4" class="form-input" required
                                  placeholder="Technical diagnosis and findings"><?php echo htmlspecialchars($repair['diagnosis'] ?? ''); ?></textarea>
                        <p class="text-sm text-gray-500 mt-1">Document the technical diagnosis and findings</p>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Update Diagnosis
                    </button>
                </form>
            </div>
        </div>

        <!-- Update Cost -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Update Cost</h3>
            </div>
            <div class="card-body">
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_cost">
                    
                    <div class="form-group">
                        <label class="form-label">Repair Cost (₹)</label>
                        <input type="number" name="cost" step="0.01" min="0" class="form-input" required
                               value="<?php echo htmlspecialchars($repair['cost'] ?? ''); ?>"
                               placeholder="0.00">
                        <p class="text-sm text-gray-500 mt-1">Update the estimated or actual repair cost</p>
                    </div>
                    
                    <button type="submit" class="btn btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Update Cost
                    </button>
                </form>
            </div>
        </div>

        <!-- Issue Description (Read-only) -->
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-semibold">Issue Description</h3>
            </div>
            <div class="card-body">
                <div class="p-3 bg-gray-50 rounded-lg text-gray-900">
                    <?php echo nl2br(htmlspecialchars($repair['issue_description'] ?? 'No description provided')); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
