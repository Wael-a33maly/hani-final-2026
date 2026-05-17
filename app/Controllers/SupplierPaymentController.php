<?php
require_once __DIR__ . '/../Core/Controller.php';

class SupplierPaymentController extends Controller {
    
    // عرض جميع المدفوعات مع فلاتر
    public function index() {
        requireLogin();
        $db = getDB();
        $page = (int)($_GET['page'] ?? 1);
        
        $sql = "SELECT sp.*, s.name as supplier_name, u.full_name as created_by_name
                FROM supplier_payments sp
                LEFT JOIN suppliers s ON sp.supplier_id = s.id
                LEFT JOIN users u ON sp.created_by = u.id
                WHERE 1=1";
        $params = [];
        
        // فلاتر
        if (!empty($_GET['supplier_id'])) {
            $sql .= " AND sp.supplier_id = ?";
            $params[] = $_GET['supplier_id'];
        }
        if (!empty($_GET['from_date'])) {
            $sql .= " AND sp.payment_date >= ?";
            $params[] = $_GET['from_date'];
        }
        if (!empty($_GET['to_date'])) {
            $sql .= " AND sp.payment_date <= ?";
            $params[] = $_GET['to_date'];
        }
        if (!empty($_GET['payment_type'])) {
            $sql .= " AND sp.payment_type = ?";
            $params[] = $_GET['payment_type'];
        }
        $sql .= " ORDER BY sp.payment_date DESC";
        
        // حساب العدد الكلي للـ Pagination
        $countSql = "SELECT COUNT(*) FROM supplier_payments sp WHERE 1=1";
        $countParams = [];
        if (!empty($_GET['supplier_id'])) {
            $countSql .= " AND sp.supplier_id = ?";
            $countParams[] = $_GET['supplier_id'];
        }
        if (!empty($_GET['from_date'])) {
            $countSql .= " AND sp.payment_date >= ?";
            $countParams[] = $_GET['from_date'];
        }
        if (!empty($_GET['to_date'])) {
            $countSql .= " AND sp.payment_date <= ?";
            $countParams[] = $_GET['to_date'];
        }
        if (!empty($_GET['payment_type'])) {
            $countSql .= " AND sp.payment_type = ?";
            $countParams[] = $_GET['payment_type'];
        }
        
        require_once __DIR__ . '/../Core/Pagination.php';
        $pagination = Pagination::paginate($db, $sql, $countSql, $params, $page, 20);
        $payments = $pagination['data'];
        
        // حساب إجمالي الصفحة الحالية أو إجمالي البحث
        $totalSql = "SELECT SUM(amount) FROM supplier_payments sp WHERE 1=1";
        if (!empty($_GET['supplier_id'])) $totalSql .= " AND sp.supplier_id = " . (int)$_GET['supplier_id'];
        if (!empty($_GET['from_date'])) $totalSql .= " AND sp.payment_date >= '" . $_GET['from_date'] . "'";
        if (!empty($_GET['to_date'])) $totalSql .= " AND sp.payment_date <= '" . $_GET['to_date'] . "'";
        if (!empty($_GET['payment_type'])) $totalSql .= " AND sp.payment_type = '" . $_GET['payment_type'] . "'";
        $total = $db->query($totalSql)->fetchColumn() ?: 0;
        
        // جلب قائمة الموردين للفلتر
        $suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();
        
        $this->view('supplier_payments.index', [
            'payments' => $payments,
            'total' => $total,
            'suppliers' => $suppliers,
            'pagination' => $pagination
        ]);
    }
    
    // نموذج إضافة دفعة جديدة
    public function create() {
        requireLogin();
        $db = getDB();
        $suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();
        $this->view('supplier_payments.form', ['suppliers' => $suppliers]);
    }
    
    // حفظ دفعة جديدة
    public function store() {
        requireLogin();
        $this->verifyCSRF();
        
        $supplierId = (int)$_POST['supplier_id'];
        $amount = (float)$_POST['amount'];
        $paymentDate = $_POST['payment_date'];
        $paymentType = $_POST['payment_type'];
        $notes = trim($_POST['notes'] ?? '');
        
        if ($supplierId <= 0 || $amount <= 0) {
            $_SESSION['error'] = 'بيانات الدفع غير صحيحة';
            redirect('/supplier-payments/create');
        }
        
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO supplier_payments (supplier_id, amount, payment_date, payment_type, notes, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$supplierId, $amount, $paymentDate, $paymentType, $notes, $this->userId]);
        $newId = $db->lastInsertId();

        logAudit($this->userId, 'إضافة دفعة مورد', 'supplier_payments', $newId, null, json_encode($_POST));
        $_SESSION['success'] = 'تم تسجيل الدفعة بنجاح';
        redirect('/supplier-payments');
    }
    
    // حذف دفعة (للمدير فقط)
    public function delete($id) {
        requireRole('admin');
        $this->verifyCSRF();
        
        $db = getDB();
        $stmt_get = $db->prepare("SELECT * FROM supplier_payments WHERE id = ?");
        $stmt_get->execute([$id]);
        $oldData = json_encode($stmt_get->fetch());

        $stmt = $db->prepare("DELETE FROM supplier_payments WHERE id = ?");
        $stmt->execute([$id]);
        
        logAudit($this->userId, 'حذف دفعة مورد', 'supplier_payments', $id, $oldData, null);
        $_SESSION['success'] = 'تم حذف الدفعة';
        redirect('/supplier-payments');
    }
}
