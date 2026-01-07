<?php
/**
 * Unit Tests for SarInvStock Model
 * Tests stock entry, level updates, reservation/release, insufficient stock validation, and optimistic locking
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 11.2
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvProductCategory.php';
require_once __DIR__ . '/../models/SarInvWarehouse.php';

class SarInvStockTest {
    private $db;
    private $stockModel;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testProductId;
    private $testWarehouseId;
    private $testCategoryId;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->stockModel = new SarInvStock();
        
        // Set up session
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['company_id'] = 1;
        $_SESSION['user_id'] = 1;
    }
    
    public function runAllTests() {
        echo "🧪 Running SarInvStock Model Unit Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Run tests
        $this->testStockEntry();
        $this->testStockLevelUpdates();
        $this->testStockReservation();
        $this->testInsufficientStockValidation();
        $this->testStockAdjustment();
        $this->testOptimisticLocking();
        
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
        
        // Create test category
        $categoryModel = new SarInvProductCategory();
        $this->testCategoryId = $categoryModel->create([
            'name' => 'Test Category for Stock Tests',
            'status' => 'active'
        ]);
        
        // Create test product
        $productModel = new SarInvProduct();
        $this->testProductId = $productModel->create([
            'name' => 'Test Product for Stock Tests',
            'sku' => 'TEST-STOCK-' . time(),
            'category_id' => $this->testCategoryId,
            'minimum_stock_level' => 5,
            'status' => 'active'
        ]);
        
        // Create test warehouse
        $warehouseModel = new SarInvWarehouse();
        $this->testWarehouseId = $warehouseModel->create([
            'name' => 'Test Warehouse for Stock Tests',
            'code' => 'TEST-STK-WH-' . time(),
            'capacity' => 1000,
            'status' => 'active'
        ]);
        
        echo "Test data setup complete.\n\n";
    }
    
    private function cleanupTestData() {
        echo "\nCleaning up test data...\n";
        
        // Delete stock entries
        $stmt = $this->db->prepare("DELETE FROM sar_inv_stock_entries WHERE product_id = ?");
        $stmt->execute([$this->testProductId]);
        
        // Delete item history
        $stmt = $this->db->prepare("DELETE FROM sar_inv_item_history WHERE product_id = ?");
        $stmt->execute([$this->testProductId]);
        
        // Delete stock
        $stmt = $this->db->prepare("DELETE FROM sar_inv_stock WHERE product_id = ?");
        $stmt->execute([$this->testProductId]);
        
        // Delete warehouse
        $stmt = $this->db->prepare("DELETE FROM sar_inv_warehouses WHERE id = ?");
        $stmt->execute([$this->testWarehouseId]);
        
        // Delete product
        $stmt = $this->db->prepare("DELETE FROM sar_inv_products WHERE id = ?");
        $stmt->execute([$this->testProductId]);
        
        // Delete category
        $stmt = $this->db->prepare("DELETE FROM sar_inv_product_categories WHERE id = ?");
        $stmt->execute([$this->testCategoryId]);
        
        echo "Test data cleanup complete.\n";
    }
    
    private function testStockEntry() {
        echo "\n📥 STOCK ENTRY\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Add stock to warehouse", function() {
            $result = $this->stockModel->addStock(
                $this->testProductId,
                $this->testWarehouseId,
                100,
                'initial',
                null,
                'Initial stock entry'
            );
            return $result !== false;
        });
        
        $this->test("Stock level updated after entry", function() {
            $stock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            return $stock && floatval($stock['quantity']) == 100;
        });
        
        $this->test("Stock entry logged in entries table", function() {
            $entries = $this->stockModel->getStockEntries($this->testProductId, $this->testWarehouseId);
            return is_array($entries) && count($entries) >= 1;
        });
        
        $this->test("Add more stock increases quantity", function() {
            $this->stockModel->addStock($this->testProductId, $this->testWarehouseId, 50, 'restock', null, 'Restock');
            $stock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            return $stock && floatval($stock['quantity']) == 150;
        });
        
        $this->test("Reject negative quantity in addStock", function() {
            try {
                $this->stockModel->addStock($this->testProductId, $this->testWarehouseId, -10);
                return false; // Should have thrown exception
            } catch (Exception $e) {
                return strpos($e->getMessage(), 'positive') !== false;
            }
        });
    }
    
    private function testStockLevelUpdates() {
        echo "\n📊 STOCK LEVEL UPDATES\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Get available quantity", function() {
            $available = $this->stockModel->getAvailableQuantity($this->testProductId, $this->testWarehouseId);
            return $available == 150; // 100 + 50 from previous tests
        });
        
        $this->test("Remove stock from warehouse", function() {
            $result = $this->stockModel->removeStock(
                $this->testProductId,
                $this->testWarehouseId,
                30,
                'sale',
                null,
                'Stock removal'
            );
            return $result !== false;
        });
        
        $this->test("Stock level decreased after removal", function() {
            $stock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            return $stock && floatval($stock['quantity']) == 120; // 150 - 30
        });
        
        $this->test("Get warehouse stock", function() {
            $stock = $this->stockModel->getWarehouseStock($this->testWarehouseId);
            return is_array($stock) && count($stock) >= 1;
        });
        
        $this->test("Get product stock across warehouses", function() {
            $stock = $this->stockModel->getProductStock($this->testProductId);
            return is_array($stock) && count($stock) >= 1;
        });
        
        $this->test("Check sufficient stock returns true", function() {
            return $this->stockModel->hasSufficientStock($this->testProductId, $this->testWarehouseId, 50);
        });
        
        $this->test("Check sufficient stock returns false for excess", function() {
            return !$this->stockModel->hasSufficientStock($this->testProductId, $this->testWarehouseId, 500);
        });
    }
    
    private function testStockReservation() {
        echo "\n🔒 STOCK RESERVATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Reserve stock", function() {
            $result = $this->stockModel->reserve(
                $this->testProductId,
                $this->testWarehouseId,
                20,
                'order',
                1,
                'Reserved for order'
            );
            return $result !== false;
        });
        
        $this->test("Reserved quantity updated", function() {
            $stock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            return $stock && floatval($stock['reserved_quantity']) == 20;
        });
        
        $this->test("Available quantity reduced by reservation", function() {
            $available = $this->stockModel->getAvailableQuantity($this->testProductId, $this->testWarehouseId);
            return $available == 100; // 120 - 20 reserved
        });
        
        $this->test("Release reserved stock", function() {
            $result = $this->stockModel->release(
                $this->testProductId,
                $this->testWarehouseId,
                10,
                'order_cancel',
                1,
                'Order cancelled'
            );
            return $result !== false;
        });
        
        $this->test("Reserved quantity decreased after release", function() {
            $stock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            return $stock && floatval($stock['reserved_quantity']) == 10; // 20 - 10
        });
        
        $this->test("Available quantity increased after release", function() {
            $available = $this->stockModel->getAvailableQuantity($this->testProductId, $this->testWarehouseId);
            return $available == 110; // 120 - 10 reserved
        });
        
        $this->test("Cannot release more than reserved", function() {
            try {
                $this->stockModel->release($this->testProductId, $this->testWarehouseId, 100);
                return false;
            } catch (Exception $e) {
                return strpos($e->getMessage(), 'reserved') !== false;
            }
        });
    }
    
    private function testInsufficientStockValidation() {
        echo "\n⚠️ INSUFFICIENT STOCK VALIDATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Reject removal exceeding available stock", function() {
            try {
                $this->stockModel->removeStock($this->testProductId, $this->testWarehouseId, 500);
                return false;
            } catch (Exception $e) {
                return strpos($e->getMessage(), 'Insufficient') !== false;
            }
        });
        
        $this->test("Reject reservation exceeding available stock", function() {
            try {
                $this->stockModel->reserve($this->testProductId, $this->testWarehouseId, 500);
                return false;
            } catch (Exception $e) {
                return strpos($e->getMessage(), 'Insufficient') !== false;
            }
        });
        
        $this->test("Get low stock items", function() {
            $lowStock = $this->stockModel->getLowStockItems();
            return is_array($lowStock);
        });
    }
    
    private function testStockAdjustment() {
        echo "\n🔧 STOCK ADJUSTMENT\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        // First release remaining reservation
        $this->stockModel->release($this->testProductId, $this->testWarehouseId, 10, 'cleanup', null, 'Cleanup');
        
        $this->test("Positive stock adjustment", function() {
            $beforeStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            $beforeQty = floatval($beforeStock['quantity']);
            
            $result = $this->stockModel->adjustStock($this->testProductId, $this->testWarehouseId, 25, 'Inventory count adjustment');
            
            $afterStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            $afterQty = floatval($afterStock['quantity']);
            
            return $result && ($afterQty == $beforeQty + 25);
        });
        
        $this->test("Negative stock adjustment", function() {
            $beforeStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            $beforeQty = floatval($beforeStock['quantity']);
            
            $result = $this->stockModel->adjustStock($this->testProductId, $this->testWarehouseId, -15, 'Damaged goods');
            
            $afterStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            $afterQty = floatval($afterStock['quantity']);
            
            return $result && ($afterQty == $beforeQty - 15);
        });
        
        $this->test("Reject adjustment resulting in negative stock", function() {
            try {
                $this->stockModel->adjustStock($this->testProductId, $this->testWarehouseId, -10000);
                return false;
            } catch (Exception $e) {
                return strpos($e->getMessage(), 'negative') !== false;
            }
        });
    }
    
    private function testOptimisticLocking() {
        echo "\n🔐 OPTIMISTIC LOCKING\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Stock record has version field", function() {
            $stock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            return $stock && isset($stock['version']);
        });
        
        $this->test("Version increments on update", function() {
            $stock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            $oldVersion = $stock['version'];
            
            // Add stock which should increment version
            $this->stockModel->addStock($this->testProductId, $this->testWarehouseId, 5, 'test', null, 'Version test');
            
            $newStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            return $newStock['version'] > $oldVersion;
        });
        
        $this->test("Concurrent modification detection", function() {
            $stock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId);
            $currentVersion = $stock['version'];
            
            // Simulate stale version
            $staleVersion = $currentVersion - 1;
            
            try {
                $this->stockModel->updateWithVersion($stock['id'], ['quantity' => 999], $staleVersion);
                return false; // Should have thrown exception
            } catch (Exception $e) {
                return strpos($e->getMessage(), 'Concurrent') !== false || 
                       strpos($e->getMessage(), 'modification') !== false;
            }
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
            echo "🎉 EXCELLENT! SarInvStock model is working correctly.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Most functionality is working. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! SarInvStock model needs attention.\n";
        }
    }
}

// Run the test suite if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    $testSuite = new SarInvStockTest();
    $testSuite->runAllTests();
}
?>
