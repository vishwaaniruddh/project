# Design Document: BOQ Master Management

## Overview

This document outlines the technical design for the BOQ Master Management feature. The feature enables administrators to create BOQ (Bill of Quantities) templates and associate stock items (products) from the `boq_items` table with specified quantities. This creates a hierarchical relationship where each BOQ master can contain multiple line items.

## Architecture

The feature follows the existing PHP MVC architecture pattern used throughout the application:

```
┌─────────────────────────────────────────────────────────────────┐
│                        Admin Interface                          │
│  (admin/boq-master/index.php, create.php, edit.php, view.php)  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Controller Layer                             │
│              (controllers/BoqMasterController.php)              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Model Layer                                │
│    (models/BoqMaster.php, models/BoqMasterItem.php)            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                     Database Layer                              │
│         (boq_master, boq_master_items, boq_items)              │
└─────────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### Database Schema

#### Existing Tables (No Changes)

**boq_master** - Already exists with basic structure:
```sql
- boq_id (INT, PK, AUTO_INCREMENT)
- boq_name (VARCHAR 200)
- is_serial_number_required (BOOLEAN)
- status (ENUM: active, inactive)
- created_at, updated_at, created_by, updated_by
```

**boq_items** - Stock items catalog (already exists):
```sql
- id (INT, PK)
- item_name, item_code, description
- unit, category, icon_class
- need_serial_number, status
- created_at, updated_at, created_by, updated_by
```

#### New Table

**boq_master_items** - Association table linking BOQ masters to stock items:
```sql
CREATE TABLE boq_master_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    boq_master_id INT NOT NULL,
    boq_item_id INT NOT NULL,
    default_quantity DECIMAL(10,2) DEFAULT 1,
    remarks TEXT,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (boq_master_id) REFERENCES boq_master(boq_id) ON DELETE CASCADE,
    FOREIGN KEY (boq_item_id) REFERENCES boq_items(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_boq_item (boq_master_id, boq_item_id),
    INDEX idx_boq_master (boq_master_id),
    INDEX idx_boq_item (boq_item_id)
);
```

### Model Layer

#### BoqMaster.php (Extend Existing)

Add methods to the existing `BoqMaster` model:

```php
// Get BOQ master with associated items count
public function getAllWithItemCount($page, $limit, $search, $status)

// Get full details including associated items
public function getWithItems($boqId)
```

#### BoqMasterItem.php (New Model)

```php
class BoqMasterItem {
    // CRUD operations for BOQ line items
    public function create($data)
    public function update($id, $data)
    public function delete($id)
    public function find($id)
    
    // Get items for a specific BOQ master
    public function getByBoqMaster($boqMasterId)
    
    // Check for duplicate item in BOQ
    public function isDuplicate($boqMasterId, $boqItemId, $excludeId = null)
    
    // Bulk operations
    public function updateSortOrder($boqMasterId, $itemOrders)
    
