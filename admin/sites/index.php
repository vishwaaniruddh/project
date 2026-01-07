<?php
require_once __DIR__ . '/../../controllers/SitesController.php';

$controller = new SitesController();
$data = $controller->index();

$title = 'Sites Management';
ob_start();
?>

<!-- Stats Cards - Row 1 -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-3" id="statsContainer">
    <div onclick="filterByStatus('all')" class="stat-card bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl shadow-sm p-4 border border-blue-200 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-blue-600 font-medium uppercase tracking-wide">Total Sites</p>
                <p class="text-2xl font-bold text-blue-700 mt-1" id="statTotal">-</p>
            </div>
            <div class="p-3 bg-blue-500 rounded-xl shadow-sm group-hover:bg-blue-600 transition-colors">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>
    </div>
    <div onclick="filterByStatus('survey_pending')" class="stat-card bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl shadow-sm p-4 border border-yellow-200 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-yellow-600 font-medium uppercase tracking-wide">Survey Pending</p>
                <p class="text-2xl font-bold text-yellow-700 mt-1" id="statSurveyPending">-</p>
            </div>
            <div class="p-3 bg-yellow-500 rounded-xl shadow-sm group-hover:bg-yellow-600 transition-colors">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>
    </div>
    <div onclick="filterByStatus('survey_done')" class="stat-card bg-gradient-to-br from-green-50 to-green-100 rounded-xl shadow-sm p-4 border border-green-200 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-green-600 font-medium uppercase tracking-wide">Survey Done</p>
                <p class="text-2xl font-bold text-green-700 mt-1" id="statSurveyDone">-</p>
            </div>
            <div class="p-3 bg-green-500 rounded-xl shadow-sm group-hover:bg-green-600 transition-colors">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>
    </div>
    <div onclick="filterByStatus('install_pending')" class="stat-card bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl shadow-sm p-4 border border-orange-200 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-orange-600 font-medium uppercase tracking-wide">Install Pending</p>
                <p class="text-2xl font-bold text-orange-700 mt-1" id="statInstallPending">-</p>
            </div>
            <div class="p-3 bg-orange-500 rounded-xl shadow-sm group-hover:bg-orange-600 transition-colors">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards - Row 2 -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div onclick="filterByStatus('install_done')" class="stat-card bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-xl shadow-sm p-4 border border-emerald-200 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-emerald-600 font-medium uppercase tracking-wide">Install Done</p>
                <p class="text-2xl font-bold text-emerald-700 mt-1" id="statInstallDone">-</p>
            </div>
            <div class="p-3 bg-emerald-500 rounded-xl shadow-sm group-hover:bg-emerald-600 transition-colors">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>
    </div>
    <div onclick="filterByStatus('vendor_assigned')" class="stat-card bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl shadow-sm p-4 border border-indigo-200 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-indigo-600 font-medium uppercase tracking-wide">Vendor Assigned</p>
                <p class="text-2xl font-bold text-indigo-700 mt-1" id="statVendorAssigned">-</p>
            </div>
            <div class="p-3 bg-indigo-500 rounded-xl shadow-sm group-hover:bg-indigo-600 transition-colors">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 6a3 3 0 11-6 0 3 3 0 016 0zM17 6a3 3 0 11-6 0 3 3 0 016 0zM12.93 17c.046-.327.07-.66.07-1a6.97 6.97 0 00-1.5-4.33A5 5 0 0119 16v1h-6.07zM6 11a5 5 0 015 5v1H1v-1a5 5 0 015-5z"></path>
                </svg>
            </div>
        </div>
    </div>
    <div onclick="filterByStatus('delegated')" class="stat-card bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl shadow-sm p-4 border border-purple-200 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-purple-600 font-medium uppercase tracking-wide">Delegated</p>
                <p class="text-2xl font-bold text-purple-700 mt-1" id="statDelegated">-</p>
            </div>
            <div class="p-3 bg-purple-500 rounded-xl shadow-sm group-hover:bg-purple-600 transition-colors">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z"></path>
                </svg>
            </div>
        </div>
    </div>
    <div onclick="filterByStatus('material_pending')" class="stat-card bg-gradient-to-br from-red-50 to-red-100 rounded-xl shadow-sm p-4 border border-red-200 cursor-pointer hover:shadow-md hover:scale-[1.02] transition-all duration-200 group">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs text-red-600 font-medium uppercase tracking-wide">Material Pending</p>
                <p class="text-2xl font-bold text-red-700 mt-1" id="statMaterialPending">-</p>
            </div>
            <div class="p-3 bg-red-500 rounded-xl shadow-sm group-hover:bg-red-600 transition-colors">
                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Header Actions -->
