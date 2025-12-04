<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SiteSurvey.php';

// Check if user is authenticated (either admin or vendor)
if (!Auth::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$surveyId = $_GET['id'] ?? null;
if (!$surveyId) {
    // Redirect based on user role
    if (Auth::isAdmin()) {
        header('Location: ../admin/surveys/index.php');
    } else {
        header('Location: ../vendor/surveys.php');
    }
    exit;
}

$currentUser = Auth::getCurrentUser();
$isAdmin = Auth::isAdmin();
$isVendor = Auth::isVendor();

$surveyModel = new SiteSurvey();
$survey = $surveyModel->findWithDetails($surveyId);

if (!$survey) {
    // Redirect based on user role
    if ($isAdmin) {
        header('Location: ../admin/surveys/index.php');
    } else {
        header('Location: ../vendor/surveys.php');
    }
    exit;
}

// Check access permissions
if ($isVendor) {
    $vendorId = Auth::getVendorId();
    if ($survey['vendor_id'] != $vendorId) {
        header('Location: ../vendor/surveys.php');
        exit;
    }
}
// Admins can view all surveys

$title = 'Survey Details - ' . ($survey['site_code'] ?? $survey['site_id'] ?? 'Unknown Site');

// Determine which layout to use
if ($isAdmin) {
    $layoutPath = __DIR__ . '/../includes/admin_layout.php';
    $backUrl = '../admin/surveys/index.php';
    $backText = 'Back to Surveys';
} else {
    $layoutPath = __DIR__ . '/../includes/vendor_layout.php';
    $backUrl = '../vendor/surveys.php';
    $backText = 'Back to My Surveys';
}

ob_start();
?>

<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900">Survey Details</h1>
            <p class="mt-2 text-lg text-gray-600">Site: <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($survey['site_code'] ?? $survey['site_id'] ?? 'Unknown'); ?></span></p>
            <p class="text-sm text-gray-500 mt-1">Detailed view of site feasibility assessment</p>
        </div>
        <div class="mt-6 lg:mt-0 lg:ml-6">
            <div class="flex space-x-3">
                <?php
                $statusClasses = [
                    'pending' => 'bg-yellow-100 text-yellow-800',
                    'submitted' => 'bg-blue-100 text-blue-800',
                    'completed' => 'bg-blue-100 text-blue-800',
                    'rejected' => 'bg-red-100 text-red-800'
                ];
                $statusClass = $statusClasses[$survey['survey_status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $statusClass; ?>">
                    <?php echo ucfirst($survey['survey_status']); ?>
                </span>
                
                <?php if ($isAdmin && $survey['survey_status'] === 'completed'): ?>
                    <!-- Admin approval buttons -->
                    <button onclick="approveSurvey(<?php echo $survey['id']; ?>)" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        Approve
                    </button>
                    <button onclick="rejectSurvey(<?php echo $survey['id']; ?>)" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Reject
                    </button>
                <?php endif; ?>
                
                <a href="<?php echo $backUrl; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                    </svg>
                    <?php echo $backText; ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Survey Information -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Basic Information -->
    <div class="professional-table bg-white">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Site Code</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['site_code'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['location'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['city_name'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['state_name'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['vendor_name'] ?? 'N/A'); ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Submitted Date</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo $survey['submitted_date'] ? date('M d, Y H:i', strtotime($survey['submitted_date'])) : 'N/A'; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Time Tracking -->
    <div class="professional-table bg-white">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Time Tracking</h3>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Check-in Time</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo $survey['checkin_datetime'] ? date('M d, Y H:i', strtotime($survey['checkin_datetime'])) : 'N/A'; ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Check-out Time</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo $survey['checkout_datetime'] ? date('M d, Y H:i', strtotime($survey['checkout_datetime'])) : 'N/A'; ?></p>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Working Hours</label>
                    <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['working_hours'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Site Assessment -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Physical Assessment -->
    <div class="professional-table bg-white">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Physical Assessment</h3>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Store Model</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['store_model'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Floor Height</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['floor_height'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ceiling Type</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['ceiling_type'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>

    <!-- Camera Assessment -->
    <div class="professional-table bg-white">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Camera Assessment</h3>
        </div>
        <div class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Total Cameras</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['total_cameras'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nos. of SLP Cameras</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['slp_cameras'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nos. of Analytic Cameras</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['analytic_cameras'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Zones Recommended</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['zones_recommended'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- POE Rack Assessment -->
<div class="professional-table bg-white mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">POE Rack Assessment</h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Existing POE Rack</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['existing_poe_rack'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Space for New Rack</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['space_new_rack'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">New POE Rack Required</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['new_poe_rack'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Material Status -->
<div class="professional-table bg-white mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Material Status</h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RRL Delivery Status</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['rrl_delivery_status'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">KPTL Space Available</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['kptl_space'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Technical Assessment -->
<div class="professional-table bg-white mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Technical Assessment</h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Site Accessibility</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['site_accessibility'] ?? 'N/A'); ?></p>
            </div>
            <?php if (!empty($survey['site_accessibility_others'])): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Site Accessibility (Others)</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['site_accessibility_others']); ?></p>
            </div>
            <?php endif; ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Power Availability</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['power_availability'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Network Connectivity</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['network_connectivity'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nos. of Ladder Required</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['nos_of_ladder'] ?? 'N/A'); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Size of Ladder Required</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['ladder_size'] ?? 'N/A'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Survey Findings -->
<div class="professional-table bg-white mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Survey Findings</h3>
    </div>
    <div class="p-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Technical Remarks</label>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($survey['technical_remarks'] ?? 'No remarks provided'); ?></p>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Challenges Identified</label>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($survey['challenges_identified'] ?? 'No challenges identified'); ?></p>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Recommendations</label>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($survey['recommendations'] ?? 'No recommendations provided'); ?></p>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Equipment Needed</label>
            <div class="bg-gray-50 p-4 rounded-lg">
                <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($survey['additional_equipment_needed'] ?? 'No additional equipment specified'); ?></p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estimated Completion Days</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['estimated_completion_days'] ?? 'N/A'); ?> days</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Work Requirements</label>
                <div class="flex flex-wrap gap-2">
                    <?php if (!empty($survey['electrical_work_required'])): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            ⚡ Electrical Work
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($survey['civil_work_required'])): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                            🏗️ Civil Work
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($survey['network_work_required'])): ?>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            🌐 Network Work
                        </span>
                    <?php endif; ?>
                    <?php if (empty($survey['electrical_work_required']) && empty($survey['civil_work_required']) && empty($survey['network_work_required'])): ?>
                        <span class="text-sm text-gray-500">No special work requirements</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Collect all photo fields
$photoFields = [
    'floor_height_photos' => 'Floor Height Photos',
    'ceiling_photos' => 'Ceiling Photos', 
    'analytic_photos' => 'Analytic Camera Photos',
    'existing_poe_photos' => 'Existing POE Rack Photos',
    'space_new_rack_photos' => 'Server Room Photos',
    'new_poe_photos' => 'New POE Rack Photos',
    'rrl_photos' => 'RRL Material Photos',
    'kptl_photos' => 'KPTL Material Photos',
    'site_photos' => 'General Site Photos'
];

$hasPhotos = false;
foreach ($photoFields as $field => $label) {
    if (!empty($survey[$field])) {
        $hasPhotos = true;
        break;
    }
}
?>

<?php if ($hasPhotos): ?>
    <div class="professional-table bg-white mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Survey Photos & Attachments</h3>
            <p class="text-sm text-gray-500 mt-1">Visual documentation from the site survey</p>
        </div>
        <div class="p-6">
            <?php 
            // Map photo fields to their remark fields
            $photoRemarksMap = [
                'floor_height_photos' => 'floor_height_photo_remarks',
                'ceiling_photos' => 'ceiling_photo_remarks',
                'analytic_photos' => 'analytic_photos_remarks',
                'existing_poe_photos' => 'existing_poe_photos_remarks',
                'space_new_rack_photos' => 'space_new_rack_photo_remarks',
                'new_poe_photos' => 'new_poe_photos_remarks',
                'rrl_photos' => 'rrl_photos_remarks',
                'kptl_photos' => 'kptl_photos_remarks',
                'site_photos' => 'site_photos_remarks'
            ];
            ?>
            <?php foreach ($photoFields as $field => $label): ?>
                <?php if (!empty($survey[$field])): ?>
                    <?php $photos = json_decode($survey[$field], true); ?>
                    <?php if (!empty($photos) && is_array($photos)): ?>
                        <div class="mb-8">
                            <h4 class="text-md font-medium text-gray-900 mb-2"><?php echo $label; ?></h4>
                            <?php 
                            // Display remarks if available
                            $remarksField = $photoRemarksMap[$field] ?? null;
                            if ($remarksField && !empty($survey[$remarksField])): 
                            ?>
                                <div class="bg-blue-50 border-l-4 border-blue-400 p-3 mb-4">
                                    <p class="text-sm text-blue-800"><strong>Remarks:</strong> <?php echo htmlspecialchars($survey[$remarksField]); ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <?php foreach ($photos as $photo): ?>
                                <div class="relative group">
                                    <img src="../<?php echo htmlspecialchars($photo); ?>" alt="<?php echo $label; ?>" class="w-full h-48 object-cover rounded-lg shadow-sm border border-gray-200 group-hover:shadow-md transition-shadow">
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all rounded-lg flex items-center justify-center">
                                        <button onclick="openPhotoModal('../<?php echo htmlspecialchars($photo); ?>', '<?php echo $label; ?>')" class="opacity-0 group-hover:opacity-100 bg-white text-gray-700 px-3 py-1 rounded-md text-sm font-medium transition-opacity">
                                            View Full Size
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Approval Status (Admin Only) -->
<?php if ($isAdmin && ($survey['survey_status'] === 'approved' || $survey['survey_status'] === 'rejected')): ?>
<div class="professional-table bg-white">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">Approval Status</h3>
    </div>
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo ucfirst($survey['survey_status']); ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Approved/Rejected By</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($survey['approved_by_name'] ?? 'N/A'); ?></p>
            </div>
            <?php if (!empty($survey['approval_date'])): ?>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo date('M d, Y H:i', strtotime($survey['approval_date'])); ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($survey['approval_remarks'])): ?>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Remarks</label>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-900 whitespace-pre-wrap"><?php echo htmlspecialchars($survey['approval_remarks']); ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Photo Modal -->
<div id="photoModal" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4">
    <div class="relative max-w-4xl max-h-full">
        <button onclick="closePhotoModal()" class="absolute top-4 right-4 text-white hover:text-gray-300 z-10">
            <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
            </svg>
        </button>
        <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain rounded-lg">
        <div id="modalCaption" class="absolute bottom-4 left-4 right-4 text-white text-center bg-black bg-opacity-50 rounded px-4 py-2"></div>
    </div>
</div>

<?php if ($isAdmin): ?>
<!-- Admin Survey Action Modal -->
<div id="surveyActionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 id="actionModalTitle" class="text-lg font-semibold text-gray-900"></h3>
        </div>
        <form id="surveyActionForm">
            <div class="p-6">
                <input type="hidden" id="actionSurveyId" name="survey_id">
                <input type="hidden" id="actionType" name="action">
                <div class="mb-4">
                    <label for="actionRemarks" class="block text-sm font-medium text-gray-700 mb-2">Remarks (Optional)</label>
                    <textarea id="actionRemarks" name="remarks" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Add any remarks or feedback..."></textarea>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                <button type="button" onclick="closeSurveyActionModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                <button type="submit" id="actionSubmitBtn" class="px-4 py-2 text-sm font-medium text-white rounded-md"></button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function openPhotoModal(src, caption) {
    document.getElementById('photoModal').classList.remove('hidden');
    document.getElementById('modalImage').src = src;
    document.getElementById('modalCaption').textContent = caption;
    document.body.style.overflow = 'hidden';
}

function closePhotoModal() {
    document.getElementById('photoModal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside the image
document.getElementById('photoModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePhotoModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
        <?php if ($isAdmin): ?>
        closeSurveyActionModal();
        <?php endif; ?>
    }
});

<?php if ($isAdmin): ?>
function approveSurvey(surveyId) {
    document.getElementById('actionModalTitle').textContent = 'Approve Survey';
    document.getElementById('actionSurveyId').value = surveyId;
    document.getElementById('actionType').value = 'approve';
    document.getElementById('actionSubmitBtn').textContent = 'Approve Survey';
    document.getElementById('actionSubmitBtn').className = 'px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700';
    document.getElementById('surveyActionModal').classList.remove('hidden');
}

function rejectSurvey(surveyId) {
    document.getElementById('actionModalTitle').textContent = 'Reject Survey';
    document.getElementById('actionSurveyId').value = surveyId;
    document.getElementById('actionType').value = 'reject';
    document.getElementById('actionSubmitBtn').textContent = 'Reject Survey';
    document.getElementById('actionSubmitBtn').className = 'px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-md hover:bg-red-700';
    document.getElementById('surveyActionModal').classList.remove('hidden');
}

function closeSurveyActionModal() {
    document.getElementById('surveyActionModal').classList.add('hidden');
    document.getElementById('surveyActionForm').reset();
}

document.getElementById('surveyActionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('../admin/surveys/process-survey-action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the survey action.');
    });
});
<?php endif; ?>
</script>

<?php
$content = ob_get_clean();
include $layoutPath;
?>