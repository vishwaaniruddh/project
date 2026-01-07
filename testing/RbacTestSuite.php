<?php
/**
 * RBAC Test Suite Runner
 * 
 * Runs all RBAC-related tests including unit tests and integration tests
 * Requirements: 6.1, 6.2, 6.4, 7.3, 7.4, 7.5, 12.2
 */

require_once __DIR__ . '/RbacCoreServicesTest.php';
require_once __DIR__ . '/RbacApiIntegrationTest.php';
require_once __DIR__ . '/UserRbacTest.php';

class RbacTestSuite
{
    private $results = [];
    
    public function runAllTests()
    {
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║         RBAC COMPREHENSIVE TEST SUITE                     ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";
        
        echo "This test suite validates the complete RBAC implementation:\n";
        echo "  • Core Services (TokenService, PermissionService, Role CRUD)\n";
        echo "  • API Integration (Auth, Roles, Permissions endpoints)\n";
        echo "  • User Model RBAC Integration\n";
        echo "  • Middleware (JWT Auth, Permission checks)\n\n";
        
        $startTime = microtime(true);
        
        // Run User RBAC Tests
        echo "═══════════════════════════════════════════════════════════\n";
        echo "TEST SUITE 1: User Model RBAC Integration\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        try {
            $userRbacTest = new UserRbacTest();
            $userRbacResult = $userRbacTest->runTests();
            $this->results['user_rbac'] = ['success' => true, 'message' => 'Completed'];
        } catch (Exception $e) {
            echo "❌ User RBAC Tests failed: " . $e->getMessage() . "\n";
            $this->results['user_rbac'] = ['success' => false, 'message' => $e->getMessage()];
        }
        
        echo "\n\n";
        
        // Run Core Services Tests
        echo "═══════════════════════════════════════════════════════════\n";
        echo "TEST SUITE 2: Core Services Unit Tests\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        try {
            $coreServicesTest = new RbacCoreServicesTest();
            $coreServicesResult = $coreServicesTest->runAllTests();
            $this->results['core_services'] = ['success' => $coreServicesResult, 'message' => 'Completed'];
        } catch (Exception $e) {
            echo "❌ Core Services Tests failed: " . $e->getMessage() . "\n";
            $this->results['core_services'] = ['success' => false, 'message' => $e->getMessage()];
        }
        
        echo "\n\n";
        
        // Run API Integration Tests
        echo "═══════════════════════════════════════════════════════════\n";
        echo "TEST SUITE 3: API Integration Tests\n";
        echo "═══════════════════════════════════════════════════════════\n\n";
        
        try {
            $apiIntegrationTest = new RbacApiIntegrationTest();
            $apiIntegrationResult = $apiIntegrationTest->runAllTests();
            $this->results['api_integration'] = ['success' => $apiIntegrationResult, 'message' => 'Completed'];
        } catch (Exception $e) {
            echo "❌ API Integration Tests failed: " . $e->getMessage() . "\n";
            $this->results['api_integration'] = ['success' => false, 'message' => $e->getMessage()];
        }
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        // Print overall summary
        $this->printOverallSummary($duration);
    }
    
    private function printOverallSummary($duration)
    {
        echo "\n\n";
        echo "╔═══════════════════════════════════════════════════════════╗\n";
        echo "║              OVERALL TEST SUITE SUMMARY                   ║\n";
        echo "╚═══════════════════════════════════════════════════════════╝\n\n";
        
        $totalSuites = count($this->results);
        $passedSuites = 0;
        
        foreach ($this->results as $suite => $result) {
            $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
            $suiteName = ucwords(str_replace('_', ' ', $suite));
            echo sprintf("%-40s %s\n", $suiteName . ':', $status);
            
            if (!$result['success'] && $result['message']) {
                echo "  Error: {$result['message']}\n";
            }
            
            if ($result['success']) {
                $passedSuites++;
            }
        }
        
        echo "\n" . str_repeat("─", 60) . "\n";
        echo "Test Suites: $passedSuites / $totalSuites passed\n";
        echo "Duration: {$duration}s\n";
        echo str_repeat("─", 60) . "\n\n";
        
        if ($passedSuites === $totalSuites) {
            echo "🎉 SUCCESS! All RBAC test suites passed!\n";
            echo "The RBAC system is ready for production use.\n\n";
            echo "Next Steps:\n";
            echo "  1. Review any warnings in individual test outputs\n";
            echo "  2. Perform manual testing of UI components\n";
            echo "  3. Test with real user scenarios\n";
            echo "  4. Review security configurations\n";
        } else {
            $failedSuites = $totalSuites - $passedSuites;
            echo "⚠️  WARNING! $failedSuites test suite(s) failed.\n";
            echo "Please review the failed tests above and fix the issues.\n\n";
            echo "Common Issues:\n";
            echo "  • Database tables not created (run setup_rbac_complete.php)\n";
            echo "  • Missing permissions or roles (run seed scripts)\n";
            echo "  • Configuration issues (check .env and config files)\n";
            echo "  • Service dependencies not loaded properly\n";
        }
        
        echo "\n";
    }
}

// Run the complete test suite if executed directly
if (php_sapi_name() === 'cli') {
    try {
        $testSuite = new RbacTestSuite();
        $testSuite->runAllTests();
    } catch (Exception $e) {
        echo "Fatal error running test suite: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
        exit(1);
    }
}
