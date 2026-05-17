<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'CompanySetting.php';
require_once MODELS_PATH . 'Supplier.php';
require_once __DIR__ . '/../Helpers/PDFHelper.php';
require_once __DIR__ . '/../Core/Pagination.php';

class SupplierController extends Controller {
    public function index() {
        requireLogin();
        $db = getDB();
        $page = (int)($_GET['page'] ?? 1);
        $search = trim($_GET['search'] ?? '');
        
        $baseQuery = "SELECT s.*, 
                      (SELECT COALESCE(SUM(total), 0) FROM purchase_invoices WHERE supplier_id = s.id) as total_purchases,
                      (SELECT COALESCE(SUM(amount), 0) FROM supplier_payments WHERE supplier_id = s.id) as total_paid,
                      (SELECT COALESCE(SUM(amount), 0) FROM supplier_opening_balance WHERE supplier_id = s.id) as total_opening
                      FROM suppliers s WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM suppliers s WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $baseQuery .= " AND (s.name LIKE ? OR s.phone LIKE ?)";
            $countQuery .= " AND (s.name LIKE ? OR s.phone LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $baseQuery .= " ORDER BY s.id DESC";
        $pagination = Pagination::paginate($db, $baseQuery, $countQuery, $params, $page, 20);
        $suppliers = $pagination['data'];
        foreach ($suppliers as &$row) {
            $row['balance'] = $row['total_purchases'] - $row['total_paid'] - $row['total_opening'];
        }
        
        $this->view('suppliers.index', [
            'suppliers' => $suppliers, 
            'pagination' => $pagination,
            'search' => $search
        ]);
    }
    
    public function create() {
        requireRole('admin');
        $this->view('suppliers.form', ['supplier' => null, 'supplier_opening_date' => date('Y-m-d'), 'supplier_opening_amount' => 0]);
    }

    public function store() {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();
        $model = new Supplier();
        $code = trim($_POST['code']);
        if (empty($code)) $code = 'SUP-' . time();
        
        $db->beginTransaction();
        try {
            $supplierId = $model->insert([
                'code' => $code,
                'name' => trim($_POST['name']),
                'phone' => trim($_POST['phone']),
                'address' => trim($_POST['address'])
            ]);
            // رصيد أول المدة
            $openingAmount = (float)($_POST['opening_amount'] ?? 0);
            if ($openingAmount != 0) {
                $stmt = $db->prepare("INSERT INTO supplier_opening_balance (supplier_id, date, amount) VALUES (?, ?, ?)");
                $stmt->execute([$supplierId, $_POST['opening_date'], $openingAmount]);
            }
            $db->commit();
            logAudit($this->userId, 'إضافة مورد ورصيد أول مدة', 'suppliers', $supplierId, null, json_encode($_POST));
            $_SESSION['success'] = 'تم إضافة المورد ورصيد أول المدة';
            redirect('/suppliers');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/suppliers/create');
        }
    }

    public function edit($id) {
        requireRole('admin');
        $model = new Supplier();
        $supplier = $model->find($id);
        if (!$supplier) redirect('/suppliers');
        $db = getDB();
        $stmt = $db->prepare("SELECT date, amount FROM supplier_opening_balance WHERE supplier_id = ? ORDER BY id LIMIT 1");
        $stmt->execute([$id]);
        $opening = $stmt->fetch();
        $this->view('suppliers.form', [
            'supplier' => $supplier,
            'supplier_opening_date' => $opening['date'] ?? date('Y-m-d'),
            'supplier_opening_amount' => $opening['amount'] ?? 0
        ]);
    }

    public function update($id) {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();
        $model = new Supplier();
        $oldData = json_encode($model->find($id));
        
        $db->beginTransaction();
        try {
            $model->update($id, [
                'code' => trim($_POST['code']),
                'name' => trim($_POST['name']),
                'phone' => trim($_POST['phone']),
                'address' => trim($_POST['address'])
            ]);
            // تحديث رصيد أول المدة
            $db->prepare("DELETE FROM supplier_opening_balance WHERE supplier_id = ?")->execute([$id]);
            $openingAmount = (float)($_POST['opening_amount'] ?? 0);
            if ($openingAmount != 0) {
                $stmt = $db->prepare("INSERT INTO supplier_opening_balance (supplier_id, date, amount) VALUES (?, ?, ?)");
                $stmt->execute([$id, $_POST['opening_date'], $openingAmount]);
            }
            $db->commit();
            logAudit($this->userId, 'تعديل مورد ورصيد أول مدة', 'suppliers', $id, $oldData, json_encode($_POST));
            $_SESSION['success'] = 'تم التحديث بنجاح';
            redirect('/suppliers');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/suppliers/edit/' . $id);
        }
    }
    
    public function delete($id) {
        requireRole('admin');
        $this->verifyCSRF();
        $model = new Supplier();
        $oldData = json_encode($model->find($id));
        $model->delete($id);
        logAudit($this->userId, 'حذف مورد', 'suppliers', $id, $oldData, null);
        $_SESSION['success'] = 'تم الحذف';
        redirect('/suppliers');
    }
    
    public function statement($id) {
        requireLogin();
        $supplierModel = new Supplier();
        $supplier = $supplierModel->find($id);
        if (!$supplier) redirect('/suppliers');
        
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        
        $db = getDB();
        // جلب الرصيد الافتتاحي (دائماً يضم الرصيد الافتتاحي بغض النظر عن تاريخه)
        $stmt_open = $db->prepare("
            SELECT (
                -(SELECT COALESCE(SUM(amount), 0) FROM supplier_opening_balance WHERE supplier_id = ?) +
                (SELECT COALESCE(SUM(total), 0) FROM purchase_invoices WHERE supplier_id = ? AND date < ?) -
                (SELECT COALESCE(SUM(amount), 0) FROM supplier_payments WHERE supplier_id = ? AND payment_date < ?)
            ) as opening_balance
        ");
        $stmt_open->execute([$id, $id, $from, $id, $from]);
        $opening = $stmt_open->fetchColumn() ?: 0;
        
        // استعلام موحد يجمع الفواتير والمدفوعات ضمن الفترة
        $stmt = $db->prepare("
            SELECT 'invoice' as type, date, invoice_number as ref, total as amount, 0 as payment, 'فاتورة مشتريات' as description
            FROM purchase_invoices WHERE supplier_id = ? AND date BETWEEN ? AND ?
            UNION ALL
            SELECT 'payment' as type, payment_date as date, 'دفعة' as ref, 0 as amount, amount as payment, notes as description
            FROM supplier_payments WHERE supplier_id = ? AND payment_date BETWEEN ? AND ?
            ORDER BY date
        ");
        $stmt->execute([$id, $from, $to, $id, $from, $to]);
        $transactions = $stmt->fetchAll();
        
        if (isset($_GET['pdf'])) {
            $settingsModel = new CompanySetting();
            $company = $settingsModel->getSettings();
            require __DIR__ . '/../Views/suppliers/statement_print.php';
            exit;
        }
        
        $this->view('suppliers.statement', compact('supplier', 'transactions', 'from', 'to', 'opening'));
    }

    public function search() {
        requireLogin();
        $q = trim($_GET['q'] ?? '');
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, phone, address FROM suppliers WHERE name LIKE ? OR phone LIKE ? OR address LIKE ? OR code LIKE ? LIMIT 20");
        $term = "%$q%";
        $stmt->execute([$term, $term, $term, $term]);
        echo json_encode($stmt->fetchAll());
    }
}
