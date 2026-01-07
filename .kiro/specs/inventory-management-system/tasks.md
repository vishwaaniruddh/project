# Implementation Plan

- [x] 1. Create database schema with sar_inv_ prefix tables




  - [x] 1.1 Create sar_inv_warehouses table for warehouse management

    - Define columns: id, name, code, location, address, capacity, status, company_id, created_at, updated_at
    - Add proper indexes for company_id, status, and code
    - _Requirements: 1.1, 1.2_


  - [x] 1.2 Create sar_inv_product_categories table for product categorization
    - Define columns: id, name, description, parent_id, level, status, company_id, created_at, updated_at
    - Add self-referencing foreign key for hierarchical categories
    - _Requirements: 2.1, 2.5_


  - [x] 1.3 Create sar_inv_products table for product management
    - Define columns: id, name, sku, category_id, description, specifications, unit_of_measure, minimum_stock_level, status, company_id, created_at, updated_at
    - Add foreign key to sar_inv_product_categories
    - Add indexes for sku, category_id, company_id

    - _Requirements: 2.2, 2.3, 2.4_

  - [x] 1.4 Create sar_inv_stock table for stock tracking
    - Define columns: id, product_id, warehouse_id, quantity, reserved_quantity, version, last_updated
    - Add unique constraint on product_id + warehouse_id combination
    - Add foreign keys to products and warehouses tables

    - Include version field for optimistic locking
    - _Requirements: 3.1, 3.2, 11.2_

  - [x] 1.5 Create sar_inv_stock_entries table for stock entry records

    - Define columns: id, product_id, warehouse_id, quantity, entry_type (in/out), reference_type, reference_id, notes, created_by, created_at
    - Add indexes for product_id, warehouse_id, entry_type, created_at
    - _Requirements: 3.3, 3.4_

  - [x] 1.6 Create sar_inv_dispatches table for dispatch management
    - Define columns: id, dispatch_number, source_warehouse_id, destination_type, destination_id, destination_address, status, dispatch_date, received_date, notes, created_by, created_at, updated_at
    - Add foreign key to warehouses table
    - Add indexes for dispatch_number, status, source_warehouse_id
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 1.7 Create sar_inv_dispatch_items table for dispatch line items

    - Define columns: id, dispatch_id, product_id, quantity, received_quantity, status, notes
    - Add foreign keys to dispatches and products tables
    - _Requirements: 4.1, 4.2_


  - [x] 1.8 Create sar_inv_transfers table for inter-warehouse transfers
    - Define columns: id, transfer_number, source_warehouse_id, destination_warehouse_id, status, transfer_date, received_date, notes, created_by, approved_by, created_at, updated_at
    - Add foreign keys to warehouses table for both source and destination
    - Add indexes for transfer_number, status
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_


  - [x] 1.9 Create sar_inv_transfer_items table for transfer line items
    - Define columns: id, transfer_id, product_id, quantity, received_quantity, status, notes
    - Add foreign keys to transfers and products tables

    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 1.10 Create sar_inv_assets table for individual asset tracking
    - Define columns: id, product_id, serial_number, barcode, status, current_location_type, current_location_id, purchase_date, warranty_expiry, company_id, created_at, updated_at
    - Add unique constraints on serial_number and barcode

    - Add indexes for status, serial_number, barcode
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [x] 1.11 Create sar_inv_asset_history table for asset movement tracking
    - Define columns: id, asset_id, action_type, from_location_type, from_location_id, to_location_type, to_location_id, notes, created_by, created_at

    - Add foreign key to assets table
    - Add indexes for asset_id, action_type, created_at
    - _Requirements: 6.2, 6.3_

  - [x] 1.12 Create sar_inv_item_history table for comprehensive item history
    - Define columns: id, product_id, warehouse_id, transaction_type, quantity, reference_type, reference_id, balance_after, notes, created_by, created_at
    - Add indexes for product_id, warehouse_id, transaction_type, created_at
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_

  - [x] 1.13 Create sar_inv_repairs table for repair management

    - Define columns: id, repair_number, asset_id, status, issue_description, diagnosis, repair_notes, cost, vendor_id, start_date, completion_date, created_by, created_at, updated_at
    - Add foreign key to assets table
    - Add indexes for repair_number, asset_id, status
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_


  - [x] 1.14 Create sar_inv_material_masters table for material templates
    - Define columns: id, name, code, description, specifications, unit_of_measure, default_quantity, status, company_id, created_at, updated_at
    - Add unique constraint on code
    - Add indexes for code, status, company_id
    - _Requirements: 9.1_


  - [x] 1.15 Create sar_inv_material_requests table for material requests
    - Define columns: id, request_number, material_master_id, product_id, quantity, status, requester_id, approver_id, fulfilled_quantity, notes, created_at, updated_at
    - Add foreign keys to material_masters and products tables
    - Add indexes for request_number, status, requester_id

    - _Requirements: 9.2, 9.3, 9.4, 9.5_

  - [x] 1.16 Create sar_inv_audit_log table for audit trail
    - Define columns: id, table_name, record_id, action, old_values, new_values, user_id, ip_address, created_at

    - Add indexes for table_name, record_id, user_id, created_at
    - _Requirements: 1.5, 12.2, 12.3_

  - [x] 1.17 Create database setup script for all sar_inv_ tables

    - Create database/setup_sar_inventory_system.php script
    - Create database/create_sar_inventory_tables.sql with all table definitions
    - Include proper error handling and rollback support
    - _Requirements: 11.1, 11.2_

