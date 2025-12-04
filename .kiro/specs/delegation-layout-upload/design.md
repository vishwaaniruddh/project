# Design Document: Delegation Layout Upload Feature

## Overview

This design document outlines the implementation of a file upload feature for the site delegation system. The feature allows admin users to upload layout-related documents (images, PDFs, Excel files, Word documents) with optional remarks when delegating sites to vendors. The uploaded files will be stored in a dedicated directory structure and tracked in a database table with full metadata.

## Architecture

### High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Delegation Form UI                        │
│  ┌────────────────┐  ┌──────────────┐  ┌─────────────────┐ │
│  │ Vendor Select  │  │ Date Display │  │ Layout Upload   │ │
│  └────────────────┘  └──────────────┘  └─────────────────┘ │
│  ┌────────────────────────────────────────────────────────┐ │
│  │              File Preview Component                     │ │
│  └────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              Process Delegation Controller                   │
│  ┌──────────────────┐  ┌────────────────────────────────┐  │
│  │ Form Validation  │  │ File Upload Handler            │  │
│  └──────────────────┘  └────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                ┌───────────┴───────────┐
                ▼                       ▼
┌──────────────────────────┐  ┌──────────────────────────┐
│  SiteDelegation Model    │  │  DelegationLayout Model  │
│  - delegateSite()        │  │  - uploadLayout()        │
│  - getActiveDelegation() │  │  - getLayoutsByDelegation│
└──────────────────────────┘  └──────────────────────────┘
                │                       │
                ▼                       ▼
┌──────────────────────────┐  ┌──────────────────────────┐
│  site_delegations table  │  │ delegation_layouts table │
└──────────────────────────┘  └──────────────────────────┘
                                        │
                                        ▼
                            ┌──────────────────────────┐
                            │  File System Storage     │
                            │  uploads/delegations/    │
                            └──────────────────────────┘
