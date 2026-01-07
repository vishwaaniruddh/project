# Requirements Document

## Introduction

This document specifies the requirements for transforming the existing menu-based access control system into a comprehensive Role-Based Access Control (RBAC) system for a SaaS-style project management platform. The system will enable Superadmins to create users, define roles, and assign granular permissions across all modules including Masters, Users, Survey, Installation, and Inventory management.

## Glossary

- **RBAC_System**: The Role-Based Access Control system that manages user authentication, role assignment, and permission verification across all application modules.
- **Superadmin**: The highest-level administrative user with full system access, capable of creating users, roles, and managing all permissions.
- **Admin**: An administrative user with elevated privileges for managing day-to-day operations, but with restricted access compared to Superadmin.
- **Manager**: A user role responsible for overseeing operations, approving requests, and managing team activities.
- **Engineer**: A technical user role responsible for executing surveys, installations, and technical operations.
- **Vendor**: An external partner user with limited access to specific sites and operations assigned to them.
- **Role**: A named collection of permissions that can be assigned to users to define their access level.
- **Permission**: A specific action or access right that can be granted or denied (e.g., `inventory.alerts.manage`).
- **Permission_Group**: A logical grouping of related permissions under a module (e.g., all inventory-related permissions).
- **User_Session**: An authenticated session containing user identity, role, and cached permissions.

## Requirements

### Requirement 1: Role Management

**User Story:** As a Superadmin, I want to manage system roles so that I can define different access levels for users.

#### Acceptance Criteria

1. THE RBAC_System SHALL provide five predefined roles: Superadmin, Admin, Manager, Engineer, and Vendor.
2. WHEN a Superadmin accesses the role management interface, THE RBAC_System SHALL display all available roles with their associated permissions.
3. THE RBAC_System SHALL store role definitions in a `roles` database table with fields for id, name, description, is_system_role, and timestamps.
4. WHILE a role is marked as system role (is_system_role = true), THE RBAC_System SHALL prevent deletion of that role.
5. THE RBAC_System SHALL allow Superadmin to create custom roles with selected permissions.

### Requirement 2: Permission Definition and Structure

**User Story:** As a Superadmin, I want a structured permission system so that I can control access to specific features and actions.

#### Acceptance Criteria

1. THE RBAC_System SHALL organize permissions into the following module groups:
   - Masters (bank, customer, country, cities, states, zones)
   - Users
   - Survey
   - Installation
   - Inventory (alerts, assets, audit, dashboard, dispatch, material_masters, material_requests, products, repairs, reports, stock, transfer, warehouses)
2. THE RBAC_System SHALL store permissions in a `permissions` database table with fields for id, module, action, permission_key, and description.
3. THE RBAC_System SHALL support the following inventory permissions:
   - alerts.manage, alerts.read
   - assets.manage, assets.read, assets.update_status, assets.update_status_full
   - audit.read
   - dashboard.adv, dashboard.contractor, dashboard.engineer
   - dispatch.acknowledge, dispatch.create, dispatch.manage, dispatch.read, dispatch.update
   - material_masters.create, material_masters.delete, material_masters.edit, material_masters.view
   - material_requests.approve, material_requests.create, material_requests.receive, material_requests.view
   - products.create, products.delete, products.manage, products.read, products.update
   - repairs.complete, repairs.create, repairs.manage, repairs.read, repairs.update
   - reports.export, reports.read
   - stock.bulk_upload, stock.create, stock.manage, stock.read, stock.update
   - transfer.create, transfer.manage, transfer.read, transfer.update
   - warehouses.create, warehouses.delete, warehouses.manage, warehouses.read, warehouses.update
4. THE RBAC_System SHALL support master module permissions: masters.bank.manage, masters.bank.read, masters.customer.manage, masters.customer.read, masters.country.manage, masters.country.read, masters.cities.manage, masters.cities.read, masters.states.manage, masters.states.read, masters.zones.manage, masters.zones.read.
5. THE RBAC_System SHALL support user module permissions: users.create, users.read, users.update, users.delete, users.manage_roles.

### Requirement 3: Role-Permission Assignment

**User Story:** As a Superadmin, I want to assign permissions to roles so that users inherit appropriate access based on their role.

#### Acceptance Criteria

1. THE RBAC_System SHALL store role-permission mappings in a `role_permissions` junction table with role_id and permission_id fields.
2. WHEN a Superadmin assigns a permission to a role, THE RBAC_System SHALL immediately apply that permission to all users with that role.
3. THE RBAC_System SHALL provide a user interface displaying permissions grouped by module with checkboxes for assignment.
4. WHEN a permission is removed from a role, THE RBAC_System SHALL revoke that permission from all users with that role within 1 second.
5. THE RBAC_System SHALL prevent removal of core permissions from the Superadmin role.

### Requirement 4: User Creation and Role Assignment

**User Story:** As a Superadmin, I want to create users and assign them roles so that they can access the system with appropriate permissions.

#### Acceptance Criteria

1. THE RBAC_System SHALL extend the existing users table to include a role_id foreign key referencing the roles table.
2. WHEN a Superadmin creates a new user, THE RBAC_System SHALL require selection of exactly one role.
3. THE RBAC_System SHALL validate that username, email, and phone are unique before creating a user.
4. WHEN a user is created, THE RBAC_System SHALL hash the password using bcrypt with a cost factor of 12.
5. THE RBAC_System SHALL allow Superadmin to change a user's role at any time through the user management interface.

### Requirement 5: User-Specific Permission Override

**User Story:** As a Superadmin, I want to grant or revoke specific permissions for individual users so that I can handle exceptions to role-based access.

#### Acceptance Criteria

