# Database Configuration Setup

## Important Security Note
**Never commit actual production database credentials to your repository!**

## Step 1: Update Production Database Credentials

Edit `config/database.php` and update the production section with your actual database credentials:

```php
case 'production':
    return [
        'host' => 'localhost', // or your production DB host
        'name' => 'your_actual_production_database_name',
        'user' => 'your_actual_production_database_user', 
        'pass' => 'your_actual_production_database_password',
        'charset' => 'utf8mb4'
    ];
```

## Step 2: Common Production Database Patterns

### For cPanel/Shared Hosting:
- **Database Name**: Usually `username_dbname` (e.g., `u444388293_site_mgmt`)
- **Database User**: Usually `username_dbuser` (e.g., `u444388293_admin`)
- **Host**: Usually `localhost`

### For VPS/Dedicated Server:
- **Host**: `localhost` or specific IP
- **Database Name**: Your chosen database name
- **User**: Your created database user
- **Password**: Your secure password

## Step 3: Test Database Connection

Use the built-in test functionality:

```php
// Test database connection
$db = Database::getInstance();
$connectionInfo = $db->getConnectionInfo();
$isConnected = $db->testConnection();
```

## Step 4: Environment-Specific Features

### Development Environment:
- ✅ Detailed error messages
- ✅ Connection logging
- ✅ Automatic connection testing

### Production Environment:
- ✅ Secure error messages (no DB details exposed)
- ✅ SSL support (if needed)
- ✅ Enhanced security options

## Step 5: Database Migration

If you need to migrate from your current database to the new environment-aware setup:

1. **Backup your current database**
2. **Update the credentials in database.php**
3. **Test the connection**
4. **Deploy to production**

## Security Best Practices

1. **Use strong passwords** for production database users
2. **Limit database user permissions** to only what's needed
3. **Use SSL connections** if available
4. **Regular database backups**
5. **Monitor database access logs**

## Troubleshooting

### Connection Failed Error:
1. Check database credentials
2. Verify database exists
3. Check user permissions
4. Confirm host/port settings

### Permission Denied:
1. Verify database user has correct permissions
2. Check if user can connect from the application server
3. Review database firewall settings