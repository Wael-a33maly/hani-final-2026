<?php
require_once __DIR__ . '/../Core/Model.php';

class Installment extends Model {
    protected $table = 'installments';
    
    public function getWithInvoiceAndCustomer($id) {
        $stmt = $this->db->prepare("
            SELECT i.*, si.invoice_number, si.total, si.paid_upfront, si.date as invoice_date, si.warehouse_id, si.sales_rep_id as invoice_rep_id,
                   c.id as customer_id, c.name as customer_name, c.phone, c.area, c.address, c.sales_rep_id as customer_rep_id,
                   u.full_name as sales_rep_name
            FROM installments i
            LEFT JOIN sales_invoices si ON i.sales_invoice_id = si.id
            LEFT JOIN customers c ON si.customer_id = c.id
            LEFT JOIN users u ON COALESCE(si.sales_rep_id, c.sales_rep_id) = u.id
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function pay($installmentId, $amount, $userId, $notes = '') {
        $installment = $this->find($installmentId);
        if (!$installment) return false;
        if ($installment['status'] === 'paid') return false;
        
        $newPaid = $installment['paid_amount'] + $amount;
        $status = ($newPaid >= $installment['amount']) ? 'paid' : 'partial';
        
        $this->db->beginTransaction();
        try {
            // تحديث القسط
            $stmt = $this->db->prepare("UPDATE installments SET paid_amount = ?, status = ? WHERE id = ?");
            $stmt->execute([$newPaid, $status, $installmentId]);

            // تحديث المتبقي من الأقساط في الفاتورة
            $this->db->exec("UPDATE sales_invoices SET remaining_installment = GREATEST(0, remaining_installment - {$amount}) WHERE id = (SELECT sales_invoice_id FROM installments WHERE id = {$installmentId})");
            
            // تسجيل الدفع
            $stmt2 = $this->db->prepare("INSERT INTO installment_payments (installment_id, amount, payment_date, notes, received_by) VALUES (?, ?, CURDATE(), ?, ?)");
            $stmt2->execute([$installmentId, $amount, $notes, $userId]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
