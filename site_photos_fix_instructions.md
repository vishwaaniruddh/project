# Site Photos Upload Fix - Deployment Instructions

## Issue Summary
Site photos uploaded in the survey form (`vendor/site-survey.php`) were not appearing in the shared view (`shared/view-survey.php`) due to:

1. **Missing Database Column**: The `site_photos` column was missing from the `site_surveys` table
2. **Processing Gap**: The survey processing script wasn't handling the `site_photos[]` field
3. **Model Gap**: The SiteSurvey model wasn't including `site_photos` in INSERT statements

## Files Modified

### 1. `vendor/process-survey-comprehensive.php`
- ✅ Added `'site_photos' => 'site_photos'` to the `$photoFields` array
- ✅ Now processes uploaded site photos correctly

### 2. `models/SiteSurvey.php`
- ✅ Added `site_photos` to the INSERT SQL statement
- ✅ Added `$data['site_photos'] ?? null` to the execute parameters
- ✅ Now saves site photos to the database

### 3. `database/add_site_photos_column.sql`
- ✅ Created migration script to add the missing `site_photos` column
- ✅ Includes proper column type (TEXT) and comment

## Deployment Steps

### Step 1: Upload Modified Files
Upload these files to your production server:
- `vendor/process-survey-comprehensive.php`
- `models/SiteSurvey.php`
- `database/add_site_photos_column.sql`

### Step 2: Run Database Migration
Execute the SQL migration on your production database:

**Option A: Via phpMyAdmin or database admin panel**
1. Open your database management tool
2. Select your site installation database
3. Run the contents of `database/add_site_photos_column.sql`

**Option B: Via command line (if you have access)**
```bash
mysql -u your_username -p your_database_name < database/add_site_photos_column.sql
```

**Option C: Via PHP script (create temporary migration script)**
Create a temporary file `run_migration.php` on your server:
```php
<?php
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Check if column exists
    $checkSql = "SELECT COUNT(*) as col_count 
                 FROM INFORMATION_SCHEMA.COLUMNS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'site_surveys' 
                   AND COLUMN_NAME = 'site_photos'";
    
    $stmt = $db->query($checkSql);
    $result = $stmt->fetch();
    
    if ($result['col_count'] == 0) {
        // Add the column
        $alterSql = "ALTER TABLE site_surveys 
                     ADD COLUMN site_photos TEXT NULL 
                     COMMENT 'JSON array of general site photo paths' 
                     AFTER kptl_photos";
        
        $db->exec($alterSql);
        echo "✓ site_photos column added successfully";
    } else {
        echo "✓ site_photos column already exists";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage();
}
?>
```

Then run: `https://project.sarsspl.com/run_migration.php`

### Step 3: Test the Fix
1. **Upload Test**: Go to a survey form and upload some site photos
2. **Verify Storage**: Check that photos are saved in `assets/uploads/surveys/`
3. **Check Display**: View the survey in the shared view to confirm photos appear
4. **Clean Up**: Remove the temporary migration script if you used Option C

## Verification Checklist

- [ ] Database has `site_photos` column in `site_surveys` table
- [ ] Survey form accepts multiple site photo uploads
- [ ] Photos are processed and saved to the database
- [ ] Photos appear in the shared survey view under "General Site Photos"
- [ ] Photo modal opens correctly when clicking on images

## Technical Details

### Database Schema
```sql
ALTER TABLE site_surveys 
ADD COLUMN site_photos TEXT NULL 
COMMENT 'JSON array of general site photo paths' 
AFTER kptl_photos;
```

### Photo Storage Format
Site photos are stored as JSON array in the database:
```json
["surveys/photo1_20241105_123456.jpg", "surveys/photo2_20241105_123457.jpg"]
```

### File Upload Location
Photos are uploaded to: `assets/uploads/surveys/`

## Troubleshooting

### Issue: Column already exists error
- **Solution**: The column was already added, proceed with testing

### Issue: Photos still not showing
- **Check**: Verify the `site_photos` column exists in your database
- **Check**: Ensure file upload permissions on `assets/uploads/surveys/` directory
- **Check**: Look for JavaScript errors in browser console during upload

### Issue: Upload fails
- **Check**: File size limits (current limit: 5MB per file)
- **Check**: File type restrictions (only images allowed)
- **Check**: Server upload limits in PHP configuration

## Security Notes
- Photos are validated for file type and size
- File names are sanitized to prevent security issues
- Upload directory is outside the web root for security

## Support
If you encounter issues:
1. Check the browser console for JavaScript errors
2. Check server error logs for PHP errors
3. Verify database column exists and has correct permissions
4. Test with smaller image files first