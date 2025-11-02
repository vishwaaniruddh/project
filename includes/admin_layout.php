<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/constants.php';

// Require admin authentication
Auth::requireRole(ADMIN_ROLE);
$currentUser = Auth::getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Admin Panel'; ?> - <?php echo APP_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/custom.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css">
    <!-- Fallback CSS for subdirectories -->
    <style>
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background-color: #2563eb;
            color: white;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #4b5563;
        }
        .btn-success {
            background-color: #059669;
            color: white;
        }
        .btn-success:hover {
            background-color: #047857;
        }
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
        .card {
            background-color: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .card-body {
            padding: 1.5rem;
        }
        .form-input, .form-select {
            display: block;
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            background-color: white;
        }
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th,
        .data-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .data-table th {
            background-color: #f9fafb;
            font-weight: 600;
            color: #374151;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        .modal-content {
            position: relative;
            background-color: white;
            margin: 2rem auto;
            padding: 0;
            border-radius: 0.5rem;
            max-width: 32rem;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .modal-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #111827;
        }
        .modal-body {
            padding: 1.5rem;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            padding: 1.5rem;
            border-top: 1px solid #e5e7eb;
        }
        .modal-close {
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.25rem;
        }
        .modal-close:hover {
            color: #374151;
        }
        /* Grid and layout utilities */
        .grid {
            display: grid;
        }
        .grid-cols-1 {
            grid-template-columns: repeat(1, minmax(0, 1fr));
        }
        .grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .grid-cols-3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .grid-cols-4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }
        .gap-3 {
            gap: 0.75rem;
        }
        .gap-4 {
            gap: 1rem;
        }
        .gap-6 {
            gap: 1.5rem;
        }
        .flex {
            display: flex;
        }
        .flex-1 {
            flex: 1 1 0%;
        }
        .items-center {
            align-items: center;
        }
        .justify-between {
            justify-content: space-between;
        }
        .space-x-2 > * + * {
            margin-left: 0.5rem;
        }
        .mb-4 {
            margin-bottom: 1rem;
        }
        .mb-6 {
            margin-bottom: 1.5rem;
        }
        .text-2xl {
            font-size: 1.5rem;
            line-height: 2rem;
        }
        .font-semibold {
            font-weight: 600;
        }
        .text-gray-900 {
            color: #111827;
        }
        .text-gray-700 {
            color: #374151;
        }
        .text-gray-500 {
            color: #6b7280;
        }
        .text-sm {
            font-size: 0.875rem;
            line-height: 1.25rem;
        }
        .mt-2 {
            margin-top: 0.5rem;
        }
        .overflow-x-auto {
            overflow-x: auto;
        }
        .max-w-4xl {
            max-width: 56rem;
        }
        .md\:grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .md\:col-span-2 {
            grid-column: span 2 / span 2;
        }
        @media (min-width: 768px) {
            .md\:grid-cols-2 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .md\:grid-cols-3 {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .md\:grid-cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
            .md\:col-span-2 {
                grid-column: span 2 / span 2;
            }
        }
        @media (min-width: 1024px) {
            .lg\:grid-cols-4 {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Admin Sidebar Styling - Match Vendor Style */
        .admin-sidebar {
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            z-index: 20;
            height: 100vh;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }
        
        .admin-sidebar.show {
            transform: translateX(0);
        }
        
        @media (min-width: 1024px) {
            .admin-sidebar {
                transform: translateX(0);
            }
        }
        
        .admin-badge {
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
        
        /* Responsive styles */
        @media (max-width: 1023px) {
            .sidebar-item {
                padding: 0.875rem 1rem;
                font-size: 0.9rem;
            }
        }
        
        /* Card responsive styles */
        .card {
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 640px) {
            .card-body {
                padding: 1rem;
            }
            
            .data-table th,
            .data-table td {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
            
            .btn {
                padding: 0.5rem 1rem;
                font-size: 0.8rem;
            }
            
            .btn-sm {
                padding: 0.375rem 0.5rem;
                font-size: 0.7rem;
            }
        }
        
        /* Modal responsive styles */
        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                top: 2rem;
                padding: 1rem;
            }
            
            .modal-content-large {
                width: 98%;
                top: 1rem;
                max-height: 95vh;
            }
            
            .modal-body-scrollable {
                padding: 1rem;
                max-height: calc(95vh - 120px);
            }
        }
        .btn {
            font-weight: 500;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            padding: 0.625rem 1.25rem;
            text-align: center;
            transition: all 0.2s;
        }
        .btn-primary {
            color: white;
            background-color: #2563eb;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        .btn-secondary {
            color: #6b7280;
            background-color: white;
            border: 1px solid #e5e7eb;
        }
        .btn-secondary:hover {
            background-color: #f9fafb;
            color: #374151;
        }
        .btn-danger {
            color: white;
            background-color: #dc2626;
        }
        .btn-danger:hover {
            background-color: #b91c1c;
        }
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.75rem;
        }
        .card {
            background-color: white;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border-radius: 0.5rem;
        }
        .card-body {
            padding: 1.5rem;
        }
        .data-table {
            width: 100%;
            font-size: 0.875rem;
            text-align: left;
            color: #6b7280;
        }
        .data-table thead {
            font-size: 0.75rem;
            color: #374151;
            text-transform: uppercase;
            background-color: #f9fafb;
        }
        .data-table th {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }
        .data-table td {
            padding: 1rem 1.5rem;
            white-space: nowrap;
        }
        .data-table tbody tr {
            background-color: white;
            border-bottom: 1px solid #e5e7eb;
        }
        .data-table tbody tr:hover {
            background-color: #f9fafb;
        }
        
        /* Responsive table */
        @media (max-width: 768px) {
            .data-table {
                font-size: 0.8rem;
            }
            .data-table th,
            .data-table td {
                padding: 0.5rem 0.75rem;
            }
            .data-table td {
                white-space: normal;
                word-break: break-word;
            }
        }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
            display: none;
        }
        .modal.show {
            display: block;
        }
        .modal-content {
            position: relative;
            top: 5rem;
            margin: 0 auto;
            padding: 1.25rem;
            border: 1px solid #e5e7eb;
            width: 91.666667%;
            max-width: 42rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 0.375rem;
            background-color: white;
        }
        .modal-content-large {
            position: relative;
            top: 2rem;
            margin: 0 auto;
            border: 1px solid #e5e7eb;
            width: 95%;
            max-width: 900px;
            max-height: 90vh;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            border-radius: 0.375rem;
            background-color: white;
            display: flex;
            flex-direction: column;
        }
        .modal-header-fixed {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            background-color: white;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        .modal-body-scrollable {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            max-height: calc(90vh - 140px);
        }
        .modal-footer-fixed {
            flex-shrink: 0;
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            gap: 0.5rem;
            border-top: 1px solid #e5e7eb;
            background-color: white;
            border-radius: 0 0 0.375rem 0.375rem;
        }
        .form-section {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #f3f4f6;
        }
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .form-section-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
        }
        
        /* Dynamic Menu Styles */
        .menu-group {
            margin-bottom: 0.25rem;
        }
        
        .menu-group-title {
            color: #9ca3af;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 0.5rem 1rem;
            margin-top: 1rem;
        }
        
        .submenu {
            background-color: #1f2937;
            border-radius: 0.375rem;
            margin: 0.25rem 0.5rem;
            overflow: hidden;
        }
        
        .submenu .sidebar-item {
            padding-left: 2.5rem;
            margin: 0;
            border-radius: 0;
        }
        
        .submenu .sidebar-item:hover {
            background-color: #374151;
        }
        
        .submenu {
            overflow: hidden;
            transition: max-height 0.3s ease-in-out;
            padding-left: 1rem;
        }
        
        .submenu-open {
            max-height: 500px;
        }
        
        .submenu-closed {
            max-height: 0;
        }
        
        .submenu-arrow {
            transition: transform 0.3s ease-in-out;
        }
        
        .submenu-open .submenu-arrow {
            transform: rotate(90deg);
        }
        
        /* Dark sidebar styles already defined above */
        
        /* Nested menu items */
        .ml-4 {
            margin-left: 1rem;
        }
        
        .ml-8 {
            margin-left: 2rem;
        }
        
        .ml-12 {
            margin-left: 3rem;
        }
        .form-input, .form-select {
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            color: #111827;
            font-size: 0.875rem;
            border-radius: 0.5rem;
            display: block;
            width: 100%;
            padding: 0.625rem;
        }
        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-success {
            background-color: #dcfce7;
            color: #166534;
        }
        .badge-danger {
            background-color: #fecaca;
            color: #991b1b;
        }
        .badge-info {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-secondary {
            background-color: #f3f4f6;
            color: #374151;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        /* Smooth scrolling for sidebar */
        nav {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
        }
        
        nav::-webkit-scrollbar {
            width: 6px;
        }
        
        nav::-webkit-scrollbar-track {
            background: transparent;
        }
        
        nav::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }
        
        nav::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen bg-gray-50">

    <!-- Sidebar -->
    <div class="admin-sidebar w-64 shadow-lg">
        <div class="flex flex-col h-full">
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 px-4 bg-black bg-opacity-20">
                <h1 class="text-xl font-bold text-white">Admin Panel</h1>
            </div>
            
            <!-- Admin Info -->
            <div class="px-4 py-4 border-b border-blue-800">
                <div class="flex items-center">
                    <div class="admin-badge w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm">
                        <?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                        <p class="text-xs text-blue-200">Administrator</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 px-4 py-4 space-y-2 overflow-y-auto">
                <?php 
                try {
                    require_once __DIR__ . '/dynamic_sidebar.php';
                    renderDynamicSidebar($currentUser); 
                    echo '<script>console.log("Dynamic sidebar loaded successfully");</script>';
                } catch (Exception $e) {
                    echo '<script>console.log("Dynamic sidebar failed, using static menu:", "' . addslashes($e->getMessage()) . '");</script>';
                    // Fallback static sidebar with clean vendor-style design
                    ?>
                    <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="sidebar-item text-white hover:bg-blue-800">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                        </svg>
                        Dashboard
                    </a>
                    
                    <a href="<?php echo BASE_URL; ?>/admin/sites/" class="sidebar-item text-white hover:bg-blue-800">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                        Sites
                    </a>
                    
                    <!-- Admin Main Menu with Dropdown -->
                    <div class="relative">
                        <button onclick="toggleAdminMenu()" class="sidebar-item text-white hover:bg-blue-800 w-full flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                                </svg>
                                Admin
                            </div>
                            <svg id="admin-arrow" class="w-4 h-4 transition-transform duration-200" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                            </svg>
                        </button>
                        
                        <!-- Admin Submenu -->
                        <div id="admin-submenu" class="hidden ml-8 mt-2 space-y-1">
                            <a href="<?php echo BASE_URL; ?>/admin/users/" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                                </svg>
                                Users
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/admin/vendors/" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"></path>
                                </svg>
                                Vendors
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/admin/masters/" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                                </svg>
                                Masters
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/admin/boq/" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                                </svg>
                                BOQ Management
                            </a>
                        </div>
                    </div>
                    
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
                            <a href="<?php echo BASE_URL; ?>/admin/inventory/" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                                </svg>
                                All Stocks
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/admin/requests/" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                Material Requests
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/admin/inventory/inwards/" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                                Material Received
                            </a>
                            
                            <a href="<?php echo BASE_URL; ?>/admin/inventory/dispatches/" class="sidebar-subitem text-gray-300 hover:text-white hover:bg-blue-800">
                                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                Material Dispatches
                            </a>
                        </div>
                    </div>
                    
                    <a href="<?php echo BASE_URL; ?>/admin/surveys/" class="sidebar-item text-white hover:bg-blue-800">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd"></path>
                        </svg>
                        Surveys
                    </a>

                    <a href="<?php echo BASE_URL; ?>/admin/reports/" class="sidebar-item text-white hover:bg-blue-800">
                        <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                        </svg>
                        Reports
                    </a>
                    <?php
                }
                ?>
            </nav>

            <!-- User Menu -->
            <div class="px-4 py-4 border-t border-blue-800">
                <a href="<?php echo BASE_URL; ?>/admin/profile.php" class="sidebar-item text-white hover:bg-blue-800">
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
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <!-- Top Header -->
            <header class="admin-header">
                <div class="flex items-center justify-between px-6 py-4">
                    <div class="flex items-center">
                        <button id="toggleSidebar" class="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>
                        <div class="ml-2">
                            <h1 class="text-xl font-semibold text-gray-900"><?php echo $title ?? 'Admin Panel'; ?></h1>
                            <p class="text-sm text-gray-500">System Administration</p>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <div class="relative" id="user-menu">
                            <button id="user-menu-button" class="flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <div class="admin-badge w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-xs">
                                    <?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?>
                                </div>
                                <span class="ml-2 text-gray-700"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                                <svg class="ml-1 w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <a href="<?php echo BASE_URL; ?>/admin/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                <a href="<?php echo BASE_URL; ?>/admin/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                                <a href="<?php echo BASE_URL; ?>/auth/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Sign out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="max-w-7xl mx-auto">
                    <?php if (isset($content)) echo $content; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-600 bg-opacity-50 z-40 lg:hidden hidden"></div>

    <!-- Scripts -->
    <script src="<?php echo BASE_URL; ?>/assets/js/app.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/admin.js"></script>
    <script src="<?php echo BASE_URL; ?>/assets/js/masters-api.js"></script>
    <script>
        // Mobile sidebar toggle
        document.getElementById('toggleSidebar')?.addEventListener('click', function() {
            const sidebar = document.querySelector('.admin-sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            sidebar.classList.toggle('show');
            overlay.classList.toggle('hidden');
        });

        // Close sidebar when clicking overlay
        document.getElementById('sidebar-overlay')?.addEventListener('click', function() {
            const sidebar = document.querySelector('.admin-sidebar');
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
        
        // Toggle inventory submenu with accordion behavior
        function toggleInventoryMenu() {
            const submenu = document.getElementById('inventory-submenu');
            const arrow = document.getElementById('inventory-arrow');
            
            if (!submenu) return;
            
            const isCurrentlyHidden = submenu.classList.contains('hidden');
            
            // Accordion behavior - close all other submenus first
            const allSubmenus = document.querySelectorAll('[id^="submenu-"], #inventory-submenu, #admin-submenu');
            const allArrows = document.querySelectorAll('[id^="arrow-"], #inventory-arrow, #admin-arrow');
            
            allSubmenus.forEach(otherSubmenu => {
                if (otherSubmenu.id !== 'inventory-submenu') {
                    otherSubmenu.classList.add('hidden');
                }
            });
            
            allArrows.forEach(otherArrow => {
                if (otherArrow.id !== 'inventory-arrow') {
                    otherArrow.style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle the inventory submenu
            if (isCurrentlyHidden) {
                submenu.classList.remove('hidden');
                if (arrow) arrow.style.transform = 'rotate(180deg)';
            } else {
                submenu.classList.add('hidden');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
        }
        
        // Toggle admin submenu with accordion behavior
        function toggleAdminMenu() {
            const submenu = document.getElementById('admin-submenu');
            const arrow = document.getElementById('admin-arrow');
            
            if (!submenu) return;
            
            const isCurrentlyHidden = submenu.classList.contains('hidden');
            
            // Accordion behavior - close all other submenus first
            const allSubmenus = document.querySelectorAll('[id^="submenu-"], #inventory-submenu, #admin-submenu');
            const allArrows = document.querySelectorAll('[id^="arrow-"], #inventory-arrow, #admin-arrow');
            
            allSubmenus.forEach(otherSubmenu => {
                if (otherSubmenu.id !== 'admin-submenu') {
                    otherSubmenu.classList.add('hidden');
                }
            });
            
            allArrows.forEach(otherArrow => {
                if (otherArrow.id !== 'admin-arrow') {
                    otherArrow.style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle the admin submenu
            if (isCurrentlyHidden) {
                submenu.classList.remove('hidden');
                if (arrow) arrow.style.transform = 'rotate(180deg)';
            } else {
                submenu.classList.add('hidden');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
        }
        
        // Auto-expand menus based on current page
        document.addEventListener('DOMContentLoaded', function() {
            const currentPath = window.location.pathname;
            
            // Auto-expand inventory menu if on inventory page
            const inventoryPaths = ['/admin/inventory/', '/admin/requests/', '/admin/inventory/inwards/', '/admin/inventory/dispatches/'];
            if (inventoryPaths.some(path => currentPath.includes(path))) {
                const submenu = document.getElementById('inventory-submenu');
                const arrow = document.getElementById('inventory-arrow');
                if (submenu && arrow) {
                    submenu.classList.remove('hidden');
                    arrow.style.transform = 'rotate(180deg)';
                }
            }
            
            // Auto-expand admin menu if on admin pages
            const adminPaths = ['/admin/users/', '/admin/vendors/', '/admin/masters/', '/admin/boq/'];
            if (adminPaths.some(path => currentPath.includes(path))) {
                const submenu = document.getElementById('admin-submenu');
                const arrow = document.getElementById('admin-arrow');
                if (submenu && arrow) {
                    submenu.classList.remove('hidden');
                    arrow.style.transform = 'rotate(180deg)';
                }
            }
            
            // For dynamic database-driven menus - auto-expand parent menus with active children
            const activeLinks = document.querySelectorAll('.sidebar-item.active, .sidebar-subitem.active');
            activeLinks.forEach(link => {
                // Find parent submenu containers
                let parent = link.closest('[id^="submenu-"]');
                while (parent) {
                    const menuId = parent.id.replace('submenu-', '');
                    const arrow = document.getElementById('arrow-' + menuId);
                    
                    parent.classList.remove('hidden');
                    if (arrow) arrow.style.transform = 'rotate(180deg)';
                    
                    // Look for parent of parent
                    parent = parent.parentElement.closest('[id^="submenu-"]');
                }
            });
        });
        
        // Dynamic menu functionality for database-driven menus with accordion behavior
        function toggleSubmenu(submenuId) {
            const submenu = document.getElementById(submenuId);
            if (!submenu) return;
            
            // Extract the menu ID from submenuId (format: 'submenu-X')
            const menuId = submenuId.replace('submenu-', '');
            const arrow = document.getElementById('arrow-' + menuId);
            
            const isCurrentlyHidden = submenu.classList.contains('hidden');
            
            // Accordion behavior - close all other submenus first
            const allSubmenus = document.querySelectorAll('[id^="submenu-"]');
            const allArrows = document.querySelectorAll('[id^="arrow-"]');
            
            allSubmenus.forEach(otherSubmenu => {
                if (otherSubmenu.id !== submenuId) {
                    otherSubmenu.classList.add('hidden');
                }
            });
            
            allArrows.forEach(otherArrow => {
                if (otherArrow.id !== 'arrow-' + menuId) {
                    otherArrow.style.transform = 'rotate(0deg)';
                }
            });
            
            // Toggle the clicked submenu
            if (isCurrentlyHidden) {
                submenu.classList.remove('hidden');
                if (arrow) arrow.style.transform = 'rotate(180deg)';
            } else {
                submenu.classList.add('hidden');
                if (arrow) arrow.style.transform = 'rotate(0deg)';
            }
        }
    </script>
    
    <!-- Common JavaScript Functions -->
    <script>
        // Modal functions
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        // Alert function
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                color: white;
                font-weight: 500;
                z-index: 9999;
                max-width: 400px;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            `;
            
            switch(type) {
                case 'success':
                    alertDiv.style.backgroundColor = '#059669';
                    break;
                case 'error':
                    alertDiv.style.backgroundColor = '#dc2626';
                    break;
                case 'warning':
                    alertDiv.style.backgroundColor = '#d97706';
                    break;
                default:
                    alertDiv.style.backgroundColor = '#2563eb';
            }
            
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => {
                    if (modal.style.display === 'flex') {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
            }
        });
    </script>
</body>
</html>