- [x] 2. Create new menu system for Inventory New






  - [x] 2.1 Create menu insertion script with new unique menu codes

    - Create database/add_sar_inventory_menu.php script
    - Insert parent menu "Inventory New" with new unique ID (starting from 100+)
    - Insert child menus: Dashboard, Warehouses, Product Category, Products, Stock Entry, Dispatches, Transfers, Assets, Item History, Repairs
    - Use new icon codes and URLs under /admin/sar-inventory/ path
    - Do NOT modify or replace existing menu items
    - _Requirements: 10.1, 12.1_


  - [x] 2.2 Set up role permissions for new inventory menus

    - Add admin role permissions for all new menu items
    - Add vendor role permissions for applicable menu items
    - Use INSERT statements only, no UPDATE on existing permissions
    - _Requirements: 12.1, 12.2_




- [x] 3. Create PHP Models for new inventory system





  - [x] 3.1 Create SarInvBaseModel class


    - Extend BaseModel class
    - Add company isolation support
    - Implement audit logging method logChange()
    - Add optimistic locking support with version field
    - _Requirements: 11.1, 12.1, 12.2_


  - [x] 3.2 Create SarInvWarehouse model

    - Extend SarInvBaseModel class
    - Implement CRUD operations for sar_inv_warehouses table
    - Add validation methods and capacity utilization calculation
    - Add method to check if warehouse has inventory before deletion
    - _Requirements: 1.1, 1.2, 1.3, 1.4_


  - [x] 3.3 Create SarInvProductCategory model

    - Extend SarInvBaseModel class
    - Implement hierarchical category operations
    - Add methods for getChildren(), getParent(), getFullPath()
    - Add method to check if category has products before deletion
    - _Requirements: 2.1, 2.5_


  - [x] 3.4 Create SarInvProduct model

    - Extend SarInvBaseModel class
    - Implement CRUD operations with category relationship
    - Add search and filter methods by name, SKU, category
    - Add JSON specifications field handling
    - _Requirements: 2.2, 2.3, 2.4_


  - [x] 3.5 Create SarInvStock model

    - Extend SarInvBaseModel class
    - Implement stock level tracking with optimistic locking
    - Add methods for getAvailableQuantity(), reserve(), release()
    - Add stock entry logging to sar_inv_stock_entries
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 11.2_


  - [x] 3.6 Create SarInvDispatch model

    - Extend SarInvBaseModel class
    - Implement dispatch creation with stock validation
    - Add status tracking (pending, shipped, delivered, cancelled)
    - Add item management methods for dispatch_items
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_


  - [x] 3.7 Create SarInvTransfer model

    - Extend SarInvBaseModel class
    - Implement transfer workflow (create, approve, receive, cancel)
    - Add stock conservation validation
    - Add item management methods for transfer_items
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_


  - [x] 3.8 Create SarInvAsset model

    - Extend SarInvBaseModel class
    - Implement asset registration with unique identifiers
    - Add location tracking and history management
    - Add status management (available, dispatched, in_repair, retired)
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_


  - [x] 3.9 Create SarInvItemHistory model

    - Extend SarInvBaseModel class
    - Implement history querying with filters (date, type, product, warehouse)
    - Add pagination support for large datasets
    - Add export methods for CSV/Excel
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_


  - [x] 3.10 Create SarInvRepair model

    - Extend SarInvBaseModel class
    - Implement repair workflow management
    - Add cost tracking and status updates
    - Link to asset and update asset status during repair
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_


  - [x] 3.11 Create SarInvMaterialMaster model

    - Extend SarInvBaseModel class
    - Implement material template management
    - Add specification storage and retrieval
    - _Requirements: 9.1_


  - [x] 3.12 Create SarInvMaterialRequest model


    - Extend SarInvBaseModel class
    - Implement request workflow (pending, approved, fulfilled, rejected)
    - Add validation against material masters and stock levels
    - Add fulfillment tracking
    - _Requirements: 9.2, 9.3, 9.4, 9.5_


  - [x] 3.13 Create SarInvAuditLog model

    - Extend SarInvBaseModel class
    - Implement audit log creation and retrieval
    - Add search and filtering functionality
    - _Requirements: 12.2, 12.3_

