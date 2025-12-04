# Implementation Plan

- [ ] 1. Create database migration for multi-warehouse support








  - Create migration script `database/add_multi_warehouse_support.php`
  - Create `warehouses` table with all required fields
  - Insert default Mumbai warehouse record
  - Add `warehouse_id` column to `inventory_stock` table with default value 1
  - Add `warehouse_id` column to `inventory_inwards` table
  - Add `source_warehouse_id` column to `inventory_dispatches` table
  - Create `warehouse_transfers` table for inter-warehouse transfers
  - Create `warehouse_transfer_items` table for transfer line items
  - Add warehouse columns to `inventory_movements` table
  - Update `inventory_summary` view to include warehouse distribution
  - Create `warehouse_stock_summary` view for warehouse-specific reporting
  - Update all existing inventory_stock records to reference Mumbai warehouse (warehouse_id = 1)
  - Create audit log entry documenting the migration
  - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5_

- [ ] 2. Implement Warehouse model and controller
- [ ] 2.1 Create Warehouse model extending BaseMaster
  - Create `models/Warehouse.php` extending BaseMaster
  - Implement `create()` method with warehouse code auto-generation
  - Implement `update()` method with validation
  - Implement `getActive()` method to fetch active warehouses
  - Implement `getWarehouseWithStockSummary()` method using warehouse_stock_summary view
  - Implement `validateWarehouseData()` method with all validation rules
  - Implement `getWarehouseStockLevels()` method for stock by warehouse
  - Implement `getWarehouseLowStockItems()` method for low stock alerts
  - Implement `getWarehouseStats()` method for dashboard statistics
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [ ] 2.2 Create WarehousesController for HTTP request handling
  - Create `controllers/WarehousesController.php` extending BaseController
  - Implement `index()` method for listing warehouses with pagination
  - Implement `create()` method to show create form
  - Implement `store()` method to save new warehouse
  - Implement `edit()` method to show edit form
  - Implement `update()` method to update warehouse
  - Implement `delete()` method with stock validation
  - Implement `toggleStatus()` method for activate/deactivate
  - Implement `stockSummary()` method to display warehouse stock details
  - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

- [ ] 3. Create warehouse management UI pages
- [ ] 3.1 Create warehouse listing page
  - Create `admin/masters/warehouses/index.php`
  - Display warehouses table with code, name, location, contact, status
  - Add search functionality by name or code
  - Add filter by status (active/inactive)
  - Display stock summary count for each warehouse
  - Add action buttons (view, edit, toggle status, delete)
  - Include pagination controls
  - _Requirements: 1.1, 1.5_

- [ ] 3.2 Create warehouse add/edit forms
  - Create `admin/masters/warehouses/add.php` for new warehouse form
  - Create `admin/masters/warehouses/edit.php` for edit warehouse form
  - Include fields: name, address, city, state, pincode, contact person, phone, email
  - Add client-side validation for required fields
  - Add server-side validation using Warehouse model
  - Display success/error messages
  - Auto-generate warehouse code on creation
  - _Requirements: 1.1, 1.2, 1.3, 1.4_

- [ ] 3.3 Create warehouse detail view page
  - Create `admin/masters/warehouses/view.php`
  - Display warehouse information (code, name, address, contact details)
  - Show warehouse stock summary using warehouse_stock_summary view
  - Display stock levels by material category
  - Show recent inward receipts to this warehouse
  - Show recent dispatches from this warehouse
  - Add link to create transfer from this warehouse
  - _Requirements: 1.5, 5.1, 5.2, 5.3, 5.4_

- [ ] 4. Modify inventory inward receipt functionality for warehouse selection
- [ ] 4.1 Update inward receipt creation to include warehouse selection
  - Modify `admin/inventory/inwards/add-inward.php`
  - Add warehouse dropdown populated from Warehouse model getActive()
  - Make warehouse selection required field
  - Validate warehouse is active before allowing receipt
  - Pass warehouse_id to Inventory model createInwardReceipt()
  - Display selected warehouse on receipt confirmation
  - _Requirements: 3.1, 3.2, 3.3, 3.5_

- [ ] 4.2 Update Inventory model to handle warehouse in inward receipts
  - Modify `models/Inventory.php` createInwardReceipt() method
  - Add warehouse_id parameter and save to inventory_inwards table
  - Modify addInwardItems() to assign warehouse_id to inventory_stock records
  - Update createIndividualStockEntries() to include warehouse_id
  - Ensure all stock items from receipt are assigned to correct warehouse
  - _Requirements: 3.1, 3.2, 3.3_

