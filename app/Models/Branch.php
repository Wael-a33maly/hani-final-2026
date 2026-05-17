<?php
/**
 * app/Models/Branch.php
 */
require_once __DIR__ . '/../Core/Model.php';

class Branch extends Model {
    protected $table = 'branches';

    // جلب جميع الفروع مع اسم المدير
    public function allWithManager() {
        $stmt = $this->db->query("
            SELECT b.*, u.full_name as manager_name 
            FROM branches b
            LEFT JOIN users u ON b.manager_id = u.id
            ORDER BY b.id DESC
        ");
        return $stmt->fetchAll();
    }

    // جلب الفروع لقائمة الـ dropdown
    public function getOptions() {
        $stmt = $this->db->query("SELECT id, name FROM branches ORDER BY name");
        return $stmt->fetchAll();
    }
}
