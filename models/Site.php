<?php
require_once __DIR__ . '/BaseModel.php';

class Site extends BaseModel {
    protected $table = 'sites';
    
    public function __construct() {
        parent::__construct();
    }
    
    public function getAllWithPagination($page = 1, $limit = 20, $search = '', $filters = []) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = '';
        $params = [];
        $conditions = [];
        
        // Search functionality
        if (!empty($search)) {
            $conditions[] = "(s.site_id LIKE ? OR s.store_id LIKE ? OR s.location LIKE ? OR ct.name LIKE ? OR cu.name LIKE ? OR s.contact_person_name LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
        
        // Filter by city
        if (!empty($filters['city'])) {
            $conditions[] = "ct.name = ?";
            $params[] = $filters['city'];
        }
        
        // Filter by state
        if (!empty($filters['state'])) {
            $conditions[] = "st.name = ?";
            $params[] = $filters['state'];
        }
        
        // Filter by activity status
        if (!empty($filters['activity_status'])) {
            $conditions[] = "s.activity_status = ?";
            $params[] = $filters['activity_status'];
        }
        
        // Filter by vendor
        if (!empty($filters['vendor'])) {
            $conditions[] = "s.vendor = ?";
            $params[] = $filters['vendor'];
        }
        
        // Filter by survey status
        if (!empty($filters['survey_status'])) {
            switch ($filters['survey_status']) {
                case 'pending':
                    $conditions[] = "(ss.survey_status IS NULL OR ss.survey_status = '')";
                    break;
                case 'submitted':
                    $conditions[] = "ss.survey_status = 'completed'";
                    break;
                case 'approved':
                    $conditions[] = "ss.survey_status = 'approved'";
                    break;
                case 'rejected':
                    $conditions[] = "ss.survey_status = 'rejected'";
                    break;
            }
        }
        
        if (!empty($conditions)) {
            $whereClause = "WHERE " . implode(" AND ", $conditions);
        }
        
        // Get total count
        $countSql = "SELECT COUNT(DISTINCT s.id) FROM {$this->table} s 
                     LEFT JOIN cities ct ON s.city_id = ct.id 
                     LEFT JOIN states st ON s.state_id = st.id 
                     LEFT JOIN countries co ON s.country_id = co.id 
                     LEFT JOIN customers cu ON s.customer_id = cu.id 
                     LEFT JOIN site_delegations sd ON s.id = sd.site_id AND sd.status = 'active'
                     LEFT JOIN vendors v ON sd.vendor_id = v.id
                     LEFT JOIN (
                         SELECT ss1.site_id, ss1.id, ss1.survey_status, ss1.submitted_date
                         FROM site_surveys ss1
                         INNER JOIN (
                             SELECT site_id, MAX(id) as max_id
                             FROM site_surveys
                             GROUP BY site_id
                         ) ss2 ON ss1.site_id = ss2.site_id AND ss1.id = ss2.max_id
                     ) ss ON s.id = ss.site_id
                     $whereClause";
        $stmt = $this->db->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();
        
        // Get paginated results with relationships
        $sql = "SELECT s.*, 
                       ct.name as city, st.name as state, co.name as country,
                       cu.name as customer,
                       sd.id as delegation_id, v.name as delegated_vendor_name,
                       sd.status as delegation_status, sd.delegation_date,
                       ss.id as survey_id, ss.survey_status as actual_survey_status,
                       ss.submitted_date as survey_submitted_date,
                       CASE 
                           WHEN ss.survey_status = 'completed' THEN 1
                           WHEN ss.survey_status = 'approved' THEN 1
                           ELSE 0
                       END as has_survey_submitted
                FROM {$this->table} s 
                LEFT JOIN cities ct ON s.city_id = ct.id 
                LEFT JOIN states st ON s.state_id = st.id 
                LEFT JOIN countries co ON s.country_id = co.id 
                LEFT JOIN customers cu ON s.customer_id = cu.id 
                LEFT JOIN site_delegations sd ON s.id = sd.site_id AND sd.status = 'active'
                LEFT JOIN vendors v ON sd.vendor_id = v.id
                LEFT JOIN (
                    SELECT ss1.site_id, ss1.id, ss1.survey_status, ss1.submitted_date
                    FROM site_surveys ss1
                    INNER JOIN (
                        SELECT site_id, MAX(id) as max_id
                        FROM site_surveys
                        GROUP BY site_id
                    ) ss2 ON ss1.site_id = ss2.site_id AND ss1.id = ss2.max_id
                ) ss ON s.id = ss.site_id
                $whereClause 
                ORDER BY s.created_at DESC 
                LIMIT $limit OFFSET $offset";

                
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $sites = $stmt->fetchAll();
        
        return [
            'sites' => $sites,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    public function findBySiteId($siteId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE site_id = ?");
        $stmt->execute([$siteId]);
        return $stmt->fetch();
    }
    
    public function findByStoreId($storeId) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE store_id = ?");
        $stmt->execute([$storeId]);
        return $stmt->fetch();
    }
    
    public function findWithRelations($id) {
        $sql = "SELECT s.*, 
                       ct.name as city_name, st.name as state_name, co.name as country_name,
                       cu.name as customer_name
                FROM {$this->table} s 
                LEFT JOIN cities ct ON s.city_id = ct.id 
                LEFT JOIN states st ON s.state_id = st.id 
                LEFT JOIN countries co ON s.country_id = co.id 
                LEFT JOIN customers cu ON s.customer_id = cu.id 
                WHERE s.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getUniqueValues($column) {
        $stmt = $this->db->prepare("SELECT DISTINCT $column FROM {$this->table} WHERE $column IS NOT NULL AND $column != '' ORDER BY $column");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    public function validateSiteData($data, $isUpdate = false, $siteId = null) {
        $errors = [];
        
        // Site ID validation
        if (empty($data['site_id'])) {
            $errors['site_id'] = 'Site ID is required';
        } else {
            // Check if site_id already exists
            $existingSite = $this->findBySiteId($data['site_id']);
            if ($existingSite && (!$isUpdate || $existingSite['id'] != $siteId)) {
                $errors['site_id'] = 'Site ID already exists';
            }
        }
        
        // Store ID validation (optional but unique if provided)
        if (!empty($data['store_id'])) {
            $existingSite = $this->findByStoreId($data['store_id']);
            if ($existingSite && (!$isUpdate || $existingSite['id'] != $siteId)) {
                $errors['store_id'] = 'Store ID already exists';
            }
        }
        
        // Location validation
        if (empty($data['location'])) {
            $errors['location'] = 'Location is required';
        }
        
        // Location foreign key validation
        if (empty($data['country_id']) || $data['country_id'] <= 0) {
            $errors['country_id'] = 'Country is required';
        }
        
        if (empty($data['state_id']) || $data['state_id'] <= 0) {
            $errors['state_id'] = 'State is required';
        }
        
        if (empty($data['city_id']) || $data['city_id'] <= 0) {
            $errors['city_id'] = 'City is required';
        }
        
        // PO Date validation
        if (!empty($data['po_date']) && !$this->isValidDate($data['po_date'])) {
            $errors['po_date'] = 'Invalid PO date format';
        }
        
        // Survey submission date validation
        if (!empty($data['survey_submission_date']) && !$this->isValidDateTime($data['survey_submission_date'])) {
            $errors['survey_submission_date'] = 'Invalid survey submission date format';
        }
        
        // Installation date validation
        if (!empty($data['installation_date']) && !$this->isValidDateTime($data['installation_date'])) {
            $errors['installation_date'] = 'Invalid installation date format';
        }
        
        return $errors;
    }
    
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    private function isValidDateTime($datetime) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $d && $d->format('Y-m-d H:i:s') === $datetime;
    }
    
    public function getSiteStats() {
        $stats = [];
        
        // Total sites
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");
        $stats['total'] = $stmt->fetchColumn();
        
        // Sites by activity status
        $stmt = $this->db->query("SELECT activity_status, COUNT(*) as count FROM {$this->table} WHERE activity_status IS NOT NULL GROUP BY activity_status");
        $statusStats = $stmt->fetchAll();
        foreach ($statusStats as $status) {
            $stats['by_status'][$status['activity_status']] = $status['count'];
        }
        
        // Survey status
        $stmt = $this->db->query("SELECT survey_status, COUNT(*) as count FROM {$this->table} GROUP BY survey_status");
        $surveyStats = $stmt->fetchAll();
        foreach ($surveyStats as $survey) {
            $stats['survey'][($survey['survey_status'] ? 'completed' : 'pending')] = $survey['count'];
        }
        
        // Installation status
        $stmt = $this->db->query("SELECT installation_status, COUNT(*) as count FROM {$this->table} GROUP BY installation_status");
        $installStats = $stmt->fetchAll();
        foreach ($installStats as $install) {
            $stats['installation'][($install['installation_status'] ? 'completed' : 'pending')] = $install['count'];
        }
        
        // Sites by state
        $stmt = $this->db->query("SELECT state, COUNT(*) as count FROM {$this->table} WHERE state IS NOT NULL GROUP BY state ORDER BY count DESC LIMIT 10");
        $stateStats = $stmt->fetchAll();
        foreach ($stateStats as $state) {
            $stats['by_state'][$state['state']] = $state['count'];
        }
        
        // Recent sites (last 30 days)
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stats['recent'] = $stmt->fetchColumn();
        
        return $stats;
    }
    
    public function updateSurveyStatus($id, $status, $submissionDate = null) {
        $data = ['survey_status' => $status ? 1 : 0];
        if ($submissionDate) {
            $data['survey_submission_date'] = $submissionDate;
        }
        return $this->update($id, $data);
    }
    
    public function updateInstallationStatus($id, $status, $installationDate = null) {
        $data = ['installation_status' => $status ? 1 : 0];
        if ($installationDate) {
            $data['installation_date'] = $installationDate;
        }
        return $this->update($id, $data);
    }
    
    public function delegateSite($id, $delegatedVendor) {
        return $this->update($id, [
            'is_delegate' => 1,
            'delegated_vendor' => $delegatedVendor
        ]);
    }
    
    public function undelegateSite($id) {
        return $this->update($id, [
            'is_delegate' => 0,
            'delegated_vendor' => null
        ]);
    }
    
    public function getAllSites() {
        $sql = "SELECT s.*, 
                       ct.name as city_name, st.name as state_name
                FROM {$this->table} s 
                LEFT JOIN cities ct ON s.city_id = ct.id 
                LEFT JOIN states st ON s.state_id = st.id 
                ORDER BY s.site_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>