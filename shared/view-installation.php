<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Installation.php';

// Helper function for file size formatting
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Check if user is authenticated (either admin or vendor)
if (!Auth::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$installationId = $_GET['id'] ?? null;
if (!$installationId) {
    // Redirect based on user role
    if (Auth::isAdmin()) {
        header('Location: ../admin/installations/index.php');
    } else {
        header('Location: ../vendor/installations.php');
    }
    exit;
}

$currentUser = Auth::getCurrentUser();
$isAdmin = Auth::isAdmin();
$isVendor = Auth::isVendor();

$installationModel = new Installation();
$installation = $installationModel->getInstallationDetails($installationId);

if (!$installation) {
    // Redirect based on user role
    if ($isAdmin) {
        header('Location: ../admin/installations/index.php');
    } else {
        header('Location: ../vendor/installations.php');
    }
    exit;
}

// Check access permissions
if ($isVendor) {
    $vendorId = Auth::getVendorId();
    if ($installation['vendor_id'] != $vendorId) {
        header('Location: ../vendor/installations.php');
        exit;
    }
}
// Admins can view all installations

// Get installation progress
$progress = $installationModel->getInstallationProgress($installationId);

$title = 'Installation Details - ' . ($installation['site_code'] ?? 'Unknown Site');

// Determine which layout to use
if ($isAdmin) {
    $layoutPath = __DIR__ . '/../includes/admin_layout.php';
    $backUrl = '../admin/installations/index.php';
    $backText = 'Back to Installations';
} else {
    $layoutPath = __DIR__ . '/../includes/vendor_layout.php';
    $backUrl = '../vendor/installations.php';
    $backText = 'Back to My Installations';
}

ob_start();
?>

<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex-1">
            <div class="flex items-center space-x-4 mb-2">
                <a href="<?php echo $backUrl; ?>" class="text-blue-600 hover:text-blue-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                </a>
                <h1 class="text-3xl font-bold text-gray-900">Installation Details</h1>
            </div>
            <p class="text-lg text-gray-600">Site: <?php echo htmlspecialchars($installation['site_code'] ?? 'Unknown'); ?></p>
            <p class="text-sm text-gray-500">Installation ID: <?php echo $installation['id']; ?></p>
        </div>
        <div class="mt-6 lg:mt-0 lg:ml-6">
            <?php
            $statusClass = '';
            $statusText = '';
            switch($installation['status']) {
                case 'assigned':
                    $statusClass = 'bg-blue-100 text-blue-800';
                    $statusText = 'Assigned';
                    break;
                case 'acknowledged':
                    $statusClass = 'bg-indigo-100 text-indigo-800';
                    $statusText = 'Acknowledged';
                    break;
                case 'in_progress':
                    $statusClass = 'bg-yellow-100 text-yellow-800';
                    $statusText = 'In Progress';
                    break;
                case 'on_hold':
                    $statusClass = 'bg-orange-100 text-orange-800';
                    $statusText = 'On Hold';
                    break;
                case 'completed':
                    $statusClass = 'bg-green-100 text-green-800';
                    $statusText = 'Completed';
                    break;
                case 'cancelled':
                    $statusClass = 'bg-red-100 text-red-800';
                    $statusText = 'Cancelled';
                    break;
                default:
                    $statusClass = 'bg-gray-100 text-gray-800';
                    $statusText = ucfirst($installation['status']);
            }
            ?>
            <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium <?php echo $statusClass; ?>">
                <?php echo $statusText; ?>
            </span>
        </div>
    </div>
</div>

<!-- Installation Information -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Basic Information -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Installation Information</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-500">Vendor:</span>
                <span class="font-medium"><?php echo htmlspecialchars($installation['vendor_name'] ?? 'Unknown'); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Priority:</span>
                <span class="font-medium capitalize"><?php echo $installation['priority']; ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Installation Type:</span>
                <span class="font-medium capitalize"><?php echo str_replace('_', ' ', $installation['installation_type']); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Delegated By:</span>
                <span class="font-medium"><?php echo htmlspecialchars($installation['delegated_by_name'] ?? 'Unknown'); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Delegation Date:</span>
                <span class="font-medium"><?php echo date('M j, Y g:i A', strtotime($installation['delegation_date'])); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Site Information -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Site Information</h3>
        <div class="space-y-3">
            <div class="flex justify-between">
                <span class="text-gray-500">Site Code:</span>
                <span class="font-medium"><?php echo htmlspecialchars($installation['site_code'] ?? 'N/A'); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Location:</span>
                <span class="font-medium"><?php echo htmlspecialchars($installation['location'] ?? 'N/A'); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">City:</span>
                <span class="font-medium"><?php echo htmlspecialchars($installation['city_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">State:</span>
                <span class="font-medium"><?php echo htmlspecialchars($installation['state_name'] ?? 'N/A'); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Remarks:</span>
                <span class="font-medium"><?php echo htmlspecialchars($installation['remarks'] ?? 'N/A'); ?></span>
            </div>
        </div>
    </div>
</div>

<!-- Schedule Information -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Schedule Information</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div>
            <span class="text-gray-500 text-sm">Expected Start Date</span>
            <div class="font-medium">
                <?php echo $installation['expected_start_date'] ? date('M j, Y', strtotime($installation['expected_start_date'])) : 'Not set'; ?>
            </div>
        </div>
        <div>
            <span class="text-gray-500 text-sm">Expected Completion</span>
            <div class="font-medium">
                <?php echo $installation['expected_completion_date'] ? date('M j, Y', strtotime($installation['expected_completion_date'])) : 'Not set'; ?>
            </div>
        </div>
        <div>
            <span class="text-gray-500 text-sm">Actual Start Date</span>
            <div class="font-medium">
                <?php echo $installation['actual_start_date'] ? date('M j, Y', strtotime($installation['actual_start_date'])) : 'Not started'; ?>
            </div>
        </div>
        <div>
            <span class="text-gray-500 text-sm">Actual Completion</span>
            <div class="font-medium">
                <?php echo $installation['actual_completion_date'] ? date('M j, Y', strtotime($installation['actual_completion_date'])) : 'Not completed'; ?>
            </div>
        </div>
    </div>
</div>

<!-- Instructions and Notes -->
<?php if ($installation['special_instructions'] || $installation['notes']): ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Instructions & Notes</h3>
    <div class="space-y-4">
        <?php if ($installation['special_instructions']): ?>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Special Instructions</h4>
                <p class="text-gray-700 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($installation['special_instructions'])); ?></p>
            </div>
        <?php endif; ?>
        <?php if ($installation['notes']): ?>
            <div>
                <h4 class="font-medium text-gray-900 mb-2">Notes</h4>
                <p class="text-gray-700 bg-gray-50 p-3 rounded"><?php echo nl2br(htmlspecialchars($installation['notes'])); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Vendor Information -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Vendor Contact Information</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <span class="text-gray-500 text-sm">Vendor Name</span>
            <div class="font-medium"><?php echo htmlspecialchars($installation['vendor_name'] ?? 'Unknown'); ?></div>
        </div>
        <div>
            <span class="text-gray-500 text-sm">Email</span>
            <div class="font-medium">
                <?php if ($installation['vendor_email']): ?>
                    <a href="mailto:<?php echo htmlspecialchars($installation['vendor_email']); ?>" class="text-blue-600 hover:text-blue-800">
                        <?php echo htmlspecialchars($installation['vendor_email']); ?>
                    </a>
                <?php else: ?>
                    Not available
                <?php endif; ?>
            </div>
        </div>
        <div>
            <span class="text-gray-500 text-sm">Phone</span>
            <div class="font-medium">
                <?php if ($installation['vendor_phone']): ?>
                    <a href="tel:<?php echo htmlspecialchars($installation['vendor_phone']); ?>" class="text-blue-600 hover:text-blue-800">
                        <?php echo htmlspecialchars($installation['vendor_phone']); ?>
                    </a>
                <?php else: ?>
                    Not available
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Installation Progress -->
<?php if (!empty($progress)): ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Installation Progress</h3>
    <div class="space-y-6">
        <?php foreach ($progress as $entry): ?>
        <div class="border-l-4 border-blue-500 pl-4">
            <div class="flex justify-between items-start mb-2">
                <h4 class="font-medium text-gray-900"><?php echo htmlspecialchars($entry['work_description']); ?></h4>
                <span class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($entry['progress_date'])); ?></span>
            </div>
            <?php if ($entry['completion_percentage']): ?>
            <div class="mb-2">
                <div class="flex justify-between text-sm mb-1">
                    <span class="text-gray-600">Progress</span>
                    <span class="font-medium"><?php echo $entry['completion_percentage']; ?>%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $entry['completion_percentage']; ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($entry['issues_faced']): ?>
            <p class="text-sm text-red-600 mb-2"><strong>Issues:</strong> <?php echo htmlspecialchars($entry['issues_faced']); ?></p>
            <?php endif; ?>
            <?php if ($entry['next_steps']): ?>
            <p class="text-sm text-gray-600"><strong>Next Steps:</strong> <?php echo htmlspecialchars($entry['next_steps']); ?></p>
            <?php endif; ?>
            <p class="text-xs text-gray-500 mt-2">Updated by: <?php echo htmlspecialchars($entry['updated_by_name']); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include $layoutPath;
?>