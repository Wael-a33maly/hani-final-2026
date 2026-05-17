<?php
require_once __DIR__ . '/../Core/Model.php';

class InstallmentPayment extends Model {
    protected $table = 'installment_payments';
    
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT ip.*, i.installment_number, i.due_date, i.amount as installment_amount,
                       si.invoice_number, si.date as invoice_date, si.total as invoice_total,
                       c.id as customer_id, c.name as customer_name, c.area,
                       u.full_name as sales_rep_name
                FROM installment_payments ip
                LEFT JOIN installments i ON ip.installment_id = i.id
                LEFT JOIN sales_invoices si ON i.sales_invoice_id = si.id
                LEFT JOIN customers c ON si.customer_id = c.id
                LEFT JOIN users u ON c.sales_rep_id = u.id
                WHERE 1=1";
        $params = [];
        if (!empty($filters['from_date'])) {
            $sql .= " AND ip.payment_date >= ?";
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $sql .= " AND ip.payment_date <= ?";
            $params[] = $filters['to_date'];
        }
        if (!empty($filters['branch_id'])) {
            $sql .= " AND si.branch_id = ?";
            $params[] = $filters['branch_id'];
        }
        if (!empty($filters['sales_rep_id'])) {
            $sql .= " AND c.sales_rep_id = ?";
            $params[] = $filters['sales_rep_id'];
        }
        $sql .= " ORDER BY ip.payment_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getTotalReceipts($filters = []) {
        $sql = "SELECT COALESCE(SUM(ip.amount), 0) as total FROM installment_payments ip
                LEFT JOIN installments i ON ip.installment_id = i.id
                LEFT JOIN sales_invoices si ON i.sales_invoice_id = si.id
                LEFT JOIN customers c ON si.customer_id = c.id
                WHERE 1=1";
        $params = [];
        if (!empty($filters['from_date'])) {
            $sql .= " AND ip.payment_date >= ?";
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $sql .= " AND ip.payment_date <= ?";
            $params[] = $filters['to_date'];
        }
        if (!empty($filters['branch_id'])) {
            $sql .= " AND si.branch_id = ?";
            $params[] = $filters['branch_id'];
        }
        if (!empty($filters['sales_rep_id'])) {
            $sql .= " AND c.sales_rep_id = ?";
            $params[] = $filters['sales_rep_id'];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch()['total'];
    }
}
