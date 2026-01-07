<?php
require_once __DIR__ . '/SarInvBaseModel.php';

/**
 * SAR Inventory Product Model
 * Manages products with category relationships and JSON specifications
 */
class SarInvProduct extends SarInvBaseModel {
    protected $table = 'sar_inv_products';
    
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_DISCONTINUED = 'discontinued';
    
    /**
     * Validate product data
     */
    public function validate($data, $isUpdate = false, $id = null) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Product name is required';
        }
        
        if (empty($data['sku'])) {
            $errors[] = 'SKU is required';
        } else {
            if ($this->skuExists($data['sku'], $id)) {
                $errors[] = 'SKU already exists';
            }
        }
        
        if (isset($data['category_id']) && $data['category_id']) {
            $categoryModel = new SarInvProductCategory();
            $category = $categoryModel->find($data['category_id']);
            if (!$category) {
                $errors[] = 'Invalid category';
            }
        }
        
        if (isset($data['minimum_stock_level']) && $data['minimum_stock_level'] < 0) {
            $errors[] = 'Minimum stock level cannot be negative';
        }
        
        if (isset($data['status']) && !in_array($data['status'], [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_DISCONTINUED])) {
            $errors[] = 'Invalid status value';
        }
        
        return $errors;
    }
    
    /**
     * Check if SKU exists
     */
    public function skuExists($sku, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE sku = ? AND company_id = ?";
        $params = [$sku, $this->getCurrentCompanyId()];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Find product by SKU
     */
    public function findBySku($sku) {
        $sql = "SELECT * FROM {$this->table} WHERE sku = ? AND company_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sku, $this->getCurrentCompanyId()]);
        return $stmt->fetch();
    }
    
    /**
     * Get products by category
     */
    public function getByCategory($categoryId) {
        return $this->findAll(['category_id' => $categoryId]);
    }
    
    /**
     * Get active products
     */
    public function getActiveProducts() {
        return $this->findAll(['status' => self::STATUS_ACTIVE]);
    }
    
    /**
     * Search products by name, SKU, or description
     */
    public function search($keyword, $categoryId = null, $status = null) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p
                LEFT JOIN sar_inv_product_categories c ON p.category_id = c.id
                WHERE p.company_id = ?";
        $params = [$this->getCurrentCompanyId()];
        
        if ($keyword) {
            $sql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.description LIKE ?)";
            $keyword = "%{$keyword}%";
            $params[] = $keyword;
            $params[] = $keyword;
            $params[] = $keyword;
        }
        
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY p.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get product with category info
     */
    public function getWithCategory($productId) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p
                LEFT JOIN sar_inv_product_categories c ON p.category_id = c.id
                WHERE p.id = ? AND p.company_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId, $this->getCurrentCompanyId()]);
        return $stmt->fetch();
    }
    
    /**
     * Get all products with category info
     */
    public function getAllWithCategory($status = null) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p
                LEFT JOIN sar_inv_product_categories c ON p.category_id = c.id
                WHERE p.company_id = ?";
        $params = [$this->getCurrentCompanyId()];
        
        if ($status) {
            $sql .= " AND p.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY p.name";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Set specifications (JSON field)
     */
    public function setSpecifications($productId, $specifications) {
        $data = ['specifications' => json_encode($specifications)];
        return $this->update($productId, $data);
    }
    
    /**
     * Get specifications (decoded JSON)
     */
    public function getSpecifications($productId) {
        $product = $this->find($productId);
        if (!$product || !$product['specifications']) {
            return [];
        }
        return json_decode($product['specifications'], true) ?: [];
    }
    
    /**
     * Create product with JSON specifications handling
     */
    public function create($data) {
        if (isset($data['specifications']) && is_array($data['specifications'])) {
            $data['specifications'] = json_encode($data['specifications']);
        }
        return parent::create($data);
    }
    
    /**
     * Update product with JSON specifications handling
     */
    public function update($id, $data) {
        if (isset($data['specifications']) && is_array($data['specifications'])) {
            $data['specifications'] = json_encode($data['specifications']);
        }
        return parent::update($id, $data);
    }
    
    /**
     * Get products with low stock
     */
    public function getLowStockProducts() {
        $sql = "SELECT p.*, 
                    COALESCE(SUM(s.quantity), 0) as total_stock,
                    COALESCE(SUM(s.reserved_quantity), 0) as total_reserved,
                    (COALESCE(SUM(s.quantity), 0) - COALESCE(SUM(s.reserved_quantity), 0)) as available_stock
                FROM {$this->table} p
                LEFT JOIN sar_inv_stock s ON p.id = s.product_id
                WHERE p.company_id = ? AND p.status = 'active'
                GROUP BY p.id
                HAVING available_stock < p.minimum_stock_level
                ORDER BY available_stock ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->getCurrentCompanyId()]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get product stock across all warehouses
     */
    public function getStockLevels($productId) {
        $sql = "SELECT s.*, w.name as warehouse_name, w.code as warehouse_code
                FROM sar_inv_stock s
                JOIN sar_inv_warehouses w ON s.warehouse_id = w.id
                WHERE s.product_id = ?
                ORDER BY w.name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get total stock for a product
     */
    public function getTotalStock($productId) {
        $sql = "SELECT 
                    COALESCE(SUM(quantity), 0) as total_quantity,
                    COALESCE(SUM(reserved_quantity), 0) as total_reserved
                FROM sar_inv_stock 
                WHERE product_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        $result = $stmt->fetch();
        
        return [
            'total_quantity' => floatval($result['total_quantity']),
            'total_reserved' => floatval($result['total_reserved']),
            'available' => floatval($result['total_quantity']) - floatval($result['total_reserved'])
        ];
    }
    
    /**
     * Check if product has stock
     */
    public function hasStock($productId) {
        $sql = "SELECT COUNT(*) FROM sar_inv_stock WHERE product_id = ? AND quantity > 0";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Safe delete - prevents deletion if product has stock
     */
    public function safeDelete($productId) {
        if ($this->hasStock($productId)) {
            return ['success' => false, 'error' => 'Cannot delete product with existing stock'];
        }
        
        $result = $this->delete($productId);
        return ['success' => $result, 'error' => $result ? null : 'Failed to delete product'];
    }
}
?>
