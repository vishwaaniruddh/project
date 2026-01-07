<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvMaterialService.php';
require_once '../../../models/SarInvMaterialMaster.php';

Auth::requireRole(ADMIN_ROLE);

$materialService = new SarInvMaterialService();

// Get material ID
$materialId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$materialId) {
    $_SESSION['error'] = 'Material ID is required';
    header('Location: ' . url('/admin/sar-inventory/materials/'));
    exit;
}

// Get material details
$material = $materialService->getMaterialMaster($materialId);

if (!$material) {
    $_SESSION['error'] = 'Material not found';
    header('Location: ' . url('/admin/sar-inventory/materials/'));
    exit;
}

$title = 'Edit Material: ' . $material['code'];
$currentUser = Auth::getCurrentUser();

$errors = [];

// Parse existing specifications
$specsJson = '';
if (!empty($material['specifications'])) {
    $specs = json_decode($material['specifications'], true);
    if ($specs) {
        $specsJson = json_encode($specs, JSON_PRETTY_PRINT);
    }
}

$formData = [
    'name' => $material['name'],
    'code' => $material['code'],
    'description' => $material['description'] ?? '',
    'specifications' => $specsJson,
    'unit_of_measure' => $material['unit_of_measure'] ?? '',
    'default_quantity' => $material['default_quantity'] ?? '',
    'status' => $material['status']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'code' => strtoupper(trim($_POST['code'] ?? '')),
        'description' => trim($_POST['description'] ?? ''),
        'specifications' => trim($_POST['specifications'] ?? ''),
        'unit_of_measure' => trim($_POST['unit_of_measure'] ?? ''),
        'default_quantity' => $_POST['default_quantity'] ?? '',
        'status' => $_POST['status'] ?? SarInvMaterialMaster::STATUS_ACTIVE
    ];
    
    // Parse specifications JSON if provided
    if (!empty($formData['specifications'])) {
        $specs = json_decode($formData['specifications'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors[] = 'Invalid JSON format for specifications';
        } else {
            $formData['specifications'] = $specs;
        }
    } else {
        $formData['specifications'] = null;
    }
    
    // Convert default_quantity
    if ($formData['default_quantity'] !== '' && $formData['default_quantity'] !== null) {
        $formData['default_quantity'] = floatval($formData['default_quantity']);
        if ($formData['default_quantity'] < 0) {
            $errors[] = 'Default quantity cannot be negative';
        }
    } else {
        $formData['default_quantity'] = 0;
    }
    
    if (empty($errors)) {
        $result = $materialService->updateMaterialMaster($materialId, $formData);
        
        if ($result['success']) {
            $_SESSION['success'] = 'Material master updated successfully';
            header('Location: ' . url('/admin/sar-inventory/materials/view.php?id=' . $materialId));
            exit;
        } else {
            $errors = $result['errors'];
        }
    }
    
    // Convert specs back to JSON string for form display
    if (is_array($formData['specifications'])) {
        $formData['specifications'] = json_encode($formData['specifications'], JSON_PRETTY_PRINT);
    }
}

// Status options
$statusOptions = [
    SarInvMaterialMaster::STATUS_ACTIVE => 'Active',
    SarInvMaterialMaster::STATUS_INACTIVE => 'Inactive'
];

// Common units of measure
$commonUnits = ['Pcs', 'Kg', 'Ltr', 'Mtr', 'Box', 'Set', 'Roll', 'Pair', 'Unit', 'Pack'];

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/materials/view.php?id=' . $materialId); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Material Master</h1>
            <p class="text-gray-600 font-mono"><?php echo htmlspecialchars($material['code']); ?></p>
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

<div class="card">
    <div class="card-body">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Material Code -->
                <div class="form-group">
                    <label class="form-label required">Material Code</label>
                    <input type="text" name="code" class="form-input font-mono" required
                           value="<?php echo htmlspecialchars($formData['code']); ?>"
                           placeholder="e.g., MAT-001" maxlength="50">
                    <p class="text-sm text-gray-500 mt-1">Unique identifier for this material</p>
                </div>

                <!-- Material Name -->
                <div class="form-group">
                    <label class="form-label required">Material Name</label>
                    <input type="text" name="name" class="form-input" required
                           value="<?php echo htmlspecialchars($formData['name']); ?>"
                           placeholder="Enter material name">
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" rows="3" class="form-input"
                          placeholder="Detailed description of the material"><?php echo htmlspecialchars($formData['description']); ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Unit of Measure -->
                <div class="form-group">
                    <label class="form-label">Unit of Measure</label>
                    <input type="text" name="unit_of_measure" class="form-input" list="units"
                           value="<?php echo htmlspecialchars($formData['unit_of_measure']); ?>"
                           placeholder="e.g., Pcs, Kg, Mtr">
                    <datalist id="units">
                        <?php foreach ($commonUnits as $unit): ?>
                        <option value="<?php echo $unit; ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>

                <!-- Default Quantity -->
                <div class="form-group">
                    <label class="form-label">Default Quantity</label>
                    <input type="number" name="default_quantity" step="0.01" min="0" class="form-input"
                           value="<?php echo htmlspecialchars($formData['default_quantity']); ?>"
                           placeholder="0">
                    <p class="text-sm text-gray-500 mt-1">Default quantity for requests</p>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <?php foreach ($statusOptions as $key => $label): ?>
                        <option value="<?php echo $key; ?>" <?php echo $formData['status'] === $key ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Specifications (JSON) -->
            <div class="form-group">
                <label class="form-label">Specifications (JSON)</label>
                <textarea name="specifications" rows="6" class="form-input font-mono text-sm"
                          placeholder='{"color": "blue", "weight": "500g", "dimensions": "10x20x5 cm"}'><?php echo htmlspecialchars($formData['specifications']); ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Optional JSON object with material specifications</p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end gap-4 pt-4 border-t">
                <a href="<?php echo url('/admin/sar-inventory/materials/view.php?id=' . $materialId); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Update Material
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
