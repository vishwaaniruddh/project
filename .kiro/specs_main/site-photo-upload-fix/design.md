# Site Photo Upload and Viewing Fix - Design Document

## Overview

This design addresses the specific issue where the site photo upload field in the vendor site survey form is not working, while all other form functionality works correctly. The solution focuses on fixing the photo upload field to properly handle file uploads, store them in the correct directory structure, and save the file paths to the database. Additionally, we'll implement photo viewing capability in the survey details page.

## Architecture

### Current Issues Identified
1. **Primary Issue**: Site photo upload field is not working in `vendor/site-survey.php` (all other form fields work correctly)
2. **Secondary Issue**: No photo viewing capability in `shared/view-survey.php`
3. Photos should be stored in `assets/uploads/surveys/YYYY/MM/` structure
4. Database column `site_photos` exists but photos are not being saved to it

### Proposed Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                    Site Survey System                       │
├─────────────────────────────────────────────────────────────┤
│  Frontend Components:                                       │
│  ├── Photo Upload Interface (vendor/site-survey.php)       │
│  ├── Photo Preview Component                               │
│  └── Photo Gallery Viewer (shared/view-survey.php)        │
├─────────────────────────────────────────────────────────────┤
│  Backend Components:                                        │
│  ├── File Upload Handler (process-photo-upload.php)        │
│  ├── Photo Management Service                              │
│  └── Database Photo Storage (site_photos column)           │
├─────────────────────────────────────────────────────────────┤
│  File System:                                              │
│  └── assets/uploads/surveys/YYYY/MM/                       │
│      ├── [survey_id]_[timestamp]_[original_name]           │
│      └── thumbnails/ (optional for performance)            │
└─────────────────────────────────────────────────────────────┘
```

## Components and Interfaces

### 1. Photo Upload Interface Component

**Location:** `vendor/site-survey.php`

**Functionality:**
- Multi-file drag-and-drop upload interface
- File type validation (JPG, JPEG, PNG, GIF)
- File size validation (max 10MB per file)
- Real-time preview of uploaded photos
- Progress indicators during upload
- Remove photo functionality

**HTML Structure:**
```html
<div class="photo-upload-section">
    <div class="upload-dropzone">
        <input type="file" multiple accept="image/*" id="photo-upload">
        <div class="upload-instructions">
            <!-- Drag & drop or click to upload -->
        </div>
    </div>
    <div class="photo-preview-grid">
        <!-- Dynamic photo previews -->
    </div>
</div>
```

### 2. Photo Upload Handler

**Location:** `vendor/process-photo-upload.php` (new file)

**Functionality:**
- Receive uploaded files via AJAX
- Validate file types and sizes
- Create directory structure if needed
- Generate unique filenames
- Store files in `assets/uploads/surveys/YYYY/MM/`
- Return JSON response with file paths

**API Endpoint:**
```php
POST /vendor/process-photo-upload.php
Content-Type: multipart/form-data

Response:
{
    "success": true,
    "files": [
        {
            "original_name": "site_photo_1.jpg",
            "stored_path": "assets/uploads/surveys/2024/11/survey_123_1699123456_site_photo_1.jpg",
            "thumbnail_path": "assets/uploads/surveys/2024/11/thumbnails/survey_123_1699123456_site_photo_1.jpg"
        }
    ]
}
```

### 3. Photo Gallery Viewer Component

**Location:** `shared/view-survey.php`

**Functionality:**
- Display photos in responsive grid layout
- Lightbox/modal view for full-size images
- Navigation between multiple photos
- Responsive design for mobile/desktop
- Loading states and error handling

**HTML Structure:**
```html
<div class="survey-photos-section">
    <h3>Site Photos</h3>
    <div class="photo-gallery-grid">
        <!-- Photo thumbnails -->
    </div>
    <div class="photo-modal" style="display: none;">
        <!-- Full-size photo viewer -->
    </div>
