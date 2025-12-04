# Enhanced Installation Workflow Design Document

## Overview

The Enhanced Installation Workflow system builds upon the existing survey management infrastructure to provide seamless installation delegation and comprehensive tracking. The design integrates with current database schemas while adding new components for installation management, real-time status updates, and dedicated administrative interfaces.

## Architecture

### System Components

1. **Survey Enhancement Layer**: Extends existing survey management with installation delegation capabilities
2. **Installation Management Module**: New dedicated interface for managing installation lifecycle
3. **Real-time Status Engine**: Provides live updates and status synchronization
4. **Delegation Workflow Engine**: Handles the complete delegation process from survey approval to installation assignment
5. **Notification System**: Manages vendor notifications and status updates

### Integration Points

- **Existing Survey System**: Enhances current `admin/surveys/index.php` with delegation actions
- **Installation Model**: Utilizes existing `models/Installation.php` with enhancements
- **Database Layer**: Extends current installation tables with additional status tracking
- **Vendor Interface**: Integrates with existing vendor portal for installation assignments

## Components and Interfaces

### 1. Enhanced Survey Management Interface

**File**: `admin/surveys/index.php` (enhanced)

**Key Features**:
- Real-time status indicators with color-coded badges
- Installation delegation buttons for approved surveys
- Status tracking columns showing delegation and installation progress
- Modal dialog for installation delegation with vendor selection

**UI Components**:
- Status badge system with live updates
- Delegation action buttons with conditional visibility
- Installation delegation modal with form validation
- Progress indicators and timeline display

### 2. Installation Management Dashboard

**File**: `admin/installations/index.php` (new)

**Key Features**:
- Comprehensive installation listing with filtering capabilities
- Real-time status updates without page refresh
- Vendor performance metrics and statistics
- Installation timeline and progress tracking

**UI Components**:
- DataTables integration for advanced filtering and sorting
- Status filter dropdown with real-time counts
- Vendor filter with performance indicators
- Date range picker for timeline filtering
- Export functionality for reports

### 3. Installation Delegation System

**Files**: 
- `admin/surveys/delegate-installation.php` (new)
- `admin/surveys/process-installation-delegation.php` (enhanced)

**Key Features**:
- Vendor selection with availability checking
- Scheduling with date validation and conflict detection
- Priority assignment and special instructions
- Automatic notification generation

**Workflow**:
1. Survey approval triggers delegation availability
2. Admin selects vendor and sets parameters
3. System validates vendor availability and capacity
4. Installation record created with delegation details
5. Vendor notification sent with assignment details
6. Survey status updated to reflect delegation

### 4. Real-time Status Management

**File**: `api/installation-status.php` (new)

**Key Features**:
- WebSocket-like polling for status updates
- Status change event handling
- Automatic UI refresh on status changes
- Conflict resolution for concurrent updates

**Status Flow**:
```
Survey Approved → Available for Delegation → Delegated → In Progress → Completed
                                        ↓
                                   On Hold / Cancelled
```

### 5. Installation Menu System

**Files**:
- `admin/installations/index.php` (new)
- `admin/installations/view.php` (new)
- `admin/installations/manage.php` (new)

**Navigation Structure**:
```
Installation
├── Dashboard (overview and statistics)
├── Active Installations (in-progress tracking)
├── Pending Delegations (awaiting assignment)
├── Completed Installations (historical records)
└── Reports (analytics and exports)
```

## Data Models

### Enhanced Installation Delegation Table

```sql
ALTER TABLE installation_delegations ADD COLUMN (
    delegation_status ENUM('pending', 'assigned', 'accepted', 'in_progress', 'completed', 'on_hold', 'cancelled') DEFAULT 'assigned',
    vendor_acceptance_date DATETIME NULL,
    vendor_notes TEXT NULL,
    admin_notes TEXT NULL,
    last_status_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    estimated_duration INT NULL COMMENT 'Duration in days',
    actual_duration INT NULL COMMENT 'Actual duration in days'
);
```

### Installation Status History Table

```sql
CREATE TABLE installation_status_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    installation_id INT NOT NULL,
    previous_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    change_reason TEXT,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (installation_id) REFERENCES installation_delegations(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
);
```

### Survey Installation Tracking

```sql
ALTER TABLE site_surveys ADD COLUMN (
    installation_delegated_at DATETIME NULL,
    installation_delegated_by INT NULL,
    installation_expected_completion DATETIME NULL,
    FOREIGN KEY (installation_delegated_by) REFERENCES users(id)
);
```

## Error Handling

### Delegation Validation

1. **Survey Status Validation**: Ensure survey is approved before delegation
2. **Vendor Availability**: Check vendor capacity and existing assignments
3. **Date Validation**: Validate start/end dates and check for conflicts
4. **Duplicate Prevention**: Prevent multiple delegations for same survey

### Status Update Handling

1. **Concurrent Update Protection**: Use optimistic locking for status changes
2. **Invalid Transition Prevention**: Validate status change sequences
3. **Rollback Capability**: Maintain history for status rollback if needed
4. **Notification Failure Handling**: Queue notifications for retry on failure

### Data Integrity

1. **Foreign Key Constraints**: Maintain referential integrity across tables
2. **Transaction Management**: Use database transactions for multi-table updates
3. **Audit Trail**: Log all changes for compliance and debugging
4. **Backup Validation**: Ensure data consistency during status transitions

## Testing Strategy

### Unit Testing

1. **Installation Model Tests**: Test delegation creation, status updates, and data retrieval
2. **Validation Logic Tests**: Test all validation rules and edge cases
3. **Status Transition Tests**: Verify valid and invalid status changes
4. **Date Calculation Tests**: Test duration calculations and date validations

### Integration Testing

1. **Survey-Installation Integration**: Test complete workflow from survey to delegation
2. **Vendor Notification Tests**: Verify notification delivery and content
3. **Database Transaction Tests**: Test multi-table update consistency
4. **API Endpoint Tests**: Test all AJAX endpoints for proper responses

### User Interface Testing

1. **Modal Dialog Tests**: Test delegation modal functionality and validation
2. **Real-time Update Tests**: Verify status updates appear without refresh
3. **Filter and Search Tests**: Test installation filtering and search capabilities
4. **Responsive Design Tests**: Ensure proper display across device sizes

### Performance Testing

1. **Large Dataset Tests**: Test performance with high volume of installations
2. **Concurrent User Tests**: Test system behavior with multiple simultaneous users
3. **Real-time Update Performance**: Test polling frequency and server load
4. **Database Query Optimization**: Ensure efficient queries for large datasets

## Security Considerations

### Access Control

1. **Role-based Permissions**: Ensure only authorized users can delegate installations
2. **Vendor Data Isolation**: Prevent vendors from accessing other vendor's installations
3. **Admin Function Protection**: Secure all administrative functions with proper authentication
4. **API Endpoint Security**: Validate all API requests and sanitize inputs

### Data Protection

1. **Input Sanitization**: Sanitize all user inputs to prevent injection attacks
2. **CSRF Protection**: Implement CSRF tokens for all form submissions
3. **SQL Injection Prevention**: Use prepared statements for all database queries
4. **XSS Prevention**: Escape all output to prevent cross-site scripting

### Audit and Compliance

1. **Action Logging**: Log all delegation and status change actions
2. **User Activity Tracking**: Track user sessions and actions for audit
3. **Data Change History**: Maintain complete history of all data changes
4. **Compliance Reporting**: Generate reports for regulatory compliance if needed