- [ ] 4.3 Update inward receipt display pages to show warehouse information
  - Modify `admin/inventory/inwards/index.php` to display warehouse column
  - Modify `admin/inventory/inwards/view-inward.php` to show warehouse details
  - Add warehouse filter to inward receipts list
  - Display warehouse name and address on receipt documents
  - _Requirements: 3.4_

- [ ] 5. Modify inventory dispatch functionality for warehouse selection
- [ ] 5.1 Update dispatch creation to include source warehouse selection
  - Modify `admin/inventory/dispatches/create-dispatch.php`
  - Add source warehouse dropdown at top of form
  - Filter available stock by selected warehouse using AJAX
  - Update stock availability display to show warehouse-specific quantities
  - Make source warehouse selection required
  - Pass source_warehouse_id to Inventory model createDispatch()
  - _Requirements: 4.1, 4.2, 4.3_

- [ ] 5.2 Update Inventory model to handle warehouse in dispatches
  - Modify `models/Inventory.php` createDispatch() method
  - Add source_warehouse_id parameter and save to inventory_dispatches table
  - Modify getAvailableStock() to filter by warehouse_id
  - Modify addDispatchItems() to only select stock from source warehouse
  - Update stock item status and maintain warehouse_id during dispatch
  - _Requirements: 4.1, 4.2, 4.4_

- [ ] 5.3 Update dispatch display pages to show warehouse information
  - Modify `admin/inventory/dispatches/index.php` to display source warehouse column
  - Modify `admin/inventory/dispatches/view-dispatch.php` to show warehouse details
  - Add warehouse filter to dispatches list
  - Display warehouse name, address, and contact on dispatch documents
  - Show warehouse information in vendor dispatch notifications
  - _Requirements: 4.5, 8.1, 8.2, 8.3, 8.4_

- [ ] 6. Update inventory stock overview for warehouse filtering
- [ ] 6.1 Add warehouse filter to stock overview page
  - Modify `admin/inventory/index.php`
  - Add warehouse dropdown filter in search/filter section
  - Modify getStockOverview() call to include warehouse_id parameter
  - Display "All Warehouses" or specific warehouse name in page header
  - Show warehouse distribution for each material in stock table
  - _Requirements: 5.1, 5.2_

- [ ] 6.2 Update Inventory model stock queries for warehouse filtering
  - Modify `models/Inventory.php` getStockOverview() method
  - Add optional warehouse_id parameter
  - Add WHERE clause for warehouse_id when provided
  - Modify getIndividualStockEntries() to support warehouse filtering
  - Update getStockByItem() to show warehouse distribution
  - _Requirements: 5.1, 5.2, 5.3_

- [ ] 6.3 Create warehouse-wise stock distribution view
  - Create `admin/inventory/warehouse-distribution.php`
  - Display materials with stock levels across all warehouses in matrix format
  - Show total stock and per-warehouse breakdown
  - Highlight warehouses with low stock for each material
  - Add export functionality for warehouse distribution report
  - _Requirements: 5.1, 5.3, 5.4, 5.5_

- [ ] 7. Implement inter-warehouse transfer functionality
- [ ] 7.1 Create WarehouseTransfer model
  - Create `models/WarehouseTransfer.php` extending BaseModel
  - Implement `createTransfer()` method with transfer number generation
  - Implement `validateTransfer()` method (check same warehouse, stock availability)
  - Implement `getTransferDetails()` method with items and warehouse info
  - Implement `updateTransferStatus()` method for status changes
  - Implement `completeTransfer()` method to move stock between warehouses
  - Implement `getTransfers()` method with pagination and filters
  - Implement private `moveStockItems()` helper to update warehouse_id in inventory_stock
  - Implement private `createMovementRecords()` helper for audit trail
  - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

- [ ] 7.2 Create transfer management UI pages
  - Create `admin/inventory/transfers/index.php` for transfer list
  - Create `admin/inventory/transfers/create-transfer.php` for new transfer form
  - Create `admin/inventory/transfers/view-transfer.php` for transfer details
  - Create `admin/inventory/transfers/complete-transfer.php` for completion
  - _Requirements: 6.1, 6.2, 6.5_

- [ ] 7.3 Implement transfer creation page
  - In `admin/inventory/transfers/create-transfer.php`
  - Add source warehouse dropdown
  - Add destination warehouse dropdown (exclude source warehouse)
  - Add material selection with available stock from source warehouse
  - Add quantity/serial number selection for each material
  - Add transfer notes field
  - Validate source has sufficient stock before submission
  - Display transfer number after creation
  - _Requirements: 6.1, 6.2, 6.3, 6.4_

