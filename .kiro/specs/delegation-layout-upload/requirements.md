# Requirements Document

## Introduction

This feature adds a file upload capability to the site delegation form in the admin panel. After the Delegation Date field, users will be able to upload layout-related documents (images, PDFs, Excel files, Word documents) along with optional remarks. The uploaded files will be stored in a database table with preview functionality and proper file management.

## Glossary

- **Delegation System**: The system component that manages the assignment of sites to vendors for installation work
- **Layout Upload**: A file upload feature that allows users to attach layout-related documents (images, PDFs, Excel, Word docs) to a site delegation
- **Delegation Form**: The web form in admin/sites/delegate.php used to assign sites to vendors
- **File Preview**: A visual representation of uploaded files before final submission
- **Remark Field**: A text input field that allows users to add notes or comments about the uploaded layout file

## Requirements

### Requirement 1

**User Story:** As an admin user, I want to upload layout files when delegating a site to a vendor, so that the vendor has access to necessary layout documentation

#### Acceptance Criteria

1. WHEN the admin user views the delegation form, THE Delegation System SHALL display a file upload section after the Delegation Date field
2. THE Delegation System SHALL accept file uploads with the following formats: images (jpg, jpeg, png, gif, webp), PDF documents, Excel files (xls, xlsx), and Word documents (doc, docx)
3. THE Delegation System SHALL limit individual file uploads to a maximum size of 10MB
4. WHERE a file is selected for upload, THE Delegation System SHALL display a preview of the file before form submission
5. THE Delegation System SHALL provide a text input field for remarks with a maximum length of 500 characters

### Requirement 2

**User Story:** As an admin user, I want to see a preview of uploaded layout files, so that I can verify the correct file is being uploaded before submission

#### Acceptance Criteria

1. WHEN a user selects an image file, THE Delegation System SHALL display a thumbnail preview of the image
2. WHEN a user selects a non-image file (PDF, Excel, Word), THE Delegation System SHALL display an appropriate file icon with the filename
3. THE Delegation System SHALL display the file size in a human-readable format (KB or MB)
4. THE Delegation System SHALL provide a remove button to clear the selected file before submission
5. WHEN a user removes a selected file, THE Delegation System SHALL clear the preview and reset the file input field

### Requirement 3

**User Story:** As an admin user, I want uploaded layout files to be stored securely with the delegation record, so that they can be retrieved and viewed later

#### Acceptance Criteria

1. WHEN the delegation form is submitted with a layout file, THE Delegation System SHALL store the file in a designated upload directory with a unique filename
2. THE Delegation System SHALL create a database record containing the delegation ID, original filename, stored filename, file path, file type, file size, remarks, and upload timestamp
3. THE Delegation System SHALL validate file types on the server side to prevent unauthorized file uploads
4. IF file upload fails, THEN THE Delegation System SHALL display an error message and prevent form submission
5. THE Delegation System SHALL maintain referential integrity between delegation records and uploaded layout files

### Requirement 4

**User Story:** As an admin user, I want to view uploaded layout files for existing delegations, so that I can access the layout documentation when needed

#### Acceptance Criteria

1. WHEN viewing an active delegation, THE Delegation System SHALL display uploaded layout files with their associated remarks
2. THE Delegation System SHALL provide a download link for each uploaded layout file
3. WHERE the uploaded file is an image, THE Delegation System SHALL display a thumbnail preview with a link to view the full-size image
4. THE Delegation System SHALL display the upload date and time for each layout file
5. THE Delegation System SHALL display the remarks associated with each uploaded layout file

### Requirement 5

**User Story:** As a system administrator, I want the file upload feature to handle errors gracefully, so that users receive clear feedback when issues occur

#### Acceptance Criteria

1. IF a user attempts to upload a file exceeding the size limit, THEN THE Delegation System SHALL display an error message indicating the maximum allowed file size
2. IF a user attempts to upload an unsupported file type, THEN THE Delegation System SHALL display an error message listing the supported file formats
3. IF a file upload fails due to server errors, THEN THE Delegation System SHALL log the error and display a user-friendly error message
4. THE Delegation System SHALL validate file uploads on both client-side and server-side
5. IF the upload directory is not writable, THEN THE Delegation System SHALL display an appropriate error message and log the issue
