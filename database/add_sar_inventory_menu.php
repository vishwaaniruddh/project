<?php
/**
 * Add SAR Inventory Management menu entries
 * This migration adds the "Inventory New" menu with all child menus
 * and sets up proper permissions for admin and vendor roles.
 * 
 * Menu IDs start from 100 to avoid conflicts with existing menus.
 * Uses INSERT statements only - does NOT modify existing menu items.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Adding SAR Inventory Management menu to the system...\n";
    echo "=".str_repeat("=", 50)."\n\n";
    
    // Define menu IDs (starting from 100 to avoid conflicts)
    $parentMenuId = 100;
    $childMenuIds = [
        'dashboard'        => 101,
        'warehouses'       => 102,
        'product_category' => 103,
        'products'         => 104,
        'stock_entry'      => 105,
        'dispatches'       => 106,
        'transfers'        => 107,
        'assets'           => 108,
        'item_history'     => 109,
        'repairs'          => 110,
        'materials'        => 111,
        'reports'          => 112,
        'audit_log'        => 113
    ];
    
    // Check if parent menu already exists
    $stmt = $db->prepare("SELECT id FROM menu_items WHERE id = ? OR title = 'Inventory New'");
    $stmt->execute([$parentMenuId]);
    $existingMenu = $stmt->fetch();
    
    if ($existingMenu) {
        echo "⚠ Inventory New menu already exists with ID: " . $existingMenu['id'] . "\n";
        echo "Skipping menu creation. Run cleanup script first if you want to recreate.\n";
        exit(0);
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Insert parent menu "Inventory New"
        $stmt = $db->prepare("
            INSERT INTO menu_items (id, parent_id, title, icon, url, sort_order, status) 
            VALUES (?, NULL, 'Inventory New', 'inventory', NULL, 100, 'active')
        ");
        $stmt->execute([$parentMenuId]);
        echo "✓ Created parent menu 'Inventory New' with ID: $parentMenuId\n";
        
        // Define child menus with their properties
        $childMenus = [
            [
                'id' => $childMenuIds['dashboard'],
                'title' => 'Dashboard',
                'icon' => 'dashboard',
                'url' => '/admin/sar-inventory/',
                'sort_order' => 1
            ],
            [
                'id' => $childMenuIds['warehouses'],
                'title' => 'Warehouses',
                'icon' => 'warehouse',
                'url' => '/admin/sar-inventory/warehouses/',
                'sort_order' => 2
            ],
            [
                'id' => $childMenuIds['product_category'],
                'title' => 'Product Category',
                'icon' => 'category',
                'url' => '/admin/sar-inventory/product-category/',
                'sort_order' => 3
            ],
            [
                'id' => $childMenuIds['products'],
                'title' => 'Products',
                'icon' => 'product',
                'url' => '/admin/sar-inventory/products/',
                'sort_order' => 4
            ],
            [
                'id' => $childMenuIds['stock_entry'],
                'title' => 'Stock Entry',
                'icon' => 'stock',
                'url' => '/admin/sar-inventory/stock-entry/',
                'sort_order' => 5
            ],
            [
                'id' => $childMenuIds['dispatches'],
                'title' => 'Dispatches',
                'icon' => 'dispatch',
                'url' => '/admin/sar-inventory/dispatches/',
                'sort_order' => 6
            ],
            [
                'id' => $childMenuIds['transfers'],
                'title' => 'Transfers',
                'icon' => 'transfer',
                'url' => '/admin/sar-inventory/transfers/',
                'sort_order' => 7
            ],
            [
                'id' => $childMenuIds['assets'],
                'title' => 'Assets',
                'icon' => 'asset',
                'url' => '/admin/sar-inventory/assets/',
                'sort_order' => 8
            ],
            [
                'id' => $childMenuIds['item_history'],
                'title' => 'Item History',
                'icon' => 'history',
                'url' => '/admin/sar-inventory/item-history/',
                'sort_order' => 9
            ],
            [
                'id' => $childMenuIds['repairs'],
                'title' => 'Repairs',
                'icon' => 'repair',
                'url' => '/admin/sar-inventory/repairs/',
                'sort_order' => 10
            ],
            [
                'id' => $childMenuIds['materials'],
                'title' => 'Materials',
                'icon' => 'material',
                'url' => '/admin/sar-inventory/materials/',
                'sort_order' => 11
            ],
            [
                'id' => $childMenuIds['reports'],
                'title' => 'Reports',
                'icon' => 'reports',
                'url' => '/admin/sar-inventory/reports/',
                'sort_order' => 12
            ],
            [
                'id' => $childMenuIds['audit_log'],
                'title' => 'Audit Log',
                'icon' => 'audit',
                'url' => '/admin/sar-inventory/audit-log/',
                'sort_order' => 13
            ]
        ];
        
        // Insert child menus
        $stmt = $db->prepare("
            INSERT INTO menu_items (id, parent_id, title, icon, url, sort_order, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        
        foreach ($childMenus as $menu) {
            $stmt->execute([
                $menu['id'],
                $parentMenuId,
                $menu['title'],
                $menu['icon'],
                $menu['url'],
                $menu['sort_order']
            ]);
            echo "  ✓ Created child menu '{$menu['title']}' with ID: {$menu['id']}\n";
        }
        
        echo "\n";
        
        // =====================================================
        // Set up role permissions
        // =====================================================
        
        echo "Setting up role permissions...\n";
        
        // All menu IDs (parent + children)
        $allMenuIds = array_merge([$parentMenuId], array_values($childMenuIds));
        
        // Admin role gets access to ALL menus
        $stmtRole = $db->prepare("
            INSERT INTO role_menu_permissions (role, menu_item_id, can_access) 
            VALUES ('admin', ?, TRUE)
        ");
        
        foreach ($allMenuIds as $menuId) {
            $stmtRole->execute([$menuId]);
        }
        echo "✓ Added admin role permissions for all " . count($allMenuIds) . " menu items\n";
        
        // Vendor role gets access to specific menus only
        // Vendors can view: Dashboard, Stock Entry, Dispatches, Item History
        $vendorMenuIds = [
            $parentMenuId,
            $childMenuIds['dashboard'],
            $childMenuIds['stock_entry'],
            $childMenuIds['dispatches'],
            $childMenuIds['item_history']
        ];
        
        $stmtVendor = $db->prepare("
            INSERT INTO role_menu_permissions (role, menu_item_id, can_access) 
            VALUES ('vendor', ?, TRUE)
        ");
        
        foreach ($vendorMenuIds as $menuId) {
            $stmtVendor->execute([$menuId]);
        }
        echo "✓ Added vendor role permissions for " . count($vendorMenuIds) . " menu items\n";
        
        // =====================================================
        // Grant access to existing admin users
        // =====================================================
        
        echo "\nGranting access to existing users...\n";
        
        // Grant access to all admin users
        $stmtAdminUsers = $db->prepare("
            INSERT IGNORE INTO user_menu_permissions (user_id, menu_item_id, can_access)
            SELECT id, ?, TRUE FROM users WHERE role = 'admin'
        ");
        
        $adminUserCount = 0;
        foreach ($allMenuIds as $menuId) {
            $stmtAdminUsers->execute([$menuId]);
            if ($adminUserCount === 0) {
                $adminUserCount = $stmtAdminUsers->rowCount();
            }
        }
        echo "✓ Granted access to $adminUserCount admin user(s) for all menu items\n";
        
        // Grant limited access to vendor users
        $stmtVendorUsers = $db->prepare("
            INSERT IGNORE INTO user_menu_permissions (user_id, menu_item_id, can_access)
            SELECT id, ?, TRUE FROM users WHERE role = 'vendor'
        ");
        
        $vendorUserCount = 0;
        foreach ($vendorMenuIds as $menuId) {
            $stmtVendorUsers->execute([$menuId]);
            if ($vendorUserCount === 0) {
                $vendorUserCount = $stmtVendorUsers->rowCount();
            }
        }
        echo "✓ Granted limited access to $vendorUserCount vendor user(s)\n";
        
        // Commit transaction
        $db->commit();
        
        echo "\n" . str_repeat("=", 51) . "\n";
        echo "✅ SAR Inventory Management menu setup completed!\n";
        echo str_repeat("=", 51) . "\n\n";
        
        echo "Menu Structure:\n";
        echo "└── Inventory New (ID: $parentMenuId)\n";
        foreach ($childMenus as $menu) {
            echo "    ├── {$menu['title']} (ID: {$menu['id']}) - {$menu['url']}\n";
        }
        
        echo "\nAdmin users have access to all menu items.\n";
        echo "Vendor users have access to: Dashboard, Stock Entry, Dispatches, Item History\n";
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
