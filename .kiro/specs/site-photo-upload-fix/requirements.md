# Site Photo Upload and Viewing Fix - Requirements Document

## Introduction

This feature addresses the critical issue where site survey photos are not being uploaded properly and cannot be viewed in the survey details page. The system needs to properly handle photo uploads to the correct directory structure and display them in the survey viewing interface.

## Glossary

- **Site_Survey_System**: The web application that manages site surveys and photo uploads
- **Photo_Upload_Module**: The component responsible for handling file uploads for survey photos
- **Survey_Viewer**: The interface that displays survey details and associated photos
- **Upload_Directory**: The file system location where photos are stored (assets/uploads/surveys/Year/month)
- **Database_Photo_Column**: The site_photos column in the site_surveys table

## Requirements

### Requirement 1

**User Story:** As a vendor, I want to upload multiple photos during a site survey, so that I can document the site conditions and equipment locations.

#### Acceptance Criteria

1. WHEN a vendor accesses the site survey form, THE Site_Survey_System SHALL display a photo upload interface that accepts multiple image files
2. WHEN a vendor selects image files for upload, THE Photo_Upload_Module SHALL validate that files are valid image formats (JPG, JPEG, PNG, GIF)
3. WHEN valid image files are selected, THE Photo_Upload_Module SHALL upload files to the directory structure assets/uploads/surveys/YYYY/MM where YYYY is the current year and MM is the current month
4. WHEN photos are successfully uploaded, THE Site_Survey_System SHALL store the file paths in the Database_Photo_Column as a JSON array
5. WHEN the survey form is submitted, THE Site_Survey_System SHALL save all photo information along with other survey data

### Requirement 2

**User Story:** As a vendor or admin, I want to view all photos associated with a site survey, so that I can review the documented site conditions.

#### Acceptance Criteria

1. WHEN a user accesses the survey details page, THE Survey_Viewer SHALL display a dedicated photo gallery section
2. WHEN photos exist for the survey, THE Survey_Viewer SHALL display thumbnail images in a responsive grid layout
3. WHEN a user clicks on a thumbnail, THE Survey_Viewer SHALL open the full-size image in a modal or lightbox view
4. WHEN no photos exist for the survey, THE Survey_Viewer SHALL display a message indicating no photos are available
5. WHERE photos are stored in the Database_Photo_Column, THE Survey_Viewer SHALL parse the JSON array and display all associated images

### Requirement 3

**User Story:** As a system administrator, I want the photo upload system to handle file organization automatically, so that photos are stored in an organized directory structure.

#### Acceptance Criteria

1. WHEN photos are uploaded, THE Photo_Upload_Module SHALL create the year directory if it does not exist
2. WHEN photos are uploaded, THE Photo_Upload_Module SHALL create the month directory within the year directory if it does not exist
3. WHEN generating file names, THE Photo_Upload_Module SHALL use unique identifiers to prevent filename conflicts
4. WHEN storing file paths, THE Photo_Upload_Module SHALL store relative paths from the web root for portability
5. WHEN file upload fails, THE Photo_Upload_Module SHALL provide clear error messages to the user

### Requirement 4

**User Story:** As a vendor, I want to see a preview of uploaded photos before submitting the survey, so that I can verify all necessary documentation is included.

#### Acceptance Criteria

1. WHEN photos are uploaded successfully, THE Site_Survey_System SHALL display thumbnail previews immediately
2. WHEN viewing photo previews, THE Site_Survey_System SHALL allow users to remove individual photos before submission
3. WHEN a photo is removed from preview, THE Site_Survey_System SHALL delete the file from the server and update the form data
4. WHEN the survey form is refreshed or reloaded, THE Site_Survey_System SHALL maintain the uploaded photos in the preview area
5. WHERE photo upload is in progress, THE Site_Survey_System SHALL display a loading indicator

### Requirement 5

**User Story:** As a system user, I want the photo viewing system to be responsive and user-friendly, so that I can easily review survey documentation on any device.

#### Acceptance Criteria

1. WHEN viewing photos on mobile devices, THE Survey_Viewer SHALL display photos in a mobile-optimized layout
2. WHEN viewing photos on desktop, THE Survey_Viewer SHALL display photos in a grid layout with hover effects
3. WHEN photos are loading, THE Survey_Viewer SHALL display loading placeholders
4. WHEN photo loading fails, THE Survey_Viewer SHALL display a broken image placeholder with error message
5. WHERE multiple photos exist, THE Survey_Viewer SHALL provide navigation controls for browsing through images