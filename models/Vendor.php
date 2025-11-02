<?php
require_once __DIR__ . '/BaseModel.php';

class Vendor extends BaseModel {
    protected $table = 'vendors';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getActiveVendors() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function getVendorStats() {
        $stats = [];
        
        // Total vendors
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'");
        $stats['total_active'] = $stmt->fetchColumn();
        
        // Vendors with active delegations
        $stmt = $this->db->query("
            SELECT COUNT(DISTINCT v.id) 
            FROM {$this->table} v 
            INNER JOIN site_delegations sd ON v.id = sd.vendor_id 
            WHERE v.status = 'active' AND sd.status = 'active'
        ");
        $stats['with_delegations'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    public function getVendorDelegations($vendorId, $status = null) {
        $sql = "
            SELECT sd.*, s.site_id, s.location, s.city, s.state, u.username as delegated_by_name
            FROM site_delegations sd
            INNER JOIN sites s ON sd.site_id = s.id
            INNER JOIN users u ON sd.delegated_by = u.id
            WHERE sd.vendor_id = ?
        ";
        
        $params = [$vendorId];
        
        if ($status) {
            $sql .= " AND sd.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY sd.delegation_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getAllVendors() {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>