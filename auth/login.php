<?php
require_once '../config/database.php';
require_once '../config/auth.php';
require_once '../config/constants.php';
require_once '../includes/error_handler.php';
require_once '../includes/logger.php';

$error = '';
$success = '';

// Check if user is already logged in
if (Auth::isLoggedIn()) {
    $user = Auth::getCurrentUser();
    if ($user['role'] === ADMIN_ROLE) {
        header('Location: ' . BASE_URL . '/admin/dashboard.php');
    } elseif ($user['role'] === VENDOR_ROLE) {
        header('Location: ' . BASE_URL . '/vendor/');
    }
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailOrPhone = trim($_POST['email_or_phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($emailOrPhone) || empty($password)) {
        $error = 'Please enter both email/phone and password.';
    } else {
        try {
            require_once '../includes/jwt_helper.php';
            require_once '../models/User.php';
            
            $userModel = new User();
            $user = $userModel->findByEmailOrPhone($emailOrPhone);
            
            if ($user && password_verify($password, $user['password_hash']) && $user['status'] === 'active') {
                // Generate JWT token
                $token = JWTHelper::generateToken($user['id'], $user['username'], $user['role']);
                
                // Update user token in database
                $userModel->updateToken($user['id'], $token);
                
                // Login successful
                Auth::login($user);
                Logger::logUserLogin($emailOrPhone, true);
                
                // Redirect based on role
                if ($user['role'] === ADMIN_ROLE) {
                    header('Location: ' . BASE_URL . '/admin/dashboard.php');
                } elseif ($user['role'] === VENDOR_ROLE) {
                    header('Location: ' . BASE_URL . '/vendor/');
                }
                exit();
            } else {
                $error = 'Invalid email/phone or password.';
                Logger::logUserLogin($emailOrPhone, false);
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
            Logger::error('Login error', ['email_or_phone' => $emailOrPhone, 'error' => $e->getMessage()]);
        }
    }
}

$title = 'Login - ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    <?php echo APP_NAME; ?>
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    Sign in to your account
                </p>
            </div>
            
            <form class="mt-8 space-y-6" method="POST">
                <?php if ($error): ?>
                    <div class="alert-error">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <label for="email_or_phone" class="sr-only">Email or Phone</label>
                        <input 
                            id="email_or_phone" 
                            name="email_or_phone" 
                            type="text" 
                            required 
                            class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                            placeholder="Email or Phone Number"
                            value="<?php echo htmlspecialchars($_POST['email_or_phone'] ?? ''); ?>"
                        >
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input 
                            id="password" 
                            name="password" 
                            type="password" 
                            required 
                            class="relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm" 
                            placeholder="Password"
                        >
                    </div>
                </div>

                <div>
                    <button 
                        type="submit" 
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    >
                        Sign in
                    </button>
                </div>
                
                <div class="text-center">
                    <p class="text-sm text-gray-600">
                        Default login: <strong>admin@example.com</strong> or <strong>+1234567890</strong> / <strong>admin123</strong>
                    </p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
</body>
</html>