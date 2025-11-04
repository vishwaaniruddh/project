<?php
/**
 * Debug Users Script
 * Shows all users in the database for debugging login issues
 */

require_once 'config/database.php';

echo "🔍 User Database Debug Information\n";
echo "=" . str_repeat("=", 50) . "\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Get all users
    $stmt = $db->query("SELECT id, username, email, role, status, password_hash, plain_password, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total users found: " . count($users) . "\n\n";
    
    if (empty($users)) {
        echo "❌ No users found in database!\n";
        echo "Run the test data generator: php testing/load-test-data.php\n";
    } else {
        echo "Users in database:\n";
        echo str_repeat("-", 100) . "\n";
        printf("%-5s %-15s %-25s %-10s %-10s %-15s %-15s %s\n", 
               'ID', 'Username', 'Email', 'Role', 'Status', 'Has Hash', 'Has Plain', 'Created');
        echo str_repeat("-", 100) . "\n";
        
        foreach ($users as $user) {
            printf("%-5s %-15s %-25s %-10s %-10s %-15s %-15s %s\n",
                   $user['id'],
                   $user['username'],
                   $user['email'] ?? 'N/A',
                   $user['role'],
                   $user['status'],
                   !empty($user['password_hash']) ? 'Yes' : 'No',
                   !empty($user['plain_password']) ? 'Yes' : 'No',
                   $user['created_at']
            );
        }
        
        echo str_repeat("-", 100) . "\n\n";
        
        // Test password verification for admin_test
        echo "🔐 Password Verification Test:\n";
        $testUser = null;
        foreach ($users as $user) {
            if ($user['username'] === 'admin_test') {
                $testUser = $user;
                break;
            }
        }
        
        if ($testUser) {
            echo "Testing admin_test user:\n";
            echo "- Username: " . $testUser['username'] . "\n";
            echo "- Role: " . $testUser['role'] . "\n";
            echo "- Status: " . $testUser['status'] . "\n";
            echo "- Has password_hash: " . (!empty($testUser['password_hash']) ? 'Yes' : 'No') . "\n";
            echo "- Has plain_password: " . (!empty($testUser['plain_password']) ? 'Yes' : 'No') . "\n";
            
            // Test password verification
            $testPassword = 'admin123';
            if (!empty($testUser['password_hash'])) {
                $hashVerified = password_verify($testPassword, $testUser['password_hash']);
                echo "- Password hash verification: " . ($hashVerified ? '✅ PASS' : '❌ FAIL') . "\n";
            }
            
            if (!empty($testUser['plain_password'])) {
                $plainVerified = ($testPassword === $testUser['plain_password']);
                echo "- Plain password verification: " . ($plainVerified ? '✅ PASS' : '❌ FAIL') . "\n";
            }
        } else {
            echo "❌ admin_test user not found!\n";
            echo "Available usernames: " . implode(', ', array_column($users, 'username')) . "\n";
        }
    }
    
    echo "\n";
    
    // Check table structure
    echo "📋 Users Table Structure:\n";
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")" . 
             ($column['Null'] === 'NO' ? ' NOT NULL' : '') . 
             ($column['Key'] === 'PRI' ? ' PRIMARY KEY' : '') . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    echo "Check your database configuration in config/database.php\n";
}

echo "\n";
echo "💡 Quick Fixes:\n";
echo "1. If no users exist: Run 'php testing/load-test-data.php'\n";
echo "2. If password fails: Check password_hash vs plain_password fields\n";
echo "3. If user not found: Check exact username spelling\n";
echo "4. Test login at: /admin/login.php (click 'Show Debug Info')\n";

?>