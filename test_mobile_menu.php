<?php
$title = 'Mobile Menu Test';
ob_start();
?>

<div class="card">
    <div class="card-body">
        <h2 class="text-2xl font-semibold text-gray-900 mb-4">Mobile Menu Test</h2>
        <p class="text-gray-700 mb-4">
            This page is for testing the mobile hamburger menu functionality.
        </p>
        
        <div class="space-y-4">
            <div class="p-4 bg-blue-50 rounded-lg">
                <h3 class="font-semibold text-blue-900">Testing Instructions:</h3>
                <ol class="list-decimal list-inside text-sm text-blue-800 mt-2 space-y-1">
                    <li>Resize your browser window to mobile size (< 1024px width)</li>
                    <li>Look for the hamburger menu (☰) in the top header</li>
                    <li>Click the hamburger menu to open the sidebar</li>
                    <li>Click outside the sidebar or on the overlay to close it</li>
                    <li>Test navigation links within the sidebar</li>
                </ol>
            </div>
            
            <div class="p-4 bg-green-50 rounded-lg">
                <h3 class="font-semibold text-green-900">Expected Behavior:</h3>
                <ul class="list-disc list-inside text-sm text-green-800 mt-2 space-y-1">
                    <li>Hamburger menu should be visible only on mobile/tablet screens</li>
                    <li>Clicking hamburger should slide in the sidebar from the left</li>
                    <li>Dark overlay should appear behind the sidebar</li>
                    <li>Clicking overlay should close the sidebar</li>
                    <li>Sidebar should close automatically after clicking navigation links</li>
                </ul>
            </div>
            
            <div class="p-4 bg-yellow-50 rounded-lg">
                <h3 class="font-semibold text-yellow-900">Debug Information:</h3>
                <div class="text-sm text-yellow-800 mt-2">
                    <p><strong>Current Screen Width:</strong> <span id="screen-width"></span>px</p>
                    <p><strong>Sidebar State:</strong> <span id="sidebar-state">Unknown</span></p>
                    <p><strong>Overlay State:</strong> <span id="overlay-state">Unknown</span></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Debug information updater
function updateDebugInfo() {
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    
    document.getElementById('screen-width').textContent = window.innerWidth;
    document.getElementById('sidebar-state').textContent = sidebar?.classList.contains('show') ? 'Open' : 'Closed';
    document.getElementById('overlay-state').textContent = overlay?.classList.contains('hidden') ? 'Hidden' : 'Visible';
}

// Update debug info on load and resize
document.addEventListener('DOMContentLoaded', updateDebugInfo);
window.addEventListener('resize', updateDebugInfo);

// Monitor sidebar state changes
const sidebar = document.querySelector('.admin-sidebar');
if (sidebar) {
    const observer = new MutationObserver(updateDebugInfo);
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
}

// Test hamburger menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.getElementById('toggleSidebar');
    if (hamburger) {
        console.log('✅ Hamburger menu found');
        hamburger.addEventListener('click', function() {
            console.log('🍔 Hamburger menu clicked');
            updateDebugInfo();
        });
    } else {
        console.error('❌ Hamburger menu not found');
    }
});
</script>

<?php
$content = ob_get_clean();
require_once '../includes/admin_layout.php';
?>