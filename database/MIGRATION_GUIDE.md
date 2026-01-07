# RBAC Data Migration Guide

This guide explains how to migrate existing users and data to the new RBAC system.

## Quick Start

### 1. Setup RBAC System

```bash
# Create RBAC tables and seed default data
php database/setup_rbac_complete.php
```

This will:
- Create roles, permissions, role_permissions, user_permissions, and refresh_tokens tables
- Add role_id column to users table
- Seed 5 default roles (Superadmin, Admin, Manager, Engineer, Vendor)
- Seed all permissions for all modules
- Assign permissions to roles

### 2. Migrate Existing Users

```bash
# Migrate existing users to RBAC system
php database/migrate_existing_users.php
```

This will:
- Create a default Superadmin user (username: `superadmin`, password: `superadmin123`)
- Map all existing 'admin' users to Admin role (role_id = 2)
- Map all existing 'vendor' users to Vendor role (role_id = 5)
- Migrate vendor_permissions to user_permissions table

### 3. Verify Migration

```bash
# Check migration status
php database/migrate_existing_users.php check
```

### 4. Test Backward Compatibility

```bash
# Run compatibility tests
php database/test_backward_compatibility.php
```

## Migration Scripts

### migrate_existing_users.php

Main migration script for user data.

**Commands:**
- `php database/migrate_existing_users.php` - Run migration
- `php database/migrate_existing_users.php check` - Check status
- `php database/migrate_existing_users.php rollback` - Rollback migration

**What it does:**
1. Verifies RBAC tables exist
2. Creates Superadmin user if not exists
3. Maps admin users to Admin role (role_id = 2)
4. Maps vendor users to Vendor role (role_id = 5)
5. Migrates vendor_permissions to user_permissions

**Output Example:**
```
==============================================
User Data Migration to RBAC System
==============================================

Step 1: Verifying RBAC tables...
✓ RBAC tables verified

Step 2: Creating default Superadmin user...
✓ Created new Superadmin user (ID: 1)
  Username: superadmin
  Password: superadmin123
  Email: superadmin@example.com

Step 3: Mapping admin users to Admin role (role_id = 2)...
✓ Mapped 5 admin user(s) to Admin role (role_id = 2)
  - admin (ID: 2)
  - john_admin (ID: 3)
  ...

Step 4: Mapping vendor users to Vendor role (role_id = 5)...
✓ Mapped 10 vendor user(s) to Vendor role (role_id = 5)
  - vendor1 (ID: 10)
  - vendor2 (ID: 11)
  ...

Step 5: Migrating vendor_permissions to user_permissions...
✓ Migrated 25 vendor permission(s) to user_permissions

==============================================
✓ Migration completed successfully!
==============================================

Summary:
  - Superadmin created: Yes
  - Admin users migrated: 5
  - Vendor users migrated: 10
  - Permissions migrated: 25
  - Total operations: 5
```

### auth_compatibility.php

Backward compatibility layer for smooth transition.

**Commands:**
- `php config/auth_compatibility.php status` - Check migration status
- `php config/auth_compatibility.php stats` - View deprecation statistics
- `php config/auth_compatibility.php clear-log` - Clear deprecation log

**Features:**
- Automatic role/role_id synchronization
- Deprecation logging for old methods
- Hybrid permission checking (RBAC + legacy)
- Migration status tracking

### test_backward_compatibility.php

Test suite for backward compatibility features.

**Command:**
```bash
php database/test_backward_compatibility.php
```

**Tests:**
1. RBAC system availability
2. Migration status
3. Role synchronization
4. Deprecation logging
5. Deprecation statistics

## User Role Mapping

| Legacy Role | New Role ID | New Role Name | Description |
|-------------|-------------|---------------|-------------|
| admin       | 1           | Superadmin    | Full system access (manually created) |
| admin       | 2           | Admin         | Administrative access (default for admin users) |
| admin       | 3           | Manager       | Managerial access (manual assignment) |
| admin       | 4           | Engineer      | Technical access (manual assignment) |
| vendor      | 5           | Vendor        | External partner access |

## Permission Migration

Old vendor permissions are mapped to new RBAC permissions:

