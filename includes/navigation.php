<?php
$currentUser = Auth::getCurrentUser();
$isAdmin = $currentUser && $currentUser['role'] === ADMIN_ROLE;
$isVendor = $currentUser && $currentUser['role'] === VENDOR_ROLE;
?>

<nav class="bg-blue-600 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="<?php echo BASE_URL; ?>" class="text-white text-xl font-bold">
                    <?php echo APP_NAME; ?>
                </a>
            </div>
            
            <?php if ($currentUser): ?>
            <div class="flex items-center space-x-4">
                <?php if ($isAdmin): ?>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="text-white hover:text-blue-200">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/admin/sites/" class="text-white hover:text-blue-200">Sites</a>
                <a href="<?php echo BASE_URL; ?>/admin/inventory/" class="text-white hover:text-blue-200">Inventory</a>
                <a href="<?php echo BASE_URL; ?>/admin/reports/" class="text-white hover:text-blue-200">Reports</a>
                <?php elseif ($isVendor): ?>
                <a href="<?php echo BASE_URL; ?>/vendor/" class="text-white hover:text-blue-200">Dashboard</a>
                <a href="<?php echo BASE_URL; ?>/vendor/sites/" class="text-white hover:text-blue-200">My Sites</a>
                <a href="<?php echo BASE_URL; ?>/vendor/materials/" class="text-white hover:text-blue-200">Materials</a>
                <?php endif; ?>
                
                <div class="relative">
                    <span class="text-white">Welcome, <?php echo htmlspecialchars($currentUser['username']); ?></span>
                    <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="ml-4 text-white hover:text-blue-200">Logout</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</nav>