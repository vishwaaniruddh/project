# Implementation Plan

- [x] 1. Database Schema Setup






  - [x] 1.1 Create migration script for RBAC tables

    - Create `roles` table with id, name, display_name, description, is_system_role, timestamps
    - Create `permissions` table with id, module, action, permission_key, display_name, description
    - Create `role_permissions` junction table with role_id, permission_id foreign keys
    - Create `user_permissions` table for user-specific overrides
    - Create `refresh_tokens` table for JWT refresh token management
    - Add `role_id` column to existing `users` table with foreign key constraint
    - _Requirements: 2.2, 3.1, 4.1, 5.1_


  - [x] 1.2 Create seed script for default roles and permissions

    - Insert 5 default roles: superadmin, admin, manager, engineer, vendor
    - Insert all permissions for masters module (12 permissions)
    - Insert all permissions for users module (5 permissions)
    - Insert all permissions for inventory module (47 permissions)
    - Insert all permissions for survey module (5 permissions)
    - Insert all permissions for installation module (6 permissions)
    - _Requirements: 2.1, 2.3, 2.4, 2.5_


  - [x] 1.3 Create default role-permission assignments

    - Assign all permissions to superadmin role
    - Assign admin-level permissions to admin role
    - Assign manager-level permissions to manager role
    - Assign engineer-level permissions to engineer role
    - Assign vendor-level permissions to vendor role
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5_

- [x] 2. Core Models Implementation







  - [x] 2.1 Create Role model

    - Implement `findByName()` method
    - Implement `getPermissions()` method to fetch role's permissions
    - Implement `assignPermission()` and `revokePermission()` methods
    - Implement `syncPermissions()` for bulk permission updates
    - Implement `isSystemRole()` check method
    - Implement `getAllWithPermissionCount()` for listing
    - _Requirements: 1.1, 1.2, 1.4, 3.2_

  - [x] 2.2 Create Permission model


    - Implement `findByKey()` method for permission lookup
    - Implement `getByModule()` to filter by module
    - Implement `getAllGroupedByModule()` for UI display
    - Implement `getRolesWithPermission()` for reverse lookup
    - _Requirements: 2.1, 2.2_

  - [x] 2.3 Create UserPermission model


    - Implement `getUserOverrides()` to get user-specific permissions
    - Implement `setOverride()` to grant/revoke specific permission
    - Implement `removeOverride()` to clear override
    - Implement `getEffectivePermissions()` combining role + overrides
    - _Requirements: 5.1, 5.2, 5.4_

- [x] 3. JWT and Token Services





  - [x] 3.1 Update JWTHelper class


    - Add `createUserToken()` method with permissions in payload
    - Update `encode()` to support custom expiry
    - Update `decode()` with proper error handling
    - Add `isExpired()` check method
    - Add `getPayload()` method for token inspection
    - _Requirements: 7.2, 12.1_


  - [x] 3.2 Create TokenService

    - Implement `generateAccessToken()` with user and permissions
    - Implement `generateRefreshToken()` with database storage
    - Implement `validateToken()` for token verification
    - Implement `refreshAccessToken()` using refresh token
    - Implement `invalidateToken()` for logout
    - _Requirements: 7.1, 7.2_


  - [x] 3.3 Create PermissionService

    - Implement `hasPermission()` checking user overrides then role
    - Implement `hasAnyPermission()` with OR logic
    - Implement `hasAllPermissions()` with AND logic
    - Implement `getUserPermissions()` returning full permission list
    - Implement `getPermissionKeys()` returning array of keys
    - Implement `checkTokenPermissions()` for JWT-based checks
    - _Requirements: 7.3, 7.4, 7.5_

- [x] 4. Middleware Implementation






  - [x] 4.1 Create JWTAuthMiddleware

    - Implement `authenticate()` to validate Bearer token
    - Implement `getTokenFromHeader()` to extract token
    - Implement `getCurrentUser()` returning user from token
    - Implement `getCurrentUserId()` helper method
    - Handle expired/invalid token responses (401)
    - _Requirements: 6.3, 12.5_


  - [x] 4.2 Create ApiPermissionMiddleware

    - Implement `require()` for single permission check
    - Implement `requireAny()` for OR permission check
    - Implement `requireAll()` for AND permission check
    - Return proper 403 JSON response on denial
    - Include required_permission in error response
    - _Requirements: 6.1, 6.2, 6.5, 12.2_

