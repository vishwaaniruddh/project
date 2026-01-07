<?php
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../models/BoqMaster.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);

$boqMasterModel = new BoqMaster();
$errors = [];
$success = false;
$formData = [
    'boq_name' => '',
    'description' => '',
    'status' => 'active'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'boq_name' => trim($_POST['boq_name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'status' => $_POST['status'] ?? 'active'
    ];
    
    // Validate the data
    $errors = $boqMasterModel->validateBoqData($formData);
    
    if (empty($errors)) {
        try {
            $boqId = $boqMasterModel->create($formData);
            if ($boqId) {
                $success = true;
                // Redirect to view page after successful creation
                header("Location: view.php?id=$boqId&created=1");
                exit;
            } else {
                $errors['general'] = 'Failed to create BOQ master. Please try again.';
            }
        } catch (Exception $e) {
            $errors['general'] = 'Database error: ' . $e->getMessage();
        }
    }
}

$title = 'Create BOQ Master';
ob_start();
?>

<div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Create BOQ Master</h1>
            <p class="mt-2 text-sm text-gray-700">Create a new Bill of Quantities template</p>
        </div>
        <div class="flex space-x-2">
            <a href="index.php" class="btn btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
                </svg>
                Back to List
            </a>
        </div>
    </div>

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

    <!-- Form -->
    <div class="card">
        <div class="card-body">
            <form method="POST" id="createBoqForm" novalidate>
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
                        <p class="mt-1 text-sm text-gray-500">Set the initial status for this BOQ template</p>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                        </svg>
                        Create BOQ Master
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createBoqForm');
    const submitBtn = document.getElementById('submitBtn');
    const boqNameInput = document.getElementById('boq_name');
    
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
    });
    
    // Form submission
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Creating...
        `;
    });
    
    // Auto-focus on BOQ name field
    boqNameInput.focus();
});

// Show success message if redirected from successful creation
const urlParams = new URLSearchParams(window.location.search);
if (urlParams.get('created') === '1') {
    showAlert('BOQ Master created successfully!', 'success');
}

// Alert function
function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `fixed top-4 right-4 z-50 p-4 rounded-md shadow-lg ${type === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200'}`;
    alertDiv.innerHTML = `
        <div class="flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                ${type === 'success' ? 
                    '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>' :
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