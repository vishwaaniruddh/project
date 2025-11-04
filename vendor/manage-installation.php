<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../models/Installation.php';

// Require vendor authentication
Auth::requireVendor();

$vendorId = Auth::getVendorId();
$installationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$installationId) {
    header('Location: installations.php');
    exit;
}

$installationModel = new Installation();

// Get installation details and verify vendor access
$installation = $installationModel->getInstallationDetails($installationId);
if (!$installation || $installation['vendor_id'] != $vendorId) {
    header('Location: installations.php');
    exit;
}

$title = 'Manage Installation #' . $installationId;
ob_start();
?>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-gray-900">Manage Installation #<?php echo $installationId; ?></h1>
        <p class="mt-2 text-sm text-gray-700">
            Update installation progress and manage timeline
        </p>
    </div>
    <a href="installations.php" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
        </svg>
        Back to Installations
    </a>
</div>

<!-- Installation Information -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <!-- Installation Details -->
    <div class="lg:col-span-2">
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Installation Details</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Information about this installation task</p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-0">
                <dl class="sm:divide-y sm:divide-gray-200">
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Site Code</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($installation['site_code'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Location</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($installation['location'] ?? 'N/A'); ?></dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">City, State</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($installation['city_name'] . ', ' . $installation['state_name']); ?></dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Installation Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo ucfirst($installation['installation_type']); ?></dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Priority</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php 
                                switch($installation['priority']) {
                                    case 'urgent': echo 'bg-red-100 text-red-800'; break;
                                    case 'high': echo 'bg-orange-100 text-orange-800'; break;
                                    case 'medium': echo 'bg-yellow-100 text-yellow-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800'; break;
                                }
                                ?>">
                                <?php echo ucfirst($installation['priority']); ?>
                            </span>
                        </dd>
                    </div>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Current Status</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                <?php 
                                switch($installation['status']) {
                                    case 'completed': echo 'bg-green-100 text-green-800'; break;
                                    case 'in_progress': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'on_hold': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                    case 'acknowledged': echo 'bg-purple-100 text-purple-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800'; break;
                                }
                                ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $installation['status'])); ?>
                            </span>
                        </dd>
                    </div>
                    <?php if ($installation['special_instructions']): ?>
                    <div class="py-4 sm:py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Special Instructions</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2"><?php echo htmlspecialchars($installation['special_instructions']); ?></dd>
                    </div>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
    </div>
    
    <!-- Timeline & Actions -->
    <div>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Timeline & Actions</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage installation timing and progress</p>
            </div>
            <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
                <div class="space-y-4">
                    <!-- Expected Timeline -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Expected Start</label>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo $installation['expected_start_date'] ? date('M j, Y', strtotime($installation['expected_start_date'])) : 'Not set'; ?>
                        </p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Expected Completion</label>
                        <p class="mt-1 text-sm text-gray-900">
                            <?php echo $installation['expected_completion_date'] ? date('M j, Y', strtotime($installation['expected_completion_date'])) : 'Not set'; ?>
                        </p>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Site Arrival Time -->
                    <div>
                        <label for="arrival_time" class="block text-sm font-medium text-gray-700">
                            Site Arrival Time <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="arrival_time" 
                               name="arrival_time"
                               value="<?php echo $installation['actual_start_date'] ? date('Y-m-d\TH:i', strtotime($installation['actual_start_date'])) : ''; ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               <?php echo $installation['status'] === 'completed' ? 'disabled' : ''; ?>>
                        <p class="mt-1 text-xs text-gray-500">When did you arrive at the site?</p>
                    </div>
                    
                    <!-- Installation Start Time -->
                    <div>
                        <label for="installation_start_time" class="block text-sm font-medium text-gray-700">
                            Installation Start Time <span class="text-red-500">*</span>
                        </label>
                        <input type="datetime-local" 
                               id="installation_start_time" 
                               name="installation_start_time"
                               value="<?php echo $installation['installation_start_time'] ? date('Y-m-d\TH:i', strtotime($installation['installation_start_time'])) : ''; ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                               <?php echo $installation['status'] === 'completed' ? 'disabled' : ''; ?>>
                        <p class="mt-1 text-xs text-gray-500">When did you start the installation work?</p>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="space-y-2">
                        <?php if ($installation['status'] !== 'completed'): ?>
                            <button onclick="updateTimings()" 
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Update Timings
                            </button>
                            <button id="proceedBtn" 
                                    onclick="proceedToInstallation()" 
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                    disabled>
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6z" clip-rule="evenodd"></path>
                                </svg>
                                Proceed to Installation
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($installation['status'] === 'in_progress'): ?>
                            <a href="installation-material-usage.php?id=<?php echo $installationId; ?>" 
                               class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"></path>
                                </svg>
                                Material Usage
                            </a>
                            <button onclick="showProgressModal()" 
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                                </svg>
                                Update Progress
                            </button>
                            <button onclick="completeInstallation()" 
                                    class="w-full inline-flex justify-center items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                Mark as Completed
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Installation Progress -->
<?php if ($installation['status'] === 'in_progress' || $installation['status'] === 'completed'): ?>
<div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <div class="px-4 py-5 sm:px-6">
        <h3 class="text-lg leading-6 font-medium text-gray-900">Installation Progress</h3>
        <p class="mt-1 max-w-2xl text-sm text-gray-500">Track your installation progress and updates</p>
    </div>
    <div class="border-t border-gray-200 px-4 py-5 sm:p-6">
        <div id="progressContainer">
            <!-- Progress will be loaded here -->
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Progress Update Modal -->
<div id="progressModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Update Installation Progress</h3>
            <form id="progressForm">
                <div class="space-y-4">
                    <div>
                        <label for="progress_percentage" class="block text-sm font-medium text-gray-700">
                            Progress Percentage
                        </label>
                        <input type="number" 
                               id="progress_percentage" 
                               name="progress_percentage"
                               min="0" 
                               max="100" 
                               step="5"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label for="work_description" class="block text-sm font-medium text-gray-700">
                            Work Description
                        </label>
                        <textarea id="work_description" 
                                  name="work_description"
                                  rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                  placeholder="Describe the work completed..."></textarea>
                    </div>
                    <div>
                        <label for="issues_faced" class="block text-sm font-medium text-gray-700">
                            Issues Faced (if any)
                        </label>
                        <textarea id="issues_faced" 
                                  name="issues_faced"
                                  rows="2"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                  placeholder="Any issues or challenges..."></textarea>
                    </div>
                    <div>
                        <label for="next_steps" class="block text-sm font-medium text-gray-700">
                            Next Steps
                        </label>
                        <textarea id="next_steps" 
                                  name="next_steps"
                                  rows="2"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                  placeholder="What needs to be done next..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeProgressModal()" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Save Progress
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    checkProceedButton();
    loadProgress();
    
    // Add event listeners to timing inputs
    document.getElementById('arrival_time').addEventListener('change', checkProceedButton);
    document.getElementById('installation_start_time').addEventListener('change', checkProceedButton);
});