<div class="mb-4">
    <div class="flex justify-between items-center gap-3">
        <p class="text-xs text-gray-500">Manage installation sites and track progress</p>
        <div class="flex items-center gap-2">
            <a href="bulk_upload.php" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                Bulk Upload
            </a>
            <a href="vendor_assign_bulk_upload.php" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM6.293 6.707a1 1 0 010-1.414l3-3a1 1 0 011.414 0l3 3a1 1 0 01-1.414 1.414L11 5.414V13a1 1 0 11-2 0V5.414L7.707 6.707a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>
                Vendor Bulk
            </a>
            <button onclick="openModal('createSiteModal')" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700 flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path>
                </svg>
                Add Site
            </button>
        </div>
    </div>
</div>

<!-- Search and Filters -->
<div class="card mb-4">
    <div class="card-body p-3">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <input type="text" id="searchInput" class="block w-full pl-8 pr-3 py-1.5 text-xs border border-gray-300 rounded leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500" placeholder="Search sites..." value="">
                </div>
            </div>
            <div>
                <select id="cityFilter" class="block w-full px-2 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Cities</option>
                </select>
            </div>
            <div>
                <select id="stateFilter" class="block w-full px-2 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All States</option>
                </select>
            </div>
            <div>
                <select id="statusFilter" class="block w-full px-2 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Status</option>
                </select>
            </div>
            <div>
                <select id="surveyStatusFilter" class="block w-full px-2 py-1.5 text-xs border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">All Survey</option>
                    <option value="pending">Pending</option>
                    <option value="submitted">Submitted</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Sites Table -->
<div class="card">
    <div class="card-body p-4">
        <div class="overflow-x-auto">
            <table class="data-table text-xs" id="sitesTable">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-xs">#</th>
                        <th class="px-2 py-2 text-xs">Actions</th>
                        <th class="px-2 py-2 text-xs">Site Details</th>
                        <th class="px-2 py-2 text-xs">Location</th>
                        <th class="px-2 py-2 text-xs">Customer/Contact</th>
                        <th class="px-2 py-2 text-xs">Vendor</th>
                        <th class="px-2 py-2 text-xs">Status</th>
                        <th class="px-2 py-2 text-xs">Progress</th>
                    </tr>
                </thead>
                <tbody id="sitesTableBody">
                    <tr>
                        <td colspan="8" class="text-center py-8 text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="w-8 h-8 text-gray-300 mb-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Loading sites...
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between border-t border-gray-200 bg-white px-3 py-2 mt-3" id="paginationContainer" style="display: none;">
            <div class="text-xs text-gray-700" id="paginationInfo"></div>
            <nav class="flex space-x-1" id="paginationNav"></nav>
        </div>
    </div>
</div>


