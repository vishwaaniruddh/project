<?php
require_once __DIR__ . '/BaseMasterController.php';
require_once __DIR__ . '/../models/Courier.php';

class CouriersController extends BaseMasterController {
    
    public function __construct() {
        parent::__construct();
        $this->model = new Courier();
        $this->modelName = 'Courier';
        $this->tableName = 'couriers';
    }
    
    protected function checkDependencies($id) {
        // Check if courier is used in material dispatches
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM material_dispatches WHERE courier_name = (SELECT courier_name FROM couriers WHERE id = ?)");
        $stmt->execute([$id]);
        $count = $stmt->fetchColumn();
        
        if ($count > 0) {
            return [
                'allowed' => false,
                'message' => "Cannot delete courier. It is associated with {$count} material dispatch(es)."
            ];
        }
        
        return ['allowed' => true, 'message' => ''];
    }
}
?>