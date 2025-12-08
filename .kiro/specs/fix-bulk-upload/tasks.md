# Implementation Plan

- [ ] 1. Implement output buffer management and error suppression
  - Add output buffering at the start of AJAX request handling
  - Suppress PHP warnings/notices during processing
  - Ensure Content-Type: application/json header is set early
  - Implement clean buffer clearing before JSON output
  - Add try-catch wrapper around entire processing logic
  - _Requirements: 2.1, 2.2, 2.3_

- [ ] 2. Implement header-based column mapping system
  - [ ] 2.1 Create column name mapping configuration
    - Define array of expected column names with variations
    - Include case-insensitive matching
    - Support common naming variations (spaces, underscores, etc.)
    - _Requirements: 1.1, 1.3_

  - [ ] 2.2 Implement header detection and parsing
    - Read first row as header row
    - Normalize header names (lowercase, trim, remove extra spaces)
    - Map headers to internal field names
    - _Requirements: 1.1, 1.2_

  - [ ] 2.3 Implement column validation
    - Check that all required columns are present
    - Return clear error message listing missing columns
    - _Requirements: 1.4, 1.5_

  - [ ] 2.4 Update data row processing to use column mapping
    - Replace fixed index access with mapped indices
    - Handle missing optional columns gracefully
    - _Requirements: 1.2_

- [ ] 3. Enhance file upload validation and error handling
  - [ ] 3.1 Improve file upload error messages
    - Create user-friendly messages for each upload error code
    - Include actionable information (file size limits, etc.)
    - _Requirements: 2.5_

  - [ ] 3.2 Add PhpSpreadsheet availability detection
    - Check if PhpSpreadsheet is available before processing Excel files
    - Return clear error message with instructions if unavailable
    - Suggest CSV upload as alternative
    - _Requirements: 3.1, 3.2, 3.3_

  - [ ] 3.3 Implement graceful dependency handling
    - Allow CSV processing even without PhpSpreadsheet
    - Provide installation instructions in error messages
    - _Requirements: 3.3, 3.4_

- [ ] 4. Improve row-by-row validation and error reporting
  - [ ] 4.1 Enhance validation error messages
    - Include specific field names in error messages
    - Include actual values that failed validation
    - Distinguish between validation, database, and system errors
    - _Requirements: 2.5, 4.3, 4.4_

  - [ ] 4.2 Implement independent row processing
    - Ensure validation errors in one row don't stop processing
    - Continue to next row after validation failure
    - Collect all errors for final report
    - _Requirements: 4.1_

  - [ ] 4.3 Enhance result tracking
    - Track row number, site_id, location for each row
    - Record action taken (create/update/skip)
    - Record status (success/failed) and detailed messages
    - _Requirements: 4.2, 4.5_

- [ ] 5. Implement comprehensive database error handling
  - [ ] 5.1 Add database connection error handling
    - Wrap database operations in try-catch blocks
    - Return JSON error response for connection failures
    - Log detailed error information to server logs
    - _Requirements: 5.1, 5.3_

  - [ ] 5.2 Implement safe error messaging
    - Create user-friendly messages for database errors
    - Never expose sensitive connection details
    - Provide generic messages for security
    - _Requirements: 5.2, 5.4_

  - [ ] 5.3 Ensure cleanup on errors
    - Clean up temporary files even when errors occur
    - Use finally blocks for guaranteed cleanup
    - _Requirements: 5.5_

- [ ] 6. Update response builder for consistent JSON output
  - Ensure all code paths return valid JSON
  - Standardize response structure across success and error cases
  - Include summary statistics in all responses
  - Add detailed row-by-row results
  - _Requirements: 2.1, 4.2, 4.5_

- [ ] 7. Add comprehensive error logging
  - Log all errors to server error log with context
  - Include file name, row number, user ID in logs
  - Log full exception stack traces for debugging
  - Never log sensitive information
  - _Requirements: 2.4, 5.3_

- [ ] 8. Create test CSV files with various scenarios
  - Create CSV with different column orders
  - Create CSV with missing optional columns
  - Create CSV with invalid data
  - Create CSV with mixed valid/invalid rows
  - _Requirements: All_

- [ ] 9. Perform manual testing
  - Test CSV upload with correct column order
  - Test CSV upload with different column order
  - Test Excel upload with PhpSpreadsheet
  - Test Excel upload without PhpSpreadsheet
  - Test file upload errors (size limit, invalid type)
  - Test database error scenarios
  - Verify JSON responses are always valid
  - Verify error messages are user-friendly
  - _Requirements: All_