    // Validation
    public function validateData($data, $isUpdate = false, $id = null)
}
```

### View Layer

#### File Structure

```
admin/boq-master/
├── index.php          # List all BOQ masters with search/filter
├── create.php         # Create new BOQ master
├── edit.php           # Edit BOQ master details
├── view.php           # View BOQ master with associated items
├── delete.php         # Delete BOQ master (AJAX endpoint)
├── toggle-status.php  # Toggle active/inactive status
├── items/
│   ├── add.php        # Add item to BOQ (AJAX endpoint)
│   ├── edit.php       # Edit BOQ line item (AJAX endpoint)
│   ├── delete.php     # Remove item from BOQ (AJAX endpoint)
│   └── search.php     # Search available items (AJAX endpoint)
└── export.php         # Export BOQ master data
```

### UI Components

#### Main List Page (index.php)

```
┌─────────────────────────────────────────────────────────────────┐
│ BOQ Master Management                              [+ Create]   │
├─────────────────────────────────────────────────────────────────┤
│ [Search...                    ] [Status ▼] [Filter]             │
├─────────────────────────────────────────────────────────────────┤
│ Actions │ BOQ Name        │ Items │ Serial Req │ Status │ Date  │
├─────────────────────────────────────────────────────────────────┤
│ 👁 ✏ 🗑 │ Network Setup   │   12  │    Yes     │ Active │ 01/05 │
│ 👁 ✏ 🗑 │ Installation    │    8  │    No      │ Active │ 01/04 │
│ 👁 ✏ 🗑 │ Testing Kit     │    5  │    Yes     │Inactive│ 01/03 │
└─────────────────────────────────────────────────────────────────┘
│ Showing 1-10 of 25                          [1] [2] [3] [Next]  │
└─────────────────────────────────────────────────────────────────┘
```

#### View/Edit BOQ Master with Items

```
┌─────────────────────────────────────────────────────────────────┐
│ BOQ: Network Setup                          [Edit] [Back]       │
├─────────────────────────────────────────────────────────────────┤
│ Description: Standard network installation kit                  │
│ Serial Required: Yes    Status: Active                          │
├─────────────────────────────────────────────────────────────────┤
│ Associated Items                                   [+ Add Item] │
├─────────────────────────────────────────────────────────────────┤
│ # │ Item Code │ Item Name      │ Unit │ Qty │ Actions          │
├─────────────────────────────────────────────────────────────────┤
│ 1 │ CAM-001   │ Security Cam   │ Nos  │  4  │ [Edit] [Remove]  │
│ 2 │ CBL-002   │ Network Cable  │ Mtr  │ 50  │ [Edit] [Remove]  │
│ 3 │ PWR-003   │ Power Supply   │ Nos  │  2  │ [Edit] [Remove]  │
└─────────────────────────────────────────────────────────────────┘
```

#### Add Item Modal

```
┌─────────────────────────────────────────────────────────────────┐
│ Add Item to BOQ                                          [X]    │
├─────────────────────────────────────────────────────────────────┤
│ Select Item *                                                   │
│ [Search items...                              ▼] [+ New Item]   │
│                                                                 │
│ Quantity *                                                      │
│ [1                                              ]               │
│                                                                 │
│ Remarks                                                         │
│ [                                               ]               │
├─────────────────────────────────────────────────────────────────┤
│                                    [Cancel] [Add Item]          │
└─────────────────────────────────────────────────────────────────┘
```

## Data Models

### BOQ Master Entity

```php
[
    'boq_id' => int,
    'boq_name' => string,
    'description' => string|null,
    'is_serial_number_required' => bool,
    'status' => 'active'|'inactive',
    'created_at' => datetime,
    'created_by' => int|null,
    'updated_at' => datetime,
    'updated_by' => int|null,
    'item_count' => int  // Computed field
]
```

### BOQ Master Item Entity

```php
[
    'id' => int,
    'boq_master_id' => int,
    'boq_item_id' => int,
    'default_quantity' => decimal,
    'remarks' => string|null,
    'sort_order' => int,
    'status' => 'active'|'inactive',
    // Joined fields from boq_items
    'item_name' => string,
    'item_code' => string,
    'unit' => string,
    'category' => string|null
]
```

## Error Handling

### Validation Errors

| Field | Validation | Error Message |
|-------|------------|---------------|
| boq_name | Required, 2-200 chars | "BOQ name is required" / "BOQ name must be 2-200 characters" |
| boq_name | Unique | "BOQ name already exists" |
| boq_item_id | Required | "Please select an item" |
| boq_item_id | Unique per BOQ | "This item is already added to this BOQ" |
| default_quantity | Required, > 0 | "Quantity must be greater than 0" |

### Database Errors

- Foreign key violations: Display user-friendly message about related records
- Duplicate entry: Display specific field that caused the conflict
- Connection errors: Log error and display generic message

### AJAX Response Format

```json
{
    "success": true|false,
    "message": "Operation result message",
    "data": { /* optional response data */ },
    "errors": { /* field-specific errors for validation failures */ }
}
```

## Testing Strategy

### Unit Tests

1. **Model Tests**
   - BoqMaster CRUD operations
   - BoqMasterItem CRUD operations
   - Validation logic
   - Duplicate detection

2. **Controller Tests**
   - Request handling
   - Response formatting
   - Error handling

### Integration Tests

1. **Database Operations**
   - Create BOQ with items
   - Update BOQ and items
   - Delete BOQ (cascade delete items)
   - Search and filter functionality

2. **UI Flow Tests**
   - Create BOQ master flow
   - Add/edit/remove items flow
   - Status toggle functionality

### Manual Testing Checklist

- [ ] Create BOQ master with all fields
- [ ] Edit BOQ master details
- [ ] Add items to BOQ
- [ ] Edit item quantity/remarks
- [ ] Remove item from BOQ
- [ ] Toggle BOQ status
- [ ] Delete BOQ master
- [ ] Search BOQ masters
- [ ] Filter by status
- [ ] Pagination navigation
- [ ] Quick add new item from modal

## Security Considerations

1. **Authentication**: All endpoints require admin role authentication via `Auth::requireRole(ADMIN_ROLE)`
2. **Input Validation**: All user inputs sanitized and validated before database operations
3. **CSRF Protection**: Form submissions include CSRF tokens where applicable
4. **SQL Injection**: All database queries use prepared statements with parameterized queries
5. **XSS Prevention**: All output escaped using `htmlspecialchars()`

## Performance Considerations

1. **Pagination**: Default 20 records per page to limit data transfer
2. **Indexed Queries**: Database indexes on frequently queried columns
3. **Lazy Loading**: Item details loaded on demand when viewing BOQ
4. **Debounced Search**: 500ms debounce on search input to reduce server requests
