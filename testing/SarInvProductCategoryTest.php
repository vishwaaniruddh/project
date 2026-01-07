<?php
/**
 * Unit Tests for SarInvProductCategory and SarInvProduct Models
 * Tests hierarchical category operations, product search/filtering, and deletion protection
 * Requirements: 2.1, 2.2, 2.3, 2.5
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SarInvProductCategory.php';
require_once __DIR__ . '/../models/SarInvProduct.php';

class SarInvProductCategoryTest {
    private $db;
    private $categoryModel;
    private $productModel;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testCategoryIds = [];
    private $testProductIds = [];
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->categoryModel = new SarInvProductCategory();
        $this->productModel = new SarInvProduct();
        
        // Set up session for company isolation
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['company_id'] = 1;
        $_SESSION['user_id'] = 1;
    }
    
    public function runAllTests() {
        echo "🧪 Running SarInvProductCategory & SarInvProduct Unit Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Run tests
        $this->testCategoryHierarchy();
        $this->testCategoryDeletionProtection();
        $this->testProductCRUD();
        $this->testProductSearchAndFilter();
        $this->testProductValidation();
        
        // Cleanup test data
        $this->cleanupTestData();
        
        // Print results
        $this->printResults();
        
        return $this->passedTests === $this->totalTests;
    }
    
    private function test($testName, $callback) {
        $this->totalTests++;
        echo "Testing: $testName... ";
        
        try {
            $result = $callback();
            if ($result) {
                echo "✅ PASS\n";
                $this->passedTests++;
                $this->results[] = ['test' => $testName, 'status' => 'PASS', 'message' => ''];
            } else {
                echo "❌ FAIL\n";
                $this->results[] = ['test' => $testName, 'status' => 'FAIL', 'message' => 'Test returned false'];
            }
        } catch (Exception $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
            $this->results[] = ['test' => $testName, 'status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }
    
    private function setupTestData() {
        echo "Setting up test data...\n";
        
        // Create parent category
        $parentId = $this->categoryModel->create([
            'name' => 'Test Parent Category',
            'status' => 'active'
        ]);
        $this->testCategoryIds['parent'] = $parentId;
        
        // Create child category
        $childId = $this->categoryModel->create([
            'name' => 'Test Child Category',
            'parent_id' => $parentId,
            'status' => 'active'
        ]);
        $this->testCategoryIds['child'] = $childId;
        
        // Create grandchild category
        $grandchildId = $this->categoryModel->create([
            'name' => 'Test Grandchild Category',
            'parent_id' => $childId,
            'status' => 'active'
        ]);
        $this->testCategoryIds['grandchild'] = $grandchildId;
        
        echo "Test data setup complete.\n\n";
    }
    
    private function cleanupTestData() {
        echo "\nCleaning up test data...\n";
        
        // Delete test products
        foreach ($this->testProductIds as $id) {
            $stmt = $this->db->prepare("DELETE FROM sar_inv_products WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        // Delete test categories in reverse order (grandchild, child, parent)
        $stmt = $this->db->prepare("DELETE FROM sar_inv_product_categories WHERE id = ?");
        if (isset($this->testCategoryIds['grandchild'])) {
            $stmt->execute([$this->testCategoryIds['grandchild']]);
        }
        if (isset($this->testCategoryIds['child'])) {
            $stmt->execute([$this->testCategoryIds['child']]);
        }
        if (isset($this->testCategoryIds['parent'])) {
            $stmt->execute([$this->testCategoryIds['parent']]);
        }
        
        // Clean up any remaining test data
        $stmt = $this->db->prepare("DELETE FROM sar_inv_products WHERE sku LIKE 'TEST-PROD-%'");
        $stmt->execute();
        $stmt = $this->db->prepare("DELETE FROM sar_inv_product_categories WHERE name LIKE 'Test%Category%'");
        $stmt->execute();
        
        echo "Test data cleanup complete.\n";
    }
    
    private function testCategoryHierarchy() {
        echo "\n🌳 CATEGORY HIERARCHY\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create category with hierarchical support", function() {
            $category = $this->categoryModel->find($this->testCategoryIds['parent']);
            return $category && $category['level'] == 0;
        });
        
        $this->test("Child category has correct level", function() {
            $category = $this->categoryModel->find($this->testCategoryIds['child']);
            return $category && $category['level'] == 1;
        });
        
        $this->test("Grandchild category has correct level", function() {
            $category = $this->categoryModel->find($this->testCategoryIds['grandchild']);
            return $category && $category['level'] == 2;
        });
        
        $this->test("Get children of parent category", function() {
            $children = $this->categoryModel->getChildren($this->testCategoryIds['parent']);
            return is_array($children) && count($children) >= 1;
        });
        
        $this->test("Get parent of child category", function() {
            $parent = $this->categoryModel->getParent($this->testCategoryIds['child']);
            return $parent && $parent['id'] == $this->testCategoryIds['parent'];
        });
        
        $this->test("Get full path from root to grandchild", function() {
            $path = $this->categoryModel->getFullPath($this->testCategoryIds['grandchild']);
            return is_array($path) && count($path) == 3;
        });
        
        $this->test("Get full path as string", function() {
            $pathString = $this->categoryModel->getFullPathString($this->testCategoryIds['grandchild']);
            return strpos($pathString, 'Test Parent Category') !== false &&
                   strpos($pathString, 'Test Child Category') !== false;
        });
        
        $this->test("Get root categories", function() {
            $roots = $this->categoryModel->getRootCategories();
            return is_array($roots);
        });
        
        $this->test("Get category tree structure", function() {
            $tree = $this->categoryModel->getCategoryTree();
            return is_array($tree);
        });
        
        $this->test("Prevent circular reference - self parent", function() {
            $errors = $this->categoryModel->validate([
                'name' => 'Test',
                'parent_id' => $this->testCategoryIds['parent']
            ], true, $this->testCategoryIds['parent']);
            return in_array('Category cannot be its own parent', $errors);
        });
    }
    
    private function testCategoryDeletionProtection() {
        echo "\n🛡️ CATEGORY DELETION PROTECTION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Prevent deletion when category has children", function() {
            $result = $this->categoryModel->safeDelete($this->testCategoryIds['parent']);
            return $result['success'] === false && 
                   strpos($result['error'], 'child') !== false;
        });
        
        $this->test("Prevent deletion when category has products", function() {
            // Create a product in the grandchild category
            $productId = $this->productModel->create([
                'name' => 'Test Product for Deletion',
                'sku' => 'TEST-PROD-DEL-' . time(),
                'category_id' => $this->testCategoryIds['grandchild'],
                'status' => 'active'
            ]);
            $this->testProductIds[] = $productId;
            
            $result = $this->categoryModel->safeDelete($this->testCategoryIds['grandchild']);
            
            // Clean up product
            $stmt = $this->db->prepare("DELETE FROM sar_inv_products WHERE id = ?");
            $stmt->execute([$productId]);
            array_pop($this->testProductIds);
            
            return $result['success'] === false && 
                   strpos($result['error'], 'products') !== false;
        });
        
        $this->test("hasProducts returns true when products exist", function() {
            // Create a product
            $productId = $this->productModel->create([
                'name' => 'Test Product',
                'sku' => 'TEST-PROD-HP-' . time(),
                'category_id' => $this->testCategoryIds['grandchild'],
                'status' => 'active'
            ]);
            $this->testProductIds[] = $productId;
            
            $hasProducts = $this->categoryModel->hasProducts($this->testCategoryIds['grandchild']);
            
            // Clean up
            $stmt = $this->db->prepare("DELETE FROM sar_inv_products WHERE id = ?");
            $stmt->execute([$productId]);
            array_pop($this->testProductIds);
            
            return $hasProducts === true;
        });
        
        $this->test("hasChildren returns true when children exist", function() {
            return $this->categoryModel->hasChildren($this->testCategoryIds['parent']) === true;
        });
    }
    
    private function testProductCRUD() {
        echo "\n📦 PRODUCT CRUD OPERATIONS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create product with valid data", function() {
            $productId = $this->productModel->create([
                'name' => 'Test Product CRUD',
                'sku' => 'TEST-PROD-CRUD-' . time(),
                'category_id' => $this->testCategoryIds['grandchild'],
                'description' => 'Test description',
                'unit_of_measure' => 'Nos',
                'minimum_stock_level' => 10,
                'status' => 'active'
            ]);
            $this->testProductIds[] = $productId;
            return $productId !== false && is_numeric($productId);
        });
        
        $this->test("Find product by ID", function() {
            $productId = end($this->testProductIds);
            $product = $this->productModel->find($productId);
            return $product && $product['id'] == $productId;
        });
        
        $this->test("Find product by SKU", function() {
            $productId = end($this->testProductIds);
            $product = $this->productModel->find($productId);
            $foundProduct = $this->productModel->findBySku($product['sku']);
            return $foundProduct && $foundProduct['id'] == $productId;
        });
        
        $this->test("Update product information", function() {
            $productId = end($this->testProductIds);
            $result = $this->productModel->update($productId, [
                'name' => 'Updated Test Product',
                'description' => 'Updated description'
            ]);
            
            $product = $this->productModel->find($productId);
            return $result && $product['name'] == 'Updated Test Product';
        });
        
        $this->test("Get products by category", function() {
            $products = $this->productModel->getByCategory($this->testCategoryIds['grandchild']);
            return is_array($products) && count($products) >= 1;
        });
        
        $this->test("Get product with category info", function() {
            $productId = end($this->testProductIds);
            $product = $this->productModel->getWithCategory($productId);
            return $product && isset($product['category_name']);
        });
    }
    
    private function testProductSearchAndFilter() {
        echo "\n🔍 PRODUCT SEARCH AND FILTER\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Search products by name", function() {
            $results = $this->productModel->search('Updated Test');
            return is_array($results) && count($results) >= 1;
        });
        
        $this->test("Search products by SKU", function() {
            $productId = end($this->testProductIds);
            $product = $this->productModel->find($productId);
            $results = $this->productModel->search($product['sku']);
            return is_array($results) && count($results) >= 1;
        });
        
        $this->test("Filter products by category", function() {
            $results = $this->productModel->search(null, $this->testCategoryIds['grandchild']);
            return is_array($results);
        });
        
        $this->test("Filter products by status", function() {
            $results = $this->productModel->search(null, null, 'active');
            return is_array($results);
        });
        
        $this->test("Get active products", function() {
            $products = $this->productModel->getActiveProducts();
            return is_array($products);
        });
        
        $this->test("Get all products with category", function() {
            $products = $this->productModel->getAllWithCategory();
            return is_array($products);
        });
    }
    
    private function testProductValidation() {
        echo "\n✅ PRODUCT VALIDATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Validation fails for missing name", function() {
            $errors = $this->productModel->validate(['sku' => 'TEST']);
            return !empty($errors) && in_array('Product name is required', $errors);
        });
        
        $this->test("Validation fails for missing SKU", function() {
            $errors = $this->productModel->validate(['name' => 'Test']);
            return !empty($errors) && in_array('SKU is required', $errors);
        });
        
        $this->test("Validation fails for duplicate SKU", function() {
            $productId = end($this->testProductIds);
            $product = $this->productModel->find($productId);
            $errors = $this->productModel->validate(['name' => 'New', 'sku' => $product['sku']]);
            return !empty($errors) && in_array('SKU already exists', $errors);
        });
        
        $this->test("Validation fails for invalid category", function() {
            $errors = $this->productModel->validate([
                'name' => 'Test',
                'sku' => 'NEW-SKU',
                'category_id' => 999999
            ]);
            return !empty($errors) && in_array('Invalid category', $errors);
        });
        
        $this->test("Validation fails for negative minimum stock", function() {
            $errors = $this->productModel->validate([
                'name' => 'Test',
                'sku' => 'NEW-SKU',
                'minimum_stock_level' => -10
            ]);
            return !empty($errors) && in_array('Minimum stock level cannot be negative', $errors);
        });
        
        $this->test("Validation passes for valid data", function() {
            $errors = $this->productModel->validate([
                'name' => 'Valid Product',
                'sku' => 'VALID-SKU-' . time(),
                'category_id' => $this->testCategoryIds['grandchild'],
                'status' => 'active'
            ]);
            return empty($errors);
        });
    }
    
    private function printResults() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        
        $failedTests = $this->totalTests - $this->passedTests;
        $successRate = $this->totalTests > 0 ? ($this->passedTests / $this->totalTests) * 100 : 0;
        
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests} ✅\n";
        echo "Failed: {$failedTests} ❌\n";
        echo "Success Rate: " . number_format($successRate, 1) . "%\n\n";
        
        if ($failedTests > 0) {
            echo "❌ FAILED TESTS:\n";
            echo "-" . str_repeat("-", 40) . "\n";
            foreach ($this->results as $result) {
                if ($result['status'] !== 'PASS') {
                    echo "• {$result['test']}: {$result['status']}";
                    if ($result['message']) {
                        echo " - {$result['message']}";
                    }
                    echo "\n";
                }
            }
        }
        
        echo "\n";
        if ($successRate >= 90) {
            echo "🎉 EXCELLENT! Product and Category models are working correctly.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Most functionality is working. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! Product and Category models need attention.\n";
        }
    }
}

// Run the test suite if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    $testSuite = new SarInvProductCategoryTest();
    $testSuite->runAllTests();
}
?>
