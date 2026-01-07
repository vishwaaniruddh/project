<?php
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvProductCategory.php';
require_once __DIR__ . '/../models/SarInvAuditLog.php';

/**
 * SAR Inventory Product Service
 * Business logic for product and category management with validation and audit logging
 */
class SarInvProductService {
    private $productModel;
    private $categoryModel;
    private $auditLog;
    
    public function __construct() {
        $this->productModel = new SarInvProduct();
        $this->categoryModel = new SarInvProductCategory();
        $this->auditLog = new SarInvAuditLog();
    }
    
    // ==================== CATEGORY OPERATIONS ====================
    
    /**
     * Create a new product category
     * @param array $data Category data
     * @return array Result with success status and category ID or errors
     */
    public function createCategory(array $data): array {
        $errors = $this->categoryModel->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $categoryId = $this->categoryModel->create($data);
            
            if ($categoryId) {
                return [
                    'success' => true,
                    'category_id' => $categoryId,
                    'message' => 'Category created successfully'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to create category']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Update an existing category
     * @param int $id Category ID
     * @param array $data Updated category data
     * @return array Result with success status
     */
    public function updateCategory(int $id, array $data): array {
        $category = $this->categoryModel->find($id);
        if (!$category) {
            return ['success' => false, 'errors' => ['Category not found']];
        }
        
        $errors = $this->categoryModel->validate($data, true, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $result = $this->categoryModel->update($id, $data);
            
            if ($result) {
                return ['success' => true, 'message' => 'Category updated successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to update category']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Delete a category with products check
     * @param int $id Category ID
     * @return array Result with success status
     */
    public function deleteCategory(int $id): array {
        $category = $this->categoryModel->find($id);
        if (!$category) {
            return ['success' => false, 'errors' => ['Category not found']];
        }
        
        // Check for assigned products
        if ($this->categoryModel->hasProducts($id)) {
            return [
                'success' => false,
                'errors' => ['Cannot delete category with assigned products. Please reassign or remove products first.']
            ];
        }
        
        // Check for child categories
        if ($this->categoryModel->hasChildren($id)) {
            return [
                'success' => false,
                'errors' => ['Cannot delete category with child categories. Please delete child categories first.']
            ];
        }
        
        try {
            $result = $this->categoryModel->delete($id);
            
            if ($result) {
                return ['success' => true, 'message' => 'Category deleted successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to delete category']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Get category by ID
     */
    public function getCategory(int $id): ?array {
        $category = $this->categoryModel->find($id);
        return $category ?: null;
    }
    
    /**
     * Get all categories
     */
    public function getAllCategories(): array {
        return $this->categoryModel->findAll();
    }
    
    /**
     * Get category tree structure
     */
    public function getCategoryTree(): array {
        return $this->categoryModel->getCategoryTree();
    }
    
    /**
     * Get flat category list with indentation
     */
    public function getFlatCategoryList(): array {
        return $this->categoryModel->getFlatTreeList();
    }
    
    /**
     * Get root categories
     */
    public function getRootCategories(): array {
        return $this->categoryModel->getRootCategories();
    }
    
    /**
     * Get category full path
     */
    public function getCategoryPath(int $categoryId): string {
        return $this->categoryModel->getFullPathString($categoryId);
    }
    
    /**
     * Check if category can be deleted
     */
    public function canDeleteCategory(int $id): array {
        $reasons = [];
        
        if ($this->categoryModel->hasProducts($id)) {
            $reasons[] = 'Category has assigned products';
        }
        
        if ($this->categoryModel->hasChildren($id)) {
            $reasons[] = 'Category has child categories';
        }
        
        return [
            'can_delete' => empty($reasons),
            'reasons' => $reasons
        ];
    }
    
    // ==================== PRODUCT OPERATIONS ====================
    
    /**
     * Create a new product
     * @param array $data Product data
     * @return array Result with success status and product ID or errors
     */
    public function createProduct(array $data): array {
        $errors = $this->productModel->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $productId = $this->productModel->create($data);
            
            if ($productId) {
                return [
                    'success' => true,
                    'product_id' => $productId,
                    'message' => 'Product created successfully'
                ];
            }
            
            return ['success' => false, 'errors' => ['Failed to create product']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Update an existing product
     * @param int $id Product ID
     * @param array $data Updated product data
     * @return array Result with success status
     */
    public function updateProduct(int $id, array $data): array {
        $product = $this->productModel->find($id);
        if (!$product) {
            return ['success' => false, 'errors' => ['Product not found']];
        }
        
        $errors = $this->productModel->validate($data, true, $id);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        try {
            $result = $this->productModel->update($id, $data);
            
            if ($result) {
                return ['success' => true, 'message' => 'Product updated successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to update product']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }

    /**
     * Delete a product with stock check
     * @param int $id Product ID
     * @return array Result with success status
     */
    public function deleteProduct(int $id): array {
        $product = $this->productModel->find($id);
        if (!$product) {
            return ['success' => false, 'errors' => ['Product not found']];
        }
        
        if ($this->productModel->hasStock($id)) {
            return [
                'success' => false,
                'errors' => ['Cannot delete product with existing stock. Please remove all stock first.']
            ];
        }
        
        try {
            $result = $this->productModel->delete($id);
            
            if ($result) {
                return ['success' => true, 'message' => 'Product deleted successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to delete product']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Get product by ID
     */
    public function getProduct(int $id): ?array {
        $product = $this->productModel->find($id);
        return $product ?: null;
    }
    
    /**
     * Get product with category info
     */
    public function getProductWithCategory(int $id): ?array {
        $product = $this->productModel->getWithCategory($id);
        return $product ?: null;
    }
    
    /**
     * Get product by SKU
     */
    public function getProductBySku(string $sku): ?array {
        $product = $this->productModel->findBySku($sku);
        return $product ?: null;
    }
    
    /**
     * Get all products
     */
    public function getAllProducts(?string $status = null): array {
        return $this->productModel->getAllWithCategory($status);
    }
    
    /**
     * Get products by category
     */
    public function getProductsByCategory(int $categoryId): array {
        return $this->productModel->getByCategory($categoryId);
    }
    
    /**
     * Search products
     */
    public function searchProducts(?string $keyword = null, ?int $categoryId = null, ?string $status = null): array {
        return $this->productModel->search($keyword, $categoryId, $status);
    }
    
    /**
     * Get active products
     */
    public function getActiveProducts(): array {
        return $this->productModel->getActiveProducts();
    }
    
    /**
     * Get low stock products
     */
    public function getLowStockProducts(): array {
        return $this->productModel->getLowStockProducts();
    }
    
    /**
     * Get product stock levels across warehouses
     */
    public function getProductStockLevels(int $productId): array {
        return $this->productModel->getStockLevels($productId);
    }
    
    /**
     * Get total stock for a product
     */
    public function getProductTotalStock(int $productId): array {
        return $this->productModel->getTotalStock($productId);
    }
    
    /**
     * Update product status
     */
    public function updateProductStatus(int $id, string $status): array {
        $validStatuses = [
            SarInvProduct::STATUS_ACTIVE,
            SarInvProduct::STATUS_INACTIVE,
            SarInvProduct::STATUS_DISCONTINUED
        ];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'errors' => ['Invalid status value']];
        }
        
        return $this->updateProduct($id, ['status' => $status]);
    }
    
    /**
     * Update product specifications
     */
    public function updateProductSpecifications(int $id, array $specifications): array {
        try {
            $result = $this->productModel->setSpecifications($id, $specifications);
            
            if ($result) {
                return ['success' => true, 'message' => 'Specifications updated successfully'];
            }
            
            return ['success' => false, 'errors' => ['Failed to update specifications']];
        } catch (Exception $e) {
            return ['success' => false, 'errors' => [$e->getMessage()]];
        }
    }
    
    /**
     * Get product specifications
     */
    public function getProductSpecifications(int $productId): array {
        return $this->productModel->getSpecifications($productId);
    }
    
    /**
     * Check if product can be deleted
     */
    public function canDeleteProduct(int $id): array {
        $reasons = [];
        
        if ($this->productModel->hasStock($id)) {
            $reasons[] = 'Product has existing stock';
        }
        
        return [
            'can_delete' => empty($reasons),
            'reasons' => $reasons
        ];
    }
    
    /**
     * Get product audit history
     */
    public function getProductAuditHistory(int $productId, int $limit = 50): array {
        return $this->auditLog->getLogsForRecord('sar_inv_products', $productId, $limit);
    }
    
    /**
     * Get category audit history
     */
    public function getCategoryAuditHistory(int $categoryId, int $limit = 50): array {
        return $this->auditLog->getLogsForRecord('sar_inv_product_categories', $categoryId, $limit);
    }
}
?>
