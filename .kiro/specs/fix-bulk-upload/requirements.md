# Requirements Document

## Introduction

This document outlines the requirements for fixing the bulk upload functionality for sites. The current system is returning "Invalid JSON response" errors when users attempt to upload site data via Excel or CSV files. The system needs to handle flexible column ordering, provide better error handling, and ensure proper JSON responses in all scenarios.

## Glossary

- **Bulk Upload System**: The web-based interface that allows administrators to upload multiple site records simultaneously via Excel or CSV files
- **Site Record**: A database entry containing information about a physical site location including identifiers, location data, contact information, and purchase order details
- **Master Data**: Reference data stored in the database (countries, states, cities, customers) that site records must reference via foreign keys
- **JSON Response**: A structured data format returned by the server to communicate upload results to the client interface
- **PhpSpreadsheet**: A PHP library used to read Excel file formats (.xlsx, .xls)

## Requirements

### Requirement 1

**User Story:** As an administrator, I want to upload site data with flexible column ordering, so that I can use different CSV/Excel formats without reformatting my data

#### Acceptance Criteria

1. WHEN the administrator uploads a file with headers, THE Bulk Upload System SHALL read the header row to determine column positions
2. THE Bulk Upload System SHALL map data columns based on header names rather than fixed positions
3. THE Bulk Upload System SHALL support common variations of column names (case-insensitive, with/without spaces)
4. THE Bulk Upload System SHALL validate that all required columns are present in the uploaded file
5. IF required columns are missing, THEN THE Bulk Upload System SHALL return a clear error message listing the missing columns

### Requirement 2

**User Story:** As an administrator, I want to receive clear error messages when uploads fail, so that I can understand and fix the issues

#### Acceptance Criteria

1. THE Bulk Upload System SHALL always return valid JSON responses regardless of error conditions
2. THE Bulk Upload System SHALL suppress PHP warnings and notices from appearing in the response
3. WHEN a PHP error occurs, THE Bulk Upload System SHALL catch the error and return it as a structured JSON error message
4. THE Bulk Upload System SHALL log detailed error information to server logs for debugging
5. THE Bulk Upload System SHALL provide user-friendly error messages that explain what went wrong and how to fix it

### Requirement 3

**User Story:** As an administrator, I want the system to handle missing dependencies gracefully, so that I understand what needs to be installed

#### Acceptance Criteria

1. WHEN PhpSpreadsheet library is not available, THE Bulk Upload System SHALL detect this condition before processing
2. IF an Excel file is uploaded without PhpSpreadsheet, THEN THE Bulk Upload System SHALL return a clear message instructing the user to upload CSV instead
3. THE Bulk Upload System SHALL provide instructions for installing missing dependencies in error messages
4. THE Bulk Upload System SHALL continue to function with CSV files even when PhpSpreadsheet is unavailable

### Requirement 4

**User Story:** As an administrator, I want detailed validation feedback for each row, so that I can correct data issues efficiently

#### Acceptance Criteria

1. THE Bulk Upload System SHALL validate each row independently and continue processing subsequent rows
2. THE Bulk Upload System SHALL return row-by-row status information including row number, action taken, and any errors
3. WHEN validation fails for a row, THE Bulk Upload System SHALL include specific field names and values in the error message
4. THE Bulk Upload System SHALL distinguish between validation errors, database errors, and system errors
5. THE Bulk Upload System SHALL provide a summary showing counts of created, updated, skipped, and failed records

### Requirement 5

**User Story:** As an administrator, I want the system to handle database connection issues gracefully, so that I receive actionable error messages

#### Acceptance Criteria

1. WHEN a database connection fails, THE Bulk Upload System SHALL catch the exception and return a JSON error response
2. THE Bulk Upload System SHALL not expose sensitive database connection details in error messages
3. THE Bulk Upload System SHALL log full database error details to server logs for administrator review
4. THE Bulk Upload System SHALL provide a generic user-friendly message for database errors
5. THE Bulk Upload System SHALL clean up uploaded files even when database errors occur
