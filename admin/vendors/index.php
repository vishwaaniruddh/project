<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/Vendor.php';
require_once __DIR__ . '/../../models/VendorPermission.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$vendorModel = new Vendor();
$permissionModel = new VendorPermission();

$vendors = $vendorModel->findAll(['status' => 'active']);
$allPermissions = $permissionModel->getAllPermissions();

$title = 'Vendor Management';
ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Vendor Management</h1>
        <p class="mt-2 text-sm text-gray-700">Manage vendor access and permissions</p>
    </div>
</div>

<!-- Vendors List -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Active Vendors</h3>
        <span class="badge badge-primary"><?php echo count($vendors); ?> vendors</span>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Vendor Details</th>
                        <th>Contact Information</th>
                        <th>Permissions</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vendors as $vendor): ?>
                    <?php $vendorPermissions = $permissionModel->getVendorPermissions($vendor['id']); ?>
                    <tr>
                        <td>
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vendor['name']); ?></div>
                                <div class="text-sm text-gray-500">ID: <?php echo $vendor['id']; ?></div>
                                <?php if ($vendor['contact_person']): ?>
                                    <div class="text-sm text-gray-500">Contact: <?php echo htmlspecialchars($vendor['contact_person']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div>
                                <?php if ($vendor['email']): ?>
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($vendor['email']); ?></div>
                                <?php endif; ?>
                                <?php if ($vendor['phone']): ?>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($vendor['phone']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach ($allPermissions as $key => $label): ?>
                                    <?php $hasPermission = $vendorPermissions[$key] ?? false; ?>
                                    <span class="badge <?php echo $hasPermission ? 'badge-success' : 'badge-secondary'; ?> text-xs">
                                        <?php echo $label; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <span class="badge badge-success">Active</span>
                        </td>
                        <td>
                            <div class="flex items-center space-x-2">
                                <button onclick="managePermissions(<?php echo $vendor['id']; ?>)" class="btn btn-sm btn-primary" title="Manage Permissions">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                                <button onclick="viewVendorStats(<?php echo $vendor['id']; ?>)" class="btn btn-sm btn-secondary" title="View Stats">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Permissions Modal -->
<div id="permissionsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">Manage Vendor Permissions</h3>
            <button type="button" class="modal-close" onclick="closeModal('permissionsModal')">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <form id="permissionsForm" action="update-permissions.php" method="POST">
            <input type="hidden" id="vendor_id" name="vendor_id">
            <div class="modal-body">
                <div id="vendor-info" class="mb-4 p-3 bg-gray-50 rounded"></div>
                
                <div class="space-y-4">
                    <h4 class="font-medium text-gray-900">Portal Access Permissions</h4>
                    <?php foreach ($allPermissions as $key => $label): ?>
                    <div class="flex items-center justify-between p-3 border rounded">
                        <div>
                            <label class="font-medium text-gray-900"><?php echo $label; ?></label>
                            <p class="text-sm text-gray-500"><?php echo getPermissionDescription($key); ?></p>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="perm_<?php echo $key; ?>" name="permissions[<?php echo $key; ?>]" value="1" class="mr-2">
                            <label for="perm_<?php echo $key; ?>" class="text-sm text-gray-700">Enabled</label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('permissionsModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Permissions</button>
            </div>
        </form>
    </div>
</div>

<script>
function managePermissions(vendorId) {
    // Fetch vendor details and current permissions
    fetch(`get-vendor-permissions.php?vendor_id=${vendorId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate modal
                document.getElementById('vendor_id').value = vendorId;
                document.getElementById('vendor-info').innerHTML = `
                    <h5 class="font-medium">${data.vendor.name}</h5>
                    <p class="text-sm text-gray-600">${data.vendor.email || 'No email'}</p>
                `;
                
                // Set permission checkboxes
                Object.keys(data.permissions).forEach(key => {
                    const checkbox = document.getElementById(`perm_${key}`);
                    if (checkbox) {
                        checkbox.checked = data.permissions[key];
                    }
                });
                
                openModal('permissionsModal');
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to load vendor permissions', 'error');
        });
}

function viewVendorStats(vendorId) {
    window.location.href = `vendor-stats.php?id=${vendorId}`;
}

// Form submission
document.getElementById('permissionsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Updating...';
    
    fetch('update-permissions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('permissionsModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
});
</script>

<?php
function getPermissionDescription($key) {
    $descriptions = [
        'view_sites' => 'Allow vendor to view and manage their assigned sites',
        'update_progress' => 'Allow vendor to update installation progress',
        'view_masters' => 'Allow vendor to view master data (customers, banks, etc.)',
        'view_reports' => 'Allow vendor to view reports and analytics',
        'view_inventory' => 'Allow vendor to view inventory information',
        'view_material_requests' => 'Allow vendor to view material requests'
    ];
    
    return $descriptions[$key] ?? 'Permission description not available';
}

$content = ob_get_clean();
include __DIR__ . '/../../includes/admin_layout.php';
?>