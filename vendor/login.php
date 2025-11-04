<?php
require_once '../config/auth.php';
require_once '../config/constants.php';

// If already logged in and is vendor, redirect to dashboard
if (Auth::isLoggedIn() && Auth::isVendor()) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Login - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                Vendor Login
            </h2>
            <p class="mt-2 text-center text-sm text-gray-600">
                <?php echo APP_NAME; ?>
            </p>
        </div>
        
        <!-- Error/Success Messages -->
        <div id="message" class="hidden"></div>
        
        <!-- Debug Information -->
        <div id="debug-info" class="hidden bg-gray-100 border border-gray-300 rounded-lg p-4 text-xs">
            <h4 class="font-semibold mb-2">Debug Information:</h4>
            <pre id="debug-content" class="whitespace-pre-wrap"></pre>
        </div>
        
        <form class="mt-8 space-y-6" id="loginForm">
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="username" class="sr-only">Email or Phone</label>
                    <input id="username" name="username" type="text" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                           placeholder="Email or Phone Number">
                </div>
                <div>
                    <label for="password" class="sr-only">Password</label>
                    <input id="password" name="password" type="password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-green-500 focus:border-green-500 focus:z-10 sm:text-sm" 
                           placeholder="Password">
                </div>
            </div>

            <div>
                <button type="submit" id="loginBtn"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50">
                    <span id="loginBtnText">Sign in</span>
                    <span id="loginSpinner" class="hidden ml-2">
                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                </button>
            </div>
            
            <!-- Debug Toggle -->
            <div class="text-center">
                <button type="button" id="debugToggle" class="text-xs text-gray-500 hover:text-gray-700">
                    Show Debug Info
                </button>
            </div>
        </form>
        
        <div class="mt-6 text-center">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-green-800 mb-2">Test Credentials:</h3>
                <div class="text-xs text-green-600 space-y-1 cursor-pointer">
                    <div class="hover:bg-green-100 p-1 rounded" onclick="fillCredentials('vendor1@test.com', 'vendor123')">
                        <strong>Vendor 1:</strong> vendor1@test.com / vendor123
                    </div>
                    <div class="hover:bg-green-100 p-1 rounded" onclick="fillCredentials('vendor2@test.com', 'vendor123')">
                        <strong>Vendor 2:</strong> vendor2@test.com / vendor123
                    </div>
                    <div class="hover:bg-green-100 p-1 rounded" onclick="fillCredentials('vendor3@test.com', 'vendor123')">
                        <strong>Vendor 3:</strong> vendor3@test.com / vendor123
                    </div>
                </div>
                <div class="text-xs text-gray-500 mt-2">Use email or phone to login</div>
            </div>
            
            <div class="mt-4">
                <a href="../admin/login.php" class="text-sm text-indigo-600 hover:text-indigo-500">
                    Admin Login →
                </a>
            </div>
        </div>
    </div>

    <script>
        let debugVisible = false;
        
        // Debug toggle
        document.getElementById('debugToggle').addEventListener('click', function() {
            debugVisible = !debugVisible;
            const debugInfo = document.getElementById('debug-info');
            const toggleBtn = document.getElementById('debugToggle');
            
            if (debugVisible) {
                debugInfo.classList.remove('hidden');
                toggleBtn.textContent = 'Hide Debug Info';
            } else {
                debugInfo.classList.add('hidden');
                toggleBtn.textContent = 'Show Debug Info';
            }
        });
        
        // Fill credentials function
        function fillCredentials(username, password) {
            document.getElementById('username').value = username;
            document.getElementById('password').value = password;
        }
        
        // Login form handler
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            const loginBtn = document.getElementById('loginBtn');
            const loginBtnText = document.getElementById('loginBtnText');
            const loginSpinner = document.getElementById('loginSpinner');
            const messageDiv = document.getElementById('message');
            const debugContent = document.getElementById('debug-content');
            
            // Show loading state
            loginBtn.disabled = true;
            loginBtnText.textContent = 'Signing in...';
            loginSpinner.classList.remove('hidden');
            messageDiv.classList.add('hidden');
            
            try {
                const response = await fetch('../api/login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        username: username,
                        password: password,
                        required_role: 'vendor'
                    })
                });
                
                const data = await response.json();
                
                // Show debug information
                debugContent.textContent = JSON.stringify(data, null, 2);
                
                // Show message
                messageDiv.classList.remove('hidden');
                messageDiv.className = 'px-4 py-3 rounded mb-4';
                
                if (data.success) {
                    messageDiv.classList.add('bg-green-100', 'border', 'border-green-400', 'text-green-700');
                    messageDiv.textContent = data.message;
                    
                    // Redirect after short delay
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 1000);
                } else {
                    messageDiv.classList.add('bg-red-100', 'border', 'border-red-400', 'text-red-700');
                    messageDiv.textContent = data.message || 'Login failed';
                    
                    // Auto-show debug info on error
                    if (!debugVisible) {
                        document.getElementById('debugToggle').click();
                    }
                }
                
            } catch (error) {
                console.error('Login error:', error);
                
                messageDiv.classList.remove('hidden');
                messageDiv.className = 'px-4 py-3 rounded mb-4 bg-red-100 border border-red-400 text-red-700';
                messageDiv.textContent = 'Network error: ' + error.message;
                
                debugContent.textContent = JSON.stringify({
                    error: 'Network/JavaScript Error',
                    message: error.message,
                    stack: error.stack
                }, null, 2);
                
                // Auto-show debug info on error
                if (!debugVisible) {
                    document.getElementById('debugToggle').click();
                }
            } finally {
                // Reset loading state
                loginBtn.disabled = false;
                loginBtnText.textContent = 'Sign in';
                loginSpinner.classList.add('hidden');
            }
        });
    </script>
</body>
</html>