<?php
/**
 * Unit Tests for SarInvAuditLog and SarInvItemHistory Models
 * Tests audit log creation, history retrieval, filtering, pagination, and export
 * Requirements: 7.1, 7.2, 7.3, 12.2, 12.3
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SarInvAuditLog.php';
require_once __DIR__ . '/../models/SarInvItemHistory.php';
require_once __DIR__ . '/../models/SarInvStock.php';
require_once __DIR__ . '/../models/SarInvProduct.php';
require_once __DIR__ . '/../models/SarInvProductCategory.php';
require_once __DIR__ . '/../models/SarInvWarehouse.php';

class SarInvAuditHistoryTest {
    private $db;
    private $auditLogModel;
    private $itemHistoryModel;
    private $stockModel;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    private $testProductId;
    private $testWarehouseId;
    private $testCategoryId;
    private $testAuditLogId;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->auditLogModel = new SarInvAuditLog();
        $this->itemHistoryModel = new SarInvItemHistory();
        $this->stockModel = new SarInvStock();
        
        // Set up session
        if (!isset($_SESSION)) {
            session_start();
        }
        $_SESSION['company_id'] = 1;
        $_SESSION['user_id'] = 1;
    }
    
    public function runAllTests() {
        echo "🧪 Running SarInvAuditLog & SarInvItemHistory Unit Tests\n";
        echo "=" . str_repeat("=", 60) . "\n\n";
        
        // Setup test data
        $this->setupTestData();
        
        // Run tests
        $this->testAuditLogCreation();
        $this->testAuditLogRetrieval();
        $this->testItemHistoryRetrieval();
        $this->testHistoryFiltering();
        $this->testHistoryPagination();
        $this->testExportFunctionality();
        
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
            'name' => 'Test Category for Audit/History',
            'status' => 'active'
        ]);
        
        // Create test product
        $productModel = new SarInvProduct();
        $this->testProductId = $productModel->create([
            'name' => 'Test Product for Audit/History',
            'sku' => 'TEST-AH-' . time(),
            'category_id' => $this->testCategoryId,
            'status' => 'active'
        ]);
        
        // Create test warehouse
        $warehouseModel = new SarInvWarehouse();
        $this->testWarehouseId = $warehouseModel->create([
            'name' => 'Test Warehouse for Audit/History',
            'code' => 'TEST-AH-WH-' . time(),
            'status' => 'active'
        ]);
        
        // Add stock to generate history entries
        $this->stockModel->addStock($this->testProductId, $this->testWarehouseId, 100, 'initial', null, 'Initial stock');
        $this->stockModel->addStock($this->testProductId, $this->testWarehouseId, 50, 'restock', null, 'Restock');
        $this->stockModel->removeStock($this->testProductId, $this->testWarehouseId, 30, 'sale', null, 'Sale');
        $this->stockModel->adjustStock($this->testProductId, $this->testWarehouseId, -10, 'Inventory adjustment');
        
        echo "Test data setup complete.\n\n";
    }
    
    private function cleanupTestData() {
        echo "\nCleaning up test data...\n";
        
        // Delete audit logs for test tables
        $stmt = $this->db->prepare("DELETE FROM sar_inv_audit_log WHERE table_name LIKE 'test_%' OR record_id = ?");
        $stmt->execute([$this->testProductId]);
        
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
    
    private function testAuditLogCreation() {
        echo "\n📝 AUDIT LOG CREATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Create audit log entry", function() {
            $result = $this->auditLogModel->createLog(
                'test_table',
                999,
                'create',
                null,
                ['name' => 'Test Record', 'status' => 'active']
            );
            return $result !== false;
        });
        
        $this->test("Audit log captures user ID", function() {
            $logs = $this->auditLogModel->getLogsByUser($_SESSION['user_id'], 1);
            return is_array($logs) && count($logs) >= 1;
        });
        
        $this->test("Create update audit log", function() {
            $result = $this->auditLogModel->createLog(
                'test_table',
                999,
                'update',
                ['name' => 'Test Record', 'status' => 'active'],
                ['name' => 'Updated Record', 'status' => 'inactive']
            );
            return $result !== false;
        });
        
        $this->test("Create delete audit log", function() {
            $result = $this->auditLogModel->createLog(
                'test_table',
                999,
                'delete',
                ['name' => 'Updated Record', 'status' => 'inactive'],
                null
            );
            return $result !== false;
        });
        
        $this->test("Audit log stores old and new values as JSON", function() {
            $logs = $this->auditLogModel->getLogsForRecord('test_table', 999, 1);
            if (empty($logs)) return false;
            
            $log = $logs[0];
            return !empty($log['old_values']) || !empty($log['new_values']);
        });
    }
    
    private function testAuditLogRetrieval() {
        echo "\n🔍 AUDIT LOG RETRIEVAL\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Get logs for specific record", function() {
            $logs = $this->auditLogModel->getLogsForRecord('test_table', 999);
            return is_array($logs) && count($logs) >= 3; // create, update, delete
        });
        
        $this->test("Get logs for specific table", function() {
            $logs = $this->auditLogModel->getLogsForTable('test_table');
            return is_array($logs) && count($logs) >= 1;
        });
        
        $this->test("Get logs by action type", function() {
            $logs = $this->auditLogModel->getLogsByAction('create');
            return is_array($logs);
        });
        
        $this->test("Get recent logs", function() {
            $logs = $this->auditLogModel->getRecentLogs(10);
            return is_array($logs);
        });
        
        $this->test("Get log with decoded values", function() {
            $logs = $this->auditLogModel->getLogsForRecord('test_table', 999, 1);
            if (empty($logs)) return false;
            
            $log = $this->auditLogModel->getLogWithDecodedValues($logs[0]['id']);
            return $log && (isset($log['old_values_decoded']) || isset($log['new_values_decoded']));
        });
        
        $this->test("Get changes between old and new values", function() {
            $logs = $this->auditLogModel->search(['table_name' => 'test_table', 'action' => 'update'], 1);
            if (empty($logs)) return false;
            
            $changes = $this->auditLogModel->getChanges($logs[0]['id']);
            return is_array($changes);
        });
        
        $this->test("Search audit logs with filters", function() {
            $logs = $this->auditLogModel->search([
                'table_name' => 'test_table',
                'action' => 'create'
            ]);
            return is_array($logs);
        });
        
        $this->test("Count logs with filters", function() {
            $count = $this->auditLogModel->countLogs(['table_name' => 'test_table']);
            return is_numeric($count) && $count >= 1;
        });
        
        $this->test("Get available tables", function() {
            $tables = $this->auditLogModel->getAvailableTables();
            return is_array($tables);
        });
        
        $this->test("Get audit statistics", function() {
            $stats = $this->auditLogModel->getStatistics();
            return is_array($stats) && isset($stats['total_logs']);
        });
        
        $this->test("Get activity by table", function() {
            $activity = $this->auditLogModel->getActivityByTable();
            return is_array($activity);
        });
        
        $this->test("Get activity by user", function() {
            $activity = $this->auditLogModel->getActivityByUser();
            return is_array($activity);
        });
    }
    
    private function testItemHistoryRetrieval() {
        echo "\n📜 ITEM HISTORY RETRIEVAL\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Get history by product", function() {
            $history = $this->itemHistoryModel->getByProduct($this->testProductId);
            return is_array($history) && count($history) >= 1;
        });
        
        $this->test("Get history by warehouse", function() {
            $history = $this->itemHistoryModel->getByWarehouse($this->testWarehouseId);
            return is_array($history) && count($history) >= 1;
        });
        
        $this->test("Get history by transaction type", function() {
            $history = $this->itemHistoryModel->getByType('stock_in');
            return is_array($history);
        });
        
        $this->test("History includes product and warehouse names", function() {
            $history = $this->itemHistoryModel->getByProduct($this->testProductId, 1);
            if (empty($history)) return false;
            
            return isset($history[0]['product_name']) && isset($history[0]['warehouse_name']);
        });
        
        $this->test("History records balance after transaction", function() {
            $history = $this->itemHistoryModel->getByProduct($this->testProductId, 1);
            if (empty($history)) return false;
            
            return isset($history[0]['balance_after']);
        });
        
        $this->test("Get all transaction types", function() {
            $types = SarInvItemHistory::getTransactionTypes();
            return is_array($types) && count($types) >= 5;
        });
    }
    
    private function testHistoryFiltering() {
        echo "\n🔎 HISTORY FILTERING\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Filter history by product", function() {
            $history = $this->itemHistoryModel->search(['product_id' => $this->testProductId]);
            return is_array($history) && count($history) >= 1;
        });
        
        $this->test("Filter history by warehouse", function() {
            $history = $this->itemHistoryModel->search(['warehouse_id' => $this->testWarehouseId]);
            return is_array($history) && count($history) >= 1;
        });
        
        $this->test("Filter history by transaction type", function() {
            $history = $this->itemHistoryModel->search(['transaction_type' => 'stock_in']);
            return is_array($history);
        });
        
        $this->test("Filter history by date range", function() {
            $history = $this->itemHistoryModel->search([
                'date_from' => date('Y-m-d', strtotime('-1 day')),
                'date_to' => date('Y-m-d')
            ]);
            return is_array($history);
        });
        
        $this->test("Filter history by keyword", function() {
            $history = $this->itemHistoryModel->search(['keyword' => 'Test Product']);
            return is_array($history);
        });
        
        $this->test("Count filtered history", function() {
            $count = $this->itemHistoryModel->countFiltered(['product_id' => $this->testProductId]);
            return is_numeric($count) && $count >= 1;
        });
        
        $this->test("Get summary by transaction type", function() {
            $summary = $this->itemHistoryModel->getSummaryByType(['product_id' => $this->testProductId]);
            return is_array($summary);
        });
        
        $this->test("Get daily summary", function() {
            $summary = $this->itemHistoryModel->getDailySummary(['product_id' => $this->testProductId], 7);
            return is_array($summary);
        });
    }
    
    private function testHistoryPagination() {
        echo "\n📄 HISTORY PAGINATION\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Get paginated results", function() {
            $result = $this->itemHistoryModel->getPaginated(['product_id' => $this->testProductId], 1, 10);
            return is_array($result) && 
                   isset($result['data']) && 
                   isset($result['pagination']);
        });
        
        $this->test("Pagination includes current page", function() {
            $result = $this->itemHistoryModel->getPaginated([], 1, 10);
            return isset($result['pagination']['current_page']) && 
                   $result['pagination']['current_page'] == 1;
        });
        
        $this->test("Pagination includes total count", function() {
            $result = $this->itemHistoryModel->getPaginated([], 1, 10);
            return isset($result['pagination']['total']) && 
                   is_numeric($result['pagination']['total']);
        });
        
        $this->test("Pagination includes total pages", function() {
            $result = $this->itemHistoryModel->getPaginated([], 1, 10);
            return isset($result['pagination']['total_pages']) && 
                   is_numeric($result['pagination']['total_pages']);
        });
        
        $this->test("Pagination includes has_more flag", function() {
            $result = $this->itemHistoryModel->getPaginated([], 1, 10);
            return isset($result['pagination']['has_more']);
        });
    }
    
    private function testExportFunctionality() {
        echo "\n📤 EXPORT FUNCTIONALITY\n";
        echo "-" . str_repeat("-", 40) . "\n";
        
        $this->test("Export to CSV", function() {
            $result = $this->itemHistoryModel->exportToCsv(['product_id' => $this->testProductId]);
            return is_array($result) && 
                   isset($result['filename']) && 
                   isset($result['content']) &&
                   isset($result['mime_type']) &&
                   $result['mime_type'] === 'text/csv';
        });
        
        $this->test("CSV export has correct filename format", function() {
            $result = $this->itemHistoryModel->exportToCsv([]);
            return strpos($result['filename'], 'item_history_') === 0 && 
                   strpos($result['filename'], '.csv') !== false;
        });
        
        $this->test("CSV export contains header row", function() {
            $result = $this->itemHistoryModel->exportToCsv(['product_id' => $this->testProductId]);
            return strpos($result['content'], 'ID') !== false && 
                   strpos($result['content'], 'Product Name') !== false;
        });
        
        $this->test("Export to Excel", function() {
            $result = $this->itemHistoryModel->exportToExcel(['product_id' => $this->testProductId]);
            return is_array($result) && 
                   isset($result['filename']) && 
                   isset($result['content']) &&
                   isset($result['mime_type']);
        });
        
        $this->test("Excel export has correct filename format", function() {
            $result = $this->itemHistoryModel->exportToExcel([]);
            return strpos($result['filename'], 'item_history_') === 0 && 
                   strpos($result['filename'], '.xls') !== false;
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
            echo "🎉 EXCELLENT! Audit and History models are working correctly.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Most functionality is working. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! Audit and History models need attention.\n";
        }
    }
}

// Run the test suite if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    $testSuite = new SarInvAuditHistoryTest();
    $testSuite->runAllTests();
}
?>
