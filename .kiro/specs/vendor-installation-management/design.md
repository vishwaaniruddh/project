# Design Document

## Overview

The Vendor Installation Management system extends the existing vendor portal to include comprehensive installation task management. This system allows vendors to view, acknowledge, and manage their assigned installation tasks with precise timing controls and status tracking.

## Architecture

### System Components
- **Installation List Interface**: Tabular view of all assigned installations
- **Installation Management Interface**: Individual installation management with timing controls
- **Status Management System**: Handles installation status transitions
- **Progress Tracking System**: Records and displays installation progress updates
- **Timing Validation System**: Ensures proper chronological order of events

### Integration Points
- Extends existing vendor portal (`vendor/index.php`)
- Integrates with existing Installation model
- Uses existing vendor authentication system
- Leverages existing vendor layout system

## Components and Interfaces

### 1. Installation List Component (`vendor/installations.php`)

**Purpose**: Display all vendor installations in a comprehensive tabular format

**Key Features**:
- Statistics dashboard showing total, in-progress, completed, and overdue installations
- Sortable table with installation details, site information, timeline, and status
- Action buttons for acknowledgment, viewing, and management
- Real-time status indicators with color coding

**Data Display**:
```
Installation Details | Site Information | Timeline | Status | Actions
- Installation ID    | Site Name        | Expected | Status | View
- Type              | Address          | Start    | Badge  | Acknowledge
- Priority          | Contact Person   | End      |        | Manage
```

### 2. Installation Management Component (`vendor/manage-installation.php`)

**Purpose**: Individual installation management with timing controls

**Key Features**:
- Installation details display
- Site arrival time input (datetime-local)
- Installation start time input (datetime-local)
- Proceed to installation button (conditional enabling)
- Progress update modal
- Installation completion functionality

**Timing Control Logic**:
```
State 1: Both fields empty → Proceed button DISABLED
State 2: Only arrival time filled → Proceed button DISABLED  
State 3: Only start time filled → Proceed button DISABLED
State 4: Both fields filled & valid → Proceed button ENABLED
```

### 3. Backend Processing Component (`vendor/process-installation-action.php`)

**Purpose**: Handle all installation-related actions via AJAX

**Supported Actions**:
- `acknowledge`: Change status from 'assigned' to 'acknowledged'
- `update_timings`: Save arrival and installation start times
- `proceed_to_installation`: Change status to 'in_progress'
- `add_progress`: Add progress update entries
- `complete_installation`: Mark installation as completed

### 4. Progress Tracking Component

**Purpose**: Track and display installation progress updates

**Features**:
- Progress percentage tracking
- Work description logging
- Issues and challenges recording
- Next steps planning
- Timeline visualization

## Data Models

### Installation Record Structure
```sql
installation_delegations:
- id (primary key)
- site_id (foreign key)
- vendor_id (foreign key)
- installation_type
- priority (urgent, high, medium, low)
- status (assigned, acknowledged, in_progress, completed, cancelled, on_hold)
- expected_start_date
- expected_completion_date
- actual_start_date (site arrival time)
- installation_start_time (installation work start)
- actual_completion_date
- special_instructions
- created_at, updated_at, updated_by
```

### Progress Tracking Structure
```sql
installation_progress:
- id (primary key)
- installation_id (foreign key)
- progress_date
- progress_percentage
- work_description
- issues_faced
- next_steps
- updated_by (foreign key to users)
- created_at
```

### Status Flow
```
assigned → acknowledged → in_progress → completed
    ↓           ↓             ↓
cancelled   on_hold      on_hold
```

## Error Handling

### Validation Rules
1. **Timing Validation**: Installation start time must be after arrival time
2. **Status Validation**: Prevent invalid status transitions
3. **Access Control**: Verify vendor ownership of installation
4. **Data Integrity**: Validate required fields and data types

### Error Responses
- **400 Bad Request**: Invalid input data or validation failures
- **403 Forbidden**: Access denied to installation
- **404 Not Found**: Installation not found
- **500 Internal Server Error**: Database or system errors

### User Feedback
- Success messages for completed actions
- Validation error messages for invalid inputs
- Confirmation dialogs for critical actions
- Real-time field validation feedback

## Testing Strategy

### Unit Testing Focus
- Installation model methods (getVendorInstallations, updateStatus, etc.)
- Timing validation logic
- Status transition validation
- Progress tracking functionality

### Integration Testing Focus
- AJAX request/response handling
- Database transaction integrity
- Authentication and authorization
- Cross-browser datetime input compatibility

### User Acceptance Testing
- Complete installation workflow from assignment to completion
- Timing input validation and button state management
- Progress tracking and update functionality
- Mobile responsiveness and usability

## Security Considerations

### Access Control
- Vendor can only access their assigned installations
- Session-based authentication required
- CSRF protection on all form submissions
- Input sanitization and validation

### Data Protection
- Sensitive installation data access logging
- Secure handling of timing information
- Progress update audit trail
- Vendor activity monitoring

## Performance Considerations

### Database Optimization
- Indexed queries on vendor_id and status fields
- Efficient joins between installations and sites tables
- Pagination for large installation lists
- Cached statistics calculations

### Frontend Optimization
- AJAX-based updates to avoid full page reloads
- Progressive enhancement for datetime inputs
- Optimized table rendering for large datasets
- Mobile-first responsive design

## User Experience Design

### Visual Hierarchy
- Clear status indicators with color coding
- Prominent action buttons with appropriate states
- Intuitive progress visualization
- Consistent iconography throughout

### Interaction Design
- Disabled button states with visual feedback
- Confirmation dialogs for destructive actions
- Real-time validation feedback
- Progressive disclosure of complex features

### Mobile Considerations
- Touch-friendly button sizes
- Responsive table design with horizontal scrolling
- Optimized datetime input for mobile devices
- Simplified navigation for smaller screens