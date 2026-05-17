<?php
require_once __DIR__ . '/../Core/Model.php';

class Product extends Model {
    protected $table = 'products';
    
    public function allWithUnit() {
        $stmt = $this->db->query("
            SELECT p.*, u.name as unit_name 
            FROM products p
            LEFT JOIN units u ON p.unit_id = u.id
            ORDER BY p.id DESC
        ");
        return $stmt->fetchAll();
    }
    
    public function findByBarcode($barcode) {
        $stmt = $this->db->prepare("SELECT * FROM products WHERE barcode = ? LIMIT 1");
        $stmt->execute([$barcode]);
        return $stmt->fetch();
    }
    
    public function search($keyword) {
        $stmt = $this->db->prepare("
            SELECT p.*, u.name as unit_name 
            FROM products p
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE p.barcode LIKE ? OR p.name LIKE ?
            LIMIT 20
        ");
        $keyword = "%$keyword%";
        $stmt->execute([$keyword, $keyword]);
        return $stmt->fetchAll();
    }
    
    public function getCurrentStock($productId, $warehouseId) {
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(
                CASE 
                    WHEN sm.type = 'in' THEN sm.quantity 
                    WHEN sm.type = 'out' THEN -sm.quantity 
                    ELSE 0 
                END
            ), 0) + COALESCE((
                SELECT SUM(quantity) FROM product_opening_balance 
                WHERE product_id = ? AND warehouse_id = ?
            ), 0) as current_stock
            FROM stock_movements sm
            WHERE sm.product_id = ? AND sm.warehouse_id = ?
            AND sm.reference != 'opening_balance'
        ");
        $stmt->execute([$productId, $warehouseId, $productId, $warehouseId]);
        return (float)$stmt->fetch()['current_stock'];
    }

    public function checkStock($productId, $warehouseId, $requiredQty) {
        $currentStock = $this->getCurrentStock($productId, $warehouseId);
        return $currentStock >= $requiredQty;
    }
}
