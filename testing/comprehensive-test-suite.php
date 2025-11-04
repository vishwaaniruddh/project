<?php
/**
 * Comprehensive Test Suite for Site Installation Management System
 * This script tests all major functionality across the application
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Site.php';
require_once __DIR__ . '/../models/Vendor.php';
require_once __DIR__ . '/../models/SiteSurvey.php';
require_once __DIR__ . '/../models/MaterialRequest.php';
require_once __DIR__ . '/../models/Installation.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Inventory.php';

class TestSuite {
    private $db;
    private $results = [];
    private $totalTests = 0;
    private $passedTests = 0;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function runAllTests() {
        echo "🚀 Starting Comprehensive Test Suite for Site Installation Management System\n";
        echo "=" . str_repeat("=", 80) . "\n\n";
        
        // Database Tests
        $this->testDatabaseConnection();
        $this->testDatabaseTables();
        
        // Model Tests
        $this->testSiteModel();
        $this->testVendorModel();
        $this->testSurveyModel();
        $this->testMaterialRequestModel();
        $this->testInstallationModel();
        $this->testUserModel();
        $this->testInventoryModel();
        
        // Authentication Tests
        $this->testAuthenticationSystem();
        
        // API Tests
        $this->testAPIEndpoints();
        
        // File System Tests
        $this->testFileSystemStructure();
        
        // Configuration Tests
        $this->testConfiguration();
        
        // Print Results
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
    
    private function testDatabaseConnection() {
        echo "\n📊 DATABASE TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $this->test("Database Connection", function() {
            return $this->db !== null && $this->db instanceof PDO;
        });
    }
    
    private function testDatabaseTables() {
        $requiredTables = [
            'users', 'sites', 'vendors', 'site_surveys', 'material_requests',
            'installation_delegations', 'inventory', 'menu_items', 'user_menu_permissions',
            'boq_items', 'cities', 'states', 'countries'
        ];
        
        foreach ($requiredTables as $table) {
            $this->test("Table exists: $table", function() use ($table) {
                $stmt = $this->db->query("SHOW TABLES LIKE '$table'");
                return $stmt->rowCount() > 0;
            });
        }
    }
    
    private function testSiteModel() {
        echo "\n🏢 SITE MODEL TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $siteModel = new Site();
        
        $this->test("Site Model - Get All Sites", function() use ($siteModel) {
            $result = $siteModel->getAllWithPagination(1, 10);
            return is_array($result) && isset($result['sites']);
        });
        
        $this->test("Site Model - Count Sites", function() use ($siteModel) {
            $result = $siteModel->getAllWithPagination(1, 10);
            return isset($result['total']) && is_numeric($result['total']);
        });
    }
    
    private function testVendorModel() {
        echo "\n👥 VENDOR MODEL TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $vendorModel = new Vendor();
        
        $this->test("Vendor Model - Get All Vendors", function() use ($vendorModel) {
            $vendors = $vendorModel->getAllVendors();
            return is_array($vendors);
        });
        
        $this->test("Vendor Model - Get Active Vendors", function() use ($vendorModel) {
            $vendors = $vendorModel->getActiveVendors();
            return is_array($vendors);
        });
        
        $this->test("Vendor Model - Get Vendor Stats", function() use ($vendorModel) {
            $stats = $vendorModel->getVendorStats();
            return is_array($stats);
        });
    }
    
    private function testSurveyModel() {
        echo "\n📋 SURVEY MODEL TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $surveyModel = new SiteSurvey();
        
        $this->test("Survey Model - Get All Surveys", function() use ($surveyModel) {
            $surveys = $surveyModel->getAllSurveys();
            return is_array($surveys);
        });
        
        $this->test("Survey Model - Get All With Details", function() use ($surveyModel) {
            $surveys = $surveyModel->getAllWithDetails();
            return is_array($surveys);
        });
    }
    
    private function testMaterialRequestModel() {
        echo "\n📦 MATERIAL REQUEST MODEL TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $materialModel = new MaterialRequest();
        
        $this->test("Material Model - Get All With Details", function() use ($materialModel) {
            $requests = $materialModel->getAllWithDetails();
            return is_array($requests);
        });
        
        $this->test("Material Model - Get Stats", function() use ($materialModel) {
            $stats = $materialModel->getStats();
            return is_array($stats) && isset($stats['total']);
        });
    }
    
    private function testInstallationModel() {
        echo "\n🔧 INSTALLATION MODEL TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $installationModel = new Installation();
        
        $this->test("Installation Model - Get All Installations", function() use ($installationModel) {
            $installations = $installationModel->getAllInstallations();
            return is_array($installations);
        });
        
        $this->test("Installation Model - Get All With Details", function() use ($installationModel) {
            $installations = $installationModel->getAllWithDetails();
            return is_array($installations);
        });
        
        $this->test("Installation Model - Get Stats", function() use ($installationModel) {
            $stats = $installationModel->getInstallationStats();
            return is_array($stats);
        });
    }
    
    private function testUserModel() {
        echo "\n👤 USER MODEL TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $userModel = new User();
        
        $this->test("User Model - Get All Users With Pagination", function() use ($userModel) {
            $result = $userModel->getAllWithPagination(1, 10);
            return is_array($result) && isset($result['users']);
        });
        
        $this->test("User Model - Check Admin Users Exist", function() use ($userModel) {
            $result = $userModel->getAllWithPagination(1, 100);
            $adminExists = false;
            foreach ($result['users'] as $user) {
                if ($user['role'] === 'admin') {
                    $adminExists = true;
                    break;
                }
            }
            return $adminExists;
        });
        
        $this->test("User Model - Get User Stats", function() use ($userModel) {
            $stats = $userModel->getUserStats();
            return is_array($stats);
        });
    }
    
    private function testInventoryModel() {
        echo "\n📊 INVENTORY MODEL TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $inventoryModel = new Inventory();
        
        $this->test("Inventory Model - Get Stock Overview", function() use ($inventoryModel) {
            $items = $inventoryModel->getStockOverview();
            return is_array($items);
        });
        
        $this->test("Inventory Model - Get Inventory Stats", function() use ($inventoryModel) {
            $stats = $inventoryModel->getInventoryStats();
            return is_array($stats);
        });
        
        $this->test("Inventory Model - Get Inward Receipts", function() use ($inventoryModel) {
            $result = $inventoryModel->getInwardReceipts(1, 10);
            return is_array($result) && isset($result['receipts']);
        });
    }
    
    private function testAuthenticationSystem() {
        echo "\n🔐 AUTHENTICATION TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $this->test("Auth Class - Methods Exist", function() {
            return method_exists('Auth', 'requireAuth') && 
                   method_exists('Auth', 'requireRole') &&
                   method_exists('Auth', 'isLoggedIn');
        });
        
        $this->test("Auth Constants - Defined", function() {
            return defined('ADMIN_ROLE') && defined('VENDOR_ROLE');
        });
    }
    
    private function testAPIEndpoints() {
        echo "\n🌐 API ENDPOINT TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $this->test("API Masters File Exists", function() {
            return file_exists(__DIR__ . '/../api/masters.php');
        });
        
        $this->test("Controllers Directory Exists", function() {
            return is_dir(__DIR__ . '/../controllers');
        });
    }
    
    private function testFileSystemStructure() {
        echo "\n📁 FILE SYSTEM TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $requiredDirectories = [
            'admin', 'vendor', 'config', 'models', 'includes', 
            'assets', 'database', 'auth'
        ];
        
        foreach ($requiredDirectories as $dir) {
            $this->test("Directory exists: $dir", function() use ($dir) {
                return is_dir(__DIR__ . "/../$dir");
            });
        }
        
        $requiredFiles = [
            'config/database.php', 'config/auth.php', 'config/constants.php',
            'includes/admin_layout.php', 'includes/vendor_layout.php'
        ];
        
        foreach ($requiredFiles as $file) {
            $this->test("File exists: $file", function() use ($file) {
                return file_exists(__DIR__ . "/../$file");
            });
        }
    }
    
    private function testConfiguration() {
        echo "\n⚙️ CONFIGURATION TESTS\n";
        echo "-" . str_repeat("-", 50) . "\n";
        
        $this->test("Constants File - APP_NAME defined", function() {
            return defined('APP_NAME');
        });
        
        $this->test("Constants File - BASE_URL defined", function() {
            return defined('BASE_URL');
        });
        
        $this->test("Database Config - DB_HOST defined", function() {
            return defined('DB_HOST');
        });
    }
    
    private function printResults() {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "📊 TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 80) . "\n";
        
        $failedTests = $this->totalTests - $this->passedTests;
        $successRate = ($this->passedTests / $this->totalTests) * 100;
        
        echo "Total Tests: {$this->totalTests}\n";
        echo "Passed: {$this->passedTests} ✅\n";
        echo "Failed: {$failedTests} ❌\n";
        echo "Success Rate: " . number_format($successRate, 1) . "%\n\n";
        
        if ($failedTests > 0) {
            echo "❌ FAILED TESTS:\n";
            echo "-" . str_repeat("-", 50) . "\n";
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
            echo "🎉 EXCELLENT! Your application is ready for production.\n";
        } elseif ($successRate >= 75) {
            echo "✅ GOOD! Your application is mostly ready. Fix the failed tests.\n";
        } else {
            echo "⚠️ WARNING! Your application needs attention before production.\n";
        }
        
        echo "\nNext Steps:\n";
        echo "1. Fix any failed tests\n";
        echo "2. Run manual testing checklist\n";
        echo "3. Test user workflows end-to-end\n";
        echo "4. Perform security testing\n";
        echo "5. Load testing with sample data\n";
    }
}

// Run the test suite
$testSuite = new TestSuite();
$testSuite->runAllTests();
?>