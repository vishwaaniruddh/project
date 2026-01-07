<?php
/**
 * Add BOQ Master Management menu entry
 * This migration adds the BOQ Master menu item to the admin sidebar
 * and sets up proper permissions for the admin role.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Adding BOQ Master Management menu to the system...\n";
    
    // Check if BOQ Master menu already exists
    $stmt = $db->prepare("SELECT id FROM menu_items WHERE title = 'BOQ Master' OR url = '/admin/boq-master/'");
    $stmt->execute();
    $existingMenu = $stmt->fetch();
    
    if ($existingMenu) {
        echo "BOQ Master menu already exists with ID: " . $existingMenu['id'] . "\n";
        $boqMasterMenuId = $existingMenu['id'];
    } else {
        // Find the Admin parent menu (where BOQ is located)
        $stmt = $db->prepare("SELECT id FROM menu_items WHERE title = 'Admin' AND parent_id IS NULL");
        $stmt->execute();
        $adminMenu = $stmt->fetch();
        
        $parentId = null;
        $sortOrder = 65; // Default sort order
        
        if ($adminMenu) {
            $parentId = $adminMenu['id'];
            // Get the sort order of existing BOQ menu to place BOQ Master after it
            $stmt = $db->prepare("SELECT sort_order FROM menu_items WHERE title = 'BOQ' AND parent_id = ?");
            $stmt->execute([$parentId]);
            $boqMenu = $stmt->fetch();
            if ($boqMenu) {
                $sortOrder = $boqMenu['sort_order'] + 1;
            }
        } else {
            // If no Admin parent, check for BOQ Management menu
            $stmt = $db->prepare("SELECT id, sort_order FROM menu_items WHERE title LIKE '%BOQ%' AND parent_id IS NULL");
            $stmt->execute();
            $boqMainMenu = $stmt->fetch();
            if ($boqMainMenu) {
                $sortOrder = $boqMainMenu['sort_order'] + 1;
            }
        }
        
        // Insert BOQ Master menu item
        $stmt = $db->prepare("
            INSERT INTO menu_items (parent_id, title, icon, url, sort_order, status) 
            VALUES (?, 'BOQ Master', 'boq', '/admin/boq-master/', ?, 'active')
        ");
        $stmt->execute([$parentId, $sortOrder]);
        $boqMasterMenuId = $db->lastInsertId();
        echo "✓ Created BOQ Master menu with ID: $boqMasterMenuId\n";
    }
    
    // Add permissions for admin role
    $stmt = $db->prepare("
        INSERT IGNORE INTO role_menu_permissions (role, menu_item_id, can_access) 
        VALUES ('admin', ?, TRUE)
    ");
    $stmt->execute([$boqMasterMenuId]);
    echo "✓ Added admin role permissions for BOQ Master menu\n";
    
    // Grant access to all existing admin users
    $stmt = $db->prepare("
        INSERT IGNORE INTO user_menu_permissions (user_id, menu_item_id, can_access)
        SELECT id, ?, TRUE FROM users WHERE role = 'admin'
    ");
    $stmt->execute([$boqMasterMenuId]);
    $adminCount = $stmt->rowCount();
    echo "✓ Granted BOQ Master access to $adminCount admin user(s)\n";
    
    echo "\n✅ BOQ Master Management menu setup completed!\n";
    echo "Menu is now accessible at: /admin/boq-master/\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
