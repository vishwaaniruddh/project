<?php
/**
 * Add New Inventory Menu for SAR Inventory Module
 * This script adds a new "New Inventory" menu section with all submenus
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Adding New Inventory Menu...\n";
    
    // First, check if the menu already exists
    $stmt = $db->prepare("SELECT id FROM menu_items WHERE title = 'New Inventory' AND parent_id IS NULL");
    $stmt->execute();
    $existingMenu = $stmt->fetch();
    
    if ($existingMenu) {
        echo "New Inventory menu already exists (ID: {$existingMenu['id']}). Skipping creation.\n";
        $parentId = $existingMenu['id'];
    } else {
        // Get the max sort_order
        $stmt = $db->query("SELECT MAX(sort_order) as max_order FROM menu_items WHERE parent_id IS NULL");
        $result = $stmt->fetch();
        $sortOrder = ($result['max_order'] ?? 0) + 10;
        
        // Insert the parent menu item "New Inventory"
        $stmt = $db->prepare("INSERT INTO menu_items (title, url, icon, parent_id, sort_order, status) 
                              VALUES (?, NULL, ?, NULL, ?, 'active')");
        $stmt->execute(['New Inventory', 'inventory', $sortOrder]);
        $parentId = $db->lastInsertId();
        echo "Created parent menu 'New Inventory' with ID: $parentId\n";
    }
    
    // Define submenus for New Inventory
    $submenus = [
        ['title' => 'Dashboard', 'url' => '/admin/sar-inventory/', 'icon' => 'dashboard', 'sort_order' => 1],
        ['title' => 'Warehouses', 'url' => '/admin/sar-inventory/warehouses/', 'icon' => 'inventory', 'sort_order' => 2],
        ['title' => 'Product Categories', 'url' => '/admin/sar-inventory/product-category/', 'icon' => 'boq', 'sort_order' => 3],
        ['title' => 'Products', 'url' => '/admin/sar-inventory/products/', 'icon' => 'inventory', 'sort_order' => 4],
        ['title' => 'Stock Entry', 'url' => '/admin/sar-inventory/stock-entry/', 'icon' => 'requests', 'sort_order' => 5],
        ['title' => 'Dispatches', 'url' => '/admin/sar-inventory/dispatches/', 'icon' => 'courier', 'sort_order' => 6],
        ['title' => 'Transfers', 'url' => '/admin/sar-inventory/transfers/', 'icon' => 'courier', 'sort_order' => 7],
    ];
    
    // Insert submenus
    foreach ($submenus as $submenu) {
        // Check if submenu already exists
        $checkStmt = $db->prepare("SELECT id FROM menu_items WHERE url = ? AND parent_id = ?");
        $checkStmt->execute([$submenu['url'], $parentId]);
        $existing = $checkStmt->fetch();
        
        if (!$existing) {
            $insertStmt = $db->prepare("INSERT INTO menu_items (title, url, icon, parent_id, sort_order, status) 
                                        VALUES (?, ?, ?, ?, ?, 'active')");
            $insertStmt->execute([
                $submenu['title'],
                $submenu['url'],
                $submenu['icon'],
                $parentId,
                $submenu['sort_order']
            ]);
            $submenuId = $db->lastInsertId();
            echo "Added submenu: {$submenu['title']} (ID: $submenuId)\n";
        } else {
            echo "Submenu already exists: {$submenu['title']}\n";
        }
    }
    
    // Now assign permissions to admin users
    echo "\nAssigning menu permissions to admin users...\n";
    
    // Get all menu IDs for New Inventory
    $stmt = $db->prepare("SELECT id, title FROM menu_items WHERE id = ? OR parent_id = ?");
    $stmt->execute([$parentId, $parentId]);
    $menuIds = $stmt->fetchAll();
    
    // Get all admin users
    $stmt = $db->query("SELECT id, username FROM users WHERE role = 'admin'");
    $adminUsers = $stmt->fetchAll();
    
    if (!empty($adminUsers)) {
        foreach ($adminUsers as $admin) {
            foreach ($menuIds as $menu) {
                // Check if permission already exists
                $checkStmt = $db->prepare("SELECT id FROM user_menu_permissions WHERE user_id = ? AND menu_item_id = ?");
                $checkStmt->execute([$admin['id'], $menu['id']]);
                
                if (!$checkStmt->fetch()) {
                    $insertPermStmt = $db->prepare("INSERT INTO user_menu_permissions (user_id, menu_item_id, can_access) 
                                                    VALUES (?, ?, 1)");
                    $insertPermStmt->execute([$admin['id'], $menu['id']]);
                    echo "Added permission for user '{$admin['username']}' to menu '{$menu['title']}'\n";
                }
            }
        }
    } else {
        echo "No admin users found.\n";
    }
    
    // Also add role-based permissions if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'role_menu_permissions'");
    if ($stmt->fetch()) {
        echo "\nAdding role-based permissions...\n";
        foreach ($menuIds as $menu) {
            $checkStmt = $db->prepare("SELECT id FROM role_menu_permissions WHERE role = 'admin' AND menu_item_id = ?");
            $checkStmt->execute([$menu['id']]);
            
            if (!$checkStmt->fetch()) {
                $insertPermStmt = $db->prepare("INSERT INTO role_menu_permissions (role, menu_item_id, can_access) 
                                                VALUES ('admin', ?, 1)");
                $insertPermStmt->execute([$menu['id']]);
                echo "Added role permission for: {$menu['title']}\n";
            }
        }
    }
    
    echo "\n✅ New Inventory menu setup complete!\n";
    echo "Please refresh the admin panel to see the new menu.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
