<?php
require_once '../../../config/auth.php';
require_once '../../../config/database.php';
require_once '../../../services/SarInvProductService.php';

Auth::requireRole(ADMIN_ROLE);

$title = 'Add Category';
$currentUser = Auth::getCurrentUser();

$productService = new SarInvProductService();

// Get flat category list for parent selection
$categories = $productService->getFlatCategoryList();

$errors = [];
$data = [
    'name' => '',
    'description' => '',
    'parent_id' => $_GET['parent_id'] ?? '',
    'status' => 'active'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'parent_id' => $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null,
        'status' => $_POST['status'] ?? 'active'
    ];
    
    $result = $productService->createCategory($data);
    
    if ($result['success']) {
        $_SESSION['success'] = 'Category created successfully';
        header('Location: ' . url('/admin/sar-inventory/product-category/'));
        exit;
    } else {
        $errors = $result['errors'];
    }
}

ob_start();
?>

<div class="mb-6">
    <div class="flex items-center gap-4">
        <a href="<?php echo url('/admin/sar-inventory/product-category/'); ?>" class="text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Add Category</h1>
            <p class="text-gray-600">Create a new product category</p>
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

<div class="card max-w-2xl">
    <div class="card-body">
        <form method="POST" class="space-y-6">
            <div class="form-group">
                <label class="form-label">Category Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($data['name']); ?>" 
                       class="form-input" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-textarea" rows="3" 
                          placeholder="Category description"><?php echo htmlspecialchars($data['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label class="form-label">Parent Category</label>
                <select name="parent_id" class="form-select">
                    <option value="">— Root Category (No Parent) —</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" 
                            <?php echo $data['parent_id'] == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['display_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">Leave empty to create a root category</p>
            </div>
            
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="active" <?php echo $data['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $data['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="flex justify-end space-x-4 pt-4 border-t">
                <a href="<?php echo url('/admin/sar-inventory/product-category/'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Category</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include '../../../includes/admin_layout.php';
?>
