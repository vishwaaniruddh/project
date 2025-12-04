# Implementation Plan

- [x] 1. Create database schema and model layer for courier management

  - Create couriers table with all required fields and indexes
  - Implement Courier model extending BaseMaster with CRUD operations
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [x] 1.1 Create couriers table migration


  - Write SQL migration file database/create_couriers_table.sql
  - Create couriers table with id, name, status, created_at, created_by, updated_at, updated_by fields
  - Add unique constraint on name field
  - Add foreign key constraints for created_by and updated_by referencing users table
  - Add indexes on status and name columns for query optimization

  - _Requirements: 1.1, 1.2, 1.3_



- [ ] 1.2 Implement Courier model class
  - Create models/Courier.php extending BaseMaster
  - Set protected $table = 'couriers'
  - Inherit getAllWithPagination, findByName, getActive, toggleStatus from BaseMaster
  - Override validateMasterData if additional validation needed beyond name uniqueness
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 2.1, 2.4, 3.1, 3.2, 4.1_

- [x] 2. Create admin interface for courier management



  - Build CRUD interface following existing master pattern (banks, cities, states)
  - Implement API endpoints for AJAX operations
  - Add search and filtering functionality
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4, 3.5_


- [ ] 2.1 Create CouriersController
  - Create controllers/CouriersController.php extending BaseMasterController
  - Implement index, create, update, show, delete, toggleStatus methods
  - Use Courier model for all database operations
  - Return JSON responses for AJAX calls
  - _Requirements: 2.1, 2.2, 3.1, 3.2, 4.1_

- [x] 2.2 Create courier CRUD API endpoints

  - Create admin/masters/couriers/view.php for fetching single courier
  - Create admin/masters/couriers/create.php for creating new courier
  - Create admin/masters/couriers/edit.php for updating courier
  - Create admin/masters/couriers/delete.php for deleting courier
  - Create admin/masters/couriers/toggle_status.php for status changes
  - All endpoints should use CouriersController and return JSON
  - _Requirements: 1.1, 1.2, 3.1, 3.2, 4.1_



- [ ] 2.3 Create main courier management page
  - Create admin/masters/couriers/index.php as main listing page
  - Use admin_layout.php for consistent UI
  - Display data table with columns: ID, Courier Name, Status, Created Date, Actions
  - Add "Add Courier" button in page header
  - Implement search bar for filtering by name
  - Add status filter dropdown (All, Active, Inactive)
  - Include modal form for add/edit operations
  - Add inline status toggle buttons with color-coded badges
  - Implement AJAX for all operations (create, update, delete, toggle status)
  - Add authentication check to ensure only admin users can access
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 3.1, 3.2, 3.3, 3.4, 3.5, 4.1, 4.2, 4.3, 4.4, 5.4_

- [ ] 3. Integrate courier menu into admin navigation
  - Add courier menu entry to database menu system under Location section
  - Assign permissions to admin role
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 3.1 Add courier menu entry and permissions
  - Create database/add_courier_menu.sql migration file
  - Insert courier menu item into menu_items table with parent_id=11 (Location section)
  - Set title as "Couriers", icon as "courier", url as "/admin/masters/couriers/"
  - Set sort_order=5 to place after Cities (which has sort_order=4)
  - Insert role permission for admin role in role_menu_permissions table
  - _Requirements: 5.1, 5.2, 5.3, 5.4_

- [x] 4. Add courier icon to dynamic sidebar


  - Update includes/dynamic_sidebar.php to include courier icon SVG
  - _Requirements: 5.1, 5.2_

- [x] 4.1 Add courier icon to renderMenuIcon function


  - Add 'courier' key to $icons array in includes/dynamic_sidebar.php
  - Use truck/delivery SVG path: '<path fill-rule="evenodd" d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" clip-rule="evenodd"></path><path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0115.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"></path>'
  - Verify icon renders correctly in navigation menu
  - _Requirements: 5.1, 5.2_
