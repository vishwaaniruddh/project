-- RBAC System - Seed Data
-- This script inserts default roles and permissions

-- =============================================
-- INSERT DEFAULT ROLES
-- =============================================
INSERT INTO roles (id, name, display_name, description, is_system_role) VALUES
(1, 'superadmin', 'Super Admin', 'Full system access, can manage all users and permissions', TRUE),
(2, 'admin', 'Administrator', 'Administrative access, can manage most operations', TRUE),
(3, 'manager', 'Manager', 'Managerial access, can approve and oversee operations', TRUE),
(4, 'engineer', 'Engineer', 'Technical access for surveys and installations', TRUE),
(5, 'vendor', 'Vendor', 'External partner with limited site access', TRUE)
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description);

-- =============================================
-- INSERT MASTERS MODULE PERMISSIONS (12)
-- =============================================
INSERT INTO permissions (module, action, permission_key, display_name, description) VALUES
('masters', 'bank.manage', 'masters.bank.manage', 'Manage Banks', 'Create, update, and delete bank records'),
('masters', 'bank.read', 'masters.bank.read', 'View Banks', 'View bank records'),
('masters', 'customer.manage', 'masters.customer.manage', 'Manage Customers', 'Create, update, and delete customer records'),
('masters', 'customer.read', 'masters.customer.read', 'View Customers', 'View customer records'),
('masters', 'country.manage', 'masters.country.manage', 'Manage Countries', 'Create, update, and delete country records'),
('masters', 'country.read', 'masters.country.read', 'View Countries', 'View country records'),
('masters', 'cities.manage', 'masters.cities.manage', 'Manage Cities', 'Create, update, and delete city records'),
('masters', 'cities.read', 'masters.cities.read', 'View Cities', 'View city records'),
('masters', 'states.manage', 'masters.states.manage', 'Manage States', 'Create, update, and delete state records'),
('masters', 'states.read', 'masters.states.read', 'View States', 'View state records'),
('masters', 'zones.manage', 'masters.zones.manage', 'Manage Zones', 'Create, update, and delete zone records'),
('masters', 'zones.read', 'masters.zones.read', 'View Zones', 'View zone records')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description);

-- =============================================
-- INSERT USERS MODULE PERMISSIONS (5)
-- =============================================
INSERT INTO permissions (module, action, permission_key, display_name, description) VALUES
('users', 'create', 'users.create', 'Create Users', 'Create new user accounts'),
('users', 'read', 'users.read', 'View Users', 'View user accounts and details'),
('users', 'update', 'users.update', 'Update Users', 'Update user account information'),
('users', 'delete', 'users.delete', 'Delete Users', 'Delete user accounts'),
('users', 'manage_roles', 'users.manage_roles', 'Manage User Roles', 'Assign and manage user roles and permissions')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description);


-- =============================================
-- INSERT INVENTORY MODULE PERMISSIONS (47)
-- =============================================
INSERT INTO permissions (module, action, permission_key, display_name, description) VALUES
-- Alerts (2)
('inventory', 'alerts.manage', 'inventory.alerts.manage', 'Manage Alerts', 'Configure and manage inventory alerts'),
('inventory', 'alerts.read', 'inventory.alerts.read', 'View Alerts', 'View inventory alerts'),

-- Assets (4)
('inventory', 'assets.manage', 'inventory.assets.manage', 'Manage Assets', 'Full asset management capabilities'),
('inventory', 'assets.read', 'inventory.assets.read', 'View Assets', 'View asset information'),
('inventory', 'assets.update_status', 'inventory.assets.update_status', 'Update Asset Status', 'Update asset status'),
('inventory', 'assets.update_status_full', 'inventory.assets.update_status_full', 'Full Status Update', 'Full asset status update capabilities'),

-- Audit (1)
('inventory', 'audit.read', 'inventory.audit.read', 'View Audit Logs', 'View inventory audit logs'),

-- Dashboard (3)
('inventory', 'dashboard.adv', 'inventory.dashboard.adv', 'Advanced Dashboard', 'Access advanced dashboard features'),
('inventory', 'dashboard.contractor', 'inventory.dashboard.contractor', 'Contractor Dashboard', 'Access contractor dashboard'),
('inventory', 'dashboard.engineer', 'inventory.dashboard.engineer', 'Engineer Dashboard', 'Access engineer dashboard'),

-- Dispatch (5)
('inventory', 'dispatch.acknowledge', 'inventory.dispatch.acknowledge', 'Acknowledge Dispatch', 'Acknowledge receipt of dispatched materials'),
('inventory', 'dispatch.create', 'inventory.dispatch.create', 'Create Dispatch', 'Create new material dispatches'),
('inventory', 'dispatch.manage', 'inventory.dispatch.manage', 'Manage Dispatches', 'Full dispatch management capabilities'),
('inventory', 'dispatch.read', 'inventory.dispatch.read', 'View Dispatches', 'View dispatch information'),
('inventory', 'dispatch.update', 'inventory.dispatch.update', 'Update Dispatch', 'Update dispatch information'),

-- Material Masters (4)
('inventory', 'material_masters.create', 'inventory.material_masters.create', 'Create Materials', 'Create new material master records'),
('inventory', 'material_masters.delete', 'inventory.material_masters.delete', 'Delete Materials', 'Delete material master records'),
('inventory', 'material_masters.edit', 'inventory.material_masters.edit', 'Edit Materials', 'Edit material master records'),
('inventory', 'material_masters.view', 'inventory.material_masters.view', 'View Materials', 'View material master records')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description);


