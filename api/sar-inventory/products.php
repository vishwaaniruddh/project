<?php
/**
 * SAR Inventory Products API
 * Product management endpoints with search and filtering
 */
require_once '../../config/auth.php';
require_once '../../config/database.php';
require_once '../../controllers/SarInvApiController.php';
require_once '../../services/SarInvProductService.php';

class ProductsApi extends SarInvApiController {
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
                    $this->sendError('Product ID required', 400);
                }
                $this->update($id);
                break;
            case 'DELETE':
                $this->requirePermission('edit');
                if (!$id) {
                    $this->sendError('Product ID required', 400);
                }
                $this->destroy($id);
                break;
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    private function handleAction(string $action, int $id): void {
        switch ($action) {
            case 'search':
                $this->search();
                break;
            case 'active':
                $this->getActive();
                break;
            case 'low_stock':
                $this->getLowStock();
                break;
            case 'by_category':
                $categoryId = $this->getQueryInt('category_id');
                $this->getByCategory($categoryId);
                break;
            case 'stock_levels':
                $this->getStockLevels($id);
                break;
            case 'by_sku':
                $sku = $this->getQuery('sku');
                $this->getBySku($sku);
                break;
            default:
                $this->sendError('Unknown action', 400);
        }
    }

    private function index(): void {
        $status = $this->getQuery('status');
        $products = $this->productService->getAllProducts($status);
        
        $this->sendSuccess([
            'products' => $products,
            'total' => count($products)
        ]);
    }
    
    private function show(int $id): void {
        $product = $this->productService->getProductWithCategory($id);
        
        if (!$product) {
            $this->sendError('Product not found', 404);
        }
        
        // Include stock levels
        $stockLevels = $this->productService->getProductStockLevels($id);
        $product['stock_levels'] = $stockLevels;
        
        $this->sendSuccess(['product' => $product]);
    }
    
    private function store(): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $errors = $this->validateRequired($data, ['name', 'sku', 'category_id']);
        if (!empty($errors)) {
            $this->sendError('Validation failed', 400, $errors);
        }
        
        $result = $this->productService->createProduct($data);
        
        if ($result['success']) {
            $this->logAction('create', 'sar_inv_products', $result['product_id'], $data);
            $product = $this->productService->getProduct($result['product_id']);
            $this->sendSuccess(['product' => $product], $result['message'], 201);
        } else {
            $this->sendError('Failed to create product', 400, $result['errors']);
        }
    }
    
    private function update(int $id): void {
        $data = $this->getJsonBody();
        if (empty($data)) {
            $data = $_POST;
        }
        
        $result = $this->productService->updateProduct($id, $data);
        
        if ($result['success']) {
            $this->logAction('update', 'sar_inv_products', $id, $data);
            $product = $this->productService->getProduct($id);
            $this->sendSuccess(['product' => $product], $result['message']);
        } else {
            $this->sendError('Failed to update product', 400, $result['errors']);
        }
    }
    
    private function destroy(int $id): void {
        $result = $this->productService->deleteProduct($id);
        
        if ($result['success']) {
            $this->logAction('delete', 'sar_inv_products', $id);
            $this->sendSuccess(null, $result['message']);
        } else {
            $this->sendError('Failed to delete product', 400, $result['errors']);
        }
    }
    
    private function search(): void {
        $keyword = $this->getQuery('q');
        $categoryId = $this->getQueryInt('category_id') ?: null;
        $status = $this->getQuery('status');
        
        $products = $this->productService->searchProducts($keyword, $categoryId, $status);
        $this->sendSuccess(['products' => $products]);
    }
    
    private function getActive(): void {
        $products = $this->productService->getActiveProducts();
        $this->sendSuccess(['products' => $products]);
    }
    
    private function getLowStock(): void {
        $products = $this->productService->getLowStockProducts();
        $this->sendSuccess(['products' => $products]);
    }
    
    private function getByCategory(int $categoryId): void {
        if (!$categoryId) {
            $this->sendError('Category ID required', 400);
        }
        
        $products = $this->productService->getProductsByCategory($categoryId);
        $this->sendSuccess(['products' => $products]);
    }
    
    private function getStockLevels(int $id): void {
        if (!$id) {
            $this->sendError('Product ID required', 400);
        }
        
        $product = $this->productService->getProduct($id);
        if (!$product) {
            $this->sendError('Product not found', 404);
        }
        
        $stockLevels = $this->productService->getProductStockLevels($id);
        $totalStock = $this->productService->getProductTotalStock($id);
        
        $this->sendSuccess([
            'stock_levels' => $stockLevels,
            'total' => $totalStock
        ]);
    }
    
    private function getBySku(string $sku): void {
        if (empty($sku)) {
            $this->sendError('SKU required', 400);
        }
        
        $product = $this->productService->getProductBySku($sku);
        
        if (!$product) {
            $this->sendError('Product not found', 404);
        }
        
        $this->sendSuccess(['product' => $product]);
    }
}

// Execute API
$api = new ProductsApi();
$api->handle();
?>
