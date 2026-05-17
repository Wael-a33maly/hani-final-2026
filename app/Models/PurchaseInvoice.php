<?php
require_once __DIR__ . '/../Core/Model.php';

class PurchaseInvoice extends Model {
    protected $table = 'purchase_invoices';
    
    public function generateInvoiceNumber() {
        $stmt = $this->db->query("SELECT MAX(id) as last_id FROM purchase_invoices");
        $last = $stmt->fetch()['last_id'];
        return 'PUR-' . str_pad($last + 1, 6, '0', STR_PAD_LEFT);
    }
    
    public function getWithSupplier($id) {
        $stmt = $this->db->prepare("
            SELECT pi.*, s.name as supplier_name 
            FROM purchase_invoices pi
            LEFT JOIN suppliers s ON pi.supplier_id = s.id
            WHERE pi.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getItems($invoiceId) {
        $stmt = $this->db->prepare("
            SELECT pii.*, p.name as product_name, p.barcode, u.name as unit_name
            FROM purchase_invoice_items pii
            LEFT JOIN products p ON pii.product_id = p.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE pii.invoice_id = ?
        ");
        $stmt->execute([$invoiceId]);
        return $stmt->fetchAll();
    }
}
