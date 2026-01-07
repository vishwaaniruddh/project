<?php
/**
 * Unit Tests for SarInvAsset and SarInvRepair Models
 * Tests asset registration, tracking, location history, repair workflow, and cost tracking
 * Requirements: 6.1, 6.2, 8.1, 8.2, 8.3
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SarInvAsset.php';
require_once __DIR__ . '/../models/SarInvRepair.php';
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvProductCategory.php';
require_once __DIR__ . '/../models/SarInvWarehouse.php';

class SarInvAssetRepairTest {
    private $db;
    private $assetModel;
    private $repairModel;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testProductId;
    private $testWarehouseId;
    private $testCategoryId;
    private $testAssetId;
    private $testRepairId;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->assetModel = new SarInvAsset();
        $this->repairModel = new SarInvRepair();
        
        // Set up session
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['company_id'] = 1;
        $_SESSION['user_id'] = 1;
    }
    
    public function runAllTests() {
        echo "🧪 Running SarInvAsset & SarInvRepair Unit Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Run tests
        $this->testAssetRegistration();
        $this->testAssetTracking();
        $this->testLocationHistory();
        $this->testRepairWorkflow();
        $this->testRepairCostTracking();
        
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
            'name' => 'Test Category for Asset/Repair',
            'status' => 'active'
        ]);
        
        // Create test product
        $productModel = new SarInvProduct();
        $this->testProductId = $productModel->create([
            'name' => 'Test Product for Asset/Repair',
            'sku' => 'TEST-AR-' . time(),
            'category_id' => $this->testCategoryId,
            'status' => 'active'
        ]);
        
        // Create test warehouse
        $warehouseModel = new SarInvWarehouse();
        $this->testWarehouseId = $warehouseModel->create([
            'name' => 'Test Warehouse for Asset/Repair',
            'code' => 'TEST-AR-WH-' . time(),
            'status' => 'active'
        ]);
        
        echo "Test data setup complete.\n\n";
    }
    
    private function cleanupTestData() {
        echo "\nCleaning up test data...\n";
        
        // Delete repairs
        if ($this->testRepairId) {
            $stmt = $this->db->prepare("DELETE FROM sar_inv_repairs WHERE id = ?");
            $stmt->execute([$this->testRepairId]);
        }
        
        // Delete asset history
        if ($this->testAssetId) {
            $stmt = $this->db->prepare("DELETE FROM sar_inv_asset_history WHERE asset_id = ?");
            $stmt->execute([$this->testAssetId]);
        }
        
        // Delete assets
        $stmt = $this->db->prepare("DELETE FROM sar_inv_assets WHERE product_id = ?");
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
    
    private function testAssetRegistration() {
        echo "\n📋 ASSET REGISTRATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Register asset with unique identifiers", function() {
            $this->testAssetId = $this->assetModel->register([
                'product_id' => $this->testProductId,
                'serial_number' => 'SN-TEST-' . time(),
                'barcode' => 'BC-TEST-' . time(),
                'current_location_type' => 'warehouse',
                'current_location_id' => $this->testWarehouseId,
                'purchase_date' => date('Y-m-d'),
                'warranty_expiry' => date('Y-m-d', strtotime('+1 year'))
            ]);
            return $this->testAssetId !== false && is_numeric($this->testAssetId);
        });
        
        $this->test("Asset created with available status", function() {
            $asset = $this->assetModel->find($this->testAssetId);
            return $asset && $asset['status'] === 'available';
        });
        
        $this->test("Asset has unique serial number", function() {
            $asset = $this->assetModel->find($this->testAssetId);
            return $asset && !empty($asset['serial_number']);
        });
        
        $this->test("Asset has unique barcode", function() {
            $asset = $this->assetModel->find($this->testAssetId);
            return $asset && !empty($asset['barcode']);
        });
        
        $this->test("Reject duplicate serial number", function() {
            $asset = $this->assetModel->find($this->testAssetId);
            $errors = $this->assetModel->validate([
                'product_id' => $this->testProductId,
                'serial_number' => $asset['serial_number']
            ]);
            return !empty($errors) && in_array('Serial number already exists', $errors);
        });
        
        $this->test("Reject duplicate barcode", function() {
            $asset = $this->assetModel->find($this->testAssetId);
            $errors = $this->assetModel->validate([
                'product_id' => $this->testProductId,
                'barcode' => $asset['barcode']
            ]);
            return !empty($errors) && in_array('Barcode already exists', $errors);
        });
        
        $this->test("Find asset by serial number", function() {
            $asset = $this->assetModel->find($this->testAssetId);
            $foundAsset = $this->assetModel->findBySerialNumber($asset['serial_number']);
            return $foundAsset && $foundAsset['id'] == $this->testAssetId;
        });
        
        $this->test("Find asset by barcode", function() {
            $asset = $this->assetModel->find($this->testAssetId);
            $foundAsset = $this->assetModel->findByBarcode($asset['barcode']);
            return $foundAsset && $foundAsset['id'] == $this->testAssetId;
        });
    }
    
    private function testAssetTracking() {
        echo "\n📍 ASSET TRACKING\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Get asset with product info", function() {
            $asset = $this->assetModel->getWithProduct($this->testAssetId);
            return $asset && isset($asset['product_name']) && isset($asset['sku']);
        });
        
        $this->test("Get assets by product", function() {
            $assets = $this->assetModel->getByProduct($this->testProductId);
            return is_array($assets) && count($assets) >= 1;
        });
        
        $this->test("Get assets by location", function() {
            $assets = $this->assetModel->getByLocation('warehouse', $this->testWarehouseId);
            return is_array($assets) && count($assets) >= 1;
        });
        
        $this->test("Get assets by status", function() {
            $assets = $this->assetModel->getByStatus('available');
            return is_array($assets);
        });
        
        $this->test("Check asset is available", function() {
            return $this->assetModel->isAvailable($this->testAssetId);
        });
        
        $this->test("Search assets by serial number", function() {
            $asset = $this->assetModel->find($this->testAssetId);
            $results = $this->assetModel->search($asset['serial_number']);
            return is_array($results) && count($results) >= 1;
        });
    }
    
    private function testLocationHistory() {
        echo "\n📜 LOCATION HISTORY\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Update asset location", function() {
            $result = $this->assetModel->updateLocation(
                $this->testAssetId,
                'site',
                123,
                'Moved to site for installation'
            );
            
            $asset = $this->assetModel->find($this->testAssetId);
            return $result && $asset['current_location_type'] === 'site';
        });
        
        $this->test("Location change logged in history", function() {
            $history = $this->assetModel->getHistory($this->testAssetId);
            return is_array($history) && count($history) >= 1;
        });
        
        $this->test("History contains movement action", function() {
            $history = $this->assetModel->getHistory($this->testAssetId);
            $hasMovement = false;
            foreach ($history as $entry) {
                if ($entry['action_type'] === 'moved') {
                    $hasMovement = true;
                    break;
                }
            }
            return $hasMovement;
        });
        
        $this->test("Update asset status", function() {
            $result = $this->assetModel->updateStatus($this->testAssetId, 'dispatched', 'Dispatched to customer');
            $asset = $this->assetModel->find($this->testAssetId);
            return $result && $asset['status'] === 'dispatched';
        });
        
        $this->test("Status change logged in history", function() {
            $history = $this->assetModel->getHistory($this->testAssetId);
            $hasStatusChange = false;
            foreach ($history as $entry) {
                if ($entry['action_type'] === 'status_change') {
                    $hasStatusChange = true;
                    break;
                }
            }
            return $hasStatusChange;
        });
        
        // Reset status for repair tests
        $this->assetModel->updateStatus($this->testAssetId, 'available');
    }
    
    private function testRepairWorkflow() {
        echo "\n🔧 REPAIR WORKFLOW\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create repair for asset", function() {
            $this->testRepairId = $this->repairModel->createRepair([
                'asset_id' => $this->testAssetId,
                'issue_description' => 'Test issue - screen not working',
                'vendor_id' => 1
            ]);
            return $this->testRepairId !== false && is_numeric($this->testRepairId);
        });
        
        $this->test("Repair has auto-generated number", function() {
            $repair = $this->repairModel->find($this->testRepairId);
            return $repair && !empty($repair['repair_number']) && 
                   strpos($repair['repair_number'], 'RPR') === 0;
        });
        
        $this->test("Repair created with pending status", function() {
            $repair = $this->repairModel->find($this->testRepairId);
            return $repair && $repair['status'] === 'pending';
        });
        
        $this->test("Asset status changed to in_repair", function() {
            $asset = $this->assetModel->find($this->testAssetId);
            return $asset && $asset['status'] === 'in_repair';
        });
        
        $this->test("Start repair changes status to in_progress", function() {
            $result = $this->repairModel->startRepair($this->testRepairId);
            $repair = $this->repairModel->find($this->testRepairId);
            return $result && $repair['status'] === 'in_progress';
        });
        
        $this->test("Start date set when repair started", function() {
            $repair = $this->repairModel->find($this->testRepairId);
            return $repair && !empty($repair['start_date']);
        });
        
        $this->test("Update diagnosis", function() {
            $result = $this->repairModel->updateDiagnosis($this->testRepairId, 'LCD panel needs replacement');
            $repair = $this->repairModel->find($this->testRepairId);
            return $result && $repair['diagnosis'] === 'LCD panel needs replacement';
        });
        
        $this->test("Get repair with details", function() {
            $repair = $this->repairModel->getWithDetails($this->testRepairId);
            return $repair && isset($repair['serial_number']) && isset($repair['product_name']);
        });
        
        $this->test("Get repairs by asset", function() {
            $repairs = $this->repairModel->getByAsset($this->testAssetId);
            return is_array($repairs) && count($repairs) >= 1;
        });
        
        $this->test("Get repairs by status", function() {
            $repairs = $this->repairModel->getByStatus('in_progress');
            return is_array($repairs);
        });
    }
    
    private function testRepairCostTracking() {
        echo "\n💰 REPAIR COST TRACKING\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Update repair cost", function() {
            $result = $this->repairModel->updateCost($this->testRepairId, 150.00);
            $repair = $this->repairModel->find($this->testRepairId);
            return $result && floatval($repair['cost']) == 150.00;
        });
        
        $this->test("Reject negative cost", function() {
            try {
                $this->repairModel->updateCost($this->testRepairId, -50);
                return false;
            } catch (Exception $e) {
                return strpos($e->getMessage(), 'negative') !== false;
            }
        });
        
        $this->test("Complete repair with cost and notes", function() {
            $result = $this->repairModel->completeRepair(
                $this->testRepairId,
                'LCD panel replaced successfully',
                200.00,
                $this->testWarehouseId
            );
            $repair = $this->repairModel->find($this->testRepairId);
            return $result && $repair['status'] === 'completed' && floatval($repair['cost']) == 200.00;
        });
        
        $this->test("Completion date set when completed", function() {
            $repair = $this->repairModel->find($this->testRepairId);
            return $repair && !empty($repair['completion_date']);
        });
        
        $this->test("Asset status restored to available after repair", function() {
            $asset = $this->assetModel->find($this->testAssetId);
            return $asset && $asset['status'] === 'available';
        });
        
        $this->test("Get total repair costs", function() {
            $costs = $this->repairModel->getTotalCosts();
            return is_array($costs) && isset($costs['total_cost']);
        });
        
        $this->test("Get repair statistics", function() {
            $stats = $this->repairModel->getStatistics();
            return is_array($stats);
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
            echo "🎉 EXCELLENT! Asset and Repair models are working correctly.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Most functionality is working. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! Asset and Repair models need attention.\n";
        }
    }
}

// Run the test suite if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    $testSuite = new SarInvAssetRepairTest();
    $testSuite->runAllTests();
}
?>