function checkProceedButton() {
    const arrivalTime = document.getElementById('arrival_time').value;
    const installationStartTime = document.getElementById('installation_start_time').value;
    const proceedBtn = document.getElementById('proceedBtn');
    
    if (arrivalTime && installationStartTime) {
        proceedBtn.disabled = false;
        proceedBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    } else {
        proceedBtn.disabled = true;
        proceedBtn.classList.add('opacity-50', 'cursor-not-allowed');
    }
}

function updateTimings() {
    const arrivalTime = document.getElementById('arrival_time').value;
    const installationStartTime = document.getElementById('installation_start_time').value;
    
    if (!arrivalTime || !installationStartTime) {
        alert('Please fill in both arrival time and installation start time.');
        return;
    }
    
    // Validate that installation start time is after arrival time
    if (new Date(installationStartTime) <= new Date(arrivalTime)) {
        alert('Installation start time must be after arrival time.');
        return;
    }
    
    fetch('process-installation-action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'update_timings',
            installation_id: <?php echo $installationId; ?>,
            arrival_time: arrivalTime,
            installation_start_time: installationStartTime
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Timings updated successfully!');
            checkProceedButton();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating timings.');
    });
}

function proceedToInstallation() {
    if (confirm('Are you sure you want to proceed to installation? This will change the status to "In Progress".')) {
        fetch('process-installation-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'proceed_to_installation',
                installation_id: <?php echo $installationId; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Installation started successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while starting installation.');
        });
    }
}

function showProgressModal() {
    document.getElementById('progressModal').classList.remove('hidden');
}

function closeProgressModal() {
    document.getElementById('progressModal').classList.add('hidden');
    document.getElementById('progressForm').reset();
}

function completeInstallation() {
    if (confirm('Are you sure you want to mark this installation as completed?')) {
        fetch('process-installation-action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'complete_installation',
                installation_id: <?php echo $installationId; ?>
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Installation completed successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while completing installation.');
        });
    }
}

function loadProgress() {
    fetch('get-installation-progress.php?id=<?php echo $installationId; ?>')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayProgress(data.progress);
        }
    })
    .catch(error => {
        console.error('Error loading progress:', error);
    });
}

function displayProgress(progressData) {
    const container = document.getElementById('progressContainer');
    if (!container) return;
    
    if (progressData.length === 0) {
        container.innerHTML = '<p class="text-gray-500 text-center py-4">No progress updates yet.</p>';
        return;
    }
    
    let html = '<div class="space-y-4">';
    progressData.forEach(progress => {
        html += `
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center">
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-sm font-medium text-blue-800">${progress.progress_percentage}%</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">${progress.progress_date}</p>
                            <p class="text-xs text-gray-500">Updated by ${progress.updated_by_name || 'System'}</p>
                        </div>
                    </div>
                </div>
                ${progress.work_description ? `<p class="text-sm text-gray-700 mb-2"><strong>Work:</strong> ${progress.work_description}</p>` : ''}
                ${progress.issues_faced ? `<p class="text-sm text-red-600 mb-2"><strong>Issues:</strong> ${progress.issues_faced}</p>` : ''}
                ${progress.next_steps ? `<p class="text-sm text-blue-600"><strong>Next Steps:</strong> ${progress.next_steps}</p>` : ''}
            </div>
        `;
    });
    html += '</div>';
    container.innerHTML = html;
}

// Progress form submission
document.getElementById('progressForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        action: 'add_progress',
        installation_id: <?php echo $installationId; ?>,
        progress_percentage: formData.get('progress_percentage'),
        work_description: formData.get('work_description'),
        issues_faced: formData.get('issues_faced'),
        next_steps: formData.get('next_steps')
    };
    
    fetch('process-installation-action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Progress updated successfully!');
            closeProgressModal();
            loadProgress();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating progress.');
    });
});
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../includes/vendor_layout.php';
?>