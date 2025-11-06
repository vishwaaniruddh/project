# Design Document

## Overview

This design enhances the existing vendor inventory overview page to provide a comprehensive stock listing interface. The current page serves as a dashboard with statistics and quick actions, but vendors need a detailed view of all their stock items with individual management capabilities. The solution will transform the existing page into a tabbed interface with both dashboard and detailed stock views.

## Architecture

### Current System Analysis

The existing system has:
- `vendor/inventory/index.php` - Dashboard-style overview with statistics
- `Inventory::getReceivedMaterialsForVendor()` - Gets dispatched materials for vendor
- `Inventory::getStockOverview()` - Gets aggregated stock data (admin-focused)
- `Inventory::getStockSummaryForItem()` - Gets summary for specific items

### Proposed Architecture

The enhanced system will use a tabbed interface approach:
1. **Dashboard Tab** - Current overview with statistics and quick actions
2. **Stock Details Tab** - Comprehensive stock listing with individual product actions
3. **Product Detail Page** - Individual product management interface

## Components and Interfaces

### 1. Enhanced Inventory Overview Page (`vendor/inventory/index.php`)

**Current State**: Dashboard with statistics and recent activity
**Enhanced State**: Tabbed interface with dashboard and stock details

#### Tab Structure:
- **Dashboard Tab**: Existing functionality (statistics, quick actions, recent activity)
- **Stock Details Tab**: New comprehensive stock listing

### 2. New Inventory Model Methods

#### `getVendorStockOverview($vendorId, $search = '', $sortBy = 'item_name', $sortOrder = 'ASC')`
```php
/**
 * Get comprehensive stock overview for a specific vendor
 * Returns aggregated stock data grouped by BOQ item with quantities and status
 */
```

#### `getVendorStockDetails($vendorId, $boqItemId)`
```php
/**
 * Get detailed stock information for a specific item for a vendor
 * Returns individual stock entries, usage history, and dispatch records
 */
```

### 3. New Product Detail Page (`vendor/inventory/product-details.php`)

**Purpose**: Individual product management and history view
**Parameters**: `boq_item_id` via GET parameter

### 4. Enhanced Data Models

#### Stock Overview Data Structure:
```php
[
    'boq_item_id' => int,
    'item_name' => string,
    'item_code' => string,
    'unit' => string,
    'category' => string,
    'icon_class' => string,
    'total_received' => int,
    'total_used' => int,
    'current_stock' => int,
    'available_stock' => int,
    'dispatched_stock' => int,
    'delivered_stock' => int,
    'avg_unit_cost' => float,
    'total_value' => float,
    'last_received_date' => datetime,
    'last_used_date' => datetime,
    'stock_status' => string // 'in_stock', 'low_stock', 'out_of_stock'
]
```

#### Product Detail Data Structure:
```php
[
    'item_info' => [...], // BOQ item details
    'stock_summary' => [...], // Aggregated quantities and values
    'individual_entries' => [...], // Individual stock entries with serial numbers
    'dispatch_history' => [...], // Dispatch records for this item
    'usage_history' => [...] // Usage/consumption records
]
```

## Data Models

### Database Tables Used

1. **inventory_dispatches** - Material dispatches to vendors
2. **inventory_dispatch_items** - Individual items in dispatches
3. **inventory_stock** - Individual stock entries with serial numbers
4. **boq_items** - Material specifications and details
5. **material_requests** - Original material requests
6. **sites** - Site information for context

### Key Relationships

```
vendors (1) -> (n) inventory_dispatches
inventory_dispatches (1) -> (n) inventory_dispatch_items
inventory_dispatch_items (1) -> (1) inventory_stock
inventory_stock (n) -> (1) boq_items
```

### Data Aggregation Logic

Stock quantities are calculated based on:
- **Total Received**: Count of all dispatched items to vendor
- **Available Stock**: Items with status 'delivered' or 'confirmed'
- **Used Stock**: Items with status 'used' or 'consumed'
- **Current Stock**: Available Stock - Used Stock

## Error Handling

### Data Validation
- Validate vendor authentication and authorization
- Ensure BOQ item ID exists and is accessible to vendor
- Handle missing or corrupted stock data gracefully

### Error Scenarios
1. **No Stock Data**: Display empty state with helpful message
2. **Database Errors**: Log errors and show generic error message
3. **Invalid Parameters**: Redirect to main inventory page
4. **Permission Errors**: Show access denied message

### Fallback Mechanisms
- If detailed stock data unavailable, show basic dispatch information
- If individual entries missing, show aggregated data only
- Graceful degradation for missing optional fields

## Testing Strategy

### Unit Testing Focus
- Inventory model methods for vendor-specific data retrieval
- Data aggregation calculations for stock quantities
- Search and filtering functionality
- Sorting and pagination logic

### Integration Testing
- End-to-end vendor stock overview workflow
- Product detail page navigation and data display
- Search functionality across different data types
- Performance testing with large datasets

### User Acceptance Testing
- Vendor workflow for finding specific materials
- Stock quantity accuracy verification
- Product detail information completeness
- Mobile responsiveness and usability

## Implementation Approach

### Phase 1: Backend Data Layer
1. Implement new Inventory model methods
2. Create data aggregation logic for vendor stock
3. Add search and filtering capabilities
4. Implement sorting and pagination

### Phase 2: Frontend Interface
1. Enhance existing inventory overview with tabbed interface
2. Create stock details table with search and actions
3. Implement product detail page
4. Add responsive design and mobile optimization

### Phase 3: Integration and Testing
1. Integrate new functionality with existing vendor layout
2. Add navigation and breadcrumbs
3. Implement error handling and validation
4. Performance optimization and caching

## Security Considerations

### Access Control
- Ensure vendors can only access their own stock data
- Validate vendor ID against authenticated user
- Prevent unauthorized access to other vendor's information

### Data Protection
- Sanitize all user inputs for search and filtering
- Use prepared statements for all database queries
- Implement proper error handling without exposing sensitive data

### Performance Security
- Implement pagination to prevent large data dumps
- Add rate limiting for search operations
- Cache frequently accessed data appropriately

## Performance Optimization

### Database Optimization
- Use indexed queries for vendor-specific data retrieval
- Implement efficient aggregation queries
- Add database indexes on frequently queried columns

### Frontend Optimization
- Implement client-side pagination for large datasets
- Use AJAX for search and filtering operations
- Optimize table rendering for large stock lists

### Caching Strategy
- Cache aggregated stock data for short periods
- Implement browser caching for static resources
- Use session storage for search preferences