</div>
```

### 4. Database Integration

**Table:** `site_surveys`
**Column:** `site_photos` (TEXT/JSON)

**Data Structure:**
```json
[
    {
        "original_name": "site_photo_1.jpg",
        "file_path": "assets/uploads/surveys/2024/11/survey_123_1699123456_site_photo_1.jpg",
        "upload_timestamp": "2024-11-06 10:30:00",
        "file_size": 2048576
    }
]
```

## Data Models

### PhotoUpload Class
```php
class PhotoUpload {
    private $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    private $maxFileSize = 10485760; // 10MB
    private $uploadBasePath = 'assets/uploads/surveys/';
    
    public function uploadPhotos($files, $surveyId);
    public function createDirectoryStructure($year, $month);
    public function generateUniqueFilename($originalName, $surveyId);
    public function validateFile($file);
    public function deletePhoto($filePath);
}
```

### PhotoGallery Class
```php
class PhotoGallery {
    public function getPhotosForSurvey($surveyId);
    public function renderPhotoGrid($photos);
    public function generateThumbnail($imagePath);
    public function validatePhotoExists($filePath);
}
```

## Error Handling

### Upload Errors
1. **File Type Invalid**: Return error message with supported formats
2. **File Size Too Large**: Return error with size limit information
3. **Directory Creation Failed**: Log error and return generic upload failure message
4. **Disk Space Full**: Return storage error message
5. **Permission Denied**: Log error and return upload failure message

### Viewing Errors
1. **Photo File Missing**: Display placeholder image with "Photo not available" message
2. **Invalid Photo Path**: Skip broken photos and continue displaying others
3. **Database Connection Error**: Display error message in photo section

## Testing Strategy

### Unit Tests
1. **PhotoUpload Class Tests**
   - File validation logic
   - Directory creation functionality
   - Filename generation uniqueness
   - File upload success/failure scenarios

2. **PhotoGallery Class Tests**
   - Photo retrieval from database
   - HTML rendering output
   - Error handling for missing files

### Integration Tests
1. **End-to-End Upload Flow**
   - Upload photos through web interface
   - Verify files stored in correct directory
   - Verify database records created
   - Verify photos display in gallery

2. **Cross-Browser Testing**
   - Test upload interface in Chrome, Firefox, Safari, Edge
   - Test responsive gallery layout on different screen sizes
   - Test drag-and-drop functionality

### Performance Tests
1. **Multiple File Upload**
   - Test uploading 10+ photos simultaneously
   - Verify progress indicators work correctly
   - Test memory usage during large uploads

2. **Gallery Loading**
   - Test gallery performance with 20+ photos
   - Verify thumbnail loading optimization
   - Test modal/lightbox performance

## Security Considerations

### File Upload Security
1. **File Type Validation**: Server-side validation of file MIME types
2. **File Size Limits**: Enforce maximum file size to prevent DoS
3. **Filename Sanitization**: Remove dangerous characters from filenames
4. **Directory Traversal Prevention**: Validate upload paths
5. **Virus Scanning**: Consider implementing file scanning for production

### Access Control
1. **Authentication**: Verify user is logged in before upload/view
2. **Authorization**: Ensure users can only view photos for surveys they have access to
3. **Direct File Access**: Consider protecting uploaded files from direct URL access

## Implementation Notes

### Directory Structure Creation
- Use PHP's `mkdir()` with recursive flag to create year/month directories
- Set appropriate permissions (755) for web server access
- Handle race conditions when multiple uploads create directories simultaneously

### File Naming Convention
- Format: `survey_{survey_id}_{timestamp}_{sanitized_original_name}`
- Ensures uniqueness and traceability
- Preserves original filename for user reference

### Database Storage
- Store photo metadata as JSON in `site_photos` column
- Include original filename, stored path, upload timestamp, and file size
- Consider migration script for existing surveys without photo data

### Frontend JavaScript
- Use modern File API for drag-and-drop functionality
- Implement AJAX upload with progress tracking
- Add client-side file validation for better UX
- Use CSS Grid/Flexbox for responsive photo gallery

### Performance Optimization
- Generate thumbnails for faster gallery loading
- Implement lazy loading for large photo galleries
- Consider CDN integration for production environments
- Optimize images during upload (optional compression)