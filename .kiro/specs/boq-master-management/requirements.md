# Requirements Document

## Introduction

This document defines the requirements for the BOQ Master Management feature. The system currently has two separate entities: `boq_master` (BOQ categories/templates) and `boq_items` (individual stock items). The goal is to create a unified BOQ Master module where administrators can create BOQ templates and associate specific stock items (products) from the `boq_items` table to each BOQ. This enables structured bill of quantities management where each BOQ can contain multiple predefined items with quantities.

## Glossary

- **BOQ_Master_System**: The administrative module for managing Bill of Quantities templates and their associated items
- **BOQ**: A Bill of Quantities template that groups related stock items together
- **BOQ_Item**: An individual stock/product item from the inventory catalog (stored in `boq_items` table)
- **BOQ_Line_Item**: An association between a BOQ and a BOQ_Item with a specified quantity
- **Administrator**: A user with admin role who has access to manage BOQ masters

## Requirements

### Requirement 1: BOQ Master CRUD Operations

**User Story:** As an Administrator, I want to create, view, edit, and delete BOQ masters, so that I can manage bill of quantities templates for the organization.

#### Acceptance Criteria

1. WHEN the Administrator navigates to the BOQ Master page, THE BOQ_Master_System SHALL display a paginated list of all BOQ masters with name, status, item count, and creation date.
2. WHEN the Administrator clicks the create button, THE BOQ_Master_System SHALL display a form with fields for BOQ name, description, serial number requirement flag, and status.
3. WHEN the Administrator submits a valid BOQ master form, THE BOQ_Master_System SHALL create the BOQ master record and display a success message within 2 seconds.
4. WHEN the Administrator submits a BOQ master form with a duplicate name, THE BOQ_Master_System SHALL display a validation error indicating the name already exists.
5. WHEN the Administrator clicks edit on a BOQ master, THE BOQ_Master_System SHALL display the edit form pre-populated with existing data.
6. WHEN the Administrator clicks delete on a BOQ master, THE BOQ_Master_System SHALL prompt for confirmation before deleting the record.

### Requirement 2: BOQ Item Association

**User Story:** As an Administrator, I want to associate stock items (products) with a BOQ master and specify quantities, so that I can define what items are included in each bill of quantities.

#### Acceptance Criteria

1. WHEN the Administrator views a BOQ master detail page, THE BOQ_Master_System SHALL display a list of all associated BOQ items with item name, code, unit, and default quantity.
2. WHEN the Administrator clicks add item to a BOQ, THE BOQ_Master_System SHALL display a searchable dropdown of available BOQ items from the boq_items table.
3. WHEN the Administrator selects a BOQ item and specifies a quantity, THE BOQ_Master_System SHALL create the association and update the item list within 2 seconds.
4. WHEN the Administrator attempts to add a duplicate item to the same BOQ, THE BOQ_Master_System SHALL display a validation error indicating the item is already associated.
5. WHEN the Administrator edits a BOQ line item, THE BOQ_Master_System SHALL allow modification of the quantity and remarks fields.
6. WHEN the Administrator removes a BOQ line item, THE BOQ_Master_System SHALL prompt for confirmation and remove the association upon confirmation.

### Requirement 3: BOQ Master Search and Filter

**User Story:** As an Administrator, I want to search and filter BOQ masters, so that I can quickly find specific bill of quantities templates.

#### Acceptance Criteria

1. WHEN the Administrator enters text in the search field, THE BOQ_Master_System SHALL filter the BOQ master list by name within 500 milliseconds.
2. WHEN the Administrator selects a status filter, THE BOQ_Master_System SHALL display only BOQ masters matching the selected status.
3. WHEN the Administrator clears all filters, THE BOQ_Master_System SHALL display the complete paginated list of BOQ masters.

### Requirement 4: BOQ Master Status Management

**User Story:** As an Administrator, I want to activate or deactivate BOQ masters, so that I can control which templates are available for use.

#### Acceptance Criteria

1. WHEN the Administrator clicks the toggle status button on an active BOQ master, THE BOQ_Master_System SHALL change the status to inactive and update the display.
2. WHEN the Administrator clicks the toggle status button on an inactive BOQ master, THE BOQ_Master_System SHALL change the status to active and update the display.
3. WHILE a BOQ master has status inactive, THE BOQ_Master_System SHALL display the BOQ master with a visual indicator showing inactive status.

### Requirement 5: BOQ Item Quick Add from Master

**User Story:** As an Administrator, I want to quickly add new stock items while associating items to a BOQ, so that I can create new products without leaving the BOQ management workflow.

#### Acceptance Criteria

1. WHEN the Administrator clicks "Add New Item" in the item selection dropdown, THE BOQ_Master_System SHALL display a modal form to create a new BOQ item.
2. WHEN the Administrator submits the new BOQ item form, THE BOQ_Master_System SHALL create the item in boq_items table and automatically add it to the current BOQ association.
3. IF the new BOQ item creation fails due to validation errors, THEN THE BOQ_Master_System SHALL display the specific validation errors and retain the form data.
