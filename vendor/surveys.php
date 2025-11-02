<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../models/SiteSurvey.php';

// Require vendor authentication
Auth::requireVendor();

$vendorId = Auth::getVendorId();
$surveyModel = new SiteSurvey();

// Get all surveys for this vendor
$surveys = $surveyModel->getVendorSurveys($vendorId);

$title = 'My Surveys';
ob_start();
?>

<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900">Site Surveys</h1>
            <p class="text-sm text-gray-500 mt-1">View and manage all your submitted site surveys</p>
        </div>
        <div class="mt-6 lg:mt-0 lg:ml-6">
            <div class="flex space-x-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                    <?php echo count(array_filter($surveys, fn($s) => $s['survey_status'] === 'pending')); ?> Pending
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                    <?php echo count(array_filter($surveys, fn($s) => $s['survey_status'] === 'approved')); ?> Approved
                </span>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                    <?php echo count(array_filter($surveys, fn($s) => $s['survey_status'] === 'rejected')); ?> Rejected
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Surveys Table -->
<div class="professional-table bg-white">
    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Survey History</h3>

        </div>
    </div>
    <div class="p-6">
        <?php if (empty($surveys)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No surveys submitted</h3>
                <p class="mt-1 text-sm text-gray-500">You haven't submitted any site surveys yet.</p>
                <div class="mt-6">
                    <a href="sites/" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        View Sites
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 this ">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ATM ID</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Survey Date</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($surveys as $survey): ?>
                        <tr class="hover:bg-gray-50 transition-colors" id="row_<?php echo $survey['id']; ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-xs font-medium text-blue-600"><?php echo $survey['id']; ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($survey['site_code'] ?? $survey['site_id']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($survey['location'] ?? ''); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center">
                                    <div class="h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center mr-2">
                                        <span class="text-xs font-medium text-gray-600"><?php echo strtoupper(substr($survey['vendor_name'] ?? Auth::getCurrentUser()['username'], 0, 1)); ?></span>
                                    </div>
                                    <span class="text-sm text-gray-900"><?php echo htmlspecialchars($survey['vendor_name'] ?? Auth::getCurrentUser()['username']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                $statusIcon = '';
                                switch($survey['survey_status']) {
                                    case 'approved':
                                        $statusClass = 'bg-green-100 text-green-800';
                                        $statusText = 'Approved';
                                        $statusIcon = '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'bg-red-100 text-red-800';
                                        $statusText = 'Rejected';
                                        $statusIcon = '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>';
                                        break;
                                    case 'completed':
                                        $statusClass = 'bg-blue-100 text-blue-800';
                                        $statusText = 'Completed';
                                        $statusIcon = '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>';
                                        break;
                                    default:
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        $statusText = 'Pending';
                                        $statusIcon = '<svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path></svg>';
                                }
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>" id="status_<?php echo $survey['id']; ?>">
                                    <?php echo $statusIcon . $statusText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="max-w-xs">
                                    <div class="text-sm text-gray-900 truncate" title="<?php echo htmlspecialchars($survey['technical_remarks'] ?? 'No remarks provided'); ?>">
                                        <?php echo htmlspecialchars(substr($survey['technical_remarks'] ?? 'No remarks provided', 0, 50)) . (strlen($survey['technical_remarks'] ?? '') > 50 ? '...' : ''); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="text-sm text-gray-900">
                                    <?php echo $survey['created_at'] ? date('M d, Y', strtotime($survey['created_at'])) : 'N/A'; ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <?php echo $survey['created_at'] ? date('H:i A', strtotime($survey['created_at'])) : ''; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="../shared/view-survey.php?id=<?php echo $survey['id']; ?>" class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                                            <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        View
                                    </a>
                                    <?php if (in_array($survey['survey_status'], ['completed', 'approved'])): ?>
                                        <a href="material-request.php?site_id=<?php echo $survey['site_id']; ?>&survey_id=<?php echo $survey['id']; ?>" class="inline-flex items-center px-3 py-1 border border-blue-300 shadow-sm text-xs font-medium rounded-md text-blue-700 bg-blue-50 hover:bg-blue-100">
                                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zM8 6a2 2 0 114 0v1H8V6zM6 9a1 1 0 012 0v1a1 1 0 11-2 0V9zm8 0a1 1 0 012 0v1a1 1 0 11-2 0V9z" clip-rule="evenodd"></path>
                                            </svg>
                                            Material Request
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                                                <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    </div>

                                </div>
                            </td>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/vendor_layout.php';
?>