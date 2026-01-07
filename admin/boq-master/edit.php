<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/BoqMaster.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$boqMasterModel = new BoqMaster();
$errors = [];
$success = false;
$boqId = (int)($_GET['id'] ?? 0);

// Validate BOQ ID
if (!$boqId) {
    header('Location: index.php?error=invalid_id');
    exit;
}

// Get existing BOQ master data
$boqMaster = $boqMasterModel->find($boqId);
if (!$boqMaster) {
    header('Location: index.php?error=not_found');
    exit;
}

// Initialize form data with existing values
$formData = [
    'boq_name' => $boqMaster['boq_name'],
    'description' => $boqMaster['description'] ?? '',
    'status' => $boqMaster['status']
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'boq_name' => trim($_POST['boq_name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Validate the data
    $errors = $boqMasterModel->validateBoqData($formData, true, $boqId);
    
    if (empty($errors)) {
        try {
            $updated = $boqMasterModel->update($boqId, $formData);
            if ($updated) {
                $success = true;
                // Redirect to view page after successful update
                header("Location: view.php?id=$boqId&updated=1");
                exit;
            } else {
                $errors['general'] = 'Failed to update BOQ master. Please try again.';
            }
        } catch (Exception $e) {
            $errors['general'] = 'Database error: ' . $e->getMessage();
        }
    }
}

$title = 'Edit BOQ Master';
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Edit BOQ Master</h1>
            <p class="mt-2 text-sm text-gray-700">Update the details of "<?php echo htmlspecialchars($boqMaster['boq_name']); ?>"</p>
        </div>
        <div class="flex space-x-2">
            <a href="view.php?id=<?php echo $boqId; ?>" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path>
                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path>
                </svg>
                View Details
            </a>
            <a href="index.php" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                </svg>
                Back to List
            </a>
        </div>
    </div>

    <!-- Success Message -->
    <?php if ($success): ?>
    <div class="mb-6 bg-green-50 border border-green-200 rounded-md p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-green-800">BOQ Master updated successfully!</p>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Error Messages -->
    <?php if (!empty($errors)): ?>
    <div class="mb-6 bg-red-50 border border-red-200 rounded-md p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">Please correct the following errors:</h3>
                <div class="mt-2 text-sm text-red-700">
                    <ul class="list-disc pl-5 space-y-1">
                        <?php foreach ($errors as $field => $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- BOQ Master Info Card -->
    <div class="card mb-6">
        <div class="card-body">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <div class="flex-shrink-0 h-12 w-12">
                        <div class="h-12 w-12 rounded-lg bg-indigo-100 flex items-center justify-center">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-medium text-gray-900">BOQ ID: <?php echo $boqMaster['boq_id']; ?></h3>
                        <p class="text-sm text-gray-500">Created on <?php echo date('M d, Y \a\t H:i', strtotime($boqMaster['created_at'])); ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $boqMaster['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo ucfirst($boqMaster['status']); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST" id="editBoqForm" novalidate>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- BOQ Name -->
                    <div class="md:col-span-2">
                        <label for="boq_name" class="form-label">
                            BOQ Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="boq_name" 
                               name="boq_name" 
                               class="form-input <?php echo isset($errors['boq_name']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''; ?>" 
                               value="<?php echo htmlspecialchars($formData['boq_name']); ?>"
                               placeholder="Enter BOQ name (e.g., Network Installation Kit)"
                               maxlength="200"
                               required>
                        <?php if (isset($errors['boq_name'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?php echo htmlspecialchars($errors['boq_name']); ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-sm text-gray-500">Enter a descriptive name for this BOQ template (2-200 characters)</p>
                    </div>

                    <!-- Description -->
                    <div class="md:col-span-2">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" 
                                  name="description" 
                                  rows="3" 
                                  class="form-textarea <?php echo isset($errors['description']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''; ?>" 
                                  placeholder="Enter a detailed description of this BOQ template..."><?php echo htmlspecialchars($formData['description']); ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?php echo htmlspecialchars($errors['description']); ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-sm text-gray-500">Optional description to help identify the purpose of this BOQ</p>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="form-label">Status</label>
                        <select id="status" 
                                name="status" 
                                class="form-select <?php echo isset($errors['status']) ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''; ?>">
                            <option value="active" <?php echo $formData['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $formData['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <p class="mt-2 text-sm text-red-600"><?php echo htmlspecialchars($errors['status']); ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-sm text-gray-500">Set the status for this BOQ template</p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="view.php?id=<?php echo $boqId; ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7.707 10.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V6h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V8a2 2 0 012-2h5v5.586l-1.293-1.293zM9 4a1 1 0 012 0v2H9V4z"></path>
                        </svg>
                        Update BOQ Master
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Audit Trail -->
    <div class="card mt-6">
        <div class="card-body">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Audit Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="font-medium text-gray-700">Created:</span>
                    <span class="text-gray-600"><?php echo date('M d, Y \a\t H:i', strtotime($boqMaster['created_at'])); ?></span>
                    <?php if (!empty($boqMaster['created_by_name'])): ?>
                        <span class="text-gray-500">by <?php echo htmlspecialchars($boqMaster['created_by_name']); ?></span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($boqMaster['updated_at']) && $boqMaster['updated_at'] !== $boqMaster['created_at']): ?>
                <div>
                    <span class="font-medium text-gray-700">Last Updated:</span>
                    <span class="text-gray-600"><?php echo date('M d, Y \a\t H:i', strtotime($boqMaster['updated_at'])); ?></span>
                    <?php if (!empty($boqMaster['updated_by_name'])): ?>
                        <span class="text-gray-500">by <?php echo htmlspecialchars($boqMaster['updated_by_name']); ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editBoqForm');
    const submitBtn = document.getElementById('submitBtn');
    const boqNameInput = document.getElementById('boq_name');
    
    // Store original values to detect changes
    const originalValues = {
        boq_name: boqNameInput.value,
        description: document.getElementById('description').value,
        status: document.getElementById('status').value
    };
    
    // Client-side validation
    function validateForm() {
        let isValid = true;
        const errors = {};
        
        // Clear previous error states
        document.querySelectorAll('.border-red-300').forEach(el => {
            el.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
        });
        document.querySelectorAll('.text-red-600').forEach(el => {
            if (el.classList.contains('client-error')) {
                el.remove();
            }
        });
        
        // BOQ Name validation
        const boqName = boqNameInput.value.trim();
        if (!boqName) {
            errors.boq_name = 'BOQ name is required';
            isValid = false;
        } else if (boqName.length < 2) {
            errors.boq_name = 'BOQ name must be at least 2 characters';
            isValid = false;
        } else if (boqName.length > 200) {
            errors.boq_name = 'BOQ name must not exceed 200 characters';
            isValid = false;
        }
        
        // Display client-side errors
        Object.keys(errors).forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
                
                const errorEl = document.createElement('p');
                errorEl.className = 'mt-2 text-sm text-red-600 client-error';
                errorEl.textContent = errors[field];
                input.parentNode.appendChild(errorEl);
            }
        });
        
        return isValid;
    }
    
    // Check if form has changes
    function hasChanges() {
        return (
            boqNameInput.value !== originalValues.boq_name ||
            document.getElementById('description').value !== originalValues.description ||
            document.getElementById('status').value !== originalValues.status
        );
    }
    
    // Real-time validation for BOQ name
    boqNameInput.addEventListener('input', function() {
        const value = this.value.trim();
        const charCount = value.length;
        
        // Update character count display if it exists
        let charCountEl = document.getElementById('boq_name_char_count');
        if (!charCountEl) {
            charCountEl = document.createElement('p');
            charCountEl.id = 'boq_name_char_count';
            charCountEl.className = 'mt-1 text-sm text-gray-500';
            this.parentNode.appendChild(charCountEl);
        }
        
        charCountEl.textContent = `${charCount}/200 characters`;
        
        if (charCount > 200) {
            charCountEl.className = 'mt-1 text-sm text-red-600';
        } else {
            charCountEl.className = 'mt-1 text-sm text-gray-500';
        }
        
        // Update submit button state
        updateSubmitButton();
    });
    
    // Update submit button based on changes
    function updateSubmitButton() {
        if (hasChanges()) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        } else {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
        }
    }
    
    // Listen for changes on all form inputs
    ['description', 'status'].forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', updateSubmitButton);
            field.addEventListener('change', updateSubmitButton);
        }
    });
    
    // Initial button state
    updateSubmitButton();
    
    // Form submission
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        if (!hasChanges()) {
            e.preventDefault();
            showAlert('No changes detected. Please modify at least one field to update.', 'info');
            return false;
        }
        
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Updating...
        `;
    });
    
    // Warn about unsaved changes
    window.addEventListener('beforeunload', function(e) {
        if (hasChanges()) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
        }
    });
    
    // Auto-focus on BOQ name field
    boqNameInput.focus();
    boqNameInput.setSelectionRange(boqNameInput.value.length, boqNameInput.value.length);
});

// Show success message if redirected from successful update
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('updated') === '1') {
    showAlert('BOQ Master updated successfully!', 'success');
}

// Alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-100 text-green-800 border-green-200' : 
                   type === 'info' ? 'bg-blue-100 text-blue-800 border-blue-200' : 
                   'bg-red-100 text-red-800 border-red-200';
    
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg border ${bgColor}`;
    alertDiv.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                ${type === 'success' ? 
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>' :
                    type === 'info' ?
                    '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>' :
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>'
                }
            </svg>
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../includes/admin_layout.php';
?>