- [x] 4. Create service layer for business logic





  - [x] 4.1 Create SarInvWarehouseService


    - Implement business logic for warehouse operations
    - Add validation for warehouse deletion with inventory check
    - Include audit logging for warehouse changes
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_


  - [x] 4.2 Create SarInvProductService

    - Implement business logic for product and category management
    - Add validation for category deletion with products check
    - Include audit logging for product changes
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

  - [x] 4.3 Create SarInvStockService


    - Implement stock entry, adjustment, and reservation logic
    - Add stock validation before operations
    - Include automatic item history logging
    - Handle concurrent operations with optimistic locking
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 11.2_


  - [x] 4.4 Create SarInvDispatchService

    - Implement dispatch creation with stock validation
    - Add automatic stock reduction on dispatch
    - Include status tracking and notifications
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 4.5 Create SarInvTransferService


    - Implement transfer workflow with approval process
    - Add stock reservation on approval, movement on receipt
    - Ensure stock conservation across transfers
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 4.6 Create SarInvAssetService


    - Implement asset registration and tracking
    - Add location update with history logging
    - Include barcode/serial number validation
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_

  - [x] 4.7 Create SarInvRepairService


    - Implement repair workflow management
    - Add asset status updates during repair lifecycle
    - Include cost tracking and completion workflows
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_


  - [x] 4.8 Create SarInvMaterialService

    - Implement material request workflow
    - Add validation against material masters and stock
    - Include fulfillment and inventory integration
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_


  - [x] 4.9 Create SarInvDashboardService

    - Implement real-time inventory metrics calculation
    - Add KPI calculation (stock levels, pending dispatches, transfers)
    - Include dashboard data caching for performance
    - _Requirements: 10.1, 10.2_


  - [x] 4.10 Create SarInvReportingService

    - Implement customizable report generation
    - Add various output formats (PDF, CSV, Excel)
    - Include report filtering and date range support
    - _Requirements: 10.3_


  - [x] 4.11 Create SarInvAlertService

    - Implement inventory alerts (low stock, overdue items)
    - Add configurable alert thresholds
    - Include notification delivery
    - _Requirements: 10.5_


