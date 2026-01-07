<?php
/**
 * Comprehensive Test Suite for SAR Inventory Management System
 * Runs all unit tests for the inventory system
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';

// Include all test files
require_once __DIR__ . '/SarInvWarehouseTest.php';
require_once __DIR__ . '/SarInvProductCategoryTest.php';
require_once __DIR__ . '/SarInvStockTest.php';
require_once __DIR__ . '/SarInvDispatchTransferTest.php';
require_once __DIR__ . '/SarInvAssetRepairTest.php';
require_once __DIR__ . '/SarInvMaterialTest.php';
require_once __DIR__ . '/SarInvAuditHistoryTest.php';

class SarInvTestSuite {
    private $results = [];
    private $totalSuites = 0;
    private $passedSuites = 0;
    
    public function runAllTests() {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════════╗\n";
        echo "║     SAR INVENTORY MANAGEMENT SYSTEM - COMPREHENSIVE TEST SUITE   ║\n";
        echo "╚══════════════════════════════════════════════════════════════════╝\n\n";
        
        $startTime = microtime(true);
        
        // Run each test suite
        $this->runTestSuite('Warehouse CRUD Operations', new SarInvWarehouseTest());
        $this->runTestSuite('Product & Category Operations', new SarInvProductCategoryTest());
        $this->runTestSuite('Stock Operations', new SarInvStockTest());
        $this->runTestSuite('Dispatch & Transfer Workflows', new SarInvDispatchTransferTest());
        $this->runTestSuite('Asset & Repair Operations', new SarInvAssetRepairTest());
        $this->runTestSuite('Material Management', new SarInvMaterialTest());
        $this->runTestSuite('Audit & History Systems', new SarInvAuditHistoryTest());
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        // Print final summary
        $this->printFinalSummary($duration);
    }
    
    private function runTestSuite($name, $testInstance) {
        $this->totalSuites++;
        
        echo "\n";
        echo "┌" . str_repeat("─", 68) . "┐\n";
        echo "│ Running: " . str_pad($name, 57) . "│\n";
        echo "└" . str_repeat("─", 68) . "┘\n";
        
        try {
            $passed = $testInstance->runAllTests();
            $this->results[$name] = $passed;
            
            if ($passed) {
                $this->passedSuites++;
            }
        } catch (Exception $e) {
            echo "❌ SUITE ERROR: " . $e->getMessage() . "\n";
            $this->results[$name] = false;
        }
    }
    
    private function printFinalSummary($duration) {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════════╗\n";
        echo "║                    FINAL TEST RESULTS SUMMARY                    ║\n";
        echo "╠══════════════════════════════════════════════════════════════════╣\n";
        
        foreach ($this->results as $suite => $passed) {
            $status = $passed ? "✅ PASS" : "❌ FAIL";
            $paddedSuite = str_pad($suite, 50);
            echo "║ {$paddedSuite} {$status}   ║\n";
        }
        
        echo "╠══════════════════════════════════════════════════════════════════╣\n";
        
        $failedSuites = $this->totalSuites - $this->passedSuites;
        $successRate = $this->totalSuites > 0 ? ($this->passedSuites / $this->totalSuites) * 100 : 0;
        
        echo "║ Total Test Suites: " . str_pad($this->totalSuites, 46) . "║\n";
        echo "║ Passed: " . str_pad($this->passedSuites . " ✅", 57) . "║\n";
        echo "║ Failed: " . str_pad($failedSuites . " ❌", 57) . "║\n";
        echo "║ Success Rate: " . str_pad(number_format($successRate, 1) . "%", 51) . "║\n";
        echo "║ Duration: " . str_pad($duration . " seconds", 55) . "║\n";
        
        echo "╠══════════════════════════════════════════════════════════════════╣\n";
        
        if ($successRate >= 90) {
            echo "║ 🎉 EXCELLENT! SAR Inventory System is ready for production.      ║\n";
        } elseif ($successRate >= 75) {
            echo "║ ✅ GOOD! Most tests passing. Review failed suites.               ║\n";
        } else {
            echo "║ ⚠️  WARNING! Multiple test failures. Review before deployment.   ║\n";
        }
        
        echo "╚══════════════════════════════════════════════════════════════════╝\n\n";
        
        // Return exit code based on results
        return $failedSuites === 0 ? 0 : 1;
    }
}

// Run the comprehensive test suite if called directly
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'] ?? '')) {
    $testSuite = new SarInvTestSuite();
    $exitCode = $testSuite->runAllTests();
    exit($exitCode);
}
?>
