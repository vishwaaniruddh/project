# Implementation Plan

- [x] 1. Create database migration for boq_master_items table






















  - Create SQL migration file `database/create_boq_master_items.sql` with the junction table schema
  - Create PHP migration script `database/setup_boq_master_items.php` to execute the migration
  - Add description column to existing boq_master table if not present
  - _Requirements: 1.1, 2.1_

- [x] 2. Create BoqMasterItem model





  - [x] 2.1 Create `models/BoqMasterItem.php` with CRUD operations


    - Implement create, update, delete, find methods
    - Implement getByBoqMaster method to fetch items for a BOQ
    - Implement isDuplicate method to check for existing item associations
    - Implement validateData method for input validation
    - _Requirements: 2.1, 2.3, 2.4, 2.5, 2.6_
  - [x] 2.2 Write unit tests for BoqMasterItem model









    - Test CRUD operations
    - Test duplicate detection
    - Test validation logic
    - _Requirements: 2.1, 2.4_

- [x] 3. Extend BoqMaster model





  - [x] 3.1 Add getAllWithItemCount method to BoqMaster model


    - Modify getAllWithPagination to include item count from boq_master_items
    - _Requirements: 1.1_
  - [x] 3.2 Add getWithItems method to BoqMaster model


    - Fetch BOQ master details with all associated items joined from boq_items
    - _Requirements: 2.1_

- [x] 4. Create admin BOQ master list page





  - [x] 4.1 Create `admin/boq-master/index.php` with paginated list view


    - Display BOQ masters with name, item count, serial requirement, status, date
    - Include search input with debounced filtering
    - Include status filter dropdown
    - Include pagination controls
    - _Requirements: 1.1, 3.1, 3.2, 3.3_
  - [x] 4.2 Add create, edit, view, delete action buttons


    - Wire up modal triggers and navigation
    - _Requirements: 1.2, 1.5, 1.6_

- [x] 5. Create BOQ master CRUD pages





  - [x] 5.1 Create `admin/boq-master/create.php` for new BOQ master


    - Form with boq_name, description, is_serial_number_required, status fields
    - Client-side and server-side validation
    - Success/error message handling
    - _Requirements: 1.2, 1.3, 1.4_
  - [x] 5.2 Create `admin/boq-master/edit.php` for editing BOQ master


    - Pre-populate form with existing data
    - Handle form submission with validation
    - _Requirements: 1.5_
  - [x] 5.3 Create `admin/boq-master/delete.php` AJAX endpoint


    - Confirm deletion and cascade delete associated items
    - Return JSON response
    - _Requirements: 1.6_
  - [x] 5.4 Create `admin/boq-master/toggle-status.php` AJAX endpoint


    - Toggle between active/inactive status
    - Return JSON response
    - _Requirements: 4.1, 4.2, 4.3_

- [x] 6. Create BOQ master view page with item management









  - [x] 6.1 Create `admin/boq-master/view.php` to display BOQ details and items


    - Show BOQ master details (name, description, status, serial requirement)
    - Display table of associated items with item code, name, unit, quantity
    - Include Add Item button to trigger modal
    - Include Edit and Remove buttons for each item row
    - _Requirements: 2.1_
  - [x] 6.2 Create `admin/boq-master/items/search.php` AJAX endpoint


    - Search boq_items table by name or code
    - Return JSON array of matching items
    - Exclude items already associated with current BOQ
    - _Requirements: 2.2_

  - [x] 6.3 Create `admin/boq-master/items/add.php` AJAX endpoint

    - Validate boq_item_id and quantity
    - Check for duplicate association
    - Create boq_master_items record
    - Return JSON response with new item data
    - _Requirements: 2.2, 2.3, 2.4_

  - [x] 6.4 Create `admin/boq-master/items/edit.php` AJAX endpoint

    - Update quantity and remarks for existing association
    - Return JSON response
    - _Requirements: 2.5_

  - [x] 6.5 Create `admin/boq-master/items/delete.php` AJAX endpoint

    - Remove item association from BOQ
    - Return JSON response
    - _Requirements: 2.6_
-


- [x] 7. Implement quick add new item feature







  - [x] 7.1 Add "Create New Item" option in item selection dropdown

    - Display modal form for creating new boq_item
    - On success, add new item to current BOQ association


    - _Requirements: 5.1, 5.2, 5.3_

- [x] 8. Add menu entry for BOQ Master Management






  - Add navigation link to admin sidebar under appropriate section
  - Ensure proper menu permissions for admin role
  - _Requirements: 1.1_