- [-] 5. Create admin interface pages under /admin/sar-inventory/







  - [x] 5.1 Create Dashboard page (admin/sar-inventory/index.php)


    - Display real-time inventory metrics and KPIs
    - Show stock levels, recent dispatches, pending transfers
    - Include quick action buttons for common operations
    - Add charts for inventory trends
    - _Requirements: 10.1, 10.2_


  - [x] 5.2 Create Warehouses management pages

    - Create admin/sar-inventory/warehouses/index.php for listing
    - Create admin/sar-inventory/warehouses/create.php for adding
    - Create admin/sar-inventory/warehouses/edit.php for editing
    - Create admin/sar-inventory/warehouses/delete.php for deletion with validation
    - Create admin/sar-inventory/warehouses/view.php for capacity visualization
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_


  - [x] 5.3 Create Product Category management pages

    - Create admin/sar-inventory/product-category/index.php for listing with hierarchy
    - Create admin/sar-inventory/product-category/create.php for adding
    - Create admin/sar-inventory/product-category/edit.php for editing
    - Create admin/sar-inventory/product-category/delete.php for deletion with validation
    - _Requirements: 2.1, 2.5_


  - [x] 5.4 Create Products management pages







    - Create admin/sar-inventory/products/index.php for listing with search/filter
    - Create admin/sar-inventory/products/create.php for adding
    - Create admin/sar-inventory/products/edit.php for editing
    - Create admin/sar-inventory/products/view.php for detailed view with stock info
    - _Requirements: 2.2, 2.3, 2.4_


  - [x] 5.5 Create Stock Entry management pages


    - Create admin/sar-inventory/stock-entry/index.php for listing entries
    - Create admin/sar-inventory/stock-entry/create.php for new stock entry
    - Include product/warehouse selection and quantity input
    - Add asset registration option for trackable items
    - Add stock adjustment functionality
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5_

  - [x] 5.6 Create Dispatches management pages





    - Create admin/sar-inventory/dispatches/index.php for listing
    - Create admin/sar-inventory/dispatches/create.php for new dispatch
    - Create admin/sar-inventory/dispatches/view.php for dispatch details
    - Create admin/sar-inventory/dispatches/update-status.php for status updates
    - Add shipping label generation
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

  - [x] 5.7 Create Transfers management pages



    - Create admin/sar-inventory/transfers/index.php for listing
    - Create admin/sar-inventory/transfers/create.php for new transfer
    - Create admin/sar-inventory/transfers/view.php for transfer details
    - Create admin/sar-inventory/transfers/approve.php for approval workflow
    - Create admin/sar-inventory/transfers/receive.php for receiving transfers
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

  - [x] 5.8 Create Assets management pages





    - Create admin/sar-inventory/assets/index.php for listing with search
    - Create admin/sar-inventory/assets/create.php for registration
    - Create admin/sar-inventory/assets/view.php for asset details and history
    - Create admin/sar-inventory/assets/update-location.php for location updates
    - Add barcode scanning support
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5_


  - [x] 5.9 Create Item History pages



    - Create admin/sar-inventory/item-history/index.php for history listing
    - Include filters for date range, transaction type, product, warehouse
    - Add pagination for large datasets
    - Add export functionality (CSV, Excel)
    - _Requirements: 7.1, 7.2, 7.3, 7.4, 7.5_


  - [x] 5.10 Create Repairs management pages






    - Create admin/sar-inventory/repairs/index.php for listing
    - Create admin/sar-inventory/repairs/create.php for new repair
    - Create admin/sar-inventory/repairs/view.php for repair details
    - Create admin/sar-inventory/repairs/update.php for status and cost updates
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.5_


  - [x] 5.11 Create Material Management pages



    - Create admin/sar-inventory/materials/index.php for material masters listing
    - Create admin/sar-inventory/materials/create.php for new material master
    - Create admin/sar-inventory/materials/requests/index.php for requests listing
    - Create admin/sar-inventory/materials/requests/create.php for new request

    - Create admin/sar-inventory/materials/requests/approve.php for approval workflow
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_

  - [x] 5.12 Create Reporting and Audit pages



    - Create admin/sar-inventory/reports/index.php for report generation
    - Create admin/sar-inventory/audit-log/index.php for audit trail viewing
    - Add report filtering and export functionality
    - Add audit log search and filtering
    - _Requirements: 7.3, 7.5, 10.3, 12.3_