- [x] 5. API Controllers






  - [x] 5.1 Create AuthController

    - Implement `login()` - validate credentials, return JWT + permissions
    - Implement `logout()` - invalidate refresh token
    - Implement `refresh()` - issue new access token
    - Implement `me()` - return current user info with permissions
    - _Requirements: 7.1, 7.2_


  - [x] 5.2 Create RolesController

    - Implement `index()` - list all roles with permission counts
    - Implement `show()` - get role with permissions
    - Implement `store()` - create new role (superadmin only)
    - Implement `update()` - update role details
    - Implement `destroy()` - delete non-system role
    - Implement `permissions()` - get role's permissions
    - Implement `updatePermissions()` - sync role permissions
    - _Requirements: 1.2, 1.4, 1.5, 3.2, 3.3, 3.4_


  - [x] 5.3 Create PermissionsController

    - Implement `index()` - list all permissions
    - Implement `grouped()` - return permissions grouped by module
    - _Requirements: 2.1, 3.3_


  - [x] 5.4 Update UsersController for RBAC

    - Update `store()` to require role_id selection
    - Update `update()` to handle role changes
    - Add `changeRole()` method for role assignment
    - Add `getPermissions()` to return user's effective permissions
    - Add `setPermissionOverrides()` for user-specific permissions
    - _Requirements: 4.2, 4.5, 5.2, 5.3_

- [x] 6. API Routes Setup






  - [x] 6.1 Create API route files

    - Create `api/auth.php` for authentication endpoints
    - Create `api/roles.php` for role management endpoints
    - Create `api/permissions.php` for permission endpoints
    - Update `api/users.php` with new RBAC endpoints
    - Add middleware protection to all routes
    - _Requirements: 12.1, 12.2, 12.3_

- [-] 7. Audit Logging




  - [x] 7.1 Create AuditService









    - Implement `log()` method for recording changes
    - Implement `getAuditLogs()` with filtering and pagination
    - Implement `getUserActivity()` for user-specific logs
    - _Requirements: 10.1, 10.2, 10.3, 10.4_

  - [x] 7.2 Integrate audit logging into RBAC operations









    - Log role creation, update, deletion
    - Log permission assignment/revocation
    - Log user role changes
    - Log user permission overrides
    - _Requirements: 5.5, 10.1, 10.2, 10.3_

- [x] 8. Data Migration






  - [x] 8.1 Create migration script for existing users

    - Map existing 'admin' users to Admin role (role_id = 2)
    - Map existing 'vendor' users to Vendor role (role_id = 5)
    - Create default Superadmin user if not exists
    - Migrate vendor_permissions to user_permissions table
    - _Requirements: 8.2, 8.3_

  - [x] 8.2 Create backward compatibility layer


    - Keep legacy role column functional during transition
    - Add deprecation logging for old permission checks
    - _Requirements: 8.4_

- [x] 9. Update Existing User Model



  - [x] 9.1 Modify User model for RBAC


    - Add `role_id` to fillable fields
    - Add `getRole()` method to fetch role details
    - Add `getPermissions()` method using PermissionService
    - Update `validateUserData()` to validate role_id
    - Update `getAllWithPagination()` to include role info
    - _Requirements: 4.1, 4.3, 4.4_

- [x] 10. Testing





  - [x] 10.1 Write unit tests for core services


    - Test TokenService token generation and validation
    - Test PermissionService permission checks
    - Test RoleService CRUD operations
    - _Requirements: 6.4, 7.3, 7.4, 7.5_

  - [x] 10.2 Write integration tests for API endpoints


    - Test auth endpoints (login, logout, refresh)
    - Test role management endpoints
    - Test permission middleware behavior
    - _Requirements: 6.1, 6.2, 12.2_