-- Material Requests (4)
INSERT INTO permissions (module, action, permission_key, display_name, description) VALUES
('inventory', 'material_requests.approve', 'inventory.material_requests.approve', 'Approve Requests', 'Approve material requests'),
('inventory', 'material_requests.create', 'inventory.material_requests.create', 'Create Requests', 'Create material requests'),
('inventory', 'material_requests.receive', 'inventory.material_requests.receive', 'Receive Materials', 'Mark materials as received'),
('inventory', 'material_requests.view', 'inventory.material_requests.view', 'View Requests', 'View material requests'),

-- Products (5)
('inventory', 'products.create', 'inventory.products.create', 'Create Products', 'Create new products'),
('inventory', 'products.delete', 'inventory.products.delete', 'Delete Products', 'Delete products'),
('inventory', 'products.manage', 'inventory.products.manage', 'Manage Products', 'Full product management capabilities'),
('inventory', 'products.read', 'inventory.products.read', 'View Products', 'View product information'),
('inventory', 'products.update', 'inventory.products.update', 'Update Products', 'Update product information'),

-- Repairs (5)
('inventory', 'repairs.complete', 'inventory.repairs.complete', 'Complete Repairs', 'Mark repairs as complete'),
('inventory', 'repairs.create', 'inventory.repairs.create', 'Create Repairs', 'Create repair records'),
('inventory', 'repairs.manage', 'inventory.repairs.manage', 'Manage Repairs', 'Full repair management capabilities'),
('inventory', 'repairs.read', 'inventory.repairs.read', 'View Repairs', 'View repair information'),
('inventory', 'repairs.update', 'inventory.repairs.update', 'Update Repairs', 'Update repair information'),

-- Reports (2)
('inventory', 'reports.export', 'inventory.reports.export', 'Export Reports', 'Export inventory reports'),
('inventory', 'reports.read', 'inventory.reports.read', 'View Reports', 'View inventory reports'),

-- Stock (5)
('inventory', 'stock.bulk_upload', 'inventory.stock.bulk_upload', 'Bulk Upload Stock', 'Bulk upload stock data'),
('inventory', 'stock.create', 'inventory.stock.create', 'Create Stock', 'Create stock entries'),
('inventory', 'stock.manage', 'inventory.stock.manage', 'Manage Stock', 'Full stock management capabilities'),
('inventory', 'stock.read', 'inventory.stock.read', 'View Stock', 'View stock information'),
('inventory', 'stock.update', 'inventory.stock.update', 'Update Stock', 'Update stock information'),

-- Transfer (4)
('inventory', 'transfer.create', 'inventory.transfer.create', 'Create Transfer', 'Create stock transfers'),
('inventory', 'transfer.manage', 'inventory.transfer.manage', 'Manage Transfers', 'Full transfer management capabilities'),
('inventory', 'transfer.read', 'inventory.transfer.read', 'View Transfers', 'View transfer information'),
('inventory', 'transfer.update', 'inventory.transfer.update', 'Update Transfer', 'Update transfer information'),

-- Warehouses (5)
('inventory', 'warehouses.create', 'inventory.warehouses.create', 'Create Warehouse', 'Create new warehouses'),
('inventory', 'warehouses.delete', 'inventory.warehouses.delete', 'Delete Warehouse', 'Delete warehouses'),
('inventory', 'warehouses.manage', 'inventory.warehouses.manage', 'Manage Warehouses', 'Full warehouse management capabilities'),
('inventory', 'warehouses.read', 'inventory.warehouses.read', 'View Warehouses', 'View warehouse information'),
('inventory', 'warehouses.update', 'inventory.warehouses.update', 'Update Warehouse', 'Update warehouse information')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description);


-- =============================================
-- INSERT SURVEY MODULE PERMISSIONS (5)
-- =============================================
INSERT INTO permissions (module, action, permission_key, display_name, description) VALUES
('survey', 'create', 'survey.create', 'Create Survey', 'Create new site surveys'),
('survey', 'read', 'survey.read', 'View Surveys', 'View survey information'),
('survey', 'update', 'survey.update', 'Update Survey', 'Update survey information'),
('survey', 'delete', 'survey.delete', 'Delete Survey', 'Delete surveys'),
('survey', 'approve', 'survey.approve', 'Approve Survey', 'Approve submitted surveys')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description);

-- =============================================
-- INSERT INSTALLATION MODULE PERMISSIONS (6)
-- =============================================
INSERT INTO permissions (module, action, permission_key, display_name, description) VALUES
('installation', 'create', 'installation.create', 'Create Installation', 'Create new installation records'),
('installation', 'read', 'installation.read', 'View Installations', 'View installation information'),
('installation', 'update', 'installation.update', 'Update Installation', 'Update installation information'),
('installation', 'delete', 'installation.delete', 'Delete Installation', 'Delete installation records'),
('installation', 'complete', 'installation.complete', 'Complete Installation', 'Mark installations as complete'),
('installation', 'approve', 'installation.approve', 'Approve Installation', 'Approve completed installations')
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name), description = VALUES(description);
