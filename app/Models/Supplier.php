<?php
require_once __DIR__ . '/../Core/Model.php';

class Supplier extends Model {
    protected $table = 'suppliers';
    
    public function allWithBalance() {
        $stmt = $this->db->query("
            SELECT s.*,
                (SELECT COALESCE(SUM(total), 0) FROM purchase_invoices WHERE supplier_id = s.id) as total_purchases,
                (SELECT COALESCE(SUM(paid), 0) FROM supplier_payments WHERE supplier_id = s.id) as total_paid
            FROM suppliers s
            ORDER BY s.id DESC
        ");
        $results = $stmt->fetchAll();
        foreach ($results as &$row) {
            $row['balance'] = $row['total_purchases'] - $row['total_paid'];
        }
        return $results;
    }
    
    public function getOpeningBalance($supplierId) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM supplier_opening_balance WHERE supplier_id = ?");
        $stmt->execute([$supplierId]);
        return $stmt->fetch()['total'];
    }
}
