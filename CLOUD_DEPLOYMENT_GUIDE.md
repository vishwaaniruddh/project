# 🌐 Cloud Deployment Guide
## Site Installation Management System

**Issue:** Your cloud server runs PHP 8.1.32, but Composer dependencies require PHP 8.3+

**Solution:** Deploy without problematic Composer dependencies

---

## 🚀 Quick Fix for Cloud Deployment

### **Option 1: Remove Composer Dependencies (Recommended)**

1. **Delete the vendor folder** from your cloud server:
   ```bash
   rm -rf vendor/
   ```

2. **Delete composer.lock** if it exists:
   ```bash
   rm -f composer.lock
   ```

3. **The application will work without Composer** - all core functionality is built with native PHP

### **Option 2: Update Composer Dependencies**

1. **On your local machine**, update `composer.json`:
   ```json
   {
       "require": {
           "php": ">=7.4",
           "phpoffice/phpspreadsheet": "^1.29"
       }
   }
   ```

2. **Run composer update**:
   ```bash
   composer update
   ```

3. **Upload the updated vendor folder** to your cloud server

---

## 📁 Files to Upload to Cloud Server

### **Essential Files (Always Required):**
```
├── admin/                 # Admin panel
├── vendor/               # Vendor portal  
├── config/               # Configuration files
├── models/               # Database models
├── includes/             # Layout files
├── assets/               # CSS, JS, images
├── database/             # Database scripts
├── auth/                 # Authentication
├── api/                  # API endpoints
├── controllers/          # Controllers
├── middleware/           # Middleware
├── uploads/              # Upload directory (create if missing)
├── index.php             # Main landing page
└── .htaccess             # URL rewriting (if using Apache)
```

### **Optional Files (Can be excluded):**
```
├── vendor/               # Composer dependencies (optional)
├── composer.json         # Composer config (optional)
├── composer.lock         # Composer lock file (optional)
├── testing/              # Testing scripts (optional)
├── .kiro/                # Development specs (optional)
└── *.md files            # Documentation (optional)
```

---

## ⚙️ Cloud Server Configuration

### **1. Update Database Configuration**
Edit `config/database.php`:
```php
define('DB_HOST', 'your-cloud-db-host');
define('DB_NAME', 'your-cloud-db-name');
define('DB_USER', 'your-cloud-db-user');
define('DB_PASS', 'your-cloud-db-password');
```

### **2. Update Base URL**
Edit `config/constants.php`:
```php
define('BASE_URL', 'https://yourdomain.com/project');
define('APP_NAME', 'Site Installation Management');
```

### **3. Set Directory Permissions**
```bash
chmod -R 755 /path/to/your/project/
chmod -R 777 /path/to/your/project/uploads/
chmod -R 777 /path/to/your/project/auth/logs/
```

### **4. Create Upload Directories**
```bash
mkdir -p uploads/sites/
mkdir -p uploads/vendors/
mkdir -p uploads/surveys/
mkdir -p uploads/installations/
mkdir -p auth/logs/
```

---

## 🧪 Testing on Cloud Server

### **1. Check Compatibility**
Upload and run: `php check_compatibility.php`

### **2. Test Database Connection**
Visit: `https://yourdomain.com/project/config/database.php`
(Should show connection status)

### **3. Test Application**
Visit: `https://yourdomain.com/project/`

### **4. Test Login**
- Admin: `admin_test` / `admin123`
- Vendor: `vendor_test1` / `vendor123`

---

## 🔧 Features That Work Without Composer

### **✅ Fully Functional:**
- Complete admin panel
- Vendor portal
- User authentication
- Site management
- Survey system
- Material requests
- Installation tracking
- Basic CSV import/export
- All reports and analytics
- Responsive design
- Database operations

### **⚠️ Limited Without Composer:**
- Advanced Excel file processing (falls back to CSV)
- Complex spreadsheet operations (basic CSV used instead)

---

## 🚨 Troubleshooting Common Issues

### **Issue: "Composer detected issues"**
**Solution:** Delete the `vendor/` folder entirely

### **Issue: "Class not found"**
**Solution:** Check if you're trying to use Composer classes. Use native PHP alternatives.

### **Issue: "Permission denied"**
**Solution:** Set proper directory permissions (755 for files, 777 for upload directories)

### **Issue: "Database connection failed"**
**Solution:** Update `config/database.php` with correct cloud database credentials

### **Issue: "File upload not working"**
**Solution:** Create upload directories and set write permissions

---

## 📋 Cloud Deployment Checklist

- [ ] Remove or update Composer dependencies
- [ ] Upload all essential files
- [ ] Update database configuration
- [ ] Update base URL configuration
- [ ] Set proper file permissions
- [ ] Create upload directories
- [ ] Test database connection
- [ ] Test admin login
- [ ] Test vendor login
- [ ] Test file uploads
- [ ] Test CSV exports
- [ ] Verify all core functionality

---

## 🎯 Immediate Action for Your Server

**Run this on your cloud server:**

```bash
# Navigate to your project directory
cd /path/to/your/project/

# Remove problematic Composer files
rm -rf vendor/
rm -f composer.lock

# Set permissions
chmod -R 755 .
chmod -R 777 uploads/
chmod -R 777 auth/logs/

# Create missing directories
mkdir -p uploads/sites uploads/vendors uploads/surveys uploads/installations auth/logs

# Test the application
php check_compatibility.php
```

**Then visit your application URL and test login functionality.**

---

Your application is designed to work perfectly without Composer dependencies. All core functionality uses native PHP, so removing the `vendor/` folder should resolve the PHP version compatibility issue immediately.

The system will automatically detect the absence of Composer dependencies and use built-in PHP alternatives for all operations.