<!-- Create Site Modal -->
<div id="createSiteModal" class="modal">
    <div class="modal-content-large">
        <div class="modal-header-fixed">
            <h3 class="modal-title">Add New Site</h3>
            <button type="button" class="modal-close" onclick="closeModal('createSiteModal')">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <form id="createSiteForm" action="create.php" method="POST">
            <div class="modal-body-scrollable">
                <div class="form-section">
                    <h4 class="form-section-title text-sm">Basic Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div class="form-group">
                            <label for="site_id" class="form-label text-xs">Site ID *</label>
                            <input type="text" id="site_id" name="site_id" class="form-input text-xs" required>
                        </div>
                        <div class="form-group">
                            <label for="store_id" class="form-label text-xs">Store ID</label>
                            <input type="text" id="store_id" name="store_id" class="form-input text-xs">
                        </div>
                        <div class="form-group md:col-span-2">
                            <label class="form-label text-xs">Location & Pincode *</label>
                            <div class="flex gap-2">
                                <input type="text" id="location" name="location" class="form-input text-xs" style="width: 70%;" placeholder="Enter location" required>
                                <input type="text" id="pincode" name="pincode" class="form-input text-xs" style="width: 30%;" placeholder="Pincode" maxlength="6">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <h4 class="form-section-title text-sm">Location Details</h4>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div class="form-group">
                            <label for="country_id" class="form-label text-xs">Country *</label>
                            <select id="country_id" name="country_id" class="form-select text-xs" required onchange="loadStatesForSite(this.value)">
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="state_id" class="form-label text-xs">State *</label>
                            <select id="state_id" name="state_id" class="form-select text-xs" required onchange="loadCitiesForSite(this.value)">
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="city_id" class="form-label text-xs">City *</label>
                            <select id="city_id" name="city_id" class="form-select text-xs" required>
                                <option value="">Select</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="zone" class="form-label text-xs">Zone</label>
                            <input type="text" id="zone" name="zone" class="form-input text-xs">
                        </div>
                        <div class="form-group">
                            <label for="branch" class="form-label text-xs">Branch</label>
                            <input type="text" id="branch" name="branch" class="form-input text-xs">
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <h4 class="form-section-title text-sm">PO & Customer</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="form-group">
                            <label for="po_number" class="form-label text-xs">PO Number</label>
                            <input type="text" id="po_number" name="po_number" class="form-input text-xs">
                        </div>
                        <div class="form-group">
                            <label for="po_date" class="form-label text-xs">PO Date</label>
                            <input type="date" id="po_date" name="po_date" class="form-input text-xs">
                        </div>
                        <div class="form-group">
                            <label for="customer_id" class="form-label text-xs">Customer</label>
                            <select id="customer_id" name="customer_id" class="form-select text-xs">
                                <option value="">Select</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-section">
                    <h4 class="form-section-title text-sm">Contact Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <div class="form-group">
                            <label for="contact_person_name" class="form-label text-xs">Contact Name</label>
                            <input type="text" name="contact_person_name" id="contact_person_name" class="form-input text-xs">
                        </div>
                        <div class="form-group">
                            <label for="contact_person_number" class="form-label text-xs">Contact Number</label>
                            <input type="tel" name="contact_person_number" id="contact_person_number" class="form-input text-xs">
                        </div>
                        <div class="form-group">
                            <label for="contact_person_email" class="form-label text-xs">Contact Email</label>
                            <input type="email" name="contact_person_email" id="contact_person_email" class="form-input text-xs">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer-fixed">
                <button type="button" onclick="closeModal('createSiteModal')" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700">Create Site</button>
            </div>
        </form>
    </div>
</div>

<!-- View Site Modal -->
<div id="viewSiteModal" class="modal">
    <div class="modal-content-large">
        <div class="modal-header-fixed">
            <h3 class="modal-title">Site Details</h3>
            <button type="button" class="modal-close" onclick="closeModal('viewSiteModal')">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <div class="modal-body-scrollable" id="viewSiteContent">
            <p class="text-center text-gray-500">Loading...</p>
        </div>
        <div class="modal-footer-fixed">
            <button type="button" onclick="closeModal('viewSiteModal')" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Close</button>
        </div>
    </div>
</div>

<!-- Edit Site Modal -->
<div id="editSiteModal" class="modal">
    <div class="modal-content-large">
        <div class="modal-header-fixed">
            <h3 class="modal-title">Edit Site</h3>
            <button type="button" class="modal-close" onclick="closeModal('editSiteModal')">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <form id="editSiteForm" method="POST">
            <div class="modal-body-scrollable" id="editSiteContent">
                <p class="text-center text-gray-500">Loading...</p>
            </div>
            <div class="modal-footer-fixed">
                <button type="button" onclick="closeModal('editSiteModal')" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700">Update Site</button>
            </div>
        </form>
    </div>
