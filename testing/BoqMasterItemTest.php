<?php
/**
 * Unit Tests for BoqMasterItem Model
 * Tests CRUD operations, duplicate detection, and validation logic
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/BoqMasterItem.php';
require_once __DIR__ . '/../models/BoqMaster.php';
require_once __DIR__ . '/../models/BoqItem.php';

class BoqMasterItemTest {
    private $db;
    private $model;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testBoqMasterId;
    private $testBoqItemId;
    private $testBoqItemId2;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->model = new BoqMasterItem();
    }
    
    public function runAllTests() {
        echo "🧪 Running BoqMasterItem Model Unit Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Run tests
        $this->testCreateOperation();
        $this->testFindOperation();
        $this->testUpdateOperation();
        $this->testDeleteOperation();
        $this->testGetByBoqMaster();
        $this->testDuplicateDetection();
        $this->testValidationLogic();
        $this->testGetWithItemDetails();
        $this->testUpdateSortOrder();
        $this->testGetItemCount();
        $this->testToggleStatus();
        $this->testGetStats();
        
        // Cleanup test data
        $this->cleanupTestData();
        
        // Print results
        $this->printResults();
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
        
        // Create test BOQ master
        $stmt = $this->db->prepare("INSERT INTO boq_master (boq_name, status, created_at) VALUES (?, 'active', NOW())");
        $stmt->execute(['Test BOQ Master for Unit Tests']);
        $this->testBoqMasterId = $this->db->lastInsertId();
        
        // Create test BOQ items
        $stmt = $this->db->prepare("INSERT INTO boq_items (item_name, item_code, unit, status, created_at) VALUES (?, ?, 'Nos', 'active', NOW())");
        $stmt->execute(['Test Item 1', 'TEST001']);
        $this->testBoqItemId = $this->db->lastInsertId();
        
        $stmt->execute(['Test Item 2', 'TEST002']);
        $this->testBoqItemId2 = $this->db->lastInsertId();
        
        echo "Test data setup complete.\n\n";
    }
    
    private function cleanupTestData() {
        echo "\nCleaning up test data...\n";
        
        // Delete test BOQ master items
        $stmt = $this->db->prepare("DELETE FROM boq_master_items WHERE boq_master_id = ?");
        $stmt->execute([$this->testBoqMasterId]);
        
        // Delete test BOQ master
        $stmt = $this->db->prepare("DELETE FROM boq_master WHERE boq_id = ?");
        $stmt->execute([$this->testBoqMasterId]);
        
        // Delete test BOQ items
        $stmt = $this->db->prepare("DELETE FROM boq_items WHERE id IN (?, ?)");
        $stmt->execute([$this->testBoqItemId, $this->testBoqItemId2]);
        
        echo "Test data cleanup complete.\n";
    }
    
    private function testCreateOperation() {
        echo "\n📝 CRUD OPERATIONS - CREATE\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create BOQ Master Item", function() {
            $data = [
                'boq_master_id' => $this->testBoqMasterId,
                'boq_item_id' => $this->testBoqItemId,
                'default_quantity' => 5.00,
                'remarks' => 'Test remarks'
            ];
            
            $id = $this->model->create($data);
            return $id !== false && is_numeric($id);
        });
        
        $this->test("Create with Default Values", function() {
            $data = [
                'boq_master_id' => $this->testBoqMasterId,
                'boq_item_id' => $this->testBoqItemId2
            ];
            
            $id = $this->model->create($data);
            if ($id === false) return false;
            
            $record = $this->model->find($id);
            return $record && 
                   $record['default_quantity'] == 1.00 && 
                   $record['sort_order'] == 0 && 
                   $record['status'] == 'active';
        });
    }
    
    private function testFindOperation() {
        echo "\n🔍 CRUD OPERATIONS - READ\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Find Existing Record", function() {
            // Get the first created record
            $stmt = $this->db->prepare("SELECT id FROM boq_master_items WHERE boq_master_id = ? LIMIT 1");
            $stmt->execute([$this->testBoqMasterId]);
            $testId = $stmt->fetchColumn();
            
            if (!$testId) return false;
            
            $record = $this->model->find($testId);
            return $record !== false && $record['id'] == $testId;
        });
        
        $this->test("Find Non-existent Record", function() {
            $record = $this->model->find(999999);
            return $record === false;
        });
    }
    
    private function testUpdateOperation() {
        echo "\n✏️ CRUD OPERATIONS - UPDATE\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Update Record", function() {
            // Get the first created record
            $stmt = $this->db->prepare("SELECT id FROM boq_master_items WHERE boq_master_id = ? LIMIT 1");
            $stmt->execute([$this->testBoqMasterId]);
            $testId = $stmt->fetchColumn();
            
            if (!$testId) return false;
            
            $updateData = [
                'default_quantity' => 10.00,
                'remarks' => 'Updated remarks'
            ];
            
            $result = $this->model->update($testId, $updateData);
            if (!$result) return false;
            
            $record = $this->model->find($testId);
            return $record && 
                   $record['default_quantity'] == 10.00 && 
                   $record['remarks'] == 'Updated remarks';
        });
    }
    
    private function testDeleteOperation() {
        echo "\n🗑️ CRUD OPERATIONS - DELETE\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Delete Record", function() {
            // Create a new BOQ item for this test to avoid duplicates
            $stmt = $this->db->prepare("INSERT INTO boq_items (item_name, item_code, unit, status, created_at) VALUES ('Delete Test Item', 'DELTEST', 'Nos', 'active', NOW())");
            $stmt->execute();
            $deleteTestItemId = $this->db->lastInsertId();
            
            // Create a record to delete
            $data = [
                'boq_master_id' => $this->testBoqMasterId,
                'boq_item_id' => $deleteTestItemId,
                'default_quantity' => 1.00
            ];
            
            $id = $this->model->create($data);
            if ($id === false) {
                // Cleanup the test item
                $stmt = $this->db->prepare("DELETE FROM boq_items WHERE id = ?");
                $stmt->execute([$deleteTestItemId]);
                return false;
            }
            
            // Delete the record
            $result = $this->model->delete($id);
            if (!$result) {
                // Cleanup the test item
                $stmt = $this->db->prepare("DELETE FROM boq_items WHERE id = ?");
                $stmt->execute([$deleteTestItemId]);
                return false;
            }
            
            // Verify it's deleted
            $record = $this->model->find($id);
            
            // Cleanup the test item
            $stmt = $this->db->prepare("DELETE FROM boq_items WHERE id = ?");
            $stmt->execute([$deleteTestItemId]);
            
            return $record === false;
        });
    }
    
    private function testGetByBoqMaster() {
        echo "\n📋 SPECIALIZED QUERIES\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Get Items by BOQ Master", function() {
            $items = $this->model->getByBoqMaster($this->testBoqMasterId);
            return is_array($items) && count($items) > 0;
        });
        
        $this->test("Get Items with Joined Data", function() {
            $items = $this->model->getByBoqMaster($this->testBoqMasterId);
            if (empty($items)) return false;
            
            $firstItem = $items[0];
            return isset($firstItem['item_name']) && 
                   isset($firstItem['item_code']) && 
                   isset($firstItem['unit']);
        });
    }
    
    private function testDuplicateDetection() {
        echo "\n🔍 DUPLICATE DETECTION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Detect Existing Duplicate", function() {
            // Should detect duplicate since we already have this association
            return $this->model->isDuplicate($this->testBoqMasterId, $this->testBoqItemId2);
        });
        
        $this->test("No Duplicate for New Association", function() {
            // Create a new BOQ item for testing
            $stmt = $this->db->prepare("INSERT INTO boq_items (item_name, item_code, unit, status, created_at) VALUES ('Test Item 3', 'TEST003', 'Nos', 'active', NOW())");
            $stmt->execute();
            $newItemId = $this->db->lastInsertId();
            
            $isDuplicate = $this->model->isDuplicate($this->testBoqMasterId, $newItemId);
            
            // Cleanup
            $stmt = $this->db->prepare("DELETE FROM boq_items WHERE id = ?");
            $stmt->execute([$newItemId]);
            
            return !$isDuplicate;
        });
        
        $this->test("Exclude ID in Duplicate Check", function() {
            // Get an existing record
            $stmt = $this->db->prepare("SELECT id, boq_item_id FROM boq_master_items WHERE boq_master_id = ? LIMIT 1");
            $stmt->execute([$this->testBoqMasterId]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) return false;
            
            // Should not detect duplicate when excluding the same record
            return !$this->model->isDuplicate($this->testBoqMasterId, $record['boq_item_id'], $record['id']);
        });
    }
    
    private function testValidationLogic() {
        echo "\n✅ VALIDATION LOGIC\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Valid Data Passes Validation", function() {
            $data = [
                'boq_master_id' => $this->testBoqMasterId,
                'boq_item_id' => 999, // Non-existent but valid format
                'default_quantity' => 5.00,
                'status' => 'active'
            ];
            
            $errors = $this->model->validateData($data);
            return empty($errors);
        });
        
        $this->test("Missing BOQ Master ID", function() {
            $data = [
                'boq_item_id' => $this->testBoqItemId,
                'default_quantity' => 5.00
            ];
            
            $errors = $this->model->validateData($data);
            return isset($errors['boq_master_id']);
        });
        
        $this->test("Missing BOQ Item ID", function() {
            $data = [
                'boq_master_id' => $this->testBoqMasterId,
                'default_quantity' => 5.00
            ];
            
            $errors = $this->model->validateData($data);
            return isset($errors['boq_item_id']);
        });
        
        $this->test("Invalid Quantity", function() {
            $data = [
                'boq_master_id' => $this->testBoqMasterId,
                'boq_item_id' => 999,
                'default_quantity' => -1
            ];
            
            $errors = $this->model->validateData($data);
            return isset($errors['default_quantity']);
        });
        
        $this->test("Invalid Status", function() {
            $data = [
                'boq_master_id' => $this->testBoqMasterId,
                'boq_item_id' => 999,
                'status' => 'invalid_status'
            ];
            
            $errors = $this->model->validateData($data);
            return isset($errors['status']);
        });
        
        $this->test("Duplicate Detection in Validation", function() {
            $data = [
                'boq_master_id' => $this->testBoqMasterId,
                'boq_item_id' => $this->testBoqItemId2, // Already exists
                'default_quantity' => 5.00
            ];
            
            $errors = $this->model->validateData($data);
            return isset($errors['boq_item_id']) && 
                   strpos($errors['boq_item_id'], 'already added') !== false;
        });
    }
    
    private function testGetWithItemDetails() {
        echo "\n🔗 JOINED QUERIES\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Get With Item Details", function() {
            // Get an existing record
            $stmt = $this->db->prepare("SELECT id FROM boq_master_items WHERE boq_master_id = ? LIMIT 1");
            $stmt->execute([$this->testBoqMasterId]);
            $testId = $stmt->fetchColumn();
            
            if (!$testId) return false;
            
            $record = $this->model->getWithItemDetails($testId);
            return $record && 
                   isset($record['item_name']) && 
                   isset($record['item_code']) && 
                   isset($record['unit']);
        });
    }
    
    private function testUpdateSortOrder() {
        echo "\n📊 SORT ORDER OPERATIONS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Update Sort Order", function() {
            // Get existing records
            $stmt = $this->db->prepare("SELECT id FROM boq_master_items WHERE boq_master_id = ? LIMIT 2");
            $stmt->execute([$this->testBoqMasterId]);
            $records = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (count($records) < 1) return false;
            
            $itemOrders = [];
            foreach ($records as $index => $id) {
                $itemOrders[$id] = $index + 10; // Set sort order to 10, 11, etc.
            }
            
            $result = $this->model->updateSortOrder($this->testBoqMasterId, $itemOrders);
            if (!$result) return false;
            
            // Verify sort order was updated
            $record = $this->model->find($records[0]);
            return $record && $record['sort_order'] == 10;
        });
    }
    
    private function testGetItemCount() {
        echo "\n🔢 COUNT OPERATIONS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Get Item Count by BOQ Master", function() {
            $count = $this->model->getItemCountByBoqMaster($this->testBoqMasterId);
            return is_numeric($count) && $count >= 0;
        });
    }
    
    private function testToggleStatus() {
        echo "\n🔄 STATUS OPERATIONS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Toggle Status", function() {
            // Get an existing record
            $stmt = $this->db->prepare("SELECT id FROM boq_master_items WHERE boq_master_id = ? LIMIT 1");
            $stmt->execute([$this->testBoqMasterId]);
            $testId = $stmt->fetchColumn();
            
            if (!$testId) return false;
            
            $originalRecord = $this->model->find($testId);
            $originalStatus = $originalRecord['status'];
            
            $result = $this->model->toggleStatus($testId);
            if (!$result) return false;
            
            $updatedRecord = $this->model->find($testId);
            $newStatus = $updatedRecord['status'];
            
            return $originalStatus !== $newStatus && 
                   in_array($newStatus, ['active', 'inactive']);
        });
    }
    
    private function testGetStats() {
        echo "\n📈 STATISTICS\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Get Statistics", function() {
            $stats = $this->model->getStats();
            return is_array($stats) && 
                   isset($stats['total']) && 
                   isset($stats['active']) && 
                   isset($stats['inactive']) && 
                   isset($stats['boq_masters_with_items']) && 
                   isset($stats['avg_items_per_boq']);
        });
    }
    
    private function printResults() {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 60) . "\n";
        
        $failedTests = $this->totalTests - $this->passedTests;
        $successRate = ($this->passedTests / $this->totalTests) * 100;
        
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
            echo "🎉 EXCELLENT! BoqMasterItem model is working correctly.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Most functionality is working. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! BoqMasterItem model needs attention.\n";
        }
    }
}

// Run the test suite if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    $testSuite = new BoqMasterItemTest();
    $testSuite->runAllTests();
}
?>