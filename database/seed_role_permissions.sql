-- RBAC System - Role Permission Assignments
-- This script assigns default permissions to each role

-- =============================================
-- SUPERADMIN ROLE (ID: 1) - ALL PERMISSIONS
-- =============================================
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1, id FROM permissions
ON DUPLICATE KEY UPDATE role_id = role_id;

-- =============================================
-- ADMIN ROLE (ID: 2)
-- All masters permissions, users (except manage_roles), 
-- all inventory permissions except audit.read
-- =============================================

-- Masters permissions (all)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE module = 'masters'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Users permissions (read, create, update - not delete or manage_roles)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE permission_key IN (
    'users.read',
    'users.create',
    'users.update'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Inventory permissions (all except audit.read)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions 
WHERE module = 'inventory' AND permission_key != 'inventory.audit.read'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Survey permissions (all)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE module = 'survey'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Installation permissions (all)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE module = 'installation'
ON DUPLICATE KEY UPDATE role_id = role_id;


-- =============================================
-- MANAGER ROLE (ID: 3)
-- Masters read permissions, users.read, 
-- inventory dashboard, reports, and approval permissions
-- =============================================

-- Masters read permissions only
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_key IN (
    'masters.bank.read',
    'masters.customer.read',
    'masters.country.read',
    'masters.cities.read',
    'masters.states.read',
    'masters.zones.read'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Users read permission
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_key = 'users.read'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Inventory dashboard, reports, and approval permissions
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_key IN (
    'inventory.dashboard.adv',
    'inventory.dashboard.contractor',
    'inventory.dashboard.engineer',
    'inventory.reports.read',
    'inventory.reports.export',
    'inventory.material_requests.approve',
    'inventory.material_requests.view',
    'inventory.dispatch.read',
    'inventory.stock.read',
    'inventory.warehouses.read',
    'inventory.products.read',
    'inventory.assets.read',
    'inventory.repairs.read',
    'inventory.transfer.read',
    'inventory.alerts.read'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Survey permissions (read and approve)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_key IN (
    'survey.read',
    'survey.approve'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Installation permissions (read and approve)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE permission_key IN (
    'installation.read',
    'installation.approve'
)
ON DUPLICATE KEY UPDATE role_id = role_id;


-- =============================================
-- ENGINEER ROLE (ID: 4)
-- Survey and installation related permissions,
-- material_requests.create, material_requests.view, stock.read
-- =============================================

-- Survey permissions (create, read, update)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE permission_key IN (
    'survey.create',
    'survey.read',
    'survey.update'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Installation permissions (create, read, update, complete)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE permission_key IN (
    'installation.create',
    'installation.read',
    'installation.update',
    'installation.complete'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Inventory permissions for engineers
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE permission_key IN (
    'inventory.material_requests.create',
    'inventory.material_requests.view',
    'inventory.stock.read',
    'inventory.dispatch.read',
    'inventory.dispatch.acknowledge',
    'inventory.dashboard.engineer',
    'inventory.warehouses.read',
    'inventory.products.read',
    'inventory.material_masters.view'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Masters read permissions (limited)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE permission_key IN (
    'masters.cities.read',
    'masters.states.read',
    'masters.zones.read'
)
ON DUPLICATE KEY UPDATE role_id = role_id;


-- =============================================
-- VENDOR ROLE (ID: 5)
-- Limited site access, material_requests.view, 
-- stock.read, dispatch.acknowledge
-- =============================================

-- Survey permissions (create, read, update for assigned sites)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE permission_key IN (
    'survey.create',
    'survey.read',
    'survey.update'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Installation permissions (create, read, update for assigned sites)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE permission_key IN (
    'installation.create',
    'installation.read',
    'installation.update',
    'installation.complete'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Inventory permissions for vendors
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE permission_key IN (
    'inventory.material_requests.view',
    'inventory.stock.read',
    'inventory.dispatch.acknowledge',
    'inventory.dispatch.read',
    'inventory.dashboard.contractor',
    'inventory.material_masters.view'
)
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Masters read permissions (limited)
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE permission_key IN (
    'masters.cities.read',
    'masters.states.read'
)
ON DUPLICATE KEY UPDATE role_id = role_id;
