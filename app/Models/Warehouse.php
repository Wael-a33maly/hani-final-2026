<?php
require_once __DIR__ . '/../Core/Model.php';

class Warehouse extends Model {
    protected $table = 'warehouses';
    
    public function allWithBranch() {
        $stmt = $this->db->query("
            SELECT w.*, b.name as branch_name 
            FROM warehouses w
            LEFT JOIN branches b ON w.branch_id = b.id
            ORDER BY w.id DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function getOptions() {
        $stmt = $this->db->query("SELECT id, name FROM warehouses ORDER BY name");
        return $stmt->fetchAll();
    }
    
    public function getByBranch($branchId) {
        $stmt = $this->db->prepare("SELECT id, name FROM warehouses WHERE branch_id = ? ORDER BY name");
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }
}
