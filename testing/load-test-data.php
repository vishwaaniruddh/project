<?php
/**
 * Load Test Data Generator
 * Creates sample data for testing the Site Installation Management System
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Site.php';
require_once __DIR__ . '/../models/Vendor.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/SiteSurvey.php';
require_once __DIR__ . '/../models/MaterialRequest.php';
require_once __DIR__ . '/../models/Installation.php';

class LoadTestDataGenerator {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function generateTestData() {
        echo "🚀 Generating Load Test Data for Site Installation Management System\n";
        echo "=" . str_repeat("=", 70) . "\n\n";
        
        try {
            // Generate test users
            $this->generateUsers();
            
            // Generate test vendors
            $this->generateVendors();
            
            // Generate test sites
            $this->generateSites();
            
            // Generate test surveys
            $this->generateSurveys();
            
            // Generate test material requests
            $this->generateMaterialRequests();
            
            // Generate test installations
            $this->generateInstallations();
            
            echo "\n✅ Test data generation completed successfully!\n";
            echo "📊 Summary:\n";
            $this->printDataSummary();
            
        } catch (Exception $e) {
            echo "❌ Error generating test data: " . $e->getMessage() . "\n";
        }
    }
    
    private function generateUsers() {
        echo "👤 Generating test users...\n";
        
        $users = [
            ['username' => 'admin_test', 'email' => 'admin@test.com', 'role' => 'admin', 'password_hash' => password_hash('admin123', PASSWORD_DEFAULT), 'status' => 'active'],
            ['username' => 'vendor_test1', 'email' => 'vendor1@test.com', 'role' => 'vendor', 'password_hash' => password_hash('vendor123', PASSWORD_DEFAULT), 'status' => 'active'],
            ['username' => 'vendor_test2', 'email' => 'vendor2@test.com', 'role' => 'vendor', 'password_hash' => password_hash('vendor123', PASSWORD_DEFAULT), 'status' => 'active'],
            ['username' => 'vendor_test3', 'email' => 'vendor3@test.com', 'role' => 'vendor', 'password_hash' => password_hash('vendor123', PASSWORD_DEFAULT), 'status' => 'active'],
        ];
        
        foreach ($users as $user) {
            $this->insertIfNotExists('users', $user, ['username' => $user['username']]);
        }
        
        echo "   ✅ Generated " . count($users) . " test users\n";
    }
    
    private function generateVendors() {
        echo "🏢 Generating test vendors...\n";
        
        $vendors = [
            ['name' => 'TechInstall Solutions', 'email' => 'contact@techinstall.com', 'phone' => '9876543210', 'contact_person' => 'John Smith', 'status' => 'active'],
            ['name' => 'QuickFix Systems', 'email' => 'info@quickfix.com', 'phone' => '9876543211', 'contact_person' => 'Jane Doe', 'status' => 'active'],
            ['name' => 'ProInstall Corp', 'email' => 'support@proinstall.com', 'phone' => '9876543212', 'contact_person' => 'Mike Johnson', 'status' => 'active'],
            ['name' => 'EliteSetup Ltd', 'email' => 'hello@elitesetup.com', 'phone' => '9876543213', 'contact_person' => 'Sarah Wilson', 'status' => 'active'],
            ['name' => 'FastTrack Installations', 'email' => 'team@fasttrack.com', 'phone' => '9876543214', 'contact_person' => 'David Brown', 'status' => 'active'],
        ];
        
        foreach ($vendors as $vendor) {
            $this->insertIfNotExists('vendors', $vendor, ['name' => $vendor['name']]);
        }
        
        echo "   ✅ Generated " . count($vendors) . " test vendors\n";
    }
    
    private function generateSites() {
        echo "🏪 Generating test sites...\n";
        
        $cities = ['Mumbai', 'Delhi', 'Bangalore', 'Chennai', 'Kolkata', 'Hyderabad', 'Pune', 'Ahmedabad'];
        $states = ['Maharashtra', 'Delhi', 'Karnataka', 'Tamil Nadu', 'West Bengal', 'Telangana', 'Maharashtra', 'Gujarat'];
        $customers = ['Reliance Retail', 'Future Group', 'Aditya Birla', 'Tata Group', 'ITC Limited'];
        $banks = ['HDFC Bank', 'ICICI Bank', 'SBI', 'Axis Bank', 'Kotak Bank'];
        
        $sites = [];
        for ($i = 1; $i <= 50; $i++) {
            $cityIndex = array_rand($cities);
            $sites[] = [
                'site_id' => 'SITE' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'location' => $cities[$cityIndex] . ' Store ' . $i,
                'city' => $cities[$cityIndex],
                'state' => $states[$cityIndex],
                'country' => 'India',
                'customer' => $customers[array_rand($customers)],
                'bank' => $banks[array_rand($banks)],
                'activity_status' => ['active', 'pending', 'completed'][array_rand(['active', 'pending', 'completed'])],
                'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 90) . ' days'))
            ];
        }
        
        foreach ($sites as $site) {
            $this->insertIfNotExists('sites', $site, ['site_id' => $site['site_id']]);
        }
        
        echo "   ✅ Generated " . count($sites) . " test sites\n";
    }
    
    private function generateSurveys() {
        echo "📋 Generating test surveys...\n";
        
        // Get some sites and vendors for surveys
        $sites = $this->db->query("SELECT id FROM sites LIMIT 30")->fetchAll();
        $vendors = $this->db->query("SELECT id FROM vendors LIMIT 5")->fetchAll();
        
        $surveyCount = 0;
        foreach ($sites as $site) {
            if (rand(1, 100) <= 70) { // 70% chance of having a survey
                $vendor = $vendors[array_rand($vendors)];
                $survey = [
                    'site_id' => $site['id'],
                    'vendor_id' => $vendor['id'],
                    'survey_status' => ['pending', 'approved', 'rejected'][array_rand(['pending', 'approved', 'rejected'])],
                    'store_model' => 'Model ' . rand(1, 5),
                    'floor_height' => rand(8, 15) . ' feet',
                    'ceiling_type' => ['False Ceiling', 'Concrete', 'Metal'][array_rand(['False Ceiling', 'Concrete', 'Metal'])],
                    'total_cameras' => rand(10, 50),
                    'analytic_cameras' => rand(5, 20),
                    'existing_poe_rack' => ['Yes', 'No'][array_rand(['Yes', 'No'])],
                    'technical_remarks' => 'Survey completed for site. All requirements noted.',
                    'submitted_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days')),
                    'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 60) . ' days'))
                ];
                
                $this->insertIfNotExists('site_surveys', $survey, ['site_id' => $site['id'], 'vendor_id' => $vendor['id']]);
                $surveyCount++;
            }
        }
        
        echo "   ✅ Generated $surveyCount test surveys\n";
    }
    
    private function generateMaterialRequests() {
        echo "📦 Generating test material requests...\n";
        
        $sites = $this->db->query("SELECT id FROM sites LIMIT 25")->fetchAll();
        $vendors = $this->db->query("SELECT id FROM vendors LIMIT 5")->fetchAll();
        
        $materials = [
            ['name' => 'IP Camera', 'unit' => 'piece'],
            ['name' => 'Network Cable', 'unit' => 'meter'],
            ['name' => 'POE Switch', 'unit' => 'piece'],
            ['name' => 'Hard Disk', 'unit' => 'piece'],
            ['name' => 'Monitor', 'unit' => 'piece'],
            ['name' => 'Mounting Bracket', 'unit' => 'piece'],
        ];
        
        $requestCount = 0;
        foreach ($sites as $site) {
            if (rand(1, 100) <= 60) { // 60% chance of having a material request
                $vendor = $vendors[array_rand($vendors)];
                $items = [];
                
                // Generate 2-5 items per request
                for ($i = 0; $i < rand(2, 5); $i++) {
                    $material = $materials[array_rand($materials)];
                    $items[] = [
                        'material_name' => $material['name'],
                        'quantity' => rand(1, 20),
                        'unit' => $material['unit'],
                        'reason' => 'Required for installation'
                    ];
                }
                
                $request = [
                    'site_id' => $site['id'],
                    'vendor_id' => $vendor['id'],
                    'request_date' => date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')),
                    'required_date' => date('Y-m-d', strtotime('+' . rand(1, 15) . ' days')),
                    'items' => json_encode($items),
                    'status' => ['pending', 'approved', 'dispatched', 'completed'][array_rand(['pending', 'approved', 'dispatched', 'completed'])],
                    'request_notes' => 'Materials required for site installation project.',
                    'created_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
                ];
                
                $stmt = $this->db->prepare("INSERT INTO material_requests (site_id, vendor_id, request_date, required_date, items, status, request_notes, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $request['site_id'], $request['vendor_id'], $request['request_date'],
                    $request['required_date'], $request['items'], $request['status'],
                    $request['request_notes'], $request['created_date']
                ]);
                $requestCount++;
            }
        }
        
        echo "   ✅ Generated $requestCount test material requests\n";
    }
    
    private function generateInstallations() {
        echo "🔧 Generating test installations...\n";
        
        $surveys = $this->db->query("SELECT id, site_id, vendor_id FROM site_surveys WHERE survey_status = 'approved' LIMIT 20")->fetchAll();
        $users = $this->db->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetch();
        
        $installationCount = 0;
        foreach ($surveys as $survey) {
            if (rand(1, 100) <= 80) { // 80% chance of approved surveys having installations
                $installation = [
                    'survey_id' => $survey['id'],
                    'site_id' => $survey['site_id'],
                    'vendor_id' => $survey['vendor_id'],
                    'delegated_by' => $users['id'],
                    'delegation_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
                    'expected_start_date' => date('Y-m-d', strtotime('-' . rand(1, 15) . ' days')),
                    'expected_completion_date' => date('Y-m-d', strtotime('+' . rand(5, 30) . ' days')),
                    'status' => ['assigned', 'in_progress', 'completed', 'on_hold'][array_rand(['assigned', 'in_progress', 'completed', 'on_hold'])],
                    'priority' => ['low', 'medium', 'high'][array_rand(['low', 'medium', 'high'])],
                    'installation_type' => 'standard',
                    'notes' => 'Installation delegated to vendor for completion.',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days'))
                ];
                
                $stmt = $this->db->prepare("INSERT INTO installation_delegations (survey_id, site_id, vendor_id, delegated_by, delegation_date, expected_start_date, expected_completion_date, status, priority, installation_type, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $installation['survey_id'], $installation['site_id'], $installation['vendor_id'],
                    $installation['delegated_by'], $installation['delegation_date'], $installation['expected_start_date'],
                    $installation['expected_completion_date'], $installation['status'], $installation['priority'],
                    $installation['installation_type'], $installation['notes'], $installation['created_at']
                ]);
                $installationCount++;
            }
        }
        
        echo "   ✅ Generated $installationCount test installations\n";
    }
    
    private function insertIfNotExists($table, $data, $checkFields) {
        // Build WHERE clause for checking existence
        $whereConditions = [];
        $whereValues = [];
        foreach ($checkFields as $field => $value) {
            $whereConditions[] = "$field = ?";
            $whereValues[] = $value;
        }
        $whereClause = implode(' AND ', $whereConditions);
        
        // Check if record exists
        $checkSql = "SELECT COUNT(*) FROM $table WHERE $whereClause";
        $stmt = $this->db->prepare($checkSql);
        $stmt->execute($whereValues);
        
        if ($stmt->fetchColumn() == 0) {
            // Insert new record
            $fields = array_keys($data);
            $placeholders = str_repeat('?,', count($fields) - 1) . '?';
            $insertSql = "INSERT INTO $table (" . implode(',', $fields) . ") VALUES ($placeholders)";
            
            $stmt = $this->db->prepare($insertSql);
            $stmt->execute(array_values($data));
        }
    }
    
    private function printDataSummary() {
        $tables = ['users', 'vendors', 'sites', 'site_surveys', 'material_requests', 'installation_delegations'];
        
        foreach ($tables as $table) {
            $stmt = $this->db->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "   $table: $count records\n";
        }
    }
}

// Generate test data
$generator = new LoadTestDataGenerator();
$generator->generateTestData();
?>