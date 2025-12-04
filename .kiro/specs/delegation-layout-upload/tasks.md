# Implementation Plan

- [x] 1. Create database table and migration


  - Create migration file `database/create_delegation_layouts_table.sql` with the delegation_layouts table schema
  - Include all fields: id, delegation_id, original_filename, stored_filename, file_path, file_type, file_size, mime_type, remarks, uploaded_by, timestamps
  - Add foreign key constraints to site_delegations and users tables
  - Add indexes on delegation_id and uploaded_at columns
  - Create PHP migration script `database/setup_delegation_layouts.php` to execute the SQL
  - _Requirements: 1.1, 1.5, 3.2, 3.5_

- [x] 2. Create DelegationLayout model


  - Create `models/DelegationLayout.php` extending BaseModel
  - Define protected properties: $table, $uploadDir, $maxFileSize, $allowedTypes, $allowedExtensions
  - Implement uploadLayout() method with file validation, unique filename generation, file moving, and database insertion
  - Implement getLayoutsByDelegation() method with JOIN to users table for uploader information
  - Implement deleteLayout() method to remove both file and database record
  - Implement private helper methods: validateFile(), generateUniqueFilename(), getFileExtension(), getHumanReadableSize()
  - _Requirements: 1.2, 1.3, 2.4, 3.1, 3.2, 3.3, 5.1, 5.2, 5.3, 5.4, 5.5_



- [ ] 3. Create uploads directory structure
  - Create `uploads/delegations/` directory if it doesn't exist
  - Add .htaccess file to uploads/delegations/ for security (deny direct access or allow only specific file types)
  - Set appropriate directory permissions (755 or as required by server)


  - _Requirements: 3.1, 3.2_

- [ ] 4. Update delegation form UI
  - Modify `admin/sites/delegate.php` to add file upload section after Delegation Date field
  - Add file input with accept attribute for allowed file types
  - Add drag-and-drop upload area with appropriate styling
  - Add remarks textarea with character counter (500 max)


  - Add hidden file preview container
  - Update form enctype to "multipart/form-data"
  - _Requirements: 1.1, 1.5, 2.1, 2.2_

- [ ] 5. Implement client-side file upload handling
  - Add JavaScript to `admin/sites/delegate.php` for file selection handling
  - Implement file validation (type and size) on client-side
  - Implement file preview generation for images (thumbnail display)
  - Implement file icon display for non-image files (PDF, Excel, Word) with filename and size



  - Implement remove file functionality to clear selection
  - Add remarks character counter functionality
  - Show/hide remarks section based on file selection
  - Display validation error messages for invalid files
  - _Requirements: 1.2, 1.3, 2.1, 2.2, 2.3, 2.4, 2.5, 5.1, 5.2_

- [ ] 6. Update process_delegation.php controller
  - Modify `admin/sites/process_delegation.php` to handle file uploads in the 'delegate' action
  - Add require statement for DelegationLayout model
  - Check if layout_file is present in $_FILES array
  - Call DelegationLayout->uploadLayout() with delegation_id, file, remarks, and user_id
  - Handle file upload exceptions and return appropriate error responses
  - Update success response to include layout upload status
  - _Requirements: 1.2, 1.3, 3.1, 3.2, 3.3, 3.4, 5.3_

- [ ] 7. Display uploaded layouts in active delegation view
  - Modify `admin/sites/delegate.php` active delegation section
  - Fetch layout files using DelegationLayout->getLayoutsByDelegation()
  - Display uploaded layouts section with grid layout
  - Show image thumbnails for image files with lightbox/modal view capability
  - Show file icons for non-image files (PDF, Excel, Word)
  - Display file metadata: filename, size, upload date/time
  - Display remarks for each uploaded file
  - Add download link for each file
  - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5_

- [ ] 8. Implement file download functionality
  - Create `admin/sites/download_layout.php` script for secure file downloads
  - Verify user authentication and authorization
  - Validate layout_id parameter
  - Fetch layout record from database
  - Verify file exists on filesystem
  - Set appropriate headers for file download (Content-Type, Content-Disposition, Content-Length)
  - Stream file to browser
  - Log download activity
  - _Requirements: 4.2_

- [ ] 9. Add error handling and validation
  - Implement server-side file type validation in DelegationLayout model
  - Implement server-side file size validation
  - Add try-catch blocks in process_delegation.php for file upload errors
  - Return JSON error responses with appropriate messages
  - Add error logging for file upload failures
  - Handle upload directory permission errors
  - Display user-friendly error messages on the frontend
  - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5_

- [ ] 10. Testing and validation
  - Test file upload with all supported file types (JPG, PNG, GIF, WEBP, PDF, XLS, XLSX, DOC, DOCX)
  - Test file size limit enforcement (attempt >10MB upload)
  - Test invalid file type rejection
  - Test file preview functionality for images
  - Test file icon display for non-images
  - Test remarks saving and display
  - Test file download functionality
  - Test form submission without file (optional upload)
  - Test multiple delegations with different files
  - Verify file and database record cleanup on delegation deletion
  - Test error handling for various failure scenarios
  - _Requirements: All requirements_
