<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/SiteSurvey.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$surveyModel = new SiteSurvey();

// Get all surveys
$surveys = $surveyModel->getAllSurveys();

$title = 'Site Surveys Management';
ob_start();
?>

<!-- Header Section -->
<div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-900">Site Surveys Management</h1>
            <p class="mt-2 text-lg text-gray-600">Review and approve vendor site feasibility surveys</p>
            <p class="text-sm text-gray-500 mt-1">Manage all submitted site surveys from vendors</p>
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
            <h3 class="text-lg font-semibold text-gray-900">All Site Surveys</h3>
            <p class="text-sm text-gray-500 mt-1">Review and manage vendor submitted surveys</p>
        </div>
    </div>
    <div class="p-6">
        <?php if (empty($surveys)): ?>
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No surveys found</h3>
                <p class="mt-1 text-sm text-gray-500">No site surveys have been submitted yet.</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="table-header">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">ATM ID</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Engineer</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Survey Date</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($surveys as $survey): ?>
                        <tr class="hover:bg-gray-50 transition-colors" id="row_<?php echo $survey['id']; ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo $survey['id']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo htmlspecialchars($survey['site_code'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo htmlspecialchars($survey['vendor_name'] ?? 'Unknown'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                switch($survey['survey_status']) {
                                    case 'approved':
                                        $statusClass = 'bg-green-100 text-green-800';
                                        $statusText = 'Approved';
                                        break;
                                    case 'rejected':
                                        $statusClass = 'bg-red-100 text-red-800';
                                        $statusText = 'Rejected';
                                        break;
                                    default:
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        $statusText = 'Pending';
                                }
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>" id="status_<?php echo $survey['id']; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-900">
                                <div class="max-w-xs truncate" title="<?php echo htmlspecialchars($survey['technical_remarks'] ?? 'No remarks'); ?>">
                                    <?php echo htmlspecialchars($survey['technical_remarks'] ?? 'No remarks'); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo date('Y-m-d H:i', strtotime($survey['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="../../shared/view-survey.php?id=<?php echo $survey['id']; ?>" class="text-blue-600 hover:text-blue-900">View</a>
                                    <?php if ($survey['survey_status'] === 'pending'): ?>
                                        <select class="form-control form-control-sm action-dropdown ml-2" data-id="<?php echo $survey['id']; ?>" style="width:auto; display:inline-block;">
                                            <option value="">Select</option>
                                            <option value="approve">Approve</option>
                                            <option value="reject">Reject</option>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Handle approval/rejection
document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.action-dropdown');
    
    dropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', function() {
            const surveyId = this.dataset.id;
            const action = this.value;
            
            if (action && surveyId) {
                const remarks = prompt(`Please enter remarks for ${action}ing this survey:`);
                if (remarks !== null) {
                    updateSurveyStatus(surveyId, action, remarks);
                }
                this.value = ''; // Reset dropdown
            }
        });
    });
});

function updateSurveyStatus(surveyId, action, remarks) {
    const formData = new FormData();
    formData.append('survey_id', surveyId);
    formData.append('action', action);
    formData.append('remarks', remarks);
    
    fetch('process-survey-action.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update status badge
            const statusBadge = document.getElementById(`status_${surveyId}`);
            if (statusBadge) {
                if (action === 'approve') {
                    statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800';
                    statusBadge.textContent = 'Approved';
                } else if (action === 'reject') {
                    statusBadge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800';
                    statusBadge.textContent = 'Rejected';
                }
            }
            
            // Remove dropdown
            const dropdown = document.querySelector(`[data-id="${surveyId}"]`);
            if (dropdown) {
                dropdown.style.display = 'none';
            }
            
            showAlert(data.message, 'success');
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred. Please try again.', 'error');
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/admin_layout.php';
?>