1. THE RBAC_System SHALL store user-specific permission overrides in a `user_permissions` table with user_id, permission_id, and is_granted fields.
2. WHEN checking user permissions, THE RBAC_System SHALL first check user-specific overrides, then fall back to role permissions.
3. THE RBAC_System SHALL display user-specific overrides distinctly from role-inherited permissions in the user interface.
4. WHEN a user-specific permission override is set to is_granted = false, THE RBAC_System SHALL deny that permission regardless of role assignment.
5. THE RBAC_System SHALL log all permission override changes in the audit_logs table.

### Requirement 6: Permission Verification Middleware

**User Story:** As a system administrator, I want automatic permission checking on all protected routes so that unauthorized access is prevented.

#### Acceptance Criteria

1. THE RBAC_System SHALL provide a PermissionMiddleware class that verifies user permissions before allowing access to protected resources.
2. WHEN a user without the required permission attempts to access a protected resource, THE RBAC_System SHALL return HTTP 403 status with an error message.
3. IF a user session is invalid or expired, THEN THE RBAC_System SHALL redirect to the login page with session_expired parameter.
4. THE RBAC_System SHALL cache user permissions in the session for performance, refreshing on role or permission changes.
5. WHEN an AJAX request fails permission check, THE RBAC_System SHALL return a JSON response with success: false and appropriate error message.

### Requirement 7: Authentication Enhancement

**User Story:** As a user, I want secure authentication so that my account and data are protected.

#### Acceptance Criteria

1. THE RBAC_System SHALL update the Auth class to load user role and permissions upon successful login.
2. WHEN a user logs in, THE RBAC_System SHALL store user_id, username, role_id, role_name, and permissions array in the session.
3. THE RBAC_System SHALL provide hasPermission($permissionKey) method in the Auth class for permission checking.
4. THE RBAC_System SHALL provide hasAnyPermission(array $permissionKeys) method for checking multiple permissions with OR logic.
5. THE RBAC_System SHALL provide hasAllPermissions(array $permissionKeys) method for checking multiple permissions with AND logic.

### Requirement 8: Migration from Menu-Based Access

**User Story:** As a system administrator, I want to migrate from the current menu-based access control to RBAC so that the transition is seamless.

#### Acceptance Criteria

1. THE RBAC_System SHALL create database migration scripts to add roles, permissions, role_permissions, and user_permissions tables.
2. THE RBAC_System SHALL migrate existing admin users to the Admin role and vendor users to the Vendor role.
3. THE RBAC_System SHALL map existing menu_items to corresponding permissions during migration.
4. WHEN migration completes, THE RBAC_System SHALL retain backward compatibility with existing user_menu_permissions for a deprecation period.
5. THE RBAC_System SHALL provide a rollback script to restore the previous access control system if needed.

### Requirement 9: Role-Based UI Rendering

**User Story:** As a user, I want to see only the menu items and features I have access to so that the interface is relevant to my role.

#### Acceptance Criteria

1. THE RBAC_System SHALL filter navigation menu items based on user permissions.
2. WHEN rendering action buttons (create, edit, delete), THE RBAC_System SHALL check corresponding permissions before displaying.
3. THE RBAC_System SHALL provide Blade/PHP helper functions: can($permission), canAny($permissions), canAll($permissions).
4. WHILE a user lacks read permission for a module, THE RBAC_System SHALL hide that module from the navigation entirely.
5. THE RBAC_System SHALL disable form fields and buttons for actions the user cannot perform rather than hiding them when partial access exists.

### Requirement 10: Audit and Logging

**User Story:** As a Superadmin, I want to track all permission-related changes so that I can audit access control modifications.

#### Acceptance Criteria

1. WHEN a role is created, modified, or deleted, THE RBAC_System SHALL log the action with old and new values in audit_logs.
2. WHEN permissions are assigned or removed from a role, THE RBAC_System SHALL log the change with role_id and affected permission_ids.
3. WHEN a user's role is changed, THE RBAC_System SHALL log the change with user_id, old_role_id, and new_role_id.
4. THE RBAC_System SHALL store the acting user's ID, IP address, and timestamp for all audit log entries.
5. THE RBAC_System SHALL provide an audit log viewer accessible only to users with audit.read permission.

### Requirement 11: Default Role Permissions

**User Story:** As a system administrator, I want predefined permission sets for each role so that the system is usable immediately after setup.

#### Acceptance Criteria

1. THE RBAC_System SHALL assign all permissions to the Superadmin role by default.
2. THE RBAC_System SHALL assign the following permissions to Admin role: all masters permissions, users.read, users.create, users.update, all inventory permissions except audit.read.
3. THE RBAC_System SHALL assign the following permissions to Manager role: masters read permissions, users.read, inventory dashboard, reports, and approval permissions.
4. THE RBAC_System SHALL assign the following permissions to Engineer role: survey and installation related permissions, material_requests.create, material_requests.view, stock.read.
5. THE RBAC_System SHALL assign the following permissions to Vendor role: limited site access, material_requests.view, stock.read, dispatch.acknowledge.

### Requirement 12: API Permission Enforcement

**User Story:** As a developer, I want API endpoints to enforce permissions so that the API is secure.

#### Acceptance Criteria

1. THE RBAC_System SHALL validate JWT tokens and extract user permissions for API requests.
2. WHEN an API request lacks required permission, THE RBAC_System SHALL return HTTP 403 with JSON error response.
3. THE RBAC_System SHALL provide an ApiPermissionMiddleware class for protecting API routes.
4. THE RBAC_System SHALL include required_permission metadata in API documentation.
5. IF an API token is expired or invalid, THEN THE RBAC_System SHALL return HTTP 401 with authentication_required error.
