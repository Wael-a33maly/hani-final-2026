<?php
require_once __DIR__ . '/../Core/Model.php';

class Unit extends Model {
    protected $table = 'units';
    
    public function getOptions() {
        $stmt = $this->db->query("SELECT id, name FROM units ORDER BY name");
        return $stmt->fetchAll();
    }
}
