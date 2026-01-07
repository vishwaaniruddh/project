<?php
/**
 * Unit Tests for SarInvWarehouse Model
 * Tests CRUD operations, deletion protection, and company isolation
 * Requirements: 1.1, 1.2, 1.3, 1.4
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SarInvWarehouse.php';
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvProductCategory.php';

class SarInvWarehouseTest {
    private $db;
    private $model;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testWarehouseId;
    private $testProductId;
    private $testCategoryId;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->model = new SarInvWarehouse();
        
        // Set up session for company isolation
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['company_id'] = 1;
        $_SESSION['user_id'] = 1;
    }
    
    public function runAllTests() {
        echo "🧪 Running SarInvWarehouse Model Unit Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Run tests
        $this->testCreateOperation();
        $this->testReadOperation();
        $this->testUpdateOperation();
        $this->testDeletionProtection();
        $this->testCompanyIsolation();
        $this->testValidation();
        
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
        
        // Create test category for products
        $categoryModel = new SarInvProductCategory();
        $this->testCategoryId = $categoryModel->create([
            'name' => 'Test Category for Warehouse Tests',
            'status' => 'active'
        ]);
        
        // Create test product
        $productModel = new SarInvProduct();
        $this->testProductId = $productModel->create([
            'name' => 'Test Product for Warehouse Tests',
            'sku' => 'TEST-WH-' . time(),
            'category_id' => $this->testCategoryId,
            'status' => 'active'
        ]);
        
        echo "Test data setup complete.\n\n";
    }
    
    private function cleanupTestData() {
        echo "\nCleaning up test data...\n";
        
        // Delete test stock entries
        if ($this->testWarehouseId) {
            $stmt = $this->db->prepare("DELETE FROM sar_inv_stock WHERE warehouse_id = ?");
            $stmt->execute([$this->testWarehouseId]);
        }
        
        // Delete test warehouse
        if ($this->testWarehouseId) {
            $stmt = $this->db->prepare("DELETE FROM sar_inv_warehouses WHERE id = ?");
            $stmt->execute([$this->testWarehouseId]);
        }
        
        // Delete test product
        if ($this->testProductId) {
            $stmt = $this->db->prepare("DELETE FROM sar_inv_products WHERE id = ?");
            $stmt->execute([$this->testProductId]);
        }
        
        // Delete test category
        if ($this->testCategoryId) {
            $stmt = $this->db->prepare("DELETE FROM sar_inv_product_categories WHERE id = ?");
            $stmt->execute([$this->testCategoryId]);
        }
        
        // Clean up any test warehouses created during tests
        $stmt = $this->db->prepare("DELETE FROM sar_inv_warehouses WHERE code LIKE 'TEST-%'");
        $stmt->execute();
        
        echo "Test data cleanup complete.\n";
    }
    
    private function testCreateOperation() {
        echo "\n📝 CRUD OPERATIONS - CREATE\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create Warehouse with valid data", function() {
            $data = [
                'name' => 'Test Warehouse',
                'code' => 'TEST-WH-' . time(),
                'location' => 'Test Location',
                'address' => 'Test Address',
                'capacity' => 1000,
                'status' => 'active'
            ];
            
            $this->testWarehouseId = $this->model->create($data);
            return $this->testWarehouseId !== false && is_numeric($this->testWarehouseId);
        });
        
        $this->test("Created warehouse has unique ID", function() {
            $warehouse = $this->model->find($this->testWarehouseId);
            return $warehouse && $warehouse['id'] == $this->testWarehouseId;
        });
    }
    
    private function testReadOperation() {
        echo "\n🔍 CRUD OPERATIONS - READ\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Find existing warehouse by ID", function() {
            $warehouse = $this->model->find($this->testWarehouseId);
            return $warehouse !== false && $warehouse['id'] == $this->testWarehouseId;
        });
        
        $this->test("Find warehouse by code", function() {
            $warehouse = $this->model->find($this->testWarehouseId);
            $foundWarehouse = $this->model->findByCode($warehouse['code']);
            return $foundWarehouse && $foundWarehouse['id'] == $this->testWarehouseId;
        });
        
        $this->test("Get all warehouses returns array", function() {
            $warehouses = $this->model->findAll();
            return is_array($warehouses);
        });
        
        $this->test("Get active warehouses", function() {
            $warehouses = $this->model->getActiveWarehouses();
            return is_array($warehouses);
        });
        
        $this->test("Find non-existent warehouse returns false", function() {
            $warehouse = $this->model->find(999999);
            return $warehouse === false;
        });
    }
    
    private function testUpdateOperation() {
        echo "\n✏️ CRUD OPERATIONS - UPDATE\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Update warehouse information", function() {
            $updateData = [
                'name' => 'Updated Test Warehouse',
                'location' => 'Updated Location',
                'capacity' => 2000
            ];
            
            $result = $this->model->update($this->testWarehouseId, $updateData);
            if (!$result) return false;
            
            $warehouse = $this->model->find($this->testWarehouseId);
            return $warehouse && 
                   $warehouse['name'] == 'Updated Test Warehouse' &&
                   $warehouse['location'] == 'Updated Location' &&
                   $warehouse['capacity'] == 2000;
        });
        
        $this->test("Update warehouse status", function() {
            $result = $this->model->update($this->testWarehouseId, ['status' => 'maintenance']);
            if (!$result) return false;
            
            $warehouse = $this->model->find($this->testWarehouseId);
            return $warehouse && $warehouse['status'] == 'maintenance';
        });
    }
    
    private function testDeletionProtection() {
        echo "\n🛡️ DELETION PROTECTION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Prevent deletion when warehouse has inventory", function() {
            // First, reset warehouse status to active
            $this->model->update($this->testWarehouseId, ['status' => 'active']);
            
            // Add stock to warehouse
            $stockModel = new SarInvStock();
            $stockModel->addStock($this->testProductId, $this->testWarehouseId, 10, 'test', null, 'Test stock');
            
            // Try to delete - should fail
            $result = $this->model->safeDelete($this->testWarehouseId);
            
            // Clean up stock
            $stmt = $this->db->prepare("DELETE FROM sar_inv_stock WHERE warehouse_id = ? AND product_id = ?");
            $stmt->execute([$this->testWarehouseId, $this->testProductId]);
            
            return $result['success'] === false && 
                   strpos($result['error'], 'inventory') !== false;
        });
        
        $this->test("Allow deletion when warehouse is empty", function() {
            // Create a new empty warehouse for deletion test
            $emptyWarehouseId = $this->model->create([
                'name' => 'Empty Warehouse for Deletion',
                'code' => 'TEST-EMPTY-' . time(),
                'status' => 'active'
            ]);
            
            $result = $this->model->safeDelete($emptyWarehouseId);
            return $result['success'] === true;
        });
        
        $this->test("hasInventory returns true when stock exists", function() {
            // Add stock
            $stockModel = new SarInvStock();
            $stockModel->addStock($this->testProductId, $this->testWarehouseId, 5, 'test', null, 'Test');
            
            $hasInventory = $this->model->hasInventory($this->testWarehouseId);
            
            // Clean up
            $stmt = $this->db->prepare("DELETE FROM sar_inv_stock WHERE warehouse_id = ? AND product_id = ?");
            $stmt->execute([$this->testWarehouseId, $this->testProductId]);
            
            return $hasInventory === true;
        });
        
        $this->test("hasInventory returns false when empty", function() {
            return $this->model->hasInventory($this->testWarehouseId) === false;
        });
    }
    
    private function testCompanyIsolation() {
        echo "\n🔒 COMPANY ISOLATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Warehouse created with current company ID", function() {
            $warehouse = $this->model->find($this->testWarehouseId);
            return $warehouse && $warehouse['company_id'] == $_SESSION['company_id'];
        });
        
        $this->test("Cannot access warehouse from different company", function() {
            // Create warehouse with different company
            $stmt = $this->db->prepare("INSERT INTO sar_inv_warehouses (name, code, company_id, status) VALUES (?, ?, ?, 'active')");
            $stmt->execute(['Other Company Warehouse', 'TEST-OTHER-' . time(), 999]);
            $otherWarehouseId = $this->db->lastInsertId();
            
            // Try to find it with current company session
            $warehouse = $this->model->find($otherWarehouseId);
            
            // Clean up
            $stmt = $this->db->prepare("DELETE FROM sar_inv_warehouses WHERE id = ?");
            $stmt->execute([$otherWarehouseId]);
            
            return $warehouse === false;
        });
    }
    
    private function testValidation() {
        echo "\n✅ VALIDATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Validation fails for missing name", function() {
            $errors = $this->model->validate(['code' => 'TEST']);
            return !empty($errors) && in_array('Warehouse name is required', $errors);
        });
        
        $this->test("Validation fails for missing code", function() {
            $errors = $this->model->validate(['name' => 'Test']);
            return !empty($errors) && in_array('Warehouse code is required', $errors);
        });
        
        $this->test("Validation fails for duplicate code", function() {
            $warehouse = $this->model->find($this->testWarehouseId);
            $errors = $this->model->validate(['name' => 'New', 'code' => $warehouse['code']]);
            return !empty($errors) && in_array('Warehouse code already exists', $errors);
        });
        
        $this->test("Validation fails for negative capacity", function() {
            $errors = $this->model->validate(['name' => 'Test', 'code' => 'NEW', 'capacity' => -100]);
            return !empty($errors) && in_array('Capacity cannot be negative', $errors);
        });
        
        $this->test("Validation fails for invalid status", function() {
            $errors = $this->model->validate(['name' => 'Test', 'code' => 'NEW', 'status' => 'invalid']);
            return !empty($errors) && in_array('Invalid status value', $errors);
        });
        
        $this->test("Validation passes for valid data", function() {
            $errors = $this->model->validate([
                'name' => 'Valid Warehouse',
                'code' => 'VALID-' . time(),
                'capacity' => 500,
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
            echo "🎉 EXCELLENT! SarInvWarehouse model is working correctly.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Most functionality is working. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! SarInvWarehouse model needs attention.\n";
        }
    }
}

// Run the test suite if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    $testSuite = new SarInvWarehouseTest();
    $testSuite->runAllTests();
}
?>
