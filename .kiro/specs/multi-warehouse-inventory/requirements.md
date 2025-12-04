# Requirements Document

## Introduction

This document outlines the requirements for extending the existing single-location inventory management system to support multiple warehouse locations. The system currently tracks materials in a single "Main Warehouse" location, but needs to be enhanced to allow the inventory manager to store materials across multiple warehouses (Mumbai, Pune, Gujarat, etc.), track inventory levels per warehouse, dispatch materials from specific warehouses, and enable inter-warehouse material transfers.

## Glossary

- **Inventory System**: The software application that manages material stock, inward receipts, dispatches, and tracking
- **Warehouse**: A physical storage location where materials are kept (e.g., Mumbai, Pune, Gujarat)
- **Warehouse Master**: A database entity that defines warehouse locations with their details
- **Material**: Physical items tracked in the inventory system, referenced by BOQ items
- **Dispatch**: The process of sending materials from a warehouse to a site or vendor
- **Inter-Warehouse Transfer**: The movement of materials from one warehouse to another warehouse
- **Inventory Manager**: A user with permissions to manage inventory, dispatches, and warehouse operations
- **Stock Level**: The quantity of a specific material available at a specific warehouse

## Requirements

### Requirement 1

**User Story:** As an inventory manager, I want to define multiple warehouse locations in the system, so that I can organize materials across different geographical locations

#### Acceptance Criteria

1. THE Inventory System SHALL provide a warehouse master management interface
2. WHEN creating a warehouse, THE Inventory System SHALL require warehouse name, location/address, contact person, and contact phone number
3. THE Inventory System SHALL assign a unique warehouse code to each warehouse
4. THE Inventory System SHALL allow the inventory manager to mark a warehouse as active or inactive
5. THE Inventory System SHALL display a list of all warehouses with their current stock summary

### Requirement 2

**User Story:** As a system administrator, I want all existing inventory items to be automatically assigned to the Mumbai warehouse, so that historical data is preserved during the migration

#### Acceptance Criteria

1. WHEN the multi-warehouse feature is activated, THE Inventory System SHALL create a default warehouse record for Mumbai
2. THE Inventory System SHALL update all existing inventory stock records to reference the Mumbai warehouse
3. THE Inventory System SHALL maintain all existing serial numbers, batch numbers, and item details during the migration
4. THE Inventory System SHALL create an audit log entry for the warehouse assignment migration
5. THE Inventory System SHALL verify that no inventory items are left without a warehouse assignment after migration

### Requirement 3

**User Story:** As an inventory manager, I want to receive materials into a specific warehouse, so that I can track which warehouse holds which materials

#### Acceptance Criteria

1. WHEN creating an inward receipt, THE Inventory System SHALL require the inventory manager to select a destination warehouse
2. THE Inventory System SHALL assign all items in the inward receipt to the selected warehouse
3. THE Inventory System SHALL update the warehouse stock levels when the inward receipt is verified
4. THE Inventory System SHALL display the warehouse name on inward receipt documents and reports
5. THE Inventory System SHALL prevent receiving materials into inactive warehouses

### Requirement 4

**User Story:** As an inventory manager, I want to dispatch materials from a specific warehouse, so that I can control which warehouse fulfills each material request

#### Acceptance Criteria

1. WHEN creating a dispatch, THE Inventory System SHALL require the inventory manager to select a source warehouse
2. THE Inventory System SHALL display only materials available in the selected warehouse for dispatch
3. WHEN selecting items for dispatch, THE Inventory System SHALL show the available quantity per warehouse
4. THE Inventory System SHALL reduce the stock level of the source warehouse when materials are dispatched
5. THE Inventory System SHALL record the source warehouse information in the dispatch document

### Requirement 5

**User Story:** As an inventory manager, I want to view stock levels for each warehouse separately, so that I can understand material distribution across locations

#### Acceptance Criteria

1. THE Inventory System SHALL provide a warehouse-wise stock report showing quantities per material per warehouse
2. WHEN viewing the stock overview, THE Inventory System SHALL allow filtering by warehouse
3. THE Inventory System SHALL display total available stock across all warehouses for each material
4. THE Inventory System SHALL show warehouse-specific stock levels in the material detail view
5. THE Inventory System SHALL highlight warehouses with low stock levels for specific materials

### Requirement 6

**User Story:** As an inventory manager, I want to transfer materials between warehouses, so that I can balance stock levels and fulfill requests from the nearest warehouse

#### Acceptance Criteria

1. THE Inventory System SHALL provide an inter-warehouse transfer function
2. WHEN creating a transfer, THE Inventory System SHALL require source warehouse, destination warehouse, and material details
3. THE Inventory System SHALL verify that sufficient quantity exists in the source warehouse before allowing transfer
4. THE Inventory System SHALL generate a unique transfer number for each inter-warehouse transfer
5. THE Inventory System SHALL update stock levels in both source and destination warehouses when transfer is completed
6. THE Inventory System SHALL create movement records for both outward (from source) and inward (to destination) transactions
7. THE Inventory System SHALL prevent transfers from a warehouse to itself

### Requirement 7

**User Story:** As an inventory manager, I want to track material movements across warehouses, so that I can audit inventory transactions and identify discrepancies

#### Acceptance Criteria

1. THE Inventory System SHALL record all warehouse-related transactions in the inventory movements table
2. WHEN a material is received, dispatched, or transferred, THE Inventory System SHALL log the source warehouse and destination warehouse
3. THE Inventory System SHALL provide a movement history report filtered by warehouse, date range, and material
4. THE Inventory System SHALL display warehouse information in all movement audit trails
5. THE Inventory System SHALL allow the inventory manager to export warehouse movement reports

### Requirement 8

**User Story:** As a vendor or site manager, I want to know which warehouse my materials are being dispatched from, so that I can estimate delivery times and coordinate logistics

#### Acceptance Criteria

1. WHEN viewing a dispatch, THE Inventory System SHALL display the source warehouse name and location
2. THE Inventory System SHALL include warehouse information in dispatch notification emails
3. THE Inventory System SHALL show warehouse contact details on the dispatch document
4. THE Inventory System SHALL display the warehouse address on delivery documentation
5. THE Inventory System SHALL allow vendors to filter their dispatches by source warehouse