```

### Component Interaction Flow

1. **User Interaction**: Admin selects file and enters remarks in the delegation form
2. **Client-Side Preview**: JavaScript displays file preview and validates file type/size
3. **Form Submission**: Form data including file is submitted via multipart/form-data
4. **Server Processing**: PHP processes the delegation and file upload
5. **File Storage**: File is saved to uploads/delegations/ with unique naming
6. **Database Recording**: File metadata is stored in delegation_layouts table
7. **Response**: Success/error response returned to client

## Components and Interfaces

### 1. Database Schema

#### New Table: `delegation_layouts`

```sql
CREATE TABLE delegation_layouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delegation_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    remarks TEXT,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (delegation_id) REFERENCES site_delegations(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_delegation_id (delegation_id),
    INDEX idx_uploaded_at (uploaded_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Field Descriptions:**
- `id`: Primary key
- `delegation_id`: Foreign key to site_delegations table
- `original_filename`: Original name of uploaded file
- `stored_filename`: Unique filename used for storage
- `file_path`: Relative path to the stored file
- `file_type`: File extension (jpg, pdf, xlsx, etc.)
- `file_size`: File size in bytes
- `mime_type`: MIME type of the file
- `remarks`: Optional user remarks about the file
- `uploaded_by`: Foreign key to users table
- `uploaded_at`: Timestamp of upload
- `created_at`: Record creation timestamp
- `updated_at`: Record update timestamp

### 2. File Storage Structure

```
uploads/
└── delegations/
    ├── layout_{delegation_id}_{timestamp}_{random}.jpg
    ├── layout_{delegation_id}_{timestamp}_{random}.pdf
    └── layout_{delegation_id}_{timestamp}_{random}.xlsx
```

**Naming Convention**: `layout_{delegation_id}_{timestamp}_{random}.{extension}`
- Prevents filename collisions
- Associates files with delegations
- Maintains original file extension

### 3. PHP Model: DelegationLayout

**File**: `models/DelegationLayout.php`

```php
class DelegationLayout extends BaseModel {
    protected $table = 'delegation_layouts';
    protected $uploadDir = 'uploads/delegations/';
    protected $maxFileSize = 10485760; // 10MB in bytes
    protected $allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    protected $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'xls', 'xlsx', 'doc', 'docx'];
    
    // Methods
    public function uploadLayout($delegationId, $file, $remarks, $uploadedBy);
    public function getLayoutsByDelegation($delegationId);
    public function deleteLayout($layoutId);
    private function validateFile($file);
    private function generateUniqueFilename($delegationId, $extension);
    private function getFileExtension($filename);
    private function getHumanReadableSize($bytes);
}
```

### 4. Frontend Components

#### A. HTML Form Enhancement (delegate.php)

Add after the Delegation Date field:

```html
<div class="md:col-span-2">
    <label for="layout_file" class="form-label">Layout Upload (Optional)</label>
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4">
        <input type="file" 
               id="layout_file" 
               name="layout_file" 
               class="hidden" 
               accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.xls,.xlsx,.doc,.docx">
        <div id="file-upload-area" class="text-center cursor-pointer">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            <p class="mt-2 text-sm text-gray-600">
                Click to upload or drag and drop
            </p>
            <p class="text-xs text-gray-500">
                Images, PDF, Excel, Word (Max 10MB)
            </p>
        </div>
        <div id="file-preview" class="hidden mt-4"></div>
    </div>
</div>
<div class="md:col-span-2" id="remarks-section" style="display:none;">
    <label for="layout_remarks" class="form-label">Layout Remarks (Optional)</label>
    <textarea id="layout_remarks" 
              name="layout_remarks" 
              class="form-textarea" 
              rows="2" 
              maxlength="500"
              placeholder="Add any notes about the uploaded layout file..."></textarea>
    <p class="text-xs text-gray-500 mt-1">
        <span id="remarks-count">0</span>/500 characters
    </p>
</div>
```

#### B. JavaScript File Upload Handler

```javascript
// File upload handling
const fileInput = document.getElementById('layout_file');
const fileUploadArea = document.getElementById('file-upload-area');
const filePreview = document.getElementById('file-preview');
const remarksSection = document.getElementById('remarks-section');
const remarksTextarea = document.getElementById('layout_remarks');
const remarksCount = document.getElementById('remarks-count');

let selectedFile = null;

// File type validation
const allowedTypes = {
    'image/jpeg': 'jpg', 'image/jpg': 'jpg', 'image/png': 'png',
    'image/gif': 'gif', 'image/webp': 'webp',
    'application/pdf': 'pdf',
    'application/vnd.ms-excel': 'xls',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': 'xlsx',
    'application/msword': 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': 'docx'
};

// Methods
function handleFileSelect(file);
function validateFile(file);
function displayFilePreview(file);
function removeFile();
function formatFileSize(bytes);
function getFileIcon(fileType);
```

#### C. Display Uploaded Layouts (Active Delegation View)

```html
<?php if ($layoutFiles && count($layoutFiles) > 0): ?>
<div class="mt-4">
    <label class="block text-sm font-medium text-gray-700 mb-2">Uploaded Layouts</label>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <?php foreach ($layoutFiles as $layout): ?>
        <div class="border rounded-lg p-3">
            <!-- File preview/icon -->
            <!-- File details -->
            <!-- Download button -->
            <!-- Remarks -->
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
```

### 5. Controller Updates

#### process_delegation.php Enhancement

Add file upload handling to the 'delegate' action:

```php
case 'delegate':
    // Existing delegation logic
    $delegationId = $delegationModel->delegateSite($siteId, $vendorId, Auth::getUserId(), $notes);
    
    // Handle file upload if present
    if (isset($_FILES['layout_file']) && $_FILES['layout_file']['error'] === UPLOAD_ERR_OK) {
        $layoutModel = new DelegationLayout();
        $remarks = $_POST['layout_remarks'] ?? '';
        $layoutModel->uploadLayout($delegationId, $_FILES['layout_file'], $remarks, Auth::getUserId());
    }
    
    // Response
    break;
```

## Data Models

### DelegationLayout Model Methods

#### uploadLayout()
```php
/**
 * Upload a layout file for a delegation
 * 
 * @param int $delegationId The delegation ID
 * @param array $file The $_FILES array element
 * @param string $remarks Optional remarks
 * @param int $uploadedBy User ID of uploader
 * @return int The layout record ID
 * @throws Exception on validation or upload failure
 */
public function uploadLayout($delegationId, $file, $remarks, $uploadedBy)
```

**Process:**
1. Validate file (type, size, errors)
2. Generate unique filename
3. Create upload directory if not exists
4. Move uploaded file to destination
5. Insert record into database
6. Return layout ID

#### getLayoutsByDelegation()
```php
/**
 * Get all layout files for a delegation
 * 
 * @param int $delegationId The delegation ID
 * @return array Array of layout records with uploader info
 */
public function getLayoutsByDelegation($delegationId)
```

**Returns:**
```php
[
    [
        'id' => 1,
        'delegation_id' => 5,
        'original_filename' => 'site_layout.pdf',
        'stored_filename' => 'layout_5_1733328000_abc123.pdf',
        'file_path' => 'uploads/delegations/layout_5_1733328000_abc123.pdf',
        'file_type' => 'pdf',
        'file_size' => 2048576,
        'mime_type' => 'application/pdf',
        'remarks' => 'Main site layout drawing',
        'uploaded_by' => 1,
        'uploaded_at' => '2024-12-04 10:30:00',
        'uploader_name' => 'admin'
    ]
]
```

#### deleteLayout()
```php
/**
 * Delete a layout file
 * 
 * @param int $layoutId The layout record ID
 * @return bool Success status
 * @throws Exception if file or record not found
 */
public function deleteLayout($layoutId)
```

**Process:**
1. Fetch layout record
2. Delete physical file from filesystem
3. Delete database record
4. Return success status

## Error Handling

### Client-Side Validation Errors

| Error Condition | Message | Action |
|----------------|---------|--------|
| File too large | "File size exceeds 10MB limit" | Prevent upload, show alert |
| Invalid file type | "File type not supported. Allowed: images, PDF, Excel, Word" | Prevent upload, show alert |
| No file selected | N/A | Allow form submission without file |

### Server-Side Validation Errors

| Error Condition | HTTP Status | Response |
|----------------|-------------|----------|
| File upload error | 400 | `{"success": false, "message": "File upload failed"}` |
| Invalid file type | 400 | `{"success": false, "message": "Invalid file type"}` |
| File too large | 400 | `{"success": false, "message": "File exceeds size limit"}` |
| Upload directory not writable | 500 | `{"success": false, "message": "Server error"}` |
| Database error | 500 | `{"success": false, "message": "Failed to save file information"}` |

### Error Logging

All server-side errors will be logged using the existing logger:

```php
error_log("Delegation layout upload error: " . $e->getMessage());
```

## Testing Strategy

### Unit Tests

1. **File Validation Tests**
   - Test allowed file types acceptance
   - Test rejected file types
   - Test file size limits
   - Test filename sanitization

2. **Model Method Tests**
   - Test uploadLayout() with valid files
   - Test uploadLayout() with invalid files
   - Test getLayoutsByDelegation()
   - Test deleteLayout()

3. **Filename Generation Tests**
   - Test unique filename generation
   - Test collision prevention
   - Test extension preservation

### Integration Tests

1. **End-to-End Upload Flow**
   - Submit delegation form with file
   - Verify file stored correctly
   - Verify database record created
   - Verify file retrievable

2. **Display Tests**
   - Verify uploaded files display in active delegation view
   - Verify file previews render correctly
   - Verify download links work

3. **Error Handling Tests**
   - Test upload with oversized file
   - Test upload with invalid file type
   - Test upload with corrupted file
   - Test upload directory permissions

### Manual Testing Checklist

- [ ] Upload image file (JPG, PNG, GIF, WEBP)
- [ ] Upload PDF document
- [ ] Upload Excel file (XLS, XLSX)
- [ ] Upload Word document (DOC, DOCX)
- [ ] Verify file preview displays correctly
- [ ] Add remarks and verify they save
- [ ] Submit form and verify file uploads
- [ ] View active delegation and verify file displays
- [ ] Download uploaded file and verify integrity
- [ ] Test file size limit (attempt >10MB upload)
- [ ] Test invalid file type (attempt .exe upload)
- [ ] Test form submission without file
- [ ] Test remarks character limit (500 chars)
- [ ] Verify file removal before submission works
- [ ] Test multiple delegations with files
- [ ] Verify file deletion when delegation is deleted

## Security Considerations

### File Upload Security

1. **File Type Validation**
   - Whitelist approach (only allowed types)
   - Validate both MIME type and extension
   - Server-side validation (never trust client)

2. **File Size Limits**
   - PHP upload_max_filesize: 10MB
   - PHP post_max_size: 12MB
   - Application-level validation: 10MB

3. **Filename Sanitization**
   - Remove special characters
   - Use generated filenames (not user-provided)
   - Prevent directory traversal attacks

4. **Storage Security**
   - Store files outside web root (or use .htaccess)
   - Use unique, unpredictable filenames
   - Implement access control for downloads

5. **Input Sanitization**
   - Sanitize remarks input
   - Escape output when displaying
   - Prevent XSS attacks

### Access Control

- Only authenticated admin users can upload files
- Only authenticated users can download files
- Verify user permissions before file operations
- Log all file upload/download activities

## Performance Considerations

### File Upload Optimization

1. **Client-Side**
   - Validate file before upload
   - Show upload progress indicator
   - Compress images client-side (optional future enhancement)

2. **Server-Side**
   - Stream large files instead of loading into memory
   - Use efficient file operations
   - Implement upload timeout handling

### Database Optimization

- Index on delegation_id for fast lookups
- Index on uploaded_at for chronological queries
- Consider pagination for large result sets

### Storage Optimization

- Implement file cleanup for cancelled/deleted delegations
- Consider file compression for PDFs and images
- Monitor disk space usage

## Future Enhancements

1. **Multiple File Upload**: Allow uploading multiple layout files per delegation
2. **File Versioning**: Track file versions when updated
3. **Image Thumbnails**: Generate thumbnails for faster preview loading
4. **File Compression**: Automatic compression for large files
5. **Cloud Storage**: Integration with S3 or similar for scalability
6. **File Sharing**: Share layout files with vendors via secure links
7. **Audit Trail**: Track who viewed/downloaded files
8. **Bulk Upload**: Upload multiple files at once
9. **File Categories**: Categorize layout files (floor plan, elevation, etc.)
10. **OCR Integration**: Extract text from uploaded PDFs for searchability
