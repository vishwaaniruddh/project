<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvProductService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Add Product';
$currentUser = Auth::getCurrentUser();

$productService = new SarInvProductService();

// Get categories for dropdown
$categories = $productService->getFlatCategoryList();

$errors = [];
$data = [
    'name' => '',
    'sku' => '',
    'category_id' => $_GET['category_id'] ?? '',
    'description' => '',
    'specifications' => '',
    'unit_of_measure' => '',
    'minimum_stock_level' => 0,
    'is_serializable' => 0,
    'status' => 'active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Parse specifications JSON if provided
    $specifications = [];
    if (!empty($_POST['specifications'])) {
        $specJson = json_decode($_POST['specifications'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $specifications = $specJson;
        }
    }
    
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'sku' => trim($_POST['sku'] ?? ''),
        'category_id' => $_POST['category_id'] !== '' ? intval($_POST['category_id']) : null,
        'description' => trim($_POST['description'] ?? ''),
        'specifications' => $specifications,
        'unit_of_measure' => trim($_POST['unit_of_measure'] ?? ''),
        'minimum_stock_level' => intval($_POST['minimum_stock_level'] ?? 0),
        'is_serializable' => isset($_POST['is_serializable']) ? 1 : 0,
        'status' => $_POST['status'] ?? 'active'
    ];
    
    $result = $productService->createProduct($data);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Product created successfully';
        header('Location: ' . url('/admin/sar-inventory/products/'));
        exit;
    } else {
        $errors = $result['errors'];
        // Keep specifications as string for form
        $data['specifications'] = $_POST['specifications'] ?? '';
    }
}

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/products/'); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Add Product</h1>
            <p class="text-gray-600">Create a new inventory product</p>
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

<div class="card max-w-3xl">
    <div class="card-body">
        <form method="POST" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Product Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>" 
                           class="form-input" required placeholder="Enter product name">
                </div>
                
                <div class="form-group">
                    <label class="form-label">SKU <span class="text-red-500">*</span></label>
                    <input type="text" name="sku" value="<?php echo htmlspecialchars($data['sku']); ?>" 
                           class="form-input font-mono" required placeholder="e.g., PROD-001">
                    <p class="text-sm text-gray-500 mt-1">Unique stock keeping unit identifier</p>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">— No Category —</option>
                        <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" 
                                <?php echo $data['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['display_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Unit of Measure</label>
                    <input type="text" name="unit_of_measure" value="<?php echo htmlspecialchars($data['unit_of_measure']); ?>" 
                           class="form-input" placeholder="e.g., pcs, kg, m">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" rows="3" 
                          placeholder="Product description"><?php echo htmlspecialchars($data['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Specifications (JSON)</label>
                <textarea name="specifications" class="form-textarea font-mono text-sm" rows="4" 
                          placeholder='{"color": "red", "size": "large", "weight": "1.5kg"}'><?php echo htmlspecialchars(is_string($data['specifications']) ? $data['specifications'] : json_encode($data['specifications'], JSON_PRETTY_PRINT)); ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Enter specifications as JSON object (optional)</p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="form-group">
                    <label class="form-label">Minimum Stock Level</label>
                    <input type="number" name="minimum_stock_level" value="<?php echo intval($data['minimum_stock_level']); ?>" 
                           class="form-input" min="0" placeholder="0">
                    <p class="text-sm text-gray-500 mt-1">Alert when stock falls below this level</p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?php echo $data['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $data['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="discontinued" <?php echo $data['status'] === 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <div class="flex items-center">
                    <input type="checkbox" name="is_serializable" id="is_serializable" value="1"
                           class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                           <?php echo !empty($data['is_serializable']) ? 'checked' : ''; ?>>
                    <label for="is_serializable" class="ml-2 text-sm font-medium text-gray-700">
                        Serializable Product
                    </label>
                </div>
                <p class="text-sm text-gray-500 mt-1">Enable this for products that need individual serial number tracking (e.g., SIM cards, devices)</p>
            </div>
            
            <div class="flex justify-end space-x-4 pt-4 border-t">
                <a href="<?php echo url('/admin/sar-inventory/products/'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Product</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
