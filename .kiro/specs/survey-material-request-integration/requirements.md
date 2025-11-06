# Requirements Document

## Introduction

This feature enhances the existing survey and material request system by adding automatic material request generation from surveys and displaying material request status indicators on both the survey and sites pages. The system will allow vendors to generate material requests directly from completed surveys and provide visual feedback about the material request status across the application.

## Glossary

- **Survey_System**: The existing site survey management system that handles survey creation, submission, and approval
- **Material_Request_System**: The existing material request management system that handles material requests, items, and dispatch
- **Survey_Page**: The vendor surveys listing page (vendor/surveys.php) that displays all surveys for a vendor
- **Sites_Page**: The vendor sites listing page (vendor/sites/index.php) that displays all assigned sites for a vendor
- **Material_Request_Button**: The "Generate Material Request" button available on approved/completed surveys
- **Material_Request_Label**: Visual indicator showing that a material request has been generated for a survey/site
- **Survey_Record**: Individual survey entry in the site_surveys table
- **Site_Record**: Individual site entry in the sites table

## Requirements

### Requirement 1

**User Story:** As a vendor, I want to generate a material request directly from a completed survey, so that I can efficiently request materials based on survey findings.

#### Acceptance Criteria

1. WHEN a vendor clicks the "Generate Material Request" button on an approved or completed survey, THE Survey_System SHALL redirect to the material request creation page with the survey and site information pre-populated
2. WHEN the material request is successfully created, THE Material_Request_System SHALL store the association between the survey and the material request
3. WHEN a material request is created from a survey, THE Survey_System SHALL update the survey record to indicate that a material request has been generated
4. THE Survey_System SHALL only display the "Generate Material Request" button for surveys with status 'approved' or 'completed'
5. THE Material_Request_System SHALL pre-populate the site_id and survey_id fields when accessed from a survey

### Requirement 2

**User Story:** As a vendor, I want to see a visual indicator on the survey page showing which surveys have material requests generated, so that I can track the status of my material requests.

#### Acceptance Criteria

1. WHEN a survey has an associated material request, THE Survey_Page SHALL display a "Material Request Generated" label next to the survey entry
2. THE Survey_Page SHALL show the material request status (pending, approved, dispatched, completed) as part of the label
3. WHEN a survey does not have an associated material request, THE Survey_Page SHALL not display any material request label
4. THE Survey_Page SHALL make the material request label clickable to navigate to the material request details
5. THE Survey_Page SHALL use distinct visual styling for different material request statuses

### Requirement 3

**User Story:** As a vendor, I want to see material request status on the sites page, so that I can quickly understand the material request status for each site.

#### Acceptance Criteria

1. WHEN a site has surveys with associated material requests, THE Sites_Page SHALL display a "Material Request" status indicator in the progress section
2. THE Sites_Page SHALL show the most recent material request status for each site
3. WHEN a site has no material requests, THE Sites_Page SHALL show "No Request" or similar indicator
4. THE Sites_Page SHALL use color-coded indicators to distinguish between different material request statuses
5. THE Sites_Page SHALL make the material request indicator clickable to view material request details

### Requirement 4

**User Story:** As a vendor, I want the material request button to be disabled after generating a request, so that I don't accidentally create duplicate requests.

#### Acceptance Criteria

1. WHEN a material request has been generated for a survey, THE Survey_System SHALL replace the "Generate Material Request" button with a "View Material Request" link
2. THE Survey_System SHALL prevent multiple material requests from being created for the same survey
3. WHEN viewing a survey with an existing material request, THE Survey_System SHALL display the material request creation date and status
4. THE Survey_System SHALL allow navigation to the existing material request details page
5. THE Survey_System SHALL maintain the association between surveys and material requests permanently

### Requirement 5

**User Story:** As a vendor, I want to see material request information integrated into the survey details, so that I have complete context when reviewing surveys.

#### Acceptance Criteria

1. WHEN viewing survey details, THE Survey_System SHALL display associated material request information if available
2. THE Survey_System SHALL show material request status, creation date, and item count in survey details
3. WHEN no material request exists for a survey, THE Survey_System SHALL show an option to create one if the survey is approved/completed
4. THE Survey_System SHALL provide direct links to material request details from survey views
5. THE Survey_System SHALL update material request information in real-time when status changes occur