- [ ] 7.4 Implement transfer completion functionality
  - In `admin/inventory/transfers/complete-transfer.php`
  - Display transfer details with items list
  - Add "Complete Transfer" button for pending/in_transit transfers
  - Call WarehouseTransfer model completeTransfer() method
  - Update inventory_stock records to change warehouse_id from source to destination
  - Create movement records for both outward (source) and inward (destination)
  - Update transfer status to 'completed' with timestamp
  - Display success message with updated stock levels
  - _Requirements: 6.5, 6.6_

- [ ] 8. Update inventory movement tracking for warehouse information
- [ ] 8.1 Modify inventory movements to include warehouse data
  - Modify `models/Inventory.php` movement creation methods
  - Add source_warehouse_id and destination_warehouse_id to movement records
  - Update createMovementOnInward trigger to include warehouse_id
  - Update createMovementOnDispatch trigger to include source_warehouse_id
  - Create movement records for transfers with both warehouse IDs
  - _Requirements: 7.1, 7.2_

- [ ] 8.2 Update movement history display to show warehouse information
  - Modify `admin/inventory/tracking/index.php`
  - Add warehouse columns to movement history table
  - Display source and destination warehouse names
  - Add warehouse filter to movement history
  - Show warehouse information in movement details
  - _Requirements: 7.2, 7.3, 7.4_

- [ ] 8.3 Create warehouse movement report
  - Create `admin/inventory/reports/warehouse-movements.php`
  - Filter movements by warehouse, date range, and material
  - Display inward, outward, and transfer movements
  - Show running balance per warehouse
  - Add export functionality (CSV/Excel)
  - _Requirements: 7.3, 7.5_

- [ ] 9. Update vendor and site views for warehouse visibility
- [ ] 9.1 Update vendor dispatch view to show warehouse information
  - Modify `vendor/dispatches/view-dispatch.php`
  - Display source warehouse name and location
  - Show warehouse contact details
  - Display warehouse address on delivery documentation
  - _Requirements: 8.1, 8.3, 8.4_

- [ ] 9.2 Add warehouse filter to vendor dispatch list
  - Modify `vendor/dispatches/index.php`
  - Add warehouse filter dropdown
  - Allow vendors to filter dispatches by source warehouse
  - Display warehouse column in dispatches table
  - _Requirements: 8.5_

- [ ] 9.3 Include warehouse information in dispatch notifications
  - Modify dispatch email notification template
  - Include source warehouse name, address, and contact
  - Add warehouse contact person and phone number
  - Display estimated delivery time based on warehouse location
  - _Requirements: 8.2_

- [ ] 10. Add warehouse management to navigation menu
  - Modify `includes/navigation.php` or menu configuration
  - Add "Warehouses" menu item under Masters section
  - Add "Warehouse Transfers" menu item under Inventory section
  - Add "Warehouse Distribution" menu item under Inventory Reports
  - Ensure proper permissions for inventory manager role
  - _Requirements: 1.1_

- [ ] 11. Create warehouse-specific reports and analytics
- [ ] 11.1 Create warehouse stock summary report
  - Create `admin/inventory/reports/warehouse-stock-summary.php`
  - Display stock levels by warehouse and category
  - Show available vs dispatched quantities per warehouse
  - Calculate total value per warehouse
  - Add comparison across warehouses
  - Include export functionality
  - _Requirements: 5.1, 5.3, 5.4_

- [ ] 11.2 Create warehouse performance dashboard
  - Create `admin/inventory/reports/warehouse-performance.php`
  - Display inward receipts count per warehouse
  - Show dispatch count per warehouse
  - Calculate transfer frequency between warehouses
  - Show average stock levels and turnover rate
  - Display low stock alerts per warehouse
  - _Requirements: 5.5, 7.4_

- [ ] 11.3 Add warehouse analytics to main dashboard
  - Modify `admin/dashboard.php`
  - Add warehouse stock distribution widget
  - Show top warehouses by stock value
  - Display recent transfers summary
  - Add low stock alerts by warehouse
  - _Requirements: 5.1, 5.5_

- [ ] 12. Update documentation and help content
  - Update `admin/help.php` with multi-warehouse feature documentation
  - Document warehouse creation process
  - Document inward receipt to specific warehouse
  - Document dispatch from specific warehouse
  - Document inter-warehouse transfer process
  - Add troubleshooting section for common warehouse issues
  - _Requirements: 1.1, 3.1, 4.1, 6.1_
