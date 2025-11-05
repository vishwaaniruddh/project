<?php
require_once __DIR__ . '/../config/database.php';

class SiteSurvey {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function create($data) {
        $sql = "INSERT INTO site_surveys (
            site_id, vendor_id, delegation_id, survey_status, survey_date,
            checkin_datetime, checkout_datetime, working_hours, store_model,
            floor_height, floor_height_photos, ceiling_type, ceiling_photos,
            total_cameras, analytic_cameras, analytic_photos,
            existing_poe_rack, existing_poe_photos, space_new_rack, space_new_rack_photos,
            new_poe_rack, new_poe_photos, zones_recommended,
            rrl_delivery_status, rrl_photos, kptl_space, kptl_photos,
            site_photos, technical_remarks, submitted_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['site_id'],
            $data['vendor_id'],
            $data['delegation_id'] ?? null,
            'pending', // Always start as pending
            $data['survey_date'] ?? date('Y-m-d H:i:s'),
            $data['checkin_datetime'] ?? null,
            $data['checkout_datetime'] ?? null,
            $data['working_hours'] ?? null,
            $data['store_model'] ?? null,
            $data['floor_height'] ?? null,
            $data['floor_height_photos'] ?? null,
            $data['ceiling_type'] ?? null,
            $data['ceiling_photos'] ?? null,
            $data['total_cameras'] ?? null,
            $data['analytic_cameras'] ?? null,
            $data['analytic_photos'] ?? null,
            $data['existing_poe_rack'] ?? null,
            $data['existing_poe_photos'] ?? null,
            $data['space_new_rack'] ?? null,
            $data['space_new_rack_photos'] ?? null,
            $data['new_poe_rack'] ?? null,
            $data['new_poe_photos'] ?? null,
            $data['zones_recommended'] ?? null,
            $data['rrl_delivery_status'] ?? null,
            $data['rrl_photos'] ?? null,
            $data['kptl_space'] ?? null,
            $data['kptl_photos'] ?? null,
            $data['site_photos'] ?? null,
            $data['remarks'] ?? $data['technical_remarks'] ?? null
        ]);
        
        if ($result) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    public function update($id, $data) {
        $sql = "UPDATE site_surveys SET 
            survey_status = ?, survey_date = ?, technical_remarks = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['survey_status'] ?? 'pending',
            $data['survey_date'] ?? null,
            $data['remarks'] ?? $data['technical_remarks'] ?? null,
            $id
        ]);
    }
    
    public function approve($id, $approvedBy, $remarks = null) {
        $sql = "UPDATE site_surveys SET 
            survey_status = 'approved',
            approved_by = ?,
            approved_date = NOW(),
            approval_remarks = ?
            WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$approvedBy, $remarks, $id]);
    }
    
    public function reject($id, $approvedBy, $remarks = null) {
        $sql = "UPDATE site_surveys SET 
            survey_status = 'rejected',
            approved_by = ?,
            approved_date = NOW(),
            approval_remarks = ?
            WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$approvedBy, $remarks, $id]);
    }
    
    public function createComprehensive($data) {
        $sql = "INSERT INTO site_surveys (
            site_id, vendor_id, delegation_id, survey_status, submitted_date,
            checkin_datetime, checkout_datetime, working_hours, store_model,
            floor_height, floor_height_photos, ceiling_type, ceiling_photos,
            total_cameras, analytic_cameras, analytic_photos,
            existing_poe_rack, existing_poe_photos, space_new_rack, space_new_rack_photos,
            new_poe_rack, new_poe_photos, zones_recommended,
            rrl_delivery_status, rrl_photos, kptl_space, kptl_photos,
            site_accessibility, power_availability, network_connectivity, space_adequacy,
            technical_remarks, challenges_identified, recommendations, estimated_completion_days
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?
        )";
        
        $params = [
            $data['site_id'], $data['vendor_id'], $data['delegation_id'], 
            $data['survey_status'], $data['submitted_date'],
            $data['checkin_datetime'], $data['checkout_datetime'], $data['working_hours'], 
            $data['store_model'],
            $data['floor_height'], $data['floor_height_photos'], $data['ceiling_type'], 
            $data['ceiling_photos'],
            $data['total_cameras'], $data['analytic_cameras'], $data['analytic_photos'],
            $data['existing_poe_rack'], $data['existing_poe_photos'], $data['space_new_rack'], 
            $data['space_new_rack_photos'],
            $data['new_poe_rack'], $data['new_poe_photos'], $data['zones_recommended'],
            $data['rrl_delivery_status'], $data['rrl_photos'], $data['kptl_space'], 
            $data['kptl_photos'],
            $data['site_accessibility'], $data['power_availability'], $data['network_connectivity'], 
            $data['space_adequacy'],
            $data['technical_remarks'], $data['challenges_identified'], $data['recommendations'], 
            $data['estimated_completion_days']
        ];
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
    
    public function find($id) {
        $sql = "SELECT * FROM site_surveys WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findWithDetails($id) {
        $sql = "SELECT ss.*, s.site_id as site_code, s.location, 
                       ct.name as city_name, st.name as state_name,
                       v.name as vendor_name, u.username as approved_by_name
                FROM site_surveys ss
                LEFT JOIN sites s ON ss.site_id = s.id
                LEFT JOIN cities ct ON s.city_id = ct.id
                LEFT JOIN states st ON s.state_id = st.id
                LEFT JOIN vendors v ON ss.vendor_id = v.id
                LEFT JOIN users u ON ss.approved_by = u.id
                WHERE ss.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findBySiteAndVendor($siteId, $vendorId) {
        $sql = "SELECT ss.*, s.site_id as site_code, s.location, 
                       ct.name as city_name, st.name as state_name,
                       v.name as vendor_name, u.username as approved_by_name
                FROM site_surveys ss
                LEFT JOIN sites s ON ss.site_id = s.id
                LEFT JOIN cities ct ON s.city_id = ct.id
                LEFT JOIN states st ON s.state_id = st.id
                LEFT JOIN vendors v ON ss.vendor_id = v.id
                LEFT JOIN users u ON ss.approved_by = u.id
                WHERE ss.site_id = ? AND ss.vendor_id = ?
                ORDER BY ss.created_at DESC
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$siteId, $vendorId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function findByDelegation($delegationId) {
        $sql = "SELECT ss.*, s.site_id as site_code, s.location, 
                       ct.name as city_name, st.name as state_name,
                       v.name as vendor_name, u.username as approved_by_name
                FROM site_surveys ss
                LEFT JOIN sites s ON ss.site_id = s.id
                LEFT JOIN cities ct ON s.city_id = ct.id
                LEFT JOIN states st ON s.state_id = st.id
                LEFT JOIN vendors v ON ss.vendor_id = v.id
                LEFT JOIN users u ON ss.approved_by = u.id
                WHERE ss.delegation_id = ?
                ORDER BY ss.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$delegationId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getVendorSurveys($vendorId, $status = null) {
        $sql = "SELECT ss.*, s.site_id as site_code, s.location, 
                       ct.name as city_name, st.name as state_name,
                       v.name as vendor_name, sd.delegation_date, u.username as approved_by_name
                FROM site_surveys ss
                LEFT JOIN sites s ON ss.site_id = s.id
                LEFT JOIN cities ct ON s.city_id = ct.id
                LEFT JOIN states st ON s.state_id = st.id
                LEFT JOIN vendors v ON ss.vendor_id = v.id
                LEFT JOIN site_delegations sd ON ss.delegation_id = sd.id
                LEFT JOIN users u ON ss.approved_by = u.id
                WHERE ss.vendor_id = ?";
        
        $params = [$vendorId];
        
        if ($status) {
            $sql .= " AND ss.survey_status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY ss.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAllSurveys($status = null) {
        $sql = "SELECT ss.*, s.site_id as site_code, s.location, 
                       ct.name as city_name, st.name as state_name,
                       v.name as vendor_name, sd.delegation_date, u.username as approved_by_name
                FROM site_surveys ss
                LEFT JOIN sites s ON ss.site_id = s.id
                LEFT JOIN cities ct ON s.city_id = ct.id
                LEFT JOIN states st ON s.state_id = st.id
                LEFT JOIN vendors v ON ss.vendor_id = v.id
                LEFT JOIN site_delegations sd ON ss.delegation_id = sd.id
                LEFT JOIN users u ON ss.approved_by = u.id";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE ss.survey_status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY ss.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function delete($id) {
        $sql = "DELETE FROM site_surveys WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    public function getAllWithDetails() {
        $sql = "SELECT ss.*, 
                       s.site_id, s.location,
                       v.name as vendor_name,
                       u.username as approved_by_name,
                       ss.technical_remarks as notes,
                       ss.submitted_date,
                       ss.approved_date,
                       ss.survey_status,
                       CONCAT(
                           'Store Model: ', COALESCE(ss.store_model, 'N/A'), '; ',
                           'Floor Height: ', COALESCE(ss.floor_height, 'N/A'), '; ',
                           'Ceiling Type: ', COALESCE(ss.ceiling_type, 'N/A'), '; ',
                           'Total Cameras: ', COALESCE(ss.total_cameras, 'N/A'), '; ',
                           'Analytic Cameras: ', COALESCE(ss.analytic_cameras, 'N/A')
                       ) as survey_data
                FROM site_surveys ss
                LEFT JOIN sites s ON ss.site_id = s.id
                LEFT JOIN vendors v ON ss.vendor_id = v.id
                LEFT JOIN users u ON ss.approved_by = u.id
                ORDER BY ss.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>