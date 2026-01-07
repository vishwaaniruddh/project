<?php
require_once __DIR__ . '/SarInvBaseModel.php';

/**
 * SAR Inventory Product Category Model
 * Manages hierarchical product categories with parent-child relationships
 */
class SarInvProductCategory extends SarInvBaseModel {
    protected $table = 'sar_inv_product_categories';
    
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    
    /**
     * Validate category data
     */
    public function validate($data, $isUpdate = false, $id = null) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Category name is required';
        }
        
        if (isset($data['parent_id']) && $data['parent_id']) {
            // Check if parent exists
            $parent = $this->find($data['parent_id']);
            if (!$parent) {
                $errors[] = 'Parent category does not exist';
            }
            
            // Prevent circular reference
            if ($id && $data['parent_id'] == $id) {
                $errors[] = 'Category cannot be its own parent';
            }
            
            // Check if setting parent would create circular reference
            if ($id && $this->wouldCreateCircularReference($id, $data['parent_id'])) {
                $errors[] = 'This would create a circular reference';
            }
        }
        
        if (isset($data['status']) && !in_array($data['status'], [self::STATUS_ACTIVE, self::STATUS_INACTIVE])) {
            $errors[] = 'Invalid status value';
        }
        
        return $errors;
    }
    
    /**
     * Check if setting parent would create circular reference
     */
    protected function wouldCreateCircularReference($categoryId, $newParentId) {
        $descendants = $this->getAllDescendantIds($categoryId);
        return in_array($newParentId, $descendants);
    }
    
    /**
     * Get all descendant IDs of a category
     */
    public function getAllDescendantIds($categoryId) {
        $descendants = [];
        $children = $this->getChildren($categoryId);
        
        foreach ($children as $child) {
            $descendants[] = $child['id'];
            $descendants = array_merge($descendants, $this->getAllDescendantIds($child['id']));
        }
        
        return $descendants;
    }
    
    /**
     * Get direct children of a category
     */
    public function getChildren($parentId) {
        $sql = "SELECT * FROM {$this->table} WHERE parent_id = ? AND company_id = ? ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$parentId, $this->getCurrentCompanyId()]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get parent category
     */
    public function getParent($categoryId) {
        $category = $this->find($categoryId);
        if (!$category || !$category['parent_id']) {
            return null;
        }
        return $this->find($category['parent_id']);
    }
    
    /**
     * Get full path from root to category
     */
    public function getFullPath($categoryId) {
        $path = [];
        $current = $this->find($categoryId);
        
        while ($current) {
            array_unshift($path, $current);
            if ($current['parent_id']) {
                $current = $this->find($current['parent_id']);
            } else {
                break;
            }
        }
        
        return $path;
    }
    
    /**
     * Get full path as string
     */
    public function getFullPathString($categoryId, $separator = ' > ') {
        $path = $this->getFullPath($categoryId);
        return implode($separator, array_column($path, 'name'));
    }
    
    /**
     * Get root categories (no parent)
     */
    public function getRootCategories() {
        $sql = "SELECT * FROM {$this->table} WHERE parent_id IS NULL AND company_id = ? ORDER BY name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->getCurrentCompanyId()]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get category tree structure
     */
    public function getCategoryTree($parentId = null) {
        if ($parentId === null) {
            $categories = $this->getRootCategories();
        } else {
            $categories = $this->getChildren($parentId);
        }
        
        foreach ($categories as &$category) {
            $category['children'] = $this->getCategoryTree($category['id']);
        }
        
        return $categories;
    }
    
    /**
     * Get flat list with indentation for display
     */
    public function getFlatTreeList($parentId = null, $level = 0) {
        $result = [];
        
        if ($parentId === null) {
            $categories = $this->getRootCategories();
        } else {
            $categories = $this->getChildren($parentId);
        }
        
        foreach ($categories as $category) {
            $category['display_level'] = $level;
            $category['display_name'] = str_repeat('— ', $level) . $category['name'];
            $result[] = $category;
            $result = array_merge($result, $this->getFlatTreeList($category['id'], $level + 1));
        }
        
        return $result;
    }
    
    /**
     * Check if category has products
     */
    public function hasProducts($categoryId) {
        $sql = "SELECT COUNT(*) FROM sar_inv_products WHERE category_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Check if category has children
     */
    public function hasChildren($categoryId) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE parent_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categoryId]);
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Safe delete - prevents deletion if category has products or children
     */
    public function safeDelete($categoryId) {
        if ($this->hasProducts($categoryId)) {
            return ['success' => false, 'error' => 'Cannot delete category with assigned products'];
        }
        
        if ($this->hasChildren($categoryId)) {
            return ['success' => false, 'error' => 'Cannot delete category with child categories'];
        }
        
        $result = $this->delete($categoryId);
        return ['success' => $result, 'error' => $result ? null : 'Failed to delete category'];
    }
    
    /**
     * Create category with automatic level calculation
     */
    public function create($data) {
        // Calculate level based on parent
        if (!empty($data['parent_id'])) {
            $parent = $this->find($data['parent_id']);
            $data['level'] = $parent ? $parent['level'] + 1 : 0;
        } else {
            $data['level'] = 0;
        }
        
        return parent::create($data);
    }
    
    /**
     * Update category with level recalculation
     */
    public function update($id, $data) {
        // Recalculate level if parent changed
        if (isset($data['parent_id'])) {
            if ($data['parent_id']) {
                $parent = $this->find($data['parent_id']);
                $data['level'] = $parent ? $parent['level'] + 1 : 0;
            } else {
                $data['level'] = 0;
            }
        }
        
        $result = parent::update($id, $data);
        
        // Update children levels if this category's level changed
        if ($result && isset($data['level'])) {
            $this->updateChildrenLevels($id);
        }
        
        return $result;
    }
    
    /**
     * Recursively update children levels
     */
    protected function updateChildrenLevels($parentId) {
        $parent = $this->find($parentId);
        if (!$parent) return;
        
        $children = $this->getChildren($parentId);
        foreach ($children as $child) {
            $newLevel = $parent['level'] + 1;
            if ($child['level'] != $newLevel) {
                $this->db->prepare("UPDATE {$this->table} SET level = ? WHERE id = ?")
                    ->execute([$newLevel, $child['id']]);
                $this->updateChildrenLevels($child['id']);
            }
        }
    }
    
    /**
     * Get active categories
     */
    public function getActiveCategories() {
        return $this->findAll(['status' => self::STATUS_ACTIVE]);
    }
    
    /**
     * Search categories
     */
    public function search($keyword) {
        $sql = "SELECT * FROM {$this->table} WHERE company_id = ? AND name LIKE ? ORDER BY level, name";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->getCurrentCompanyId(), "%{$keyword}%"]);
        return $stmt->fetchAll();
    }
}
?>
