<?php
require_once __DIR__ . '/../Core/Model.php';

class Customer extends Model {
    protected $table = 'customers';
    
    public function allWithRep() {
        $stmt = $this->db->query("
            SELECT c.*, u.full_name as rep_name 
            FROM customers c
            LEFT JOIN users u ON c.sales_rep_id = u.id
            ORDER BY c.id DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function generateCode() {
        $stmt = $this->db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(code, 6) AS UNSIGNED)), 0) FROM customers WHERE code LIKE 'CUST-%'");
        $maxNum = (int) $stmt->fetchColumn();
        return 'CUST-' . str_pad($maxNum + 1, 6, '0', STR_PAD_LEFT);
    }

    public function generateCodesBatch($count) {
        $stmt = $this->db->query("SELECT COALESCE(MAX(CAST(SUBSTRING(code, 6) AS UNSIGNED)), 0) FROM customers WHERE code LIKE 'CUST-%'");
        $startNum = (int) $stmt->fetchColumn() + 1;
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = 'CUST-' . str_pad($startNum + $i, 6, '0', STR_PAD_LEFT);
        }
        return $codes;
    }

    public function getOpeningBalance($customerId) {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM customer_opening_balance WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        return $stmt->fetch()['total'];
    }
    
    public function getInstallmentsList($customerId) {
        $stmt = $this->db->prepare("
            SELECT i.*, si.invoice_number, si.total, si.paid_upfront
            FROM installments i
            LEFT JOIN sales_invoices si ON i.sales_invoice_id = si.id
            WHERE si.customer_id = ?
            ORDER BY i.due_date DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll();
    }
}
