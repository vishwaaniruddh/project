# Backward Compatibility Layer

This document explains the backward compatibility features implemented during the transition from the legacy role-based system to the new RBAC (Role-Based Access Control) system.

## Overview

The backward compatibility layer ensures that existing code continues to work during the migration period while providing deprecation warnings to help identify code that needs to be updated.

## Features

### 1. Legacy Role Column Sync

The system automatically synchronizes the legacy `role` column with the new `role_id` column in both directions:

- **When `role_id` is updated**: The legacy `role` column is automatically updated
- **When `role` is updated**: The `role_id` column is automatically updated

This ensures that code using either the old or new system continues to work.

#### Role Mapping

| role_id | Role Name   | Legacy Role |
|---------|-------------|-------------|
| 1       | Superadmin  | admin       |
| 2       | Admin       | admin       |
| 3       | Manager     | admin       |
| 4       | Engineer    | admin       |
| 5       | Vendor      | vendor      |

### 2. Deprecation Logging

All uses of deprecated authentication methods are logged to help identify code that needs migration:

- **Log Location**: `logs/auth_deprecation.log`
- **Log Format**: `[timestamp] DEPRECATED: method - message (called from file:line)`

#### Deprecated Methods

The following methods are deprecated and will log warnings:

- `AuthCompatibility::requireRole()` - Use permission-based checks instead
- `AuthCompatibility::requireVendorPermission()` - Use `PermissionService::hasPermission()`
- `AuthCompatibility::hasLegacyVendorPermission()` - Use `PermissionService::hasPermission()`

### 3. Hybrid Permission Checking

The `AuthCompatibility::hasPermission()` method provides a transition path:

1. First tries the new RBAC system
2. Falls back to legacy vendor_permissions if RBAC fails
3. Logs deprecation warnings for legacy usage

## Usage

### Check Migration Status

```bash
php config/auth_compatibility.php status
```

Output:
```
RBAC System: ✓ Available
Users Migrated: 15
Users Not Migrated: 0
Legacy Permissions: Yes
```

### View Deprecation Statistics

```bash
php config/auth_compatibility.php stats
```

Output:
```
Total Deprecated Calls: 42

By Method:
  hasLegacyVendorPermission: 25 call(s)
  requireRole: 12 call(s)
  requireVendorPermission: 5 call(s)

By File:
  sites.php: 15 call(s)
  vendors.php: 10 call(s)
  dashboard.php: 8 call(s)
```

### Clear Deprecation Log

```bash
php config/auth_compatibility.php clear-log
```

### Test Backward Compatibility

```bash
php database/test_backward_compatibility.php
```

This runs a comprehensive test suite to verify:
- RBAC system availability
- Migration status
- Role synchronization
- Deprecation logging
- Statistics collection

## Migration Guide

### Step 1: Run Initial Setup

```bash
# Setup RBAC tables
php database/setup_rbac_complete.php

# Migrate existing users
php database/migrate_existing_users.php
```

### Step 2: Enable Deprecation Logging

In your application bootstrap or config file:

```php
require_once __DIR__ . '/config/auth_compatibility.php';
AuthCompatibility::setDeprecationLogging(true);
```

### Step 3: Identify Deprecated Usage

Run your application normally and check the deprecation log:

```bash
php config/auth_compatibility.php stats
```

### Step 4: Update Code

Replace deprecated methods with RBAC equivalents:

**Before:**
```php
Auth::requireRole('admin');
```

**After:**
```php
require_once __DIR__ . '/middleware/ApiPermissionMiddleware.php';
ApiPermissionMiddleware::require('users.manage');
```

**Before:**
```php
Auth::requireVendorPermission('view_sites');
```

**After:**
```php
require_once __DIR__ . '/services/PermissionService.php';
$permissionService = new PermissionService();
if (!$permissionService->hasPermission($userId, 'survey.read')) {
    // Handle permission denied
}
```

### Step 5: Verify Migration

```bash
# Check that all users are migrated
php database/migrate_existing_users.php check

# Verify no deprecated methods are in use
php config/auth_compatibility.php stats
```

### Step 6: Disable Deprecation Logging (Optional)

Once migration is complete:

```php
AuthCompatibility::setDeprecationLogging(false);
```

## API Reference

### AuthCompatibility Class

#### Static Methods

##### `setDeprecationLogging(bool $enabled): void`
Enable or disable deprecation logging.

##### `syncLegacyRole(int $userId, ?int $roleId): bool`
Sync role_id to legacy role column.

##### `syncRoleId(int $userId, string $legacyRole): bool`
Sync legacy role to role_id column.

##### `hasPermission(int $userId, string $permissionKey): bool`
Check permission using RBAC or legacy system.

##### `isRBACAvailable(): bool`
Check if RBAC system is set up and available.

##### `getMigrationStatus(): array`
Get detailed migration status information.

##### `getDeprecationLog(int $limit = 100): array`
Get recent deprecation log entries.

##### `clearDeprecationLog(): bool`
Clear the deprecation log file.

##### `getDeprecationStats(): array`
Get statistics about deprecated method usage.

## Troubleshooting

### Issue: Users not migrated

**Solution:**
```bash
php database/migrate_existing_users.php
```

### Issue: RBAC tables not found

**Solution:**
```bash
php database/setup_rbac_complete.php
```

### Issue: Deprecation log growing too large

**Solution:**
```bash
php config/auth_compatibility.php clear-log
```

### Issue: Permission checks failing

**Check:**
1. Verify RBAC tables exist: `php config/auth_compatibility.php status`
2. Verify users are migrated: `php database/migrate_existing_users.php check`
3. Check deprecation log for errors: `php config/auth_compatibility.php stats`

## Timeline

### Phase 1: Setup (Week 1)
- ✓ Create RBAC tables
- ✓ Migrate existing users
- ✓ Enable backward compatibility

### Phase 2: Transition (Weeks 2-4)
- Enable deprecation logging
- Identify deprecated usage
- Update code incrementally
- Test thoroughly

### Phase 3: Cleanup (Week 5+)
- Verify all code migrated
- Disable deprecation logging
- Consider removing legacy columns (optional)

## Best Practices

1. **Enable logging early**: Turn on deprecation logging as soon as RBAC is set up
2. **Monitor regularly**: Check deprecation stats weekly during transition
3. **Update incrementally**: Migrate one module at a time
4. **Test thoroughly**: Use `test_backward_compatibility.php` after each change
5. **Keep logs**: Archive deprecation logs before clearing for audit purposes

## Support

For issues or questions:
1. Check this documentation
2. Run diagnostic scripts: `php config/auth_compatibility.php status`
3. Review deprecation logs: `php config/auth_compatibility.php stats`
4. Test compatibility: `php database/test_backward_compatibility.php`
