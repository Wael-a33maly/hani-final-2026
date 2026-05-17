<?php
require_once __DIR__ . '/../Core/Model.php';

class SalesInvoice extends Model {
    protected $table = 'sales_invoices';
    
    public function generateInvoiceNumber() {
        $stmt = $this->db->query("SELECT MAX(id) as last_id FROM sales_invoices");
        $last = $stmt->fetch()['last_id'];
        return 'INV-' . str_pad($last + 1, 6, '0', STR_PAD_LEFT);
    }
    
    public function getWithCustomer($id) {
        $stmt = $this->db->prepare("
            SELECT si.*, c.name as customer_name, c.phone as customer_phone, c.area, c.address,
                   w.name as warehouse_name,
                   u.full_name as sales_rep_name, u.phone as sales_rep_phone
            FROM sales_invoices si
            LEFT JOIN customers c ON si.customer_id = c.id
            LEFT JOIN warehouses w ON si.warehouse_id = w.id
            LEFT JOIN users u ON COALESCE(si.sales_rep_id, c.sales_rep_id) = u.id
            WHERE si.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getItems($invoiceId) {
        $stmt = $this->db->prepare("
            SELECT sii.*, p.name as product_name, p.barcode, u.name as unit_name
            FROM sales_invoice_items sii
            LEFT JOIN products p ON sii.product_id = p.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE sii.invoice_id = ?
        ");
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }
    
    public function getInstallments($invoiceId) {
        $stmt = $this->db->prepare("SELECT * FROM installments WHERE sales_invoice_id = ? ORDER BY installment_number");
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }
}
