<?php
/**
 * Verification Script for User RBAC Implementation
 * 
 * This script verifies that all required methods and properties
 * have been added to the User model for RBAC support.
 */

require_once __DIR__ . '/../models/User.php';

echo "=== User Model RBAC Implementation Verification ===\n\n";

$user = new User();
$reflection = new ReflectionClass($user);

// Check 1: Verify fillable property exists
echo "1. Checking fillable property:\n";
try {
    $fillableProperty = $reflection->getProperty('fillable');
    $fillableProperty->setAccessible(true);
    $fillable = $fillableProperty->getValue($user);
    
    if (in_array('role_id', $fillable)) {
        echo "   ✓ 'role_id' is in fillable fields\n";
    } else {
        echo "   ✗ 'role_id' is NOT in fillable fields\n";
    }
} catch (ReflectionException $e) {
    echo "   ⚠ fillable property not found (may be inherited)\n";
}

// Check 2: Verify getRole method exists
echo "\n2. Checking getRole() method:\n";
if ($reflection->hasMethod('getRole')) {
    $method = $reflection->getMethod('getRole');
    $params = $method->getParameters();
    
    echo "   ✓ getRole() method exists\n";
    echo "   ✓ Parameters: " . count($params) . " (userId)\n";
    
    if ($method->hasReturnType()) {
        echo "   ✓ Return type: " . $method->getReturnType() . "\n";
    }
} else {
    echo "   ✗ getRole() method NOT found\n";
}

// Check 3: Verify getPermissions method exists
echo "\n3. Checking getPermissions() method:\n";
if ($reflection->hasMethod('getPermissions')) {
    $method = $reflection->getMethod('getPermissions');
    $params = $method->getParameters();
    
    echo "   ✓ getPermissions() method exists\n";
    echo "   ✓ Parameters: " . count($params) . " (userId)\n";
    
    if ($method->hasReturnType()) {
        echo "   ✓ Return type: " . $method->getReturnType() . "\n";
    }
} else {
    echo "   ✗ getPermissions() method NOT found\n";
}

// Check 4: Verify validateUserData method exists
echo "\n4. Checking validateUserData() method:\n";
if ($reflection->hasMethod('validateUserData')) {
    echo "   ✓ validateUserData() method exists\n";
    
    // Check if the method validates role_id by examining the source
    $method = $reflection->getMethod('validateUserData');
    $filename = $method->getFileName();
    $startLine = $method->getStartLine();
    $endLine = $method->getEndLine();
    
    $source = file($filename);
    $methodSource = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));
    
    if (strpos($methodSource, 'role_id') !== false) {
        echo "   ✓ Method includes role_id validation logic\n";
    } else {
        echo "   ✗ Method does NOT include role_id validation\n";
    }
} else {
    echo "   ✗ validateUserData() method NOT found\n";
}

// Check 5: Verify getAllWithPagination includes role info
echo "\n5. Checking getAllWithPagination() method:\n";
if ($reflection->hasMethod('getAllWithPagination')) {
    echo "   ✓ getAllWithPagination() method exists\n";
    
    // Check if the method includes role information
    $method = $reflection->getMethod('getAllWithPagination');
    $filename = $method->getFileName();
    $startLine = $method->getStartLine();
    $endLine = $method->getEndLine();
    
    $source = file($filename);
    $methodSource = implode('', array_slice($source, $startLine - 1, $endLine - $startLine + 1));
    
    if (strpos($methodSource, 'roles r') !== false || strpos($methodSource, 'role_name') !== false) {
        echo "   ✓ Method includes role table JOIN\n";
    } else {
        echo "   ✗ Method does NOT include role information\n";
    }
} else {
    echo "   ✗ getAllWithPagination() method NOT found\n";
}

// Check 6: Verify required dependencies
echo "\n6. Checking dependencies:\n";
$constructor = $reflection->getConstructor();
$filename = $constructor->getFileName();
$source = file_get_contents($filename);

if (strpos($source, "require_once __DIR__ . '/Role.php'") !== false) {
    echo "   ✓ Role model is required\n";
} else {
    echo "   ✗ Role model is NOT required\n";
}

if (strpos($source, "require_once __DIR__ . '/../services/PermissionService.php'") !== false) {
    echo "   ✓ PermissionService is required\n";
} else {
    echo "   ✗ PermissionService is NOT required\n";
}

echo "\n=== Verification Complete ===\n";
echo "\nNote: Functional testing requires RBAC database tables to be set up.\n";
echo "Run database/setup_rbac_complete.php to create the required schema.\n";
