<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/SiteSurvey.php';
require_once __DIR__ . '/../../models/Installation.php';
require_once __DIR__ . '/../../models/Vendor.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$surveyModel = new SiteSurvey();
$installationModel = new Installation();
$vendorModel = new Vendor();

// Get all surveys with installation status
$surveys = $surveyModel->getAllSurveys();
$activeVendors = $vendorModel->getActiveVendors();

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
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    <?php echo count(array_filter($surveys, fn($s) => ($s['installation_status'] ?? 'not_delegated') === 'delegated')); ?> Delegated for Installation
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
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Site Code</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Survey Status</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Installation Status</th>
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
                                <div class="font-medium"><?php echo htmlspecialchars($survey['site_code'] ?? 'N/A'); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars(substr($survey['location'] ?? '', 0, 30)); ?></div>
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
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <?php
                                $installationStatus = $survey['installation_status'] ?? 'not_delegated';
                                $installationClass = '';
                                $installationText = '';
                                switch($installationStatus) {
                                    case 'delegated':
                                        $installationClass = 'bg-blue-100 text-blue-800';
                                        $installationText = 'Delegated';
                                        break;
                                    case 'in_progress':
                                        $installationClass = 'bg-purple-100 text-purple-800';
                                        $installationText = 'In Progress';
                                        break;
                                    case 'completed':
                                        $installationClass = 'bg-green-100 text-green-800';
                                        $installationText = 'Completed';
                                        break;
                                    default:
                                        $installationClass = 'bg-gray-100 text-gray-800';
                                        $installationText = 'Not Delegated';
                                }
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $installationClass; ?>" id="installation_status_<?php echo $survey['id']; ?>">
                                    <?php echo $installationText; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-900">
                                <?php echo date('Y-m-d H:i', strtotime($survey['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div class="flex items-center justify-center space-x-2">
                                    <a href="../../shared/view-survey.php?id=<?php echo $survey['id']; ?>" class="text-blue-600 hover:text-blue-900 text-xs">View</a>
                                    
                                    <?php if ($survey['survey_status'] === 'pending'): ?>
                                        <select class="form-control form-control-sm action-dropdown" data-id="<?php echo $survey['id']; ?>" style="width:auto; display:inline-block; font-size: 11px;">
                                            <option value="">Survey Action</option>
                                            <option value="approve">Approve</option>
                                            <option value="reject">Reject</option>
                                        </select>
                                    <?php endif; ?>
                                    
                                    <?php if ($survey['survey_status'] === 'approved' && ($survey['installation_status'] ?? 'not_delegated') === 'not_delegated'): ?>
                                        <button onclick="delegateForInstallation(<?php echo $survey['id']; ?>)" class="text-green-600 hover:text-green-900 text-xs">
                                            Delegate Installation
                                        </button>
                                    <?php endif; ?>
                                    
                                    <?php if (($survey['installation_status'] ?? 'not_delegated') === 'delegated'): ?>
                                        <a href="../installations/view.php?survey_id=<?php echo $survey['id']; ?>" class="text-purple-600 hover:text-purple-900 text-xs">
                                            View Installation
                                        </a>
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

<!-- Installation Delegation Modal -->
<div id="installationDelegationModal" class="modal">
    <div class="modal-content max-w-2xl">
        <div class="modal-header">
            <h3 class="modal-title">Delegate for Installation</h3>
            <button type="button" class="modal-close" onclick="closeModal('installationDelegationModal')">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <form id="installationDelegationForm">
            <input type="hidden" id="delegation_survey_id" name="survey_id">
            <div class="modal-body">
                <div id="surveyInfo" class="mb-4 p-3 bg-gray-50 rounded"></div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="vendor_id" class="form-label">Select Vendor *</label>
                        <select id="vendor_id" name="vendor_id" class="form-select" required>
                            <option value="">Choose Vendor</option>
                            <?php foreach ($activeVendors as $vendor): ?>
                                <option value="<?php echo $vendor['id']; ?>">
                                    <?php echo htmlspecialchars($vendor['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority" class="form-label">Priority</label>
                        <select id="priority" name="priority" class="form-select">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="expected_start_date" class="form-label">Expected Start Date</label>
                        <input type="date" id="expected_start_date" name="expected_start_date" class="form-input" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="expected_completion_date" class="form-label">Expected Completion Date</label>
                        <input type="date" id="expected_completion_date" name="expected_completion_date" class="form-input" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="installation_type" class="form-label">Installation Type</label>
                        <select id="installation_type" name="installation_type" class="form-select">
                            <option value="standard">Standard Installation</option>
                            <option value="complex">Complex Installation</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="upgrade">Upgrade</option>
                        </select>
                    </div>
                    
                    <div class="form-group md:col-span-2">
                        <label for="special_instructions" class="form-label">Special Instructions</label>
                        <textarea id="special_instructions" name="special_instructions" rows="3" class="form-input" placeholder="Any special instructions for the installation team..."></textarea>
                    </div>
                    
                    <div class="form-group md:col-span-2">
                        <label for="delegation_notes" class="form-label">Notes</label>
                        <textarea id="delegation_notes" name="notes" rows="2" class="form-input" placeholder="Additional notes or comments..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('installationDelegationModal')" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary">Delegate Installation</button>
            </div>
        </form>
    </div>
</div>

<script>
// Handle approval/rejection and installation delegation
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
            
            // Remove dropdown and add delegation button if approved
            const actionsCell = document.querySelector(`[data-id="${surveyId}"]`).closest('td');
            if (action === 'approve') {
                const delegateBtn = document.createElement('button');
                delegateBtn.onclick = () => delegateForInstallation(surveyId);
                delegateBtn.className = 'text-green-600 hover:text-green-900 text-xs ml-2';
                delegateBtn.textContent = 'Delegate Installation';
                actionsCell.appendChild(delegateBtn);
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

function delegateForInstallation(surveyId) {
    // Fetch survey details
    fetch(`get-survey-details.php?id=${surveyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const survey = data.survey;
                
                // Populate survey info
                document.getElementById('delegation_survey_id').value = surveyId;
                document.getElementById('surveyInfo').innerHTML = `
                    <h5 class="font-medium text-gray-900">Survey Details</h5>
                    <div class="grid grid-cols-2 gap-4 mt-2 text-sm">
                        <div><span class="text-gray-500">Site:</span> ${survey.site_code}</div>
                        <div><span class="text-gray-500">Location:</span> ${survey.location || 'N/A'}</div>
                        <div><span class="text-gray-500">Survey Vendor:</span> ${survey.vendor_name}</div>
                        <div><span class="text-gray-500">Survey Date:</span> ${new Date(survey.created_at).toLocaleDateString()}</div>
                    </div>
                `;
                
                openModal('installationDelegationModal');
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Failed to load survey details', 'error');
        });
}

// Installation delegation form submission
document.getElementById('installationDelegationForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Processing...';
    
    fetch('process-installation-delegation.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            closeModal('installationDelegationModal');
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while delegating installation.', 'error');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/admin_layout.php';
?>