<?php
/**
 * Unit Tests for SarInvDispatch and SarInvTransfer Models
 * Tests dispatch creation with stock validation, transfer approval/receiving workflow, and stock conservation
 * Requirements: 4.1, 4.2, 5.1, 5.2, 5.3
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SarInvDispatch.php';
require_once __DIR__ . '/../models/SarInvTransfer.php';
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvProductCategory.php';
require_once __DIR__ . '/../models/SarInvWarehouse.php';

class SarInvDispatchTransferTest {
    private $db;
    private $dispatchModel;
    private $transferModel;
    private $stockModel;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testProductId;
    private $testWarehouseId1;
    private $testWarehouseId2;
    private $testCategoryId;
    private $testDispatchId;
    private $testTransferId;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->dispatchModel = new SarInvDispatch();
        $this->transferModel = new SarInvTransfer();
        $this->stockModel = new SarInvStock();
        
        // Set up session
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['company_id'] = 1;
        $_SESSION['user_id'] = 1;
    }
    
    public function runAllTests() {
        echo "🧪 Running SarInvDispatch & SarInvTransfer Unit Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Run tests
        $this->testDispatchCreation();
        $this->testDispatchStockValidation();
        $this->testDispatchWorkflow();
        $this->testTransferCreation();
        $this->testTransferWorkflow();
        $this->testStockConservation();
        
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
            'name' => 'Test Category for Dispatch/Transfer',
            'status' => 'active'
        ]);
        
        // Create test product
        $productModel = new SarInvProduct();
        $this->testProductId = $productModel->create([
            'name' => 'Test Product for Dispatch/Transfer',
            'sku' => 'TEST-DT-' . time(),
            'category_id' => $this->testCategoryId,
            'status' => 'active'
        ]);
        
        // Create source warehouse
        $warehouseModel = new SarInvWarehouse();
        $this->testWarehouseId1 = $warehouseModel->create([
            'name' => 'Source Warehouse',
            'code' => 'TEST-SRC-' . time(),
            'capacity' => 1000,
            'status' => 'active'
        ]);
        
        // Create destination warehouse
        $this->testWarehouseId2 = $warehouseModel->create([
            'name' => 'Destination Warehouse',
            'code' => 'TEST-DST-' . time(),
            'capacity' => 1000,
            'status' => 'active'
        ]);
        
        // Add initial stock to source warehouse
        $this->stockModel->addStock($this->testProductId, $this->testWarehouseId1, 100, 'initial', null, 'Initial stock');
        
        echo "Test data setup complete.\n\n";
    }
    
    private function cleanupTestData() {
        echo "\nCleaning up test data...\n";
        
        // Delete dispatch items
        if ($this->testDispatchId) {
            $stmt = $this->db->prepare("DELETE FROM sar_inv_dispatch_items WHERE dispatch_id = ?");
            $stmt->execute([$this->testDispatchId]);
        }
        
        // Delete dispatches
        $stmt = $this->db->prepare("DELETE FROM sar_inv_dispatches WHERE source_warehouse_id IN (?, ?)");
        $stmt->execute([$this->testWarehouseId1, $this->testWarehouseId2]);
        
        // Delete transfer items
        if ($this->testTransferId) {
            $stmt = $this->db->prepare("DELETE FROM sar_inv_transfer_items WHERE transfer_id = ?");
            $stmt->execute([$this->testTransferId]);
        }
        
        // Delete transfers
        $stmt = $this->db->prepare("DELETE FROM sar_inv_transfers WHERE source_warehouse_id = ? OR destination_warehouse_id = ?");
        $stmt->execute([$this->testWarehouseId1, $this->testWarehouseId2]);
        
        // Delete stock entries
        $stmt = $this->db->prepare("DELETE FROM sar_inv_stock_entries WHERE product_id = ?");
        $stmt->execute([$this->testProductId]);
        
        // Delete item history
        $stmt = $this->db->prepare("DELETE FROM sar_inv_item_history WHERE product_id = ?");
        $stmt->execute([$this->testProductId]);
        
        // Delete stock
        $stmt = $this->db->prepare("DELETE FROM sar_inv_stock WHERE product_id = ?");
        $stmt->execute([$this->testProductId]);
        
        // Delete warehouses
        $stmt = $this->db->prepare("DELETE FROM sar_inv_warehouses WHERE id IN (?, ?)");
        $stmt->execute([$this->testWarehouseId1, $this->testWarehouseId2]);
        
        // Delete product
        $stmt = $this->db->prepare("DELETE FROM sar_inv_products WHERE id = ?");
        $stmt->execute([$this->testProductId]);
        
        // Delete category
        $stmt = $this->db->prepare("DELETE FROM sar_inv_product_categories WHERE id = ?");
        $stmt->execute([$this->testCategoryId]);
        
        echo "Test data cleanup complete.\n";
    }
    
    private function testDispatchCreation() {
        echo "\n📦 DISPATCH CREATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create dispatch with valid data", function() {
            $this->testDispatchId = $this->dispatchModel->createDispatch([
                'source_warehouse_id' => $this->testWarehouseId1,
                'destination_type' => 'site',
                'destination_address' => 'Test Site Address'
            ], [
                ['product_id' => $this->testProductId, 'quantity' => 10]
            ]);
            return $this->testDispatchId !== false && is_numeric($this->testDispatchId);
        });
        
        $this->test("Dispatch has auto-generated number", function() {
            $dispatch = $this->dispatchModel->find($this->testDispatchId);
            return $dispatch && !empty($dispatch['dispatch_number']) && 
                   strpos($dispatch['dispatch_number'], 'DSP') === 0;
        });
        
        $this->test("Dispatch created with pending status", function() {
            $dispatch = $this->dispatchModel->find($this->testDispatchId);
            return $dispatch && $dispatch['status'] === 'pending';
        });
        
        $this->test("Dispatch items created correctly", function() {
            $items = $this->dispatchModel->getItems($this->testDispatchId);
            return is_array($items) && count($items) == 1 && 
                   $items[0]['product_id'] == $this->testProductId &&
                   $items[0]['quantity'] == 10;
        });
        
        $this->test("Get dispatch with details", function() {
            $dispatch = $this->dispatchModel->getWithDetails($this->testDispatchId);
            return $dispatch && isset($dispatch['items']) && isset($dispatch['source_warehouse_name']);
        });
    }
    
    private function testDispatchStockValidation() {
        echo "\n🔍 DISPATCH STOCK VALIDATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Reject dispatch exceeding available stock", function() {
            try {
                $this->dispatchModel->createDispatch([
                    'source_warehouse_id' => $this->testWarehouseId1,
                    'destination_type' => 'site'
                ], [
                    ['product_id' => $this->testProductId, 'quantity' => 500]
                ]);
                return false;
            } catch (Exception $e) {
                return strpos($e->getMessage(), 'Insufficient') !== false;
            }
        });
        
        $this->test("Validation fails for missing source warehouse", function() {
            $errors = $this->dispatchModel->validate(['destination_type' => 'site']);
            return !empty($errors) && in_array('Source warehouse is required', $errors);
        });
        
        $this->test("Validation fails for missing destination type", function() {
            $errors = $this->dispatchModel->validate(['source_warehouse_id' => 1]);
            return !empty($errors) && in_array('Destination type is required', $errors);
        });
    }
    
    private function testDispatchWorkflow() {
        echo "\n🔄 DISPATCH WORKFLOW\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Update dispatch status to approved", function() {
            $result = $this->dispatchModel->updateStatus($this->testDispatchId, 'approved');
            $dispatch = $this->dispatchModel->find($this->testDispatchId);
            return $result && $dispatch['status'] === 'approved';
        });
        
        $this->test("Update dispatch status to shipped", function() {
            $beforeStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId1);
            $beforeQty = floatval($beforeStock['quantity']);
            
            $result = $this->dispatchModel->updateStatus($this->testDispatchId, 'shipped');
            
            $afterStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId1);
            $afterQty = floatval($afterStock['quantity']);
            
            // Stock should be reduced by 10 (dispatch quantity)
            return $result && ($afterQty == $beforeQty - 10);
        });
        
        $this->test("Dispatch date set when shipped", function() {
            $dispatch = $this->dispatchModel->find($this->testDispatchId);
            return $dispatch && !empty($dispatch['dispatch_date']);
        });
        
        $this->test("Update dispatch status to delivered", function() {
            $result = $this->dispatchModel->updateStatus($this->testDispatchId, 'delivered');
            $dispatch = $this->dispatchModel->find($this->testDispatchId);
            return $result && $dispatch['status'] === 'delivered';
        });
        
        $this->test("Received date set when delivered", function() {
            $dispatch = $this->dispatchModel->find($this->testDispatchId);
            return $dispatch && !empty($dispatch['received_date']);
        });
        
        $this->test("Get dispatches by status", function() {
            $dispatches = $this->dispatchModel->getByStatus('delivered');
            return is_array($dispatches);
        });
    }
    
    private function testTransferCreation() {
        echo "\n🔀 TRANSFER CREATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create transfer with valid data", function() {
            $this->testTransferId = $this->transferModel->createTransfer([
                'source_warehouse_id' => $this->testWarehouseId1,
                'destination_warehouse_id' => $this->testWarehouseId2
            ], [
                ['product_id' => $this->testProductId, 'quantity' => 20]
            ]);
            return $this->testTransferId !== false && is_numeric($this->testTransferId);
        });
        
        $this->test("Transfer has auto-generated number", function() {
            $transfer = $this->transferModel->find($this->testTransferId);
            return $transfer && !empty($transfer['transfer_number']) && 
                   strpos($transfer['transfer_number'], 'TRF') === 0;
        });
        
        $this->test("Transfer created with pending status", function() {
            $transfer = $this->transferModel->find($this->testTransferId);
            return $transfer && $transfer['status'] === 'pending';
        });
        
        $this->test("Transfer items created correctly", function() {
            $items = $this->transferModel->getItems($this->testTransferId);
            return is_array($items) && count($items) == 1 && 
                   $items[0]['product_id'] == $this->testProductId &&
                   $items[0]['quantity'] == 20;
        });
        
        $this->test("Validation fails for same source and destination", function() {
            $errors = $this->transferModel->validate([
                'source_warehouse_id' => $this->testWarehouseId1,
                'destination_warehouse_id' => $this->testWarehouseId1
            ]);
            return !empty($errors) && in_array('Source and destination warehouses must be different', $errors);
        });
    }
    
    private function testTransferWorkflow() {
        echo "\n🔄 TRANSFER WORKFLOW\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Approve transfer reserves stock", function() {
            $beforeStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId1);
            $beforeReserved = floatval($beforeStock['reserved_quantity']);
            
            $result = $this->transferModel->approve($this->testTransferId);
            
            $afterStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId1);
            $afterReserved = floatval($afterStock['reserved_quantity']);
            
            return $result && ($afterReserved == $beforeReserved + 20);
        });
        
        $this->test("Transfer status updated to approved", function() {
            $transfer = $this->transferModel->find($this->testTransferId);
            return $transfer && $transfer['status'] === 'approved';
        });
        
        $this->test("Transfer date set when approved", function() {
            $transfer = $this->transferModel->find($this->testTransferId);
            return $transfer && !empty($transfer['transfer_date']);
        });
        
        $this->test("Get transfer with details", function() {
            $transfer = $this->transferModel->getWithDetails($this->testTransferId);
            return $transfer && isset($transfer['items']) && 
                   isset($transfer['source_warehouse_name']) &&
                   isset($transfer['destination_warehouse_name']);
        });
    }
    
    private function testStockConservation() {
        echo "\n⚖️ STOCK CONSERVATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Receive transfer adds stock to destination", function() {
            $sourceStockBefore = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId1);
            $sourceQtyBefore = floatval($sourceStockBefore['quantity']);
            
            $destStockBefore = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId2);
            $destQtyBefore = $destStockBefore ? floatval($destStockBefore['quantity']) : 0;
            
            $result = $this->transferModel->receive($this->testTransferId);
            
            $sourceStockAfter = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId1);
            $sourceQtyAfter = floatval($sourceStockAfter['quantity']);
            
            $destStockAfter = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId2);
            $destQtyAfter = floatval($destStockAfter['quantity']);
            
            // Source should decrease by 20, destination should increase by 20
            return $result && 
                   ($sourceQtyAfter == $sourceQtyBefore - 20) &&
                   ($destQtyAfter == $destQtyBefore + 20);
        });
        
        $this->test("Transfer status updated to received", function() {
            $transfer = $this->transferModel->find($this->testTransferId);
            return $transfer && $transfer['status'] === 'received';
        });
        
        $this->test("Received date set when received", function() {
            $transfer = $this->transferModel->find($this->testTransferId);
            return $transfer && !empty($transfer['received_date']);
        });
        
        $this->test("Total stock conserved across warehouses", function() {
            // After all operations: initial 100 - 10 dispatch - 20 transfer = 70 in source
            // Destination should have 20 from transfer
            $sourceStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId1);
            $destStock = $this->stockModel->getStock($this->testProductId, $this->testWarehouseId2);
            
            $totalStock = floatval($sourceStock['quantity']) + floatval($destStock['quantity']);
            
            // Total should be 90 (100 initial - 10 dispatched)
            return $totalStock == 90;
        });
        
        $this->test("Get transfers by status", function() {
            $transfers = $this->transferModel->getByStatus('received');
            return is_array($transfers);
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
            echo "🎉 EXCELLENT! Dispatch and Transfer models are working correctly.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Most functionality is working. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! Dispatch and Transfer models need attention.\n";
        }
    }
}

// Run the test suite if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    $testSuite = new SarInvDispatchTransferTest();
    $testSuite->runAllTests();
}
?>
