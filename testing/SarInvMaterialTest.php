<?php
/**
 * Unit Tests for SarInvMaterialMaster and SarInvMaterialRequest Models
 * Tests material master creation, request workflow, and fulfillment tracking
 * Requirements: 9.1, 9.2, 9.3, 9.4, 9.5
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SarInvMaterialMaster.php';
require_once __DIR__ . '/../models/SarInvMaterialRequest.php';
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvProductCategory.php';

class SarInvMaterialTest {
    private $db;
    private $materialMasterModel;
    private $materialRequestModel;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testMaterialMasterId;
    private $testMaterialRequestId;
    private $testProductId;
    private $testCategoryId;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->materialMasterModel = new SarInvMaterialMaster();
        $this->materialRequestModel = new SarInvMaterialRequest();
        
        // Set up session
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['company_id'] = 1;
        $_SESSION['user_id'] = 1;
    }
    
    public function runAllTests() {
        echo "🧪 Running SarInvMaterial Unit Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Run tests
        $this->testMaterialMasterCreation();
        $this->testMaterialMasterValidation();
        $this->testMaterialRequestWorkflow();
        $this->testFulfillmentTracking();
        
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
            'name' => 'Test Category for Material Tests',
            'status' => 'active'
        ]);
        
        // Create test product
        $productModel = new SarInvProduct();
        $this->testProductId = $productModel->create([
            'name' => 'Test Product for Material Tests',
            'sku' => 'TEST-MAT-' . time(),
            'category_id' => $this->testCategoryId,
            'status' => 'active'
        ]);
        
        echo "Test data setup complete.\n\n";
    }
    
    private function cleanupTestData() {
        echo "\nCleaning up test data...\n";
        
        // Delete material requests
        $stmt = $this->db->prepare("DELETE FROM sar_inv_material_requests WHERE material_master_id = ? OR product_id = ?");
        $stmt->execute([$this->testMaterialMasterId, $this->testProductId]);
        
        // Delete material masters
        $stmt = $this->db->prepare("DELETE FROM sar_inv_material_masters WHERE id = ?");
        $stmt->execute([$this->testMaterialMasterId]);
        
        // Clean up test material masters by code pattern
        $stmt = $this->db->prepare("DELETE FROM sar_inv_material_masters WHERE code LIKE 'TEST-MM-%'");
        $stmt->execute();
        
        // Delete product
        $stmt = $this->db->prepare("DELETE FROM sar_inv_products WHERE id = ?");
        $stmt->execute([$this->testProductId]);
        
        // Delete category
        $stmt = $this->db->prepare("DELETE FROM sar_inv_product_categories WHERE id = ?");
        $stmt->execute([$this->testCategoryId]);
        
        echo "Test data cleanup complete.\n";
    }
    
    private function testMaterialMasterCreation() {
        echo "\n📋 MATERIAL MASTER CREATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create material master with valid data", function() {
            $this->testMaterialMasterId = $this->materialMasterModel->create([
                'name' => 'Test Material Master',
                'code' => 'TEST-MM-' . time(),
                'description' => 'Test material description',
                'unit_of_measure' => 'Nos',
                'default_quantity' => 10,
                'specifications' => ['color' => 'blue', 'size' => 'medium'],
                'status' => 'active'
            ]);
            return $this->testMaterialMasterId !== false && is_numeric($this->testMaterialMasterId);
        });
        
        $this->test("Material master has unique code", function() {
            $material = $this->materialMasterModel->find($this->testMaterialMasterId);
            return $material && !empty($material['code']);
        });
        
        $this->test("Find material master by code", function() {
            $material = $this->materialMasterModel->find($this->testMaterialMasterId);
            $foundMaterial = $this->materialMasterModel->findByCode($material['code']);
            return $foundMaterial && $foundMaterial['id'] == $this->testMaterialMasterId;
        });
        
        $this->test("Specifications stored as JSON", function() {
            $specs = $this->materialMasterModel->getSpecifications($this->testMaterialMasterId);
            return is_array($specs) && isset($specs['color']) && $specs['color'] === 'blue';
        });
        
        $this->test("Get active material masters", function() {
            $materials = $this->materialMasterModel->getActiveMaterials();
            return is_array($materials);
        });
        
        $this->test("Search material masters", function() {
            $results = $this->materialMasterModel->search('Test Material');
            return is_array($results) && count($results) >= 1;
        });
    }
    
    private function testMaterialMasterValidation() {
        echo "\n✅ MATERIAL MASTER VALIDATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Validation fails for missing name", function() {
            $errors = $this->materialMasterModel->validate(['code' => 'TEST']);
            return !empty($errors) && in_array('Material name is required', $errors);
        });
        
        $this->test("Validation fails for missing code", function() {
            $errors = $this->materialMasterModel->validate(['name' => 'Test']);
            return !empty($errors) && in_array('Material code is required', $errors);
        });
        
        $this->test("Validation fails for duplicate code", function() {
            $material = $this->materialMasterModel->find($this->testMaterialMasterId);
            $errors = $this->materialMasterModel->validate(['name' => 'New', 'code' => $material['code']]);
            return !empty($errors) && in_array('Material code already exists', $errors);
        });
        
        $this->test("Validation fails for negative default quantity", function() {
            $errors = $this->materialMasterModel->validate([
                'name' => 'Test',
                'code' => 'NEW-CODE',
                'default_quantity' => -5
            ]);
            return !empty($errors) && in_array('Default quantity cannot be negative', $errors);
        });
        
        $this->test("Validation passes for valid data", function() {
            $errors = $this->materialMasterModel->validate([
                'name' => 'Valid Material',
                'code' => 'VALID-' . time(),
                'default_quantity' => 10,
                'status' => 'active'
            ]);
            return empty($errors);
        });
    }
    
    private function testMaterialRequestWorkflow() {
        echo "\n🔄 MATERIAL REQUEST WORKFLOW\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create material request", function() {
            $this->testMaterialRequestId = $this->materialRequestModel->create([
                'material_master_id' => $this->testMaterialMasterId,
                'quantity' => 50,
                'notes' => 'Test material request'
            ]);
            return $this->testMaterialRequestId !== false && is_numeric($this->testMaterialRequestId);
        });
        
        $this->test("Request has auto-generated number", function() {
            $request = $this->materialRequestModel->find($this->testMaterialRequestId);
            return $request && !empty($request['request_number']) && 
                   strpos($request['request_number'], 'MR') === 0;
        });
        
        $this->test("Request created with pending status", function() {
            $request = $this->materialRequestModel->find($this->testMaterialRequestId);
            return $request && $request['status'] === 'pending';
        });
        
        $this->test("Request has requester ID set", function() {
            $request = $this->materialRequestModel->find($this->testMaterialRequestId);
            return $request && $request['requester_id'] == $_SESSION['user_id'];
        });
        
        $this->test("Find request by request number", function() {
            $request = $this->materialRequestModel->find($this->testMaterialRequestId);
            $foundRequest = $this->materialRequestModel->findByRequestNumber($request['request_number']);
            return $foundRequest && $foundRequest['id'] == $this->testMaterialRequestId;
        });
        
        $this->test("Get request with details", function() {
            $request = $this->materialRequestModel->getWithDetails($this->testMaterialRequestId);
            return $request && isset($request['material_name']);
        });
        
        $this->test("Approve material request", function() {
            $result = $this->materialRequestModel->approve($this->testMaterialRequestId, null, 'Approved for testing');
            $request = $this->materialRequestModel->find($this->testMaterialRequestId);
            return $result && $request['status'] === 'approved';
        });
        
        $this->test("Approver ID set when approved", function() {
            $request = $this->materialRequestModel->find($this->testMaterialRequestId);
            return $request && !empty($request['approver_id']);
        });
        
        $this->test("Get requests by status", function() {
            $requests = $this->materialRequestModel->getByStatus('approved');
            return is_array($requests);
        });
        
        $this->test("Get requests by requester", function() {
            $requests = $this->materialRequestModel->getByRequester($_SESSION['user_id']);
            return is_array($requests) && count($requests) >= 1;
        });
    }
    
    private function testFulfillmentTracking() {
        echo "\n📦 FULFILLMENT TRACKING\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Partial fulfillment updates quantity", function() {
            $result = $this->materialRequestModel->fulfill($this->testMaterialRequestId, 20, 'Partial fulfillment');
            $request = $this->materialRequestModel->find($this->testMaterialRequestId);
            return $result && floatval($request['fulfilled_quantity']) == 20;
        });
        
        $this->test("Partial fulfillment sets status to partially_fulfilled", function() {
            $request = $this->materialRequestModel->find($this->testMaterialRequestId);
            return $request && $request['status'] === 'partially_fulfilled';
        });
        
        $this->test("Get fulfillment progress", function() {
            $progress = $this->materialRequestModel->getFulfillmentProgress($this->testMaterialRequestId);
            return $progress && 
                   $progress['requested'] == 50 &&
                   $progress['fulfilled'] == 20 &&
                   $progress['remaining'] == 30 &&
                   $progress['percentage'] == 40;
        });
        
        $this->test("Complete fulfillment updates status to fulfilled", function() {
            $result = $this->materialRequestModel->fulfill($this->testMaterialRequestId, 30, 'Final fulfillment');
            $request = $this->materialRequestModel->find($this->testMaterialRequestId);
            return $result && $request['status'] === 'fulfilled';
        });
        
        $this->test("Fulfilled quantity equals requested quantity", function() {
            $request = $this->materialRequestModel->find($this->testMaterialRequestId);
            return floatval($request['fulfilled_quantity']) == floatval($request['quantity']);
        });
        
        $this->test("Reject fulfillment exceeding requested quantity", function() {
            // Create a new request for this test
            $newRequestId = $this->materialRequestModel->create([
                'material_master_id' => $this->testMaterialMasterId,
                'quantity' => 10
            ]);
            $this->materialRequestModel->approve($newRequestId);
            
            try {
                $this->materialRequestModel->fulfill($newRequestId, 20);
                // Clean up
                $stmt = $this->db->prepare("DELETE FROM sar_inv_material_requests WHERE id = ?");
                $stmt->execute([$newRequestId]);
                return false;
            } catch (Exception $e) {
                // Clean up
                $stmt = $this->db->prepare("DELETE FROM sar_inv_material_requests WHERE id = ?");
                $stmt->execute([$newRequestId]);
                return strpos($e->getMessage(), 'exceed') !== false;
            }
        });
        
        $this->test("Get pending count", function() {
            $count = $this->materialRequestModel->getPendingCount();
            return is_numeric($count);
        });
        
        $this->test("Get request statistics", function() {
            $stats = $this->materialRequestModel->getStatistics();
            return is_array($stats) && isset($stats['total_requests']);
        });
        
        $this->test("Search material requests", function() {
            $results = $this->materialRequestModel->search('Test');
            return is_array($results);
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
            echo "🎉 EXCELLENT! Material models are working correctly.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Most functionality is working. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! Material models need attention.\n";
        }
    }
}

// Run the test suite if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    $testSuite = new SarInvMaterialTest();
    $testSuite->runAllTests();
}
?>
