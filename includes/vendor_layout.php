<?php
require_once __DIR__ . '/../models/VendorPermission.php';
$permissionModel = new VendorPermission();
$vendorPermissions = $permissionModel->getVendorPermissions(Auth::getVendorId());
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Vendor Portal'; ?> - Site Installation Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <style>
        .vendor-sidebar {
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }
        
        /* Enhanced Form Styles */
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            line-height: 1.25rem;
            background-color: #ffffff;
            transition: all 0.2s ease;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-input:disabled {
            background-color: #f3f4f6;
            color: #6b7280;
            cursor: not-allowed;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #ffffff;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
        }
        
        .form-section h4 {
            color: #1f2937;
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        /* Grid layouts */
        .grid {
            display: grid;
        }
        
        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        
        .grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        
        .gap-6 {
            gap: 1.5rem;
        }
        
        @media (min-width: 768px) {
            .md\\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .md\\:col-span-2 {
                grid-column: span 2 / span 2;
            }
        }
        
        /* Button enhancements */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.5rem;
            border: 1px solid transparent;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            color: #ffffff;
            border-color: #3b82f6;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }
        
        .btn-secondary {
            background-color: #ffffff;
            color: #374151;
            border-color: #d1d5db;
        }
        
        .btn-secondary:hover {
            background-color: #f9fafb;
            border-color: #9ca3af;
        }
        
        /* Image preview styles */
        .preview img {
            border: 2px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        
        .preview img:hover {
            border-color: #3b82f6;
            transform: scale(1.05);
        }
        
        /* Alert styles */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #fca5a5;
            color: #991b1b;
        }
        
        /* Utility classes */
        .flex {
            display: flex;
        }
        
        .justify-between {
            justify-content: space-between;
        }
        
        .justify-end {
            justify-content: flex-end;
        }
        
        .items-center {
            align-items: center;
        }
        
        .space-x-2 > * + * {
            margin-left: 0.5rem;
        }
        
        .space-x-4 > * + * {
            margin-left: 1rem;
        }
        
        .space-y-6 > * + * {
            margin-top: 1.5rem;
        }
        
        .mb-8 {
            margin-bottom: 2rem;
        }
        
        .mt-2 {
            margin-top: 0.5rem;
        }
        
        .text-xs {
            font-size: 0.75rem;
        }
        
        .text-gray-500 {
            color: #6b7280;
        }
        .vendor-badge {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .sidebar-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(4px);
        }
        .sidebar-item.active {
            background-color: rgba(255, 255, 255, 0.15);
            border-left: 4px solid #f59e0b;
        }
        
        .sidebar-subitem {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .sidebar-subitem:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(2px);
        }
        .vendor-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        .stats-card {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        .stats-icon {
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .professional-table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        .table-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
        }
        .mobile-responsive {
            display: none;
        }
        @media (max-width: 1024px) {
            .vendor-sidebar {
                transform: translateX(-100%);
                position: fixed;
                z-index: 50;
                height: 100vh;
            }
            .vendor-sidebar.show {
                transform: translateX(0);
            }
            .mobile-responsive {
                display: block;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-50">
        <!-- Sidebar -->
        <div class="vendor-sidebar w-64 shadow-lg">
            <div class="flex flex-col h-full">
                <!-- Logo -->
                <div class="flex items-center justify-center h-16 px-4 bg-black bg-opacity-20">
                    <h1 class="text-xl font-bold text-white">Vendor Portal</h1>
                </div>
                
                <!-- Vendor Info -->
                <div class="px-4 py-4 border-b border-blue-800">
                    <div class="flex items-center">
                        <div class="vendor-badge w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm">
                            V
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-white"><?php echo htmlspecialchars(Auth::getCurrentUser()['username']); ?></p>
                            <p class="text-xs text-blue-200">Vendor Access</p>
                        </div>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 px-4 py-4 space-y-2">
                    <a href="<?php echo BASE_URL; ?>/vendor/" class="sidebar-item text-white hover:bg-blue-800">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                        </svg>
                        Dashboard
                    </a>
                    
                    <?php if ($vendorPermissions['view_sites'] ?? false): ?>
                    <a href="<?php echo BASE_URL; ?>/vendor/sites/" class="sidebar-item text-white hover:bg-blue-800">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v8a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2H4zm0 2h12v8H4V6z" clip-rule="evenodd"></path>
                        </svg>
                        My Sites
                    </a>
                    <a href="<?php echo BASE_URL; ?>/vendor/surveys.php" class="sidebar-item text-white hover:bg-blue-800">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                        </svg>
                        Site Surveys
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($vendorPermissions['view_masters'] ?? false): ?>
                    <a href="<?php echo BASE_URL; ?>/vendor/masters/" class="sidebar-item text-white hover:bg-blue-800">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                        </svg>
                        Master Data
                    </a>
                    <?php endif; ?>
                    
                    <?php // Temporarily removed permission check for testing ?>
                    <!-- Inventory Main Menu with Dropdown -->
                    <div class="relative">
                        <button onclick="toggleInventoryMenu()" class="sidebar-item text-white hover:bg-blue-800 w-full flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2L3 7v11a1 1 0 001 1h12a1 1 0 001-1V7l-7-5zM8 15a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Inventory
                            </div>
                            <svg id="inventory-arrow" class="w-4 h-4 transition-transform duration-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        
                        <!-- Inventory Submenu -->
                        <div id="inventory-submenu" class="hidden ml-8 mt-2 space-y-1">
                            <a href="<?php echo BASE_URL; ?>/vendor/inventory/" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                                </svg>
                                Inventory Overview
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/vendor/material-requests-list.php" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                Material Requests
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/vendor/material-received.php" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                Material Received
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/vendor/material-dispatches.php" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Material Dispatches
                            </a>
                        </div>
                    </div>
                    <?php // End of temporarily removed permission check ?>
                    
                    <?php if ($vendorPermissions['view_reports'] ?? false): ?>
                    <a href="<?php echo BASE_URL; ?>/vendor/reports/" class="sidebar-item text-white hover:bg-blue-800">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Reports
                    </a>
                    <?php endif; ?>
                </nav>

                <!-- User Menu -->
                <div class="px-4 py-4 border-t border-blue-800">
                    <a href="<?php echo BASE_URL; ?>/vendor/profile.php" class="sidebar-item text-white hover:bg-blue-800">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                        Profile
                    </a>
                    <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="sidebar-item text-white hover:bg-red-600">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 3a1 1 0 00-1 1v12a1 1 0 102 0V4a1 1 0 00-1-1zm10.293 9.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L14.586 9H7a1 1 0 100 2h7.586l-1.293 1.293z" clip-rule="evenodd"></path>
                        </svg>
                        Logout
                    </a>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Header -->
            <header class="vendor-header">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center">
                        <button id="toggleSidebar" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <div class="ml-2">
                            <h1 class="text-xl font-semibold text-gray-900"><?php echo $title ?? 'Vendor Portal'; ?></h1>
                            <p class="text-sm text-gray-500">Professional Site Management</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="relative" id="user-menu">
                            <button id="user-menu-button" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <div class="vendor-badge w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs">
                                    V
                                </div>
                                <span class="ml-2 text-gray-700"><?php echo htmlspecialchars(Auth::getCurrentUser()['username']); ?></span>
                                <svg class="ml-1 w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="<?php echo BASE_URL; ?>/vendor/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="max-w-7xl mx-auto">
                    <?php echo $content ?? ''; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-40 lg:hidden hidden"></div>

    <!-- Scripts -->
    <script src="<?php echo BASE_URL; ?>/assets/js/admin.js"></script>
    <script>
        // Mobile sidebar toggle
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            const sidebar = document.querySelector('.vendor-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('hidden');
        });

        // Close sidebar when clicking overlay
        document.getElementById('sidebar-overlay')?.addEventListener('click', function() {
            const sidebar = document.querySelector('.vendor-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.remove('show');
            overlay.classList.add('hidden');
        });

        // User dropdown toggle
        document.getElementById('user-menu-button')?.addEventListener('click', function() {
            const dropdown = document.getElementById('user-dropdown');
            dropdown.classList.toggle('hidden');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const userMenu = document.getElementById('user-menu');
            const dropdown = document.getElementById('user-dropdown');
            
            if (userMenu && !userMenu.contains(event.target)) {
                dropdown?.classList.add('hidden');
            }
        });

        // Add active class to current page sidebar item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const sidebarItems = document.querySelectorAll('.sidebar-item');
            
            sidebarItems.forEach(item => {
                if (item.getAttribute('href') && currentPath.includes(item.getAttribute('href'))) {
                    item.classList.add('active');
                }
            });
        });
        
        // Toggle inventory submenu
        function toggleInventoryMenu() {
            const submenu = document.getElementById('inventory-submenu');
            const arrow = document.getElementById('inventory-arrow');
            
            if (submenu.classList.contains('hidden')) {
                submenu.classList.remove('hidden');
                arrow.style.transform = 'rotate(180deg)';
            } else {
                submenu.classList.add('hidden');
                arrow.style.transform = 'rotate(0deg)';
            }
        }
        
        // Auto-expand inventory menu if on inventory page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            const inventoryPaths = ['/vendor/inventory/', '/vendor/material-request.php', '/vendor/material-received.php', '/vendor/material-dispatches.php'];
            
            if (inventoryPaths.some(path => currentPath.includes(path))) {
                const submenu = document.getElementById('inventory-submenu');
                const arrow = document.getElementById('inventory-arrow');
                if (submenu && arrow) {
                    submenu.classList.remove('hidden');
                    arrow.style.transform = 'rotate(180deg)';
                }
            }
        });
    </script>
</body>
</html>