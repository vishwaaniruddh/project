<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/Site.php';
require_once __DIR__ . '/../models/SiteSurvey.php';
require_once __DIR__ . '/../models/BoqItem.php';
require_once __DIR__ . '/../models/BoqMaster.php';
require_once __DIR__ . '/../models/BoqMasterItem.php';
require_once __DIR__ . '/../models/SiteDelegation.php';
require_once __DIR__ . '/../models/MaterialRequest.php';

// Require vendor authentication
//Auth::requireVendor();

//$vendorId = Auth::getVendorId();
$siteId = $_GET['site_id'] ?? null;
$surveyId = $_GET['survey_id'] ?? null;

$delegationModel = new SiteDelegation();
$vendorId = $delegationModel->findSiteVendorId($siteId);

// If no site_id provided, show site selection page
if (!$siteId) {
    require_once __DIR__ . '/../models/VendorPermission.php';
    $permissionModel = new VendorPermission();
    $vendorSites = $permissionModel->getVendorSites($vendorId);
    
    $title = 'Material Request - Select Site';
    ob_start();
    ?>
    
    <!-- Header Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <div class="flex-1">
                <h1 class="text-3xl font-bold text-gray-900">Material Request</h1>
                <p class="mt-2 text-lg text-gray-600">Select a site to create a material request</p>
            </div>
            <div class="mt-6 lg:mt-0 lg:ml-6">
                <a href="sites/index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                    </svg>
                    Back to Sites
                </a>
            </div>
        </div>
    </div>

    <!-- Site Selection -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Select Site for Material Request</h3>
        
        <?php if (empty($vendorSites)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H9m0 0H5m0 0h2M7 7h10M7 11h4m6 0h4M7 15h10"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Sites Available</h3>
                <p class="text-gray-500">You don't have access to any sites yet. Please contact your administrator.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($vendorSites as $site): ?>
                    <div class="border border-gray-200 rounded-lg p-6 hover:border-blue-300 hover:shadow-md transition-all duration-200">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($site['site_id']); ?></h4>
                                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($site['location'] ?? 'Location not specified'); ?></p>
                                
                                <div class="flex items-center text-xs text-gray-500 mb-4">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <?php echo htmlspecialchars($site['city_name'] ?? 'City not specified'); ?>
                                </div>
                                
                                <a href="material-request.php?site_id=<?php echo $site['id']; ?>" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Create Request
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php
    $content = ob_get_clean();
    include __DIR__ . '/../includes/admin_layout.php';
    exit;
}

// Get site details
$siteModel = new Site();
$site = $siteModel->find($siteId);

if (!$site) {
    header('Location: sites/index.php');
    exit;
}

// Get survey details - always try to find the survey for this site and vendor
$surveyModel = new SiteSurvey();
$survey = $surveyModel->findBySiteAndVendor($siteId, $vendorId);

// echo '$siteId' . $siteId ; 
// echo '$vendorId' . $vendorId;
// var_dump($survey);
// return ; 
// If a specific survey_id was provided, verify it matches
if ($surveyId && $survey && $survey['id'] != $surveyId) {
    // The provided survey_id doesn't match the site/vendor survey
    $survey = null;
}

// Get BOQ items for material selection
$boqModel = new BoqItem();
$boqItems = $boqModel->getActive();

// Get active BOQ Masters for selection
$boqMasterModel = new BoqMaster();
$boqMasters = $boqMasterModel->getActive();

// Get existing material requests for this site
$materialRequestModel = new MaterialRequest();
$existingRequests = $materialRequestModel->findBySite($siteId);

// Check if there's an active request (approved, dispatched, or completed) that should prevent new requests
$hasActiveRequest = false;
$activeRequestStatus = null;
foreach ($existingRequests as $req) {
    if (in_array($req['status'], ['approved', 'dispatched', 'partially_dispatched', 'completed', 'pending'])) {
        $hasActiveRequest = true;
        $activeRequestStatus = $req['status'];
        break;
    }
}

$title = 'Material Request - ' . ($site['site_id'] ?? 'Site #' . $siteId);
ob_start();
?>

<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900">Material Request</h1>
            <p class="mt-2 text-lg text-gray-600">Site: <span class="font-semibold text-blue-600"><?php echo htmlspecialchars($site['site_id'] ?? 'Unknown'); ?></span></p>
            <p class="text-sm text-gray-500 mt-1">Request materials needed for installation</p>
        </div>
        <div class="mt-6 lg:mt-0 lg:ml-6">
            <a href="sites/index.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                </svg>
                Back to Sites
            </a>
        </div>
    </div>
</div>

<!-- Site Information -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Site Information</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Site ID</label>
            <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($site['site_id'] ?? 'N/A'); ?></p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
            <p class="text-sm text-gray-900 bg-gray-50 p-2 rounded"><?php echo htmlspecialchars($site['location'] ?? 'N/A'); ?></p>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Survey Status</label>
            <?php if ($survey): ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                    Survey Completed
                </span>
            <?php else: ?>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    No Survey Found
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Existing Material Requests -->
<?php if (!empty($existingRequests)): ?>
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
            <svg class="w-5 h-5 inline mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path>
            </svg>
            Previous Material Requests
        </h3>
        <span class="text-sm text-gray-500"><?php echo count($existingRequests); ?> request(s) found</span>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request ID</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Required Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($existingRequests as $request): 
                    $items = json_decode($request['items'] ?? '[]', true);
                    $itemCount = is_array($items) ? count($items) : 0;
                    
                    // Status badge colors
                    $statusColors = [
                        'draft' => 'bg-gray-100 text-gray-800',
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'approved' => 'bg-blue-100 text-blue-800',
                        'dispatched' => 'bg-purple-100 text-purple-800',
                        'partially_dispatched' => 'bg-indigo-100 text-indigo-800',
                        'completed' => 'bg-green-100 text-green-800',
                        'rejected' => 'bg-red-100 text-red-800',
                        'cancelled' => 'bg-gray-100 text-gray-600'
                    ];
                    $statusColor = $statusColors[$request['status']] ?? 'bg-gray-100 text-gray-800';
                ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm font-medium text-gray-900">#<?php echo $request['id']; ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('M j, Y', strtotime($request['request_date'])); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo date('M j, Y', strtotime($request['required_date'])); ?></td>
                    <td class="px-4 py-3 text-sm text-gray-500"><?php echo $itemCount; ?> item(s)</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusColor; ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-sm">
                        <button type="button" onclick="viewRequestDetails(<?php echo $request['id']; ?>)" class="text-blue-600 hover:text-blue-800 mr-3">
                            <svg class="w-4 h-4 inline" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                            </svg>
                            View
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ($hasActiveRequest): ?>
<!-- Active Request Notice - Cannot create new request -->
<div class="bg-yellow-50 rounded-lg border border-yellow-200 p-6 mb-8">
    <div class="flex items-start">
        <div class="flex-shrink-0">
            <svg class="h-6 w-6 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
        </div>
        <div class="ml-3">
            <h3 class="text-lg font-medium text-yellow-800">Material Request Already Exists</h3>
            <p class="mt-2 text-sm text-yellow-700">
                A material request for this site is already <strong><?php echo ucfirst(str_replace('_', ' ', $activeRequestStatus)); ?></strong>. 
                You cannot create a new material request until the existing one is completed or cancelled.
            </p>
            <p class="mt-2 text-sm text-yellow-700">
                Please view the existing request details above.
            </p>
        </div>
    </div>
</div>
<?php else: ?>
<!-- Material Request Form -->
<form id="materialRequestForm" action="process-material-request.php" method="POST">
    <input type="hidden" name="site_id" value="<?php echo $siteId; ?>">
    <input type="hidden" name="survey_id" value="<?php echo $surveyId ?? ''; ?>">
    <input type="hidden" name="vendor_id" value="<?php echo $vendorId ?? ''; ?>">
    
    <!-- Request Details -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Details</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="request_date" class="block text-sm font-medium text-gray-700 mb-2">Request Date *</label>
                <input type="date" id="request_date" name="request_date" value="<?php echo date('Y-m-d'); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div>
                <label for="required_date" class="block text-sm font-medium text-gray-700 mb-2">Required Date *</label>
                <input type="date" id="required_date" name="required_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
            </div>
            <div class="md:col-span-2">
                <label for="request_notes" class="block text-sm font-medium text-gray-700 mb-2">Request Notes</label>
                <textarea id="request_notes" name="request_notes" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Any special instructions or notes for this material request..."></textarea>
            </div>
        </div>
    </div>

    <!-- Material Items -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Material Items</h3>
        
        <!-- BOQ Master Selection -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <label for="boq_master_select" class="block text-sm font-medium text-blue-900 mb-2">
                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                </svg>
                Select BOQ Master *
            </label>
            <select id="boq_master_select" name="boq_master_id" class="w-full px-3 py-2 border border-blue-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" onchange="loadBoqMasterItems(this.value)" required>
                <option value="">-- Select a BOQ Master to load materials --</option>
                <?php foreach ($boqMasters as $master): ?>
                    <option value="<?php echo $master['boq_id']; ?>"><?php echo htmlspecialchars($master['boq_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <p class="text-xs text-blue-600 mt-1">Select a BOQ Master to load all materials for this request</p>
        </div>
        
        <!-- Materials Table -->
        <div id="materialsTableSection" class="hidden">
            <div class="flex justify-between items-center mb-3">
                <h4 class="text-md font-medium text-gray-900">Materials List</h4>
                <span id="itemCount" class="text-sm text-gray-500"></span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 border border-gray-200 rounded-lg">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Quantity</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        </tr>
                    </thead>
                    <tbody id="materialsTableBody" class="bg-white divide-y divide-gray-200">
                        <!-- Materials will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="noItemsMessage" class="text-center py-8 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <p>Select a BOQ Master above to load all materials for this request.</p>
        </div>
    </div>

    <!-- Survey-Based Recommendations (if survey exists) -->
    <?php if ($survey): ?>
    <div class="bg-blue-50 rounded-lg border border-blue-200 p-6 mb-8" style="display:none;">
        <h3 class="text-lg font-semibold text-blue-900 mb-4">Survey-Based Recommendations</h3>
        <p class="text-sm text-blue-700 mb-4">Based on your site survey, here are some recommended materials:</p>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php if ($survey['total_cameras']): ?>
            <div class="bg-white p-4 rounded-lg border border-blue-200">
                <h4 class="font-medium text-blue-900">Cameras</h4>
                <p class="text-sm text-blue-700">Total cameras needed: <?php echo $survey['total_cameras']; ?></p>
                <p class="text-sm text-blue-700">Analytic cameras: <?php echo $survey['analytic_cameras'] ?? 'N/A'; ?></p>
                <button type="button" onclick="addRecommendedCameras()" class="mt-2 text-xs text-blue-600 hover:text-blue-800">Add to Request</button>
            </div>
            <?php endif; ?>
            
            <?php if ($survey['new_poe_rack'] === 'Yes'): ?>
            <div class="bg-white p-4 rounded-lg border border-blue-200">
                <h4 class="font-medium text-blue-900">POE Equipment</h4>
                <p class="text-sm text-blue-700">New POE rack required</p>
                <button type="button" onclick="addRecommendedPOE()" class="mt-2 text-xs text-blue-600 hover:text-blue-800">Add to Request</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Submit Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <div class="flex justify-between items-center">
            <div>
                <h3 class="text-lg font-semibold text-gray-900">Submit Request</h3>
                <p class="text-sm text-gray-500 mt-1">Review your material request before submitting</p>
            </div>
            <div class="flex space-x-3">
                <button type="button" onclick="saveDraft()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Save Draft
                </button>
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    Submit Request
                </button>
            </div>
        </div>
    </div>
</form>
<?php endif; ?>

<script>
const boqItems = <?php echo json_encode($boqItems); ?>;
let currentBoqMasterItems = [];
const hasActiveRequest = <?php echo $hasActiveRequest ? 'true' : 'false'; ?>;

// Set minimum required date to tomorrow
if (!hasActiveRequest) {
    document.getElementById('required_date').min = new Date(Date.now() + 86400000).toISOString().split('T')[0];
}

// View request details in modal
function viewRequestDetails(requestId) {
    fetch(`get-material-request-details.php?id=${requestId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showRequestModal(data.request);
            } else {
                alert('Error loading request details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading request details.');
        });
}

function showRequestModal(request) {
    const items = request.items_data || [];
    let itemsHtml = '';
    
    if (items.length > 0) {
        itemsHtml = `
            <table class="min-w-full divide-y divide-gray-200 mt-4">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    ${items.map(item => `
                        <tr>
                            <td class="px-3 py-2 text-sm text-gray-900">${item.item_name || item.material_name || '-'}</td>
                            <td class="px-3 py-2 text-sm text-gray-500">${item.item_code || '-'}</td>
                            <td class="px-3 py-2 text-sm text-gray-900">${item.quantity || '-'}</td>
                            <td class="px-3 py-2 text-sm text-gray-500">${item.unit || '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        `;
    } else {
        itemsHtml = '<p class="text-gray-500 mt-4">No items found in this request.</p>';
    }
    
    const modalHtml = `
        <div id="requestModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" onclick="closeRequestModal(event)">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-2xl shadow-lg rounded-md bg-white" onclick="event.stopPropagation()">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Material Request #${request.id}</h3>
                    <button onclick="closeRequestModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
                
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">Request Date:</span>
                        <span class="ml-2 text-gray-900">${request.request_date}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Required Date:</span>
                        <span class="ml-2 text-gray-900">${request.required_date}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Status:</span>
                        <span class="ml-2 px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">${request.status}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">Vendor:</span>
                        <span class="ml-2 text-gray-900">${request.vendor_name || 'N/A'}</span>
                    </div>
                </div>
                
                ${request.request_notes ? `<div class="mt-4"><span class="text-gray-500">Notes:</span><p class="text-gray-900 mt-1">${request.request_notes}</p></div>` : ''}
                
                <h4 class="text-md font-medium text-gray-900 mt-6">Items (${items.length})</h4>
                ${itemsHtml}
                
                <div class="mt-6 flex justify-end">
                    <button onclick="closeRequestModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300">Close</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function closeRequestModal(event) {
    if (event && event.target.id !== 'requestModal') return;
    const modal = document.getElementById('requestModal');
    if (modal) modal.remove();
}

// Load BOQ Master items when selected
function loadBoqMasterItems(boqMasterId) {
    const tableSection = document.getElementById('materialsTableSection');
    const tableBody = document.getElementById('materialsTableBody');
    const noItemsMessage = document.getElementById('noItemsMessage');
    const itemCount = document.getElementById('itemCount');
    
    if (!boqMasterId) {
        tableSection.classList.add('hidden');
        noItemsMessage.classList.remove('hidden');
        currentBoqMasterItems = [];
        return;
    }
    
    // Show loading state
    tableSection.classList.remove('hidden');
    noItemsMessage.classList.add('hidden');
    tableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Loading materials...</td></tr>';
    
    // Fetch items for the selected BOQ Master
    fetch(`get-boq-master-items.php?boq_master_id=${boqMasterId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.items.length > 0) {
                currentBoqMasterItems = data.items;
                renderMaterialsTable();
                itemCount.textContent = `${data.items.length} item(s)`;
            } else {
                tableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No materials found for this BOQ Master.</td></tr>';
                itemCount.textContent = '0 items';
                currentBoqMasterItems = [];
            }
        })
        .catch(error => {
            console.error('Error loading BOQ Master items:', error);
            tableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-red-500">Error loading materials. Please try again.</td></tr>';
            itemCount.textContent = '';
        });
}

// Render materials in table format
function renderMaterialsTable() {
    const tableBody = document.getElementById('materialsTableBody');
    
    if (currentBoqMasterItems.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">No materials available.</td></tr>';
        return;
    }
    
    let html = '';
    currentBoqMasterItems.forEach((item, index) => {
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <div class="text-sm font-medium text-gray-900">${item.item_name}</div>
                    <input type="hidden" name="items[${index}][boq_item_id]" value="${item.boq_item_id}">
                    <input type="hidden" name="items[${index}][item_code]" value="${item.item_code || ''}">
                    <input type="hidden" name="items[${index}][unit]" value="${item.unit || ''}">
                </td>
                <td class="px-4 py-3 text-sm text-gray-500">${item.item_code || '-'}</td>
                <td class="px-4 py-3">
                    <input type="number" name="items[${index}][quantity]" min="0" value="${item.default_quantity || 1}" 
                           class="w-24 px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </td>
                <td class="px-4 py-3 text-sm text-gray-500">${item.unit || '-'}</td>
                <td class="px-4 py-3 text-sm text-gray-500">${item.category || '-'}</td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

function saveDraft() {
    const form = document.getElementById('materialRequestForm');
    const formData = new FormData(form);
    formData.append('save_draft', '1');
    
    fetch('process-material-request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Draft saved successfully!');
        } else {
            alert('Error saving draft: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the draft.');
    });
}

// Form submission
document.getElementById('materialRequestForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const boqMasterSelect = document.getElementById('boq_master_select');
    if (!boqMasterSelect.value) {
        alert('Please select a BOQ Master.');
        return;
    }
    
    if (currentBoqMasterItems.length === 0) {
        alert('No materials available for this BOQ Master.');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('process-material-request.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Material request submitted successfully!');
            window.location.href = 'sites/index.php';
        } else {
            alert('Error submitting request: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while submitting the request.');
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/admin_layout.php';
?>