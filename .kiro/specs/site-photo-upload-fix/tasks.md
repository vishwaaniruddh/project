# Site Photo Upload Fix - Implementation Plan

- [x] 1. Investigate and fix the photo upload field in vendor site survey form


  - Examine the current photo upload field implementation in `vendor/site-survey.php`
  - Identify why the photo upload field is not working while other fields work
  - Check the form's file upload configuration and enctype attribute
  - Verify the photo upload field HTML structure and JavaScript handlers
  - _Requirements: 1.1, 1.2, 1.3_

- [ ] 2. Implement proper photo upload processing
  - [x] 2.1 Create or fix the photo upload handler in the form processing

    - Examine the current form processing logic for photo uploads
    - Implement proper file validation (file type, size, security checks)
    - Create the directory structure `assets/uploads/surveys/YYYY/MM/` if it doesn't exist
    - Generate unique filenames to prevent conflicts
    - _Requirements: 1.3, 1.4, 3.1, 3.2, 3.3_

  - [x] 2.2 Fix database storage of photo paths

    - Ensure uploaded photo paths are properly stored in the `site_photos` column
    - Store photo metadata as JSON array including file paths and original names
    - Handle multiple photo uploads if supported
    - _Requirements: 1.4, 1.5_

  - [x] 2.3 Add proper error handling and user feedback


    - Implement clear error messages for upload failures
    - Add success feedback when photos are uploaded
    - Handle edge cases like file size limits and invalid file types
    - _Requirements: 3.5_

- [ ] 3. Implement photo viewing in survey details page
  - [x] 3.1 Add photo gallery section to survey viewer


    - Modify `shared/view-survey.php` to include a photo gallery section
    - Retrieve photo data from the `site_photos` database column
    - Parse JSON photo data and validate file existence
    - _Requirements: 2.1, 2.5_

  - [x] 3.2 Create responsive photo display interface

    - Implement thumbnail grid layout for multiple photos
    - Add click-to-enlarge functionality with modal/lightbox
    - Ensure responsive design works on mobile and desktop
    - Handle cases where no photos exist with appropriate messaging
    - _Requirements: 2.2, 2.3, 2.4, 5.1, 5.2_

  - [x] 3.3 Add photo navigation and loading states

    - Implement navigation controls for browsing multiple photos
    - Add loading indicators and error handling for missing photos
    - Optimize photo loading performance
    - _Requirements: 5.3, 5.4, 5.5_

- [ ] 4. Test and validate the photo upload and viewing functionality
  - [x] 4.1 Test photo upload functionality


    - Verify photos upload correctly through the vendor survey form
    - Test with different file types and sizes
    - Verify photos are stored in correct directory structure
    - Confirm database records are created properly
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5_

  - [x] 4.2 Test photo viewing functionality

    - Verify photos display correctly in survey details page
    - Test responsive layout on different screen sizes
    - Test modal/lightbox functionality
    - Verify error handling for missing or corrupted photos
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 5.1, 5.2, 5.3, 5.4, 5.5_

- [x] 5. Create upload directory structure and set permissions


  - Create the `assets/uploads/surveys/` directory structure if it doesn't exist
  - Set appropriate file permissions for web server access
  - Add .htaccess file for security if needed
  - _Requirements: 3.1, 3.2_