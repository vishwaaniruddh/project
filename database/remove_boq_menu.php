<?php
/**
 * Remove BOQ menu entry and keep only BOQ Master
 * This migration removes the old BOQ menu item from the admin sidebar
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Removing BOQ menu entry (keeping BOQ Master)...\n";
    
    // Find and remove the BOQ menu item (not BOQ Master)
    $stmt = $db->prepare("SELECT id, title, url FROM menu_items WHERE title = 'BOQ' AND url = '/admin/boq/'");
    $stmt->execute();
    $boqMenu = $stmt->fetch();
    
    if ($boqMenu) {
        // Remove role permissions for this menu item
        $stmt = $db->prepare("DELETE FROM role_menu_permissions WHERE menu_item_id = ?");
        $stmt->execute([$boqMenu['id']]);
        echo "✓ Removed role permissions for BOQ menu\n";
        
        // Remove user permissions for this menu item
        $stmt = $db->prepare("DELETE FROM user_menu_permissions WHERE menu_item_id = ?");
        $stmt->execute([$boqMenu['id']]);
        echo "✓ Removed user permissions for BOQ menu\n";
        
        // Delete the menu item
        $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$boqMenu['id']]);
        echo "✓ Removed BOQ menu item (ID: {$boqMenu['id']})\n";
    } else {
        echo "- BOQ menu item not found (may already be removed)\n";
    }
    
    // Verify BOQ Master menu exists
    $stmt = $db->prepare("SELECT id, title, url FROM menu_items WHERE title = 'BOQ Master'");
    $stmt->execute();
    $boqMasterMenu = $stmt->fetch();
    
    if ($boqMasterMenu) {
        echo "✓ BOQ Master menu exists (ID: {$boqMasterMenu['id']}, URL: {$boqMasterMenu['url']})\n";
    } else {
        echo "⚠ BOQ Master menu not found - you may need to run add_boq_master_menu.php first\n";
    }
    
    echo "\n✅ BOQ menu removal completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
