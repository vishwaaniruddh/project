<?php
/**
 * SAR Inventory Product Categories API
 * Category management with hierarchical support
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvProductService.php';

class CategoriesApi extends SarInvApiController {
    private $productService;
    
    public function __construct() {
        parent::__construct();
        $this->productService = new SarInvProductService();
    }
    
    public function handle(): void {
        $this->requireAuth();
        
        $method = $this->getMethod();
        $id = $this->getQueryInt('id');
        $action = $this->getQuery('action');
        
        if ($action) {
            $this->handleAction($action, $id);
            return;
        }
        
        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->show($id);
                } else {
                    $this->index();
                }
                break;
            case 'POST':
                $this->requirePermission('create');
                $this->store();
                break;
            case 'PUT':
                $this->requirePermission('edit');
                if (!$id) {
                    $this->sendError('Category ID required', 400);
                }
                $this->update($id);
                break;
            case 'DELETE':
                $this->requirePermission('edit');
                if (!$id) {
                    $this->sendError('Category ID required', 400);
                }
                $this->destroy($id);
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    private function handleAction(string $action, int $id): void {
        switch ($action) {
            case 'tree':
                $this->getTree();
                break;
            case 'flat':
                $this->getFlat();
                break;
            case 'root':
                $this->getRoot();
                break;
            case 'path':
                $this->getPath($id);
                break;
            case 'can_delete':
                $this->canDelete($id);
                break;
            default:
                $this->sendError('Unknown action', 400);
        }
    }
    
    private function index(): void {
        $categories = $this->productService->getAllCategories();
        
        $this->sendSuccess([
            'categories' => $categories,
            'total' => count($categories)
        ]);
    }
    
    private function show(int $id): void {
        $category = $this->productService->getCategory($id);
        
        if (!$category) {
            $this->sendError('Category not found', 404);
        }
        
        // Include path
        $category['path'] = $this->productService->getCategoryPath($id);
        
        $this->sendSuccess(['category' => $category]);
    }
    
    private function store(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['name']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->productService->createCategory($data);
        
        if ($result['success']) {
            $this->logAction('create', 'sar_inv_product_categories', $result['category_id'], $data);
            $category = $this->productService->getCategory($result['category_id']);
            $this->sendSuccess(['category' => $category], $result['message'], 201);
        } else {
            $this->sendError('Failed to create category', 400, $result['errors']);
        }
    }
    
    private function update(int $id): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $result = $this->productService->updateCategory($id, $data);
        
        if ($result['success']) {
            $this->logAction('update', 'sar_inv_product_categories', $id, $data);
            $category = $this->productService->getCategory($id);
            $this->sendSuccess(['category' => $category], $result['message']);
        } else {
            $this->sendError('Failed to update category', 400, $result['errors']);
        }
    }
    
    private function destroy(int $id): void {
        $result = $this->productService->deleteCategory($id);
        
        if ($result['success']) {
            $this->logAction('delete', 'sar_inv_product_categories', $id);
            $this->sendSuccess(null, $result['message']);
        } else {
            $this->sendError('Failed to delete category', 400, $result['errors']);
        }
    }
    
    private function getTree(): void {
        $tree = $this->productService->getCategoryTree();
        $this->sendSuccess(['tree' => $tree]);
    }
    
    private function getFlat(): void {
        $categories = $this->productService->getFlatCategoryList();
        $this->sendSuccess(['categories' => $categories]);
    }
    
    private function getRoot(): void {
        $categories = $this->productService->getRootCategories();
        $this->sendSuccess(['categories' => $categories]);
    }
    
    private function getPath(int $id): void {
        if (!$id) {
            $this->sendError('Category ID required', 400);
        }
        
        $path = $this->productService->getCategoryPath($id);
        $this->sendSuccess(['path' => $path]);
    }
    
    private function canDelete(int $id): void {
        if (!$id) {
            $this->sendError('Category ID required', 400);
        }
        
        $result = $this->productService->canDeleteCategory($id);
        $this->sendSuccess($result);
    }
}

// Execute API
$api = new CategoriesApi();
$api->handle();
?>
