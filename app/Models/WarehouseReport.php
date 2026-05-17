<?php
require_once __DIR__ . '/../Core/Model.php';

class WarehouseReport extends Model {
    // جرد مادة في كل المخازن
    public function getProductStockInAllWarehouses($productId) {
        $stmt = $this->db->prepare("
            SELECT w.id, w.name as warehouse_name, 
                   COALESCE(b.name, 'بدون فرع') as branch_name,
                   COALESCE(SUM(CASE WHEN sm.type = 'in' THEN sm.quantity ELSE -sm.quantity END), 0) 
                   + COALESCE((SELECT SUM(pob.quantity) FROM product_opening_balance pob WHERE pob.product_id = ? AND pob.warehouse_id = w.id), 0) as quantity
            FROM warehouses w
            LEFT JOIN branches b ON w.branch_id = b.id
            LEFT JOIN stock_movements sm ON w.id = sm.warehouse_id AND sm.product_id = ? AND sm.reference != 'opening_balance'
            GROUP BY w.id
            ORDER BY w.name
        ");
        $stmt->execute([$productId, $productId]);
        return $stmt->fetchAll();
    }
    
    // حركة مادة بين تاريخين
    public function getProductMovements($productId, $fromDate, $toDate) {
        $stmt = $this->db->prepare("
            SELECT sm.*, w.name as warehouse_name,
                   CASE WHEN sm.type = 'in' THEN 'وارد' ELSE 'صادر' END as type_ar,
                   sm.reference, sm.reference_id, sm.created_at
            FROM stock_movements sm
            LEFT JOIN warehouses w ON sm.warehouse_id = w.id
            WHERE sm.product_id = ? AND DATE(sm.created_at) BETWEEN ? AND ? AND sm.reference != 'opening_balance'
            ORDER BY sm.created_at ASC
        ");
        $stmt->execute([$productId, $fromDate, $toDate]);
        return $stmt->fetchAll();
    }

    // جلب رصيد أول المدة لمادة (مجموع كل المخازن)
    public function getProductOpeningBalance($productId) {
        $stmt = $this->db->prepare("
            SELECT SUM(quantity) as total_qty, COALESCE(MIN(date), CURDATE()) as bal_date
            FROM product_opening_balance
            WHERE product_id = ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }
    
    // جرد مستودع معين (كل المواد فيه)
    public function getWarehouseStock($warehouseId) {
        $stmt = $this->db->prepare("
            SELECT p.id as product_id, p.name as product_name, p.barcode, u.name as unit_name,
                   COALESCE(SUM(CASE WHEN sm.type = 'in' THEN sm.quantity ELSE -sm.quantity END), 0)
                   + COALESCE((SELECT SUM(quantity) FROM product_opening_balance WHERE product_id = p.id AND warehouse_id = ?), 0) as stock,
                   p.selling_price
            FROM products p
            LEFT JOIN units u ON p.unit_id = u.id
            LEFT JOIN stock_movements sm ON p.id = sm.product_id AND sm.warehouse_id = ? AND sm.reference != 'opening_balance'
            GROUP BY p.id
            HAVING stock != 0
            ORDER BY p.name
        ");
        $stmt->execute([$warehouseId, $warehouseId]);
        return $stmt->fetchAll();
    }
    
    // جرد فرع (كل مخازن الفرع)
    public function getBranchStock($branchId) {
        $stmt = $this->db->prepare("
            SELECT p.id as product_id, p.name as product_name, p.barcode, u.name as unit_name,
                   COALESCE(SUM(CASE WHEN sm.type = 'in' THEN sm.quantity ELSE -sm.quantity END), 0)
                   + COALESCE((SELECT SUM(pob.quantity) FROM product_opening_balance pob WHERE pob.product_id = p.id AND pob.warehouse_id = w.id), 0) as stock,
                   w.name as warehouse_name
            FROM products p
            LEFT JOIN units u ON p.unit_id = u.id
            JOIN warehouses w ON w.branch_id = ?
            LEFT JOIN stock_movements sm ON p.id = sm.product_id AND sm.warehouse_id = w.id AND sm.reference != 'opening_balance'
            GROUP BY p.id, w.id
            HAVING stock != 0
            ORDER BY p.name, w.name
        ");
        $stmt->execute([$branchId]);
        return $stmt->fetchAll();
    }
    
    // تحويل بين المخازن (تسجيل وتحريك)
    public function createTransfer($fromWarehouseId, $toWarehouseId, $productId, $quantity, $userId, $notes = '') {
        // التحقق من الرصيد
        require_once MODELS_PATH . 'StockModel.php';
        $stockModel = new StockModel();
        if (!$stockModel->checkStock($productId, $fromWarehouseId, $quantity)) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $transferNumber = 'TRF-' . time();
            // تسجيل التحويل
            $stmt = $this->db->prepare("INSERT INTO warehouse_transfers (transfer_number, from_warehouse_id, to_warehouse_id, product_id, quantity, transfer_date, notes, created_by) VALUES (?, ?, ?, ?, ?, CURDATE(), ?, ?)");
            $stmt->execute([$transferNumber, $fromWarehouseId, $toWarehouseId, $productId, $quantity, $notes, $userId]);
            
            // حركة صادر من المخزن المصدر
            $stmt2 = $this->db->prepare("INSERT INTO stock_movements (product_id, warehouse_id, type, quantity, reference, reference_id) VALUES (?, ?, 'out', ?, 'transfer', ?)");
            $stmt2->execute([$productId, $fromWarehouseId, $quantity, $transferNumber]);
            $stockModel->updateStock($productId, $fromWarehouseId, $quantity, 'subtract');
            
            // حركة وارد إلى المخزن الوجهة
            $stmt3 = $this->db->prepare("INSERT INTO stock_movements (product_id, warehouse_id, type, quantity, reference, reference_id) VALUES (?, ?, 'in', ?, 'transfer', ?)");
            $stmt3->execute([$productId, $toWarehouseId, $quantity, $transferNumber]);
            $stockModel->updateStock($productId, $toWarehouseId, $quantity, 'add');
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    // عهدة بضاعة للمندوب: إسناد، مبيعات، استرداد، تقرير
    public function assignToSalesRep($salesRepId, $productId, $quantity, $fromWarehouseId, $userId) {
        // التحقق من الرصيد
        require_once MODELS_PATH . 'StockModel.php';
        $stockModel = new StockModel();
        if (!$stockModel->checkStock($productId, $fromWarehouseId, $quantity)) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            // إضافة أو تحديث عهدة المندوب
            $stmt = $this->db->prepare("INSERT INTO sales_rep_stock (sales_rep_id, product_id, quantity, assigned_from_warehouse_id, assigned_date) VALUES (?, ?, ?, ?, CURDATE())
                                        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
            $stmt->execute([$salesRepId, $productId, $quantity, $fromWarehouseId]);
            
            // خصم من المخزن
            $stmt2 = $this->db->prepare("INSERT INTO stock_movements (product_id, warehouse_id, type, quantity, reference, reference_id) VALUES (?, ?, 'out', ?, 'sales_rep_assign', ?)");
            $stmt2->execute([$productId, $fromWarehouseId, $quantity, $salesRepId]);
            $stockModel->updateStock($productId, $fromWarehouseId, $quantity, 'subtract');
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function getSalesRepStockReport($salesRepId) {
        $stmt = $this->db->prepare("
            SELECT srs.*, p.name as product_name, p.barcode, u.name as unit_name,
                   COALESCE((SELECT SUM(quantity) FROM sales_rep_sales WHERE sales_rep_stock_id = srs.id), 0) as sold,
                   COALESCE((SELECT SUM(quantity) FROM sales_rep_return WHERE sales_rep_stock_id = srs.id), 0) as returned,
                   (srs.quantity - 
                    COALESCE((SELECT SUM(quantity) FROM sales_rep_sales WHERE sales_rep_stock_id = srs.id), 0) - 
                    COALESCE((SELECT SUM(quantity) FROM sales_rep_return WHERE sales_rep_stock_id = srs.id), 0)
                   ) as current_stock
            FROM sales_rep_stock srs
            LEFT JOIN products p ON srs.product_id = p.id
            LEFT JOIN units u ON p.unit_id = u.id
            WHERE srs.sales_rep_id = ?
        ");
        $stmt->execute([$salesRepId]);
        return $stmt->fetchAll();
    }

    public function recordSalesRepSale($repStockId, $quantity, $customerId, $priceType, $price, $userId) {
        $this->db->beginTransaction();
        try {
            // تسجيل المبيع
            $stmt = $this->db->prepare("INSERT INTO sales_rep_sales (sales_rep_stock_id, quantity, sale_date, customer_id, price_type, price) VALUES (?, ?, CURDATE(), ?, ?, ?)");
            $stmt->execute([$repStockId, $quantity, $customerId, $priceType, $price]);
            
            // ملاحظة: لا نخصم من المخزن العام لأنها خصمت عند الإسناد للمندوب
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function returnFromSalesRep($repStockId, $quantity, $toWarehouseId, $userId) {
        $this->db->beginTransaction();
        try {
            // تسجيل الاسترداد
            $stmt = $this->db->prepare("INSERT INTO sales_rep_return (sales_rep_stock_id, quantity, return_to_warehouse_id, return_date) VALUES (?, ?, ?, CURDATE())");
            $stmt->execute([$repStockId, $quantity, $toWarehouseId]);
            
            // جلب الـ product_id
            $stmt_prod = $this->db->prepare("SELECT product_id FROM sales_rep_stock WHERE id = ?");
            $stmt_prod->execute([$repStockId]);
            $productId = $stmt_prod->fetchColumn();
            
            // إضافة للمخزن
            require_once MODELS_PATH . 'StockModel.php';
            $stockModel = new StockModel();
            $stmt2 = $this->db->prepare("INSERT INTO stock_movements (product_id, warehouse_id, type, quantity, reference, reference_id) VALUES (?, ?, 'in', ?, 'sales_rep_return', ?)");
            $stmt2->execute([$productId, $toWarehouseId, $quantity, $repStockId]);
            $stockModel->updateStock($productId, $toWarehouseId, $quantity, 'add');
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
