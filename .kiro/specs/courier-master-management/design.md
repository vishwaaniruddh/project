# Design Document

## Overview

This design implements a courier master management system in the admin section. The system will allow administrators to create, view, update, and manage courier service providers that can be used for tracking material dispatches and deliveries. The courier master will be integrated into the existing admin menu structure under the Location section.

## Architecture

### Current System Analysis

The existing system has:
- Dynamic menu system using `Menu` model and database-driven navigation
- Master data management patterns (states, cities, etc.)
- Standard admin CRUD interfaces with modal-based forms
- Audit trail fields (created_at, created_by, updated_at, updated_by)

### Proposed Architecture

The courier master system will follow the existing master data management pattern:
1. **Database Table** - `couriers` table with standard master fields
2. **Model Layer** - `Courier` model for data operations
3. **Admin Interface** - CRUD interface at `admin/masters/couriers.php`
4. **Menu Integration** - Add courier menu item under Location section in menu system

## Components and Interfaces

### 1. Database Schema

#### Couriers Table
```sql
CREATE TABLE couriers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    courier_name VARCHAR(255) NOT NULL UNIQUE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id),
    INDEX idx_status (status),
    INDEX idx_courier_name (courier_name)
);
```

### 2. Courier Model (`models/Courier.php`)

#### Methods:
```php
class Courier {
    // Get all couriers with optional filtering
    public function getAllCouriers($search = '', $status = null)
    
    // Get single courier by ID
    public function getCourierById($id)
    
    // Create new courier
    public function createCourier($courierName, $status, $userId)
    
    // Update existing courier
    public function updateCourier($id, $courierName, $status, $userId)
    
    // Check if courier name exists (for duplicate prevention)
    public function courierNameExists($courierName, $excludeId = null)
    
    // Get active couriers only (for dropdowns)
    public function getActiveCouriers()
    
    // Toggle courier status
    public function toggleStatus($id, $userId)
}
```

### 3. Admin Interface (`admin/masters/couriers.php`)

**Layout Structure:**
- Page header with title and "Add Courier" button
- Search bar for filtering couriers
- Data table displaying all couriers
- Modal form for add/edit operations
- Status toggle functionality

**Table Columns:**
- ID
- Courier Name
- Status (badge with color coding)
- Created Date
- Actions (Edit, Toggle Status)

### 4. Menu Integration

**Menu Entry:**
- Parent: Location section
- Title: Couriers
- URL: /admin/masters/couriers.php
- Icon: 'business' (truck/delivery icon)
- Order: After Cities

## Data Models

### Courier Data Structure
```php
[
    'id' => int,
    'courier_name' => string,
    'status' => string, // 'active' or 'inactive'
    'created_at' => datetime,
    'created_by' => int,
    'created_by_name' => string, // joined from users table
    'updated_at' => datetime,
    'updated_by' => int,
    'updated_by_name' => string // joined from users table
]
```

### Form Validation Rules
- **courier_name**: Required, max 255 characters, unique
- **status**: Required, must be 'active' or 'inactive'

## Error Handling

### Validation Errors
- Empty courier name: "Courier name is required"
- Duplicate courier name: "Courier name already exists"
- Invalid status: "Invalid status value"

### Database Errors
- Connection failures: Log error, show generic message
- Constraint violations: Show user-friendly message
- Foreign key errors: Handle gracefully

### User Feedback
- Success messages for create/update/delete operations
- Error messages displayed in modal or as alerts
- Loading states during AJAX operations

## Testing Strategy

### Unit Testing Focus
- Courier model methods for CRUD operations
- Duplicate name detection
- Status toggle functionality
- Search and filtering logic

### Integration Testing
- End-to-end courier creation workflow
- Edit and update operations
- Status toggle with audit trail
- Menu navigation and access control

### User Acceptance Testing
- Admin workflow for managing couriers
- Search functionality
- Form validation
- Mobile responsiveness

## Implementation Approach

### Phase 1: Database and Model Layer
1. Create couriers table migration
2. Implement Courier model with all methods
3. Add seed data for testing

### Phase 2: Admin Interface
1. Create admin/masters/couriers.php page
2. Implement CRUD operations
3. Add search and filtering
4. Implement status toggle

### Phase 3: Menu Integration
1. Add courier menu entry to database
2. Assign permissions to admin role
3. Test navigation and access control

## Security Considerations

### Access Control
- Ensure only admin users can access courier management
- Validate user permissions before any operation
- Check authentication on every request

### Data Protection
- Sanitize all user inputs
- Use prepared statements for all database queries
- Implement CSRF protection for forms
- Validate status values against whitelist

### Audit Trail
- Record created_by and updated_by for all operations
- Maintain timestamp history
- Log all courier modifications

## Performance Optimization

### Database Optimization
- Add indexes on courier_name and status columns
- Use efficient queries with proper joins
- Implement pagination for large datasets

### Frontend Optimization
- Use AJAX for status toggle to avoid page reload
- Implement client-side search for better UX
- Cache active couriers list for dropdowns

### Caching Strategy
- Cache active couriers list with short TTL
- Invalidate cache on courier updates
- Use session storage for search preferences

## Integration Points

### Future Usage
The courier master will be used in:
- Material dispatch tracking
- Delivery management
- Shipment records
- Installation logistics

### API Endpoints (if needed)
- GET /api/couriers - List all couriers
- GET /api/couriers/active - List active couriers only
- POST /api/couriers - Create courier
- PUT /api/couriers/{id} - Update courier
- PATCH /api/couriers/{id}/status - Toggle status

## UI/UX Considerations

### Visual Design
- Follow existing admin interface patterns
- Use consistent color coding for status badges
- Maintain responsive design for mobile devices

### User Experience
- Inline editing for quick updates
- Confirmation dialogs for status changes
- Clear success/error feedback
- Keyboard shortcuts for common actions

### Accessibility
- Proper ARIA labels for screen readers
- Keyboard navigation support
- High contrast for status indicators
- Focus management in modals
