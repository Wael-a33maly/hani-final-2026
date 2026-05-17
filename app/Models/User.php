<?php
/**
 * app/Models/User.php
 */
require_once __DIR__ . '/../Core/Model.php';

class User extends Model {
    protected $table = 'users';

    // جلب مستخدم حسب اسم المستخدم
    public function findByUsername($username) {
        $stmt = $this->db->prepare(
            "SELECT * FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->execute([$username]);
        return $stmt->fetch();
    }

    // جلب جميع المستخدمين مع اسم الفرع
    public function allWithBranch() {
        $stmt = $this->db->query("
            SELECT u.*, b.name as branch_name 
            FROM users u
            LEFT JOIN branches b ON u.branch_id = b.id
            ORDER BY u.id DESC
        ");
        return $stmt->fetchAll();
    }

    // تحديث آخر تسجيل دخول
    public function updateLastLogin($userId) {
        $stmt = $this->db->prepare(
            "UPDATE users SET updated_at = NOW() WHERE id = ?"
        );
        return $stmt->execute([$userId]);
    }
}
