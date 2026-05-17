<?php
require_once __DIR__ . '/../Core/Model.php';

class StockModel extends Model {
    protected $table = 'current_stock';
    
    /**
     * تحديث رصيد المخزون الحالي (زيادة أو نقصان)
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity الكمية (موجبة)
     * @param string $type 'add' أو 'subtract'
     * @return bool
     */
    public function updateStock($productId, $warehouseId, $quantity, $type = 'add') {
        $quantity = abs((float)$quantity);
        if ($type === 'subtract') {
            $quantity = -$quantity;
        }
        $sql = "INSERT INTO current_stock (product_id, warehouse_id, quantity) 
                VALUES (?, ?, ?) 
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$productId, $warehouseId, $quantity]);
    }
    
    /**
     * الحصول على الرصيد الحالي لمادة في مخزن معين
     * @return float
     */
    public function getStock($productId, $warehouseId) {
        $stmt = $this->db->prepare("SELECT quantity FROM current_stock WHERE product_id = ? AND warehouse_id = ?");
        $stmt->execute([$productId, $warehouseId]);
        $result = $stmt->fetch();
        return $result ? (float)$result['quantity'] : 0.0;
    }
    
    /**
     * التحقق من توفر الكمية المطلوبة
     * @return bool
     */
    public function checkStock($productId, $warehouseId, $requiredQty) {
        $current = $this->getStock($productId, $warehouseId);
        return $current >= (float)$requiredQty;
    }
    
    /**
     * إعادة حساب الرصيد بالكامل من stock_movements (للاستخدام عند التلف أو التصحيح)
     */
    public function recalculateAllStock() {
        // حذف كل الأرصدة الحالية
        $this->db->exec("TRUNCATE TABLE current_stock");
        
        // حساب الرصيد من الحركات (باستثناء رصيد أول المدة)
        $sql = "INSERT INTO current_stock (product_id, warehouse_id, quantity)
                SELECT product_id, warehouse_id, 
                       SUM(CASE WHEN type = 'in' THEN quantity ELSE -quantity END) as total
                FROM stock_movements
                WHERE reference != 'opening_balance'
                GROUP BY product_id, warehouse_id
                ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)";
        $this->db->exec($sql);

        // إضافة أرصدة أول المدة
        $sql2 = "INSERT INTO current_stock (product_id, warehouse_id, quantity)
                 SELECT product_id, warehouse_id, SUM(quantity)
                 FROM product_opening_balance
                 GROUP BY product_id, warehouse_id
                 ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)";
        $this->db->exec($sql2);
        return true;
    }
}
