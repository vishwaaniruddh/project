# Site Installation Management System
## Complete Project Documentation

---

**Project Name:** Site Installation Management System  
**Company:** Karvy Technologies Pvt Ltd  
**Version:** 1.0  
**Date:** November 2025  
**Technology Stack:** PHP, MySQL, JavaScript, Tailwind CSS  

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Features & Modules](#features--modules)
4. [User Roles & Permissions](#user-roles--permissions)
5. [Database Design](#database-design)
6. [Installation Guide](#installation-guide)
7. [User Manual](#user-manual)
8. [API Documentation](#api-documentation)
9. [Security Features](#security-features)
10. [Testing & Quality Assurance](#testing--quality-assurance)
11. [Deployment Guide](#deployment-guide)
12. [Maintenance & Support](#maintenance--support)

---

## Project Overview

### Purpose
The Site Installation Management System is a comprehensive web-based application designed to streamline and manage the entire lifecycle of site installations. It provides a centralized platform for administrators and field vendors to coordinate site surveys, material management, installation tracking, and reporting.

### Key Objectives
- **Centralized Management:** Single platform for all installation-related activities
- **Real-time Tracking:** Live updates on installation progress and material usage
- **Efficient Communication:** Seamless coordination between admin and field teams
- **Data-Driven Decisions:** Comprehensive reporting and analytics
- **Mobile-Friendly:** Responsive design for field operations

### Target Users
- **System Administrators:** Complete system oversight and management
- **Field Vendors:** Site surveys, installations, and progress updates
- **Management:** Reports and analytics for decision making

---

## System Architecture

### Technology Stack

#### Backend
- **Language:** PHP 8.0+
- **Database:** MySQL 8.0+
- **Authentication:** JWT (JSON Web Tokens)
- **Session Management:** PHP Sessions with security enhancements

#### Frontend
- **Framework:** Vanilla JavaScript with modern ES6+ features
- **CSS Framework:** Tailwind CSS 3.0
- **UI Components:** Custom responsive components
- **Icons:** Heroicons and custom SVG icons

#### Security
- **Authentication:** Multi-factor authentication ready
- **Authorization:** Role-based access control (RBAC)
- **Data Protection:** Input validation, SQL injection prevention
- **Session Security:** Secure session handling with timeout

### Directory Structure
```
project/
├── admin/                  # Admin panel modules
│   ├── dashboard.php
│   ├── masters/           # Master data management
│   ├── sites/             # Site management
│   ├── vendors/           # Vendor management
│   ├── users/             # User management
│   ├── inventory/         # Inventory management
│   ├── boq/              # Bill of Quantities
│   ├── requests/         # Material requests
│   ├── surveys/          # Site surveys
│   ├── installations/    # Installation tracking
│   └── reports/          # Reports and analytics
├── vendor/               # Vendor portal
│   ├── index.php
│   ├── sites/
│   ├── surveys/
│   ├── installations/
│   ├── inventory/
│   └── material-requests/
├── auth/                 # Authentication system
├── api/                  # REST API endpoints
├── config/               # Configuration files
├── includes/             # Shared components
├── models/               # Data models
├── controllers/          # Business logic
├── database/             # Database scripts
├── assets/               # Static assets
└── testing/              # Test files
```

---

## Features & Modules

### 1. Authentication & Authorization
- **Unified Login System:** Single login form handling both admin and vendor access
- **Role-Based Access:** Automatic redirection based on user roles
- **Session Management:** Secure session handling with auto-logout
- **Password Security:** Encrypted passwords with strength requirements

### 2. Master Data Management
- **Geographic Data:** Countries, states, cities, zones
- **Site Categories:** Different types of installation sites
- **User Management:** Admin and vendor user accounts
- **System Configuration:** Application settings and parameters

### 3. Site Management
- **Site Registration:** Complete site information management
- **Bulk Operations:** CSV import/export for mass site management
- **Site Delegation:** Assign sites to specific vendors
- **Status Tracking:** Real-time site status updates

### 4. Vendor Management
- **Vendor Profiles:** Comprehensive vendor information
- **Permission Management:** Granular access control
- **Performance Tracking:** Vendor performance metrics
- **Communication Tools:** Direct messaging and notifications

### 5. Survey Management
- **Site Surveys:** Detailed site assessment forms
- **Photo Documentation:** Image upload and management
- **Survey Approval:** Multi-level approval workflow
- **Survey Reports:** Automated report generation

### 6. Installation Management
- **Installation Tracking:** Real-time progress monitoring
- **Material Usage:** Track material consumption
- **Progress Updates:** Regular status updates from field
- **Quality Control:** Installation quality checkpoints

### 7. Inventory Management
- **Stock Management:** Real-time inventory tracking
- **Material Requests:** Vendor material request system
- **Dispatch Management:** Material dispatch tracking
- **Stock Reconciliation:** Regular stock audits

### 8. BOQ (Bill of Quantities)
- **BOQ Creation:** Detailed quantity specifications
- **Material Planning:** Automatic material calculations
- **Cost Estimation:** Budget planning and tracking
- **BOQ Approval:** Multi-level approval process

### 9. Reporting & Analytics
- **Dashboard Analytics:** Real-time system metrics
- **Custom Reports:** Flexible report generation
- **Export Options:** PDF, Excel, CSV exports
- **Performance Metrics:** KPI tracking and analysis

---

## User Roles & Permissions

### Administrator Role
**Full System Access**
- Master data management (countries, states, cities, zones)
- Site management (create, edit, delete, bulk operations)
- Vendor management (registration, permissions, performance)
- User management (create accounts, assign roles)
- Inventory management (stock control, dispatch approval)
- BOQ management (create, approve, modify)
- Survey oversight (review, approve, reject)
- Installation monitoring (track progress, quality control)
- Reports and analytics (all reports, system metrics)
- System configuration (settings, parameters)

### Vendor Role
**Field Operations Access**
- Site information (view assigned sites)
- Site surveys (conduct, submit, update)
- Installation management (update progress, material usage)
- Material requests (submit requests, track status)
- Inventory tracking (view allocated materials)
- Progress reporting (submit updates, photos)
- Profile management (update vendor information)

### Permission Matrix
| Feature | Admin | Vendor |
|---------|-------|--------|
| Master Data | Full | Read |
| Site Management | Full | Assigned Only |
| Vendor Management | Full | Own Profile |
| User Management | Full | None |
| Surveys | Full | Assigned Sites |
| Installations | Full | Assigned Sites |
| Inventory | Full | View/Request |
| BOQ | Full | View |
| Reports | Full | Limited |

---

## Database Design

### Core Tables

#### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'vendor') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    token TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### Sites Table
```sql
CREATE TABLE sites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_name VARCHAR(255) NOT NULL,
    site_code VARCHAR(50) UNIQUE NOT NULL,
    address TEXT,
    city_id INT,
    state_id INT,
    country_id INT,
    zone_id INT,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('pending', 'surveyed', 'approved', 'in_progress', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id),
    FOREIGN KEY (state_id) REFERENCES states(id),
    FOREIGN KEY (country_id) REFERENCES countries(id),
    FOREIGN KEY (zone_id) REFERENCES zones(id)
);
```

#### Site Surveys Table
```sql
CREATE TABLE site_surveys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    vendor_id INT NOT NULL,
    survey_date DATE,
    survey_status ENUM('pending', 'in_progress', 'completed', 'approved', 'rejected') DEFAULT 'pending',
    technical_feasibility ENUM('feasible', 'not_feasible', 'conditional'),
    power_availability ENUM('available', 'not_available', 'needs_setup'),
    network_connectivity ENUM('good', 'fair', 'poor', 'none'),
    site_accessibility ENUM('easy', 'moderate', 'difficult'),
    survey_notes TEXT,
    photos JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id),
    FOREIGN KEY (vendor_id) REFERENCES users(id)
);
```

#### Installations Table
```sql
CREATE TABLE installations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    site_id INT NOT NULL,
    vendor_id INT NOT NULL,
    installation_status ENUM('not_started', 'in_progress', 'completed', 'on_hold') DEFAULT 'not_started',
    start_date DATE,
    expected_completion_date DATE,
    actual_completion_date DATE,
    progress_percentage DECIMAL(5,2) DEFAULT 0.00,
    installation_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id),
    FOREIGN KEY (vendor_id) REFERENCES users(id)
);
```

#### Material Requests Table
```sql
CREATE TABLE material_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    vendor_id INT NOT NULL,
    site_id INT NOT NULL,
    request_date DATE NOT NULL,
    required_date DATE,
    status ENUM('pending', 'approved', 'dispatched', 'received', 'rejected') DEFAULT 'pending',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id),
    FOREIGN KEY (site_id) REFERENCES sites(id)
);
```

### Relationship Diagram
```
Users (Admin/Vendor)
    ↓
Site Delegations → Sites ← Site Surveys
    ↓                ↓
Installations ← Material Requests
    ↓                ↓
Material Usage → Inventory Items
```

---

## Installation Guide

### System Requirements

#### Server Requirements
- **Web Server:** Apache 2.4+ or Nginx 1.18+
- **PHP:** Version 8.0 or higher
- **Database:** MySQL 8.0+ or MariaDB 10.5+
- **Memory:** Minimum 512MB RAM (2GB recommended)
- **Storage:** Minimum 1GB free space

#### PHP Extensions Required
```
- mysqli or pdo_mysql
- json
- session
- curl
- gd or imagick (for image processing)
- mbstring
- openssl
```

### Installation Steps

#### 1. Download and Extract
```bash
# Download the project files
# Extract to your web server directory
unzip site-installation-management.zip
cd site-installation-management
```

#### 2. Database Setup
```sql
-- Create database
CREATE DATABASE site_management;

-- Create user (optional)
CREATE USER 'site_user'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON site_management.* TO 'site_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 3. Configuration
```php
// config/database.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'site_management');
define('DB_USER', 'site_user');
define('DB_PASS', 'secure_password');

// config/constants.php
define('BASE_URL', 'http://your-domain.com');
define('APP_NAME', 'Site Installation Management System');
```

#### 4. Run Database Scripts
```bash
# Execute database setup scripts in order
mysql -u site_user -p site_management < database/create_tables.sql
mysql -u site_user -p site_management < database/insert_master_data.sql
mysql -u site_user -p site_management < database/create_default_users.sql
```

#### 5. Set Permissions
```bash
# Set proper file permissions
chmod 755 -R .
chmod 777 -R logs/
chmod 777 -R uploads/
```

#### 6. Web Server Configuration

**Apache (.htaccess)**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

**Nginx**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

---

## User Manual

### Getting Started

#### First Login
1. Navigate to your installation URL
2. You'll be redirected to the login page automatically
3. Use default credentials:
   - **Admin:** admin@example.com / admin123
   - **Vendor:** vendor@example.com / vendor123
4. Change default passwords immediately after first login

#### Dashboard Overview

**Admin Dashboard**
- System overview with key metrics
- Recent activities and notifications
- Quick access to main modules
- Performance indicators and alerts

**Vendor Dashboard**
- Assigned sites overview
- Pending surveys and installations
- Material request status
- Progress tracking tools

### Admin Operations

#### 1. Master Data Management
**Adding Countries/States/Cities:**
1. Navigate to Admin → Masters
2. Select the appropriate category
3. Click "Add New" button
4. Fill in required information
5. Save changes

**Bulk Import:**
1. Download the CSV template
2. Fill in data following the format
3. Upload the completed CSV file
4. Review and confirm import

#### 2. Site Management
**Creating New Sites:**
1. Go to Admin → Sites → Add New Site
2. Fill in site details:
   - Site name and code
   - Complete address
   - Geographic coordinates
   - Contact information
3. Assign to appropriate zone
4. Save site information

**Site Delegation:**
1. Select sites to delegate
2. Choose target vendor
3. Set delegation parameters
4. Confirm delegation

#### 3. Vendor Management
**Adding New Vendors:**
1. Navigate to Admin → Vendors → Add Vendor
2. Enter vendor details:
   - Company information
   - Contact details
   - Service areas
   - Capabilities
3. Set permissions and access levels
4. Generate login credentials

**Managing Permissions:**
1. Select vendor from list
2. Click "Manage Permissions"
3. Configure module access
4. Set site-specific permissions
5. Save changes

### Vendor Operations

#### 1. Site Surveys
**Conducting Surveys:**
1. Go to Vendor → Surveys
2. Select assigned site
3. Fill survey form:
   - Technical feasibility
   - Power availability
   - Network connectivity
   - Site accessibility
   - Detailed notes
4. Upload photos
5. Submit for approval

**Survey Updates:**
1. Access pending surveys
2. Edit survey details
3. Add additional information
4. Resubmit if required

#### 2. Installation Management
**Starting Installation:**
1. Navigate to Vendor → Installations
2. Select approved site
3. Update installation status
4. Set expected completion date
5. Begin progress tracking

**Progress Updates:**
1. Access ongoing installations
2. Update progress percentage
3. Add work completion notes
4. Upload progress photos
5. Submit material usage data

#### 3. Material Requests
**Requesting Materials:**
1. Go to Vendor → Material Requests
2. Click "New Request"
3. Select site and materials needed
4. Specify quantities and urgency
5. Add justification notes
6. Submit request

**Tracking Requests:**
1. View request status
2. Check approval progress
3. Track dispatch information
4. Confirm material receipt

---

## API Documentation

### Authentication Endpoints

#### POST /api/login.php
**Purpose:** User authentication
**Request:**
```json
{
    "username": "user@example.com",
    "password": "password123",
    "role": "admin" // optional
}
```
**Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "user": {
        "id": 1,
        "username": "admin",
        "role": "admin"
    },
    "token": "jwt_token_here",
    "redirect": "/admin/dashboard.php"
}
```

#### POST /api/logout.php
**Purpose:** User logout
**Headers:** `Authorization: Bearer {token}`
**Response:**
```json
{
    "success": true,
    "message": "Logged out successfully"
}
```

### Site Management Endpoints

#### GET /api/sites.php
**Purpose:** Retrieve sites list
**Parameters:**
- `page` (optional): Page number
- `limit` (optional): Items per page
- `status` (optional): Filter by status
- `vendor_id` (optional): Filter by vendor

**Response:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "site_name": "Site A",
            "site_code": "SA001",
            "status": "pending",
            "city": "Mumbai",
            "state": "Maharashtra"
        }
    ],
    "pagination": {
        "current_page": 1,
        "total_pages": 5,
        "total_records": 50
    }
}
```

#### POST /api/sites.php
**Purpose:** Create new site
**Request:**
```json
{
    "site_name": "New Site",
    "site_code": "NS001",
    "address": "Complete address",
    "city_id": 1,
    "state_id": 1,
    "country_id": 1,
    "latitude": 19.0760,
    "longitude": 72.8777
}
```

### Survey Management Endpoints

#### GET /api/surveys.php
**Purpose:** Get surveys list
**Parameters:**
- `site_id` (optional): Filter by site
- `vendor_id` (optional): Filter by vendor
- `status` (optional): Filter by status

#### POST /api/surveys.php
**Purpose:** Submit new survey
**Request:**
```json
{
    "site_id": 1,
    "survey_date": "2025-11-05",
    "technical_feasibility": "feasible",
    "power_availability": "available",
    "network_connectivity": "good",
    "site_accessibility": "easy",
    "survey_notes": "Site is ready for installation",
    "photos": ["photo1.jpg", "photo2.jpg"]
}
```

### Material Request Endpoints

#### GET /api/material-requests.php
**Purpose:** Get material requests
**Parameters:**
- `vendor_id` (optional): Filter by vendor
- `site_id` (optional): Filter by site
- `status` (optional): Filter by status

#### POST /api/material-requests.php
**Purpose:** Create material request
**Request:**
```json
{
    "site_id": 1,
    "required_date": "2025-11-10",
    "priority": "high",
    "materials": [
        {
            "item_id": 1,
            "quantity": 10,
            "unit": "pieces"
        }
    ],
    "notes": "Urgent requirement for installation"
}
```

---

## Security Features

### Authentication Security
- **Password Encryption:** BCrypt hashing with salt
- **JWT Tokens:** Secure token-based authentication
- **Session Management:** Secure session handling with timeout
- **Rate Limiting:** Login attempt restrictions
- **Account Lockout:** Temporary lockout after failed attempts

### Data Protection
- **Input Validation:** Server-side validation for all inputs
- **SQL Injection Prevention:** Prepared statements and parameterized queries
- **XSS Protection:** Output encoding and CSP headers
- **CSRF Protection:** Token-based CSRF prevention
- **File Upload Security:** Type validation and secure storage

### Access Control
- **Role-Based Access:** Granular permission system
- **Route Protection:** Middleware-based access control
- **API Security:** Token validation for API endpoints
- **Data Isolation:** Users can only access authorized data

### Security Headers
```php
// Implemented security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000');
header('Content-Security-Policy: default-src \'self\'');
```

### Audit Logging
- **User Actions:** All user activities logged
- **System Events:** Critical system events tracked
- **Error Logging:** Comprehensive error tracking
- **Access Logs:** Login/logout activities recorded

---

## Testing & Quality Assurance

### Testing Strategy

#### Unit Testing
- **Model Testing:** Database operations and business logic
- **Controller Testing:** API endpoints and request handling
- **Utility Testing:** Helper functions and utilities

#### Integration Testing
- **Database Integration:** Data persistence and retrieval
- **API Integration:** End-to-end API workflows
- **Authentication Flow:** Login/logout processes

#### User Acceptance Testing
- **Admin Workflows:** Complete admin user journeys
- **Vendor Workflows:** Field operations testing
- **Cross-browser Testing:** Multiple browser compatibility

### Test Coverage Areas

#### Functional Testing
- ✅ User authentication and authorization
- ✅ Site management operations
- ✅ Survey submission and approval
- ✅ Installation tracking
- ✅ Material request workflows
- ✅ Inventory management
- ✅ Report generation

#### Security Testing
- ✅ SQL injection prevention
- ✅ XSS attack prevention
- ✅ CSRF protection
- ✅ Authentication bypass attempts
- ✅ File upload security
- ✅ Session security

#### Performance Testing
- ✅ Database query optimization
- ✅ Page load times
- ✅ Concurrent user handling
- ✅ Large dataset operations
- ✅ File upload performance

### Quality Metrics
- **Code Coverage:** 85%+ for critical modules
- **Performance:** Page load < 3 seconds
- **Security:** Zero critical vulnerabilities
- **Compatibility:** 95%+ browser compatibility

---

## Deployment Guide

### Production Environment Setup

#### Server Configuration
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install required packages
sudo apt install apache2 mysql-server php8.0 php8.0-mysql php8.0-curl php8.0-gd php8.0-mbstring

# Configure Apache
sudo a2enmod rewrite
sudo systemctl restart apache2

# Secure MySQL
sudo mysql_secure_installation
```

#### SSL Certificate Setup
```bash
# Install Certbot
sudo apt install certbot python3-certbot-apache

# Obtain SSL certificate
sudo certbot --apache -d yourdomain.com

# Auto-renewal
sudo crontab -e
# Add: 0 12 * * * /usr/bin/certbot renew --quiet
```

#### Production Configuration
```php
// config/production.php
define('DEBUG_MODE', false);
define('ERROR_REPORTING', false);
define('LOG_LEVEL', 'ERROR');
define('SESSION_TIMEOUT', 3600); // 1 hour
define('ENABLE_CACHE', true);
```

### Deployment Checklist

#### Pre-deployment
- [ ] Code review completed
- [ ] All tests passing
- [ ] Security scan completed
- [ ] Performance testing done
- [ ] Database backup created
- [ ] SSL certificate configured

#### Deployment Steps
1. **Backup Current System**
   ```bash
   # Database backup
   mysqldump -u user -p database_name > backup_$(date +%Y%m%d).sql
   
   # File backup
   tar -czf files_backup_$(date +%Y%m%d).tar.gz /var/www/html/
   ```

2. **Deploy New Code**
   ```bash
   # Upload files
   rsync -avz --exclude='.git' ./ user@server:/var/www/html/
   
   # Set permissions
   sudo chown -R www-data:www-data /var/www/html/
   sudo chmod -R 755 /var/www/html/
   ```

3. **Database Migration**
   ```bash
   # Run migration scripts
   mysql -u user -p database_name < database/migrations/latest.sql
   ```

4. **Post-deployment Verification**
   - [ ] Application loads correctly
   - [ ] Login functionality works
   - [ ] Database connections successful
   - [ ] All modules accessible
   - [ ] SSL certificate active

### Monitoring Setup

#### Application Monitoring
```bash
# Install monitoring tools
sudo apt install htop iotop nethogs

# Setup log monitoring
tail -f /var/log/apache2/error.log
tail -f /var/www/html/logs/application.log
```

#### Performance Monitoring
- **Server Resources:** CPU, Memory, Disk usage
- **Database Performance:** Query execution times
- **Application Metrics:** Response times, error rates
- **User Activity:** Login patterns, feature usage

---

## Maintenance & Support

### Regular Maintenance Tasks

#### Daily Tasks
- Monitor system logs for errors
- Check database performance
- Verify backup completion
- Review security alerts

#### Weekly Tasks
- Update system packages
- Analyze performance metrics
- Review user feedback
- Check disk space usage

#### Monthly Tasks
- Security vulnerability scan
- Database optimization
- Performance tuning
- User access review

### Backup Strategy

#### Database Backups
```bash
#!/bin/bash
# Daily database backup script
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u backup_user -p$BACKUP_PASS site_management > /backups/db_$DATE.sql
gzip /backups/db_$DATE.sql

# Keep only last 30 days
find /backups -name "db_*.sql.gz" -mtime +30 -delete
```

#### File Backups
```bash
#!/bin/bash
# Weekly file backup script
DATE=$(date +%Y%m%d)
tar -czf /backups/files_$DATE.tar.gz /var/www/html/ --exclude='logs' --exclude='cache'

# Keep only last 12 weeks
find /backups -name "files_*.tar.gz" -mtime +84 -delete
```

### Troubleshooting Guide

#### Common Issues

**Login Problems**
- Check database connection
- Verify user credentials
- Review session configuration
- Check file permissions

**Performance Issues**
- Analyze slow queries
- Check server resources
- Review cache configuration
- Optimize database indexes

**File Upload Issues**
- Check PHP upload limits
- Verify directory permissions
- Review file size restrictions
- Check available disk space

#### Log Analysis
```bash
# Check Apache error logs
sudo tail -f /var/log/apache2/error.log

# Check application logs
tail -f /var/www/html/logs/application.log

# Check MySQL slow query log
sudo tail -f /var/log/mysql/mysql-slow.log
```

### Support Contacts

#### Technical Support
- **Email:** support@karvytech.com
- **Phone:** +91-XXX-XXX-XXXX
- **Hours:** 9 AM - 6 PM IST (Monday - Friday)

#### Emergency Support
- **Email:** emergency@karvytech.com
- **Phone:** +91-XXX-XXX-XXXX
- **Availability:** 24/7 for critical issues

### Version History

#### Version 1.0 (November 2025)
- Initial release
- Core functionality implementation
- Admin and vendor portals
- Basic reporting features

#### Planned Updates

**Version 1.1 (Q1 2026)**
- Mobile application
- Advanced analytics
- API enhancements
- Performance optimizations

**Version 1.2 (Q2 2026)**
- Multi-language support
- Advanced workflow automation
- Integration capabilities
- Enhanced security features

---

## Appendices

### Appendix A: Database Schema
[Complete database schema with all tables, relationships, and indexes]

### Appendix B: API Reference
[Complete API documentation with all endpoints, parameters, and examples]

### Appendix C: Configuration Options
[All configuration parameters and their descriptions]

### Appendix D: Error Codes
[Complete list of error codes and their meanings]

### Appendix E: Changelog
[Detailed changelog for all versions]

---

**Document Version:** 1.0  
**Last Updated:** November 2025  
**Next Review:** February 2026  

---

© 2025 Karvy Technologies Pvt Ltd. All rights reserved.