| Old Permission Key      | New Permission Key                |
|------------------------|-----------------------------------|
| view_sites             | survey.read                       |
| update_progress        | installation.update               |
| view_masters           | masters.bank.read                 |
| view_reports           | inventory.reports.read            |
| view_inventory         | inventory.stock.read              |
| view_material_requests | inventory.material_requests.view  |

## Backward Compatibility

### Automatic Synchronization

The User model automatically syncs role and role_id:

```php
// When creating a user with role_id
$user->create([
    'username' => 'newuser',
    'role_id' => 2  // Automatically sets role = 'admin'
]);

// When creating a user with legacy role
$user->create([
    'username' => 'newuser',
    'role' => 'vendor'  // Automatically sets role_id = 5
]);

// When updating role_id
$user->update($userId, [
    'role_id' => 5  // Automatically updates role = 'vendor'
]);
```

### Deprecation Logging

Enable logging to track deprecated method usage:

```php
require_once __DIR__ . '/config/auth_compatibility.php';
AuthCompatibility::setDeprecationLogging(true);

// This will log a deprecation warning
Auth::requireRole('admin');  // DEPRECATED

// Use this instead
ApiPermissionMiddleware::require('users.manage');
```

## Rollback

If you need to rollback the migration:

```bash
php database/migrate_existing_users.php rollback
```

This will:
- Clear role_id from all users (except Superadmin)
- Remove Superadmin user
- Clear user_permissions table

**Note:** This does NOT remove RBAC tables. To completely remove RBAC:

```bash
php database/setup_rbac_system.php drop
```

## Troubleshooting

### Issue: "RBAC tables not found"

**Solution:**
```bash
php database/setup_rbac_complete.php
```

### Issue: "No users migrated"

**Cause:** Users already have role_id set

**Check:**
```bash
php database/migrate_existing_users.php check
```

### Issue: "Permission checks failing"

**Debug steps:**
1. Check RBAC availability: `php config/auth_compatibility.php status`
2. Verify user migration: `php database/migrate_existing_users.php check`
3. Check deprecation log: `php config/auth_compatibility.php stats`

### Issue: "Superadmin user already exists"

**Behavior:** Migration will skip creating a new Superadmin and use the existing one

**To update existing user to Superadmin:**
```sql
UPDATE users SET role_id = 1 WHERE username = 'your_admin_username';
```

## Best Practices

1. **Backup First**: Always backup your database before migration
2. **Test Environment**: Run migration in test environment first
3. **Check Status**: Verify migration status after completion
4. **Monitor Logs**: Enable deprecation logging during transition
5. **Incremental Update**: Update code module by module
6. **Keep Compatibility**: Don't remove legacy columns until fully migrated

## Migration Checklist

- [ ] Backup database
- [ ] Run RBAC setup: `php database/setup_rbac_complete.php`
- [ ] Verify tables created: `php database/setup_rbac_system.php check`
- [ ] Run user migration: `php database/migrate_existing_users.php`
- [ ] Verify migration: `php database/migrate_existing_users.php check`
- [ ] Test compatibility: `php database/test_backward_compatibility.php`
- [ ] Enable deprecation logging in application
- [ ] Test application functionality
- [ ] Monitor deprecation stats: `php config/auth_compatibility.php stats`
- [ ] Update code to use RBAC methods
- [ ] Verify no deprecated usage: `php config/auth_compatibility.php stats`
- [ ] Document any custom role assignments needed

## Next Steps

After successful migration:

1. **Update Application Code**: Replace deprecated Auth methods with RBAC equivalents
2. **Assign Custom Roles**: Assign Manager/Engineer roles as needed
3. **Configure Permissions**: Set up user-specific permission overrides
4. **Test Thoroughly**: Verify all functionality works with new system
5. **Monitor**: Keep deprecation logging enabled for a transition period

## Support Files

- `database/migrate_existing_users.php` - Main migration script
- `config/auth_compatibility.php` - Backward compatibility layer
- `database/test_backward_compatibility.php` - Test suite
- `database/BACKWARD_COMPATIBILITY.md` - Detailed compatibility documentation
- `models/User.php` - Updated with auto-sync functionality

## Default Credentials

After migration, you can log in with:

**Superadmin:**
- Username: `superadmin`
- Password: `superadmin123`
- Email: `superadmin@example.com`

**Existing Users:**
- All existing users retain their original credentials
- Their roles are automatically mapped to RBAC roles