</div>

<!-- Delegate Site Modal -->
<div id="delegateSiteModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title text-sm">Delegate Site</h3>
            <button type="button" class="modal-close" onclick="closeModal('delegateSiteModal')">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
        <form id="delegateSiteForm" method="POST">
            <input type="hidden" id="delegate_site_id" name="site_id">
            <div class="modal-body">
                <div class="form-group">
                    <label for="delegate_vendor_id" class="form-label text-xs">Select Vendor *</label>
                    <select id="delegate_vendor_id" name="vendor_id" class="form-select text-xs" required>
                        <option value="">Choose Vendor</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeModal('delegateSiteModal')" class="px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50">Cancel</button>
                <button type="submit" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded hover:bg-blue-700">Delegate</button>
            </div>
        </form>
    </div>
</div>


<script>
    // Global variables
    let currentPage = 1;
    let currentFilters = {};
    let debounceTimer = null;
    let activeStatFilter = null;

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadStats();
        loadSites();
        setupEventListeners();
    });

    // Filter by clicking stat cards
    function filterByStatus(status) {
        // Remove active state from all cards
        document.querySelectorAll('.stat-card').forEach(card => {
            card.classList.remove('ring-2', 'ring-offset-2');
        });
        
        // Reset filters first
        document.getElementById('searchInput').value = '';
        document.getElementById('cityFilter').value = '';
        document.getElementById('stateFilter').value = '';
        document.getElementById('statusFilter').value = '';
        document.getElementById('surveyStatusFilter').value = '';
        
        if (status === 'all' || status === activeStatFilter) {
            activeStatFilter = null;
            loadSites(1);
            return;
        }
        
        activeStatFilter = status;
        
        // Add active state to clicked card
        event.currentTarget.classList.add('ring-2', 'ring-offset-2');
        
        // Set appropriate filter based on status
        switch(status) {
            case 'survey_pending':
                document.getElementById('surveyStatusFilter').value = 'pending';
                break;
            case 'survey_done':
                document.getElementById('surveyStatusFilter').value = 'approved';
                break;
            case 'install_pending':
                document.getElementById('statusFilter').value = 'installation_pending';
                break;
            case 'install_done':
                document.getElementById('statusFilter').value = 'installation_done';
                break;
            case 'vendor_assigned':
                document.getElementById('statusFilter').value = 'vendor_assigned';
                break;
            case 'delegated':
                document.getElementById('statusFilter').value = 'delegated';
                break;
            case 'material_pending':
                document.getElementById('statusFilter').value = 'material_pending';
                break;
        }
        
        loadSites(1);
    }

    // Load statistics
    function loadStats() {
        fetch('../../api/sites.php?action=stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('statTotal').textContent = data.data.total_sites;
                    document.getElementById('statSurveyPending').textContent = data.data.survey_pending;
                    document.getElementById('statSurveyDone').textContent = data.data.survey_completed;
                    document.getElementById('statInstallPending').textContent = data.data.installation_pending;
                    document.getElementById('statInstallDone').textContent = data.data.installation_done;
                    document.getElementById('statVendorAssigned').textContent = data.data.vendor_assigned;
                    document.getElementById('statDelegated').textContent = data.data.delegated_sites;
                    document.getElementById('statMaterialPending').textContent = data.data.material_pending;
                }
            })
            .catch(error => console.error('Error loading stats:', error));
    }

    // Load sites with filters
    function loadSites(page = 1) {
        currentPage = page;
        const params = new URLSearchParams();
        params.append('action', 'list');
        params.append('page', page);
        
        const search = document.getElementById('searchInput').value;
        if (search) params.append('search', search);
        
        const city = document.getElementById('cityFilter').value;
        if (city) params.append('city', city);
        
        const state = document.getElementById('stateFilter').value;
        if (state) params.append('state', state);
        
        const status = document.getElementById('statusFilter').value;
        if (status) params.append('activity_status', status);
        
        const surveyStatus = document.getElementById('surveyStatusFilter').value;
        if (surveyStatus) params.append('survey_status', surveyStatus);

        document.getElementById('sitesTableBody').innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-8 text-gray-500">
                    <div class="flex flex-col items-center">
                        <svg class="w-8 h-8 text-gray-300 mb-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading...
                    </div>
                </td>
            </tr>`;

        fetch('../../api/sites.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderSites(data.data.sites, data.data.pagination);
                    populateFilters(data.data.filter_options);
                }
            })
            .catch(error => {
                console.error('Error loading sites:', error);
                document.getElementById('sitesTableBody').innerHTML = `
                    <tr><td colspan="8" class="text-center py-8 text-red-500">Error loading sites</td></tr>`;
            });
    }

    // Render sites table
    function renderSites(sites, pagination) {
        const tbody = document.getElementById('sitesTableBody');
        
        if (!sites || sites.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center py-8 text-gray-500">No sites found</td></tr>`;
            document.getElementById('paginationContainer').style.display = 'none';
            return;
        }

        let html = '';
        let serialNo = ((pagination.current_page - 1) * pagination.limit) + 1;

        sites.forEach(site => {
            const surveyBadge = site.has_survey_submitted 
                ? (site.actual_survey_status === 'approved' 
                    ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Approved</span>'
                    : site.actual_survey_status === 'rejected'
                        ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Rejected</span>'
                        : '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Submitted</span>')
                : '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>';

            const installBadge = site.installation_status 
                ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Done</span>'
                : '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Pending</span>';

            html += `
                <tr>
                    <td class="px-2 py-2 text-xs text-gray-500 font-medium">${serialNo++}</td>
                    <td class="px-2 py-2">
                        <div class="flex items-center space-x-1 flex-wrap gap-1">
                            <button onclick="viewSite(${site.id})" class="p-1 text-blue-600 hover:bg-blue-50 rounded" title="View">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10 12a2 2 0 100-4 2 2 0 000 4z"></path><path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"></path></svg>
                            </button>
                            <button onclick="editSite(${site.id})" class="p-1 text-green-600 hover:bg-green-50 rounded" title="Edit">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg>
                            </button>
                            ${!site.has_survey_submitted ? `
                                <button onclick="delegateSite(${site.id})" class="p-1 text-indigo-600 hover:bg-indigo-50 rounded" title="Delegate">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"></path></svg>
                                </button>
                            ` : ''}
                            <button onclick="deleteSite(${site.id})" class="p-1 text-red-600 hover:bg-red-50 rounded" title="Delete">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                            </button>
                            ${!site.has_survey_submitted ? `<button onclick="conductSurvey(${site.id})" class="px-2 py-0.5 text-xs font-medium rounded text-white bg-green-500 hover:bg-green-600">Survey</button>` : ''}
                            ${site.actual_survey_status === 'approved' && site.survey_id ? `<button onclick="viewSurvey(${site.survey_id})" class="px-2 py-0.5 text-xs font-medium rounded text-white bg-purple-500 hover:bg-purple-600" title="View Survey">View Survey</button>` : ''}
                        </div>
                    </td>
                    <td class="px-2 py-2">
                        <div class="text-xs font-medium text-gray-900">${site.site_id || ''}</div>
                        ${site.store_id ? `<div class="text-xs text-gray-500">Store: ${site.store_id}</div>` : ''}
                        ${site.po_number ? `<div class="text-xs text-gray-500">PO: ${site.po_number}</div>` : ''}
                    </td>
                    <td class="px-2 py-2">
                        <div class="text-xs text-gray-900">${site.city || ''}, ${site.state || ''}</div>
                        <div class="text-xs text-gray-500">${site.country || ''}</div>
                        ${site.pincode ? `<div class="text-xs text-gray-500">PIN: ${site.pincode}</div>` : ''}
                    </td>
                    <td class="px-2 py-2">
                        ${site.customer ? `<div class="text-xs text-gray-900">${site.customer}</div>` : ''}
                        ${site.contact_person_name ? `<div class="text-xs text-gray-600">${site.contact_person_name}</div>` : ''}
                        ${site.contact_person_number ? `<div class="text-xs text-gray-500">📞 ${site.contact_person_number}</div>` : ''}
                    </td>
                    <td class="px-2 py-2">
                        ${site.vendor ? `<div class="text-xs text-gray-900">${site.vendor}</div>` : '<span class="text-xs text-gray-400">No vendor</span>'}
                        ${site.delegation_status === 'active' && site.delegated_vendor_name ? `
                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">Delegated</span>
                            <div class="text-xs text-orange-600">${site.delegated_vendor_name}</div>
                        ` : ''}
                    </td>
                    <td class="px-2 py-2">
                        ${site.activity_status 
                            ? `<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">${site.activity_status}</span>`
                            : '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">No Status</span>'}
                    </td>
                    <td class="px-2 py-2">
                        <div class="space-y-0.5">
                            <div class="flex items-center"><span class="text-xs text-gray-500 w-10">Survey:</span>${surveyBadge}</div>
                            <div class="flex items-center"><span class="text-xs text-gray-500 w-10">Install:</span>${installBadge}</div>
                        </div>
                    </td>
                </tr>`;
        });

        tbody.innerHTML = html;
        renderPagination(pagination);
    }

    // Render pagination
    function renderPagination(pagination) {
        const container = document.getElementById('paginationContainer');
        const info = document.getElementById('paginationInfo');
        const nav = document.getElementById('paginationNav');

        if (pagination.total_pages <= 1) {
            container.style.display = 'none';
            return;
        }

        container.style.display = 'flex';
        
        const start = ((pagination.current_page - 1) * pagination.limit) + 1;
        const end = Math.min(pagination.current_page * pagination.limit, pagination.total_records);
        info.textContent = `Showing ${start} to ${end} of ${pagination.total_records} results`;

        let navHtml = '';
        for (let i = 1; i <= pagination.total_pages; i++) {
            const activeClass = i === pagination.current_page 
                ? 'bg-blue-600 text-white' 
                : 'text-gray-600 bg-white border border-gray-300 hover:bg-gray-50';
            navHtml += `<button onclick="loadSites(${i})" class="px-2.5 py-1 text-xs font-medium rounded ${activeClass}">${i}</button>`;
        }
        nav.innerHTML = navHtml;
    }

    // Populate filter dropdowns
    function populateFilters(options) {
        if (options.cities) {
            const citySelect = document.getElementById('cityFilter');
            const currentCity = citySelect.value;
            citySelect.innerHTML = '<option value="">All Cities</option>';
            options.cities.forEach(city => {
                if (city) citySelect.innerHTML += `<option value="${city}" ${city === currentCity ? 'selected' : ''}>${city}</option>`;
            });
        }
        if (options.states) {
            const stateSelect = document.getElementById('stateFilter');
            const currentState = stateSelect.value;
            stateSelect.innerHTML = '<option value="">All States</option>';
            options.states.forEach(state => {
                if (state) stateSelect.innerHTML += `<option value="${state}" ${state === currentState ? 'selected' : ''}>${state}</option>`;
            });
        }
        if (options.activity_statuses) {
            const statusSelect = document.getElementById('statusFilter');
            const currentStatus = statusSelect.value;
            statusSelect.innerHTML = '<option value="">All Status</option>';
            options.activity_statuses.forEach(status => {
                if (status) statusSelect.innerHTML += `<option value="${status}" ${status === currentStatus ? 'selected' : ''}>${status}</option>`;
            });
        }
    }

    // Setup event listeners
    function setupEventListeners() {
        document.getElementById('searchInput').addEventListener('keyup', function() {
            if (debounceTimer) clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => loadSites(1), 500);
        });

        ['cityFilter', 'stateFilter', 'statusFilter', 'surveyStatusFilter'].forEach(id => {
            document.getElementById(id).addEventListener('change', () => loadSites(1));
        });
    }

    // Site actions
    function viewSite(id) {
        fetch(`view.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const site = data.site;
                    document.getElementById('viewSiteContent').innerHTML = `
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div><label class="font-medium text-gray-700">Site ID</label><p class="bg-gray-50 p-2 rounded">${site.site_id || '-'}</p></div>
                            <div><label class="font-medium text-gray-700">Store ID</label><p class="bg-gray-50 p-2 rounded">${site.store_id || '-'}</p></div>
                            <div class="col-span-2"><label class="font-medium text-gray-700">Location</label><p class="bg-gray-50 p-2 rounded">${site.location || '-'}</p></div>
                            <div><label class="font-medium text-gray-700">City</label><p class="bg-gray-50 p-2 rounded">${site.city || '-'}</p></div>
                            <div><label class="font-medium text-gray-700">State</label><p class="bg-gray-50 p-2 rounded">${site.state || '-'}</p></div>
                            <div><label class="font-medium text-gray-700">Country</label><p class="bg-gray-50 p-2 rounded">${site.country || '-'}</p></div>
                            <div><label class="font-medium text-gray-700">Pincode</label><p class="bg-gray-50 p-2 rounded">${site.pincode || '-'}</p></div>
                            <div><label class="font-medium text-gray-700">Customer</label><p class="bg-gray-50 p-2 rounded">${site.customer || '-'}</p></div>
                            <div><label class="font-medium text-gray-700">Vendor</label><p class="bg-gray-50 p-2 rounded">${site.vendor || '-'}</p></div>
                            <div><label class="font-medium text-gray-700">Activity Status</label><p class="bg-gray-50 p-2 rounded">${site.activity_status || '-'}</p></div>
                            <div><label class="font-medium text-gray-700">Survey Status</label><p class="bg-gray-50 p-2 rounded">${site.survey_status ? 'Completed' : 'Pending'}</p></div>
                        </div>`;
                    openModal('viewSiteModal');
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => showAlert('Failed to load site', 'error'));
    }

    function editSite(id) {
        fetch(`edit.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirect to edit page or populate modal
                    window.location.href = `edit.php?id=${id}`;
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => showAlert('Failed to load site', 'error'));
    }

    function deleteSite(id) {
        if (confirm('Are you sure you want to delete this site?')) {
            fetch(`delete.php?id=${id}`, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(data.message, 'success');
                        loadSites(currentPage);
                        loadStats();
                    } else {
                        showAlert(data.message, 'error');
                    }
                })
                .catch(error => showAlert('Failed to delete site', 'error'));
        }
    }

    function delegateSite(id) {
        document.getElementById('delegate_site_id').value = id;
        loadVendorsForDelegate();
        openModal('delegateSiteModal');
    }

    function loadVendorsForDelegate() {
        fetch('../../api/masters.php?path=vendors&status=active&limit=1000')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.records) {
                    const select = document.getElementById('delegate_vendor_id');
                    select.innerHTML = '<option value="">Choose Vendor</option>';
                    data.data.records.forEach(vendor => {
                        select.innerHTML += `<option value="${vendor.id}">${vendor.name}</option>`;
                    });
                }
            })
            .catch(error => console.error('Error loading vendors:', error));
    }

    function conductSurvey(id) {
        window.location.href = `../site-survey/create.php?site_id=${id}`;
    }

    function conductMaterials(siteId, surveyId) {
        window.location.href = `../material-request.php?site_id=${siteId}&survey_id=${surveyId}`;
    }

    function viewSiteSurvey(surveyId) {
        window.location.href = `../site-survey/view.php?id=${surveyId}`;
    }

    function viewSurvey(surveyId) {
        window.location.href = `../surveys/view-survey.php?id=${surveyId}`;
    }

    // Alert function
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `fixed top-4 right-4 z-50 p-3 rounded-md shadow-lg text-sm ${
            type === 'success' ? 'bg-green-100 border border-green-400 text-green-700' :
            type === 'error' ? 'bg-red-100 border border-red-400 text-red-700' :
            'bg-blue-100 border border-blue-400 text-blue-700'
        }`;
        alertDiv.textContent = message;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 3000);
    }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/admin_layout.php';
?>
