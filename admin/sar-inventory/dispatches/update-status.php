<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvDispatchService.php';

Auth::requireRole(ADMIN_ROLE);

$dispatchService = new SarInvDispatchService();

// Handle POST request (quick action from view page)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    
    if ($id && $newStatus) {
        $result = $dispatchService->updateStatus($id, $newStatus);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = implode(', ', $result['errors']);
        }
        
        header('Location: ' . url('/admin/sar-inventory/dispatches/view.php?id=' . $id));
        exit;
    }
}

$title = 'Update Dispatch Status';
$currentUser = Auth::getCurrentUser();

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

if (empty($validTransitions)) {
    $_SESSION['error'] = 'No status transitions available for this dispatch';
    header('Location: ' . url('/admin/sar-inventory/dispatches/view.php?id=' . $id));
    exit;
}

$errors = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_status'])) {
    $newStatus = trim($_POST['new_status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($newStatus)) {
        $errors[] = 'Please select a new status';
    } elseif (!in_array($newStatus, $validTransitions)) {
        $errors[] = 'Invalid status transition';
    }
    
    if (empty($errors)) {
        $result = $dispatchService->updateStatus($id, $newStatus);
        
        if ($result['success']) {
            $_SESSION['success'] = $result['message'];
            header('Location: ' . url('/admin/sar-inventory/dispatches/view.php?id=' . $id));
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
}

// Status info
$statusInfo = [
    'pending' => ['color' => 'gray', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'description' => 'Dispatch is awaiting approval'],
    'approved' => ['color' => 'blue', 'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'description' => 'Dispatch has been approved and ready for shipping'],
    'shipped' => ['color' => 'indigo', 'icon' => 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', 'description' => 'Items have been shipped from warehouse'],
    'in_transit' => ['color' => 'purple', 'icon' => 'M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0', 'description' => 'Shipment is on its way to destination'],
    'delivered' => ['color' => 'green', 'icon' => 'M5 13l4 4L19 7', 'description' => 'Items have been delivered to destination'],
    'cancelled' => ['color' => 'red', 'icon' => 'M6 18L18 6M6 6l12 12', 'description' => 'Dispatch has been cancelled']
];

$currentStatusInfo = $statusInfo[$dispatch['status']] ?? $statusInfo['pending'];

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/dispatches/view.php?id=' . $id); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Update Status</h1>
            <p class="text-gray-600">Dispatch: <?php echo htmlspecialchars($dispatch['dispatch_number']); ?></p>
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

<div class="max-w-2xl">
    <!-- Current Status -->
    <div class="card mb-6">
        <div class="card-body">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Status</h3>
            
            <div class="flex items-center p-4 bg-<?php echo $currentStatusInfo['color']; ?>-50 rounded-lg">
                <div class="flex-shrink-0 w-12 h-12 bg-<?php echo $currentStatusInfo['color']; ?>-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-<?php echo $currentStatusInfo['color']; ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $currentStatusInfo['icon']; ?>"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-lg font-medium text-<?php echo $currentStatusInfo['color']; ?>-900">
                        <?php echo ucfirst(str_replace('_', ' ', $dispatch['status'])); ?>
                    </p>
                    <p class="text-sm text-<?php echo $currentStatusInfo['color']; ?>-700">
                        <?php echo $currentStatusInfo['description']; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Update Form -->
    <div class="card">
        <div class="card-body">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Select New Status</h3>
            
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="space-y-3 mb-6">
                    <?php foreach ($validTransitions as $status): 
                        $info = $statusInfo[$status] ?? $statusInfo['pending'];
                    ?>
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                        <input type="radio" name="new_status" value="<?php echo $status; ?>" 
                               class="h-4 w-4 text-blue-600 border-gray-300">
                        <div class="ml-4 flex items-center flex-1">
                            <div class="flex-shrink-0 w-10 h-10 bg-<?php echo $info['color']; ?>-100 rounded-full flex items-center justify-center">
                                <svg class="w-5 h-5 text-<?php echo $info['color']; ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $info['icon']; ?>"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium text-gray-900"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></p>
                                <p class="text-sm text-gray-500"><?php echo $info['description']; ?></p>
                            </div>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-group mb-6">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="notes" class="form-textarea" rows="3" 
                              placeholder="Add any notes about this status change"></textarea>
                </div>
                
                <div class="flex justify-end space-x-4 pt-4 border-t">
                    <a href="<?php echo url('/admin/sar-inventory/dispatches/view.php?id=' . $id); ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Warning for Cancel -->
    <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <div class="flex">
            <svg class="w-5 h-5 text-yellow-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div>
                <h4 class="text-sm font-medium text-yellow-800">Important Notes</h4>
                <ul class="mt-1 text-sm text-yellow-700 list-disc list-inside">
                    <li>Shipping a dispatch will reduce stock from the source warehouse</li>
                    <li>Cancelling a dispatch will release any reserved stock</li>
                    <li>Status changes cannot be undone</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