- [x] 6. Implement API endpoints for inventory operations







  - [x] 6.1 Create SarInvApiController base class

    - Implement base controller with common functionality
    - Add API response formatting and error handling
    - Include authentication and permission validation
    - _Requirements: 12.1_


  - [x] 6.2 Implement warehouse API endpoints

    - Create api/sar-inventory/warehouses.php for CRUD operations
    - Add warehouse listing and search functionality
    - Include warehouse capacity and utilization APIs
    - _Requirements: 1.1, 1.2, 1.3, 1.4_


  - [x] 6.3 Implement product and category API endpoints

    - Create api/sar-inventory/products.php for product management
    - Create api/sar-inventory/categories.php for category management
    - Add product search and filtering APIs
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_


  - [x] 6.4 Implement stock and asset API endpoints

    - Create api/sar-inventory/stock.php for stock operations
    - Create api/sar-inventory/assets.php for asset management
    - Include stock level queries and history APIs
    - _Requirements: 3.1, 3.2, 3.3, 6.1, 6.2, 6.3, 6.4_


  - [x] 6.5 Implement dispatch and transfer API endpoints

    - Create api/sar-inventory/dispatches.php for dispatch management
    - Create api/sar-inventory/transfers.php for transfer management
    - Include status updates and tracking APIs
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 5.1, 5.2, 5.3_

  - [x] 6.6 Implement remaining API endpoints


    - Create api/sar-inventory/repairs.php for repair management
    - Create api/sar-inventory/materials.php for material management
    - Create api/sar-inventory/dashboard.php for dashboard data
    - Create api/sar-inventory/reports.php for report generation
    - _Requirements: 7.1, 7.2, 8.1, 8.2, 9.1, 9.2, 10.1_

- [x] 7. Implement security and permission system






  - [x] 7.1 Create SarInvPermissionMiddleware

    - Implement inventory-specific permission validation
    - Add role-based access control for inventory modules
    - Include company isolation enforcement
    - _Requirements: 12.1_


  - [x] 7.2 Implement session and security management

    - Add secure session handling for inventory operations
    - Implement session timeout policies
    - Add suspicious activity detection and logging
    - _Requirements: 12.4, 12.5_

- [x] 8. Write unit tests for core inventory operations





  - [x] 8.1 Create tests for warehouse CRUD operations


    - Test create, read, update, delete operations
    - Test deletion protection when inventory exists
    - Test company isolation
    - _Requirements: 1.1, 1.2, 1.3, 1.4_


  - [x] 8.2 Create tests for product and category operations

    - Test hierarchical category operations
    - Test product search and filtering
    - Test category deletion protection
    - _Requirements: 2.1, 2.2, 2.3, 2.5_


  - [x] 8.3 Create tests for stock operations

    - Test stock entry and level updates
    - Test stock reservation and release
    - Test insufficient stock validation
    - Test optimistic locking for concurrent operations
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 11.2_


  - [x] 8.4 Create tests for dispatch and transfer workflows

    - Test dispatch creation with stock validation
    - Test transfer approval and receiving workflow
    - Test stock conservation across transfers
    - _Requirements: 4.1, 4.2, 5.1, 5.2, 5.3_


  - [x] 8.5 Create tests for asset and repair operations

    - Test asset registration and tracking
    - Test location history management
    - Test repair workflow and cost tracking
    - _Requirements: 6.1, 6.2, 8.1, 8.2, 8.3_


  - [x] 8.6 Create tests for material management

    - Test material master creation
    - Test material request workflow
    - Test fulfillment tracking
    - _Requirements: 9.1, 9.2, 9.3, 9.4, 9.5_


  - [x] 8.7 Create tests for audit and history systems

    - Test audit log creation for various operations
    - Test history retrieval, filtering, and pagination
    - Test export functionality
    - _Requirements: 7.1, 7.2, 7.3, 12.2, 12.3_
