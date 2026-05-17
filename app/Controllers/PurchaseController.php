<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'PurchaseInvoice.php';
require_once MODELS_PATH . 'Supplier.php';
require_once MODELS_PATH . 'Product.php';
require_once __DIR__ . '/../Core/Pagination.php';
if (!function_exists('currentUserRole') && file_exists(__DIR__ . '/../Helpers/PermissionHelper.php')) {
    require_once __DIR__ . '/../Helpers/PermissionHelper.php';
}

class PurchaseController extends Controller {
    public function index() {
        requireLogin();
        $db = getDB();
        $page = (int)($_GET['page'] ?? 1);
        
        $supplier_id = $_GET['supplier_id'] ?? '';
        $from_date = $_GET['from_date'] ?? '';
        $to_date = $_GET['to_date'] ?? '';
        $payment_type = $_GET['payment_type'] ?? '';
        
        $baseQuery = "SELECT pi.*, s.name as supplier_name FROM purchase_invoices pi LEFT JOIN suppliers s ON pi.supplier_id = s.id WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM purchase_invoices pi WHERE 1=1";
        $params = [];
        
        if (!empty($supplier_id)) {
            $baseQuery .= " AND pi.supplier_id = ?";
            $countQuery .= " AND pi.supplier_id = ?";
            $params[] = $supplier_id;
        }
        if (!empty($from_date)) {
            $baseQuery .= " AND pi.date >= ?";
            $countQuery .= " AND pi.date >= ?";
            $params[] = $from_date;
        }
        if (!empty($to_date)) {
            $baseQuery .= " AND pi.date <= ?";
            $countQuery .= " AND pi.date <= ?";
            $params[] = $to_date;
        }
        if (!empty($payment_type)) {
            $baseQuery .= " AND pi.payment_type = ?";
            $countQuery .= " AND pi.payment_type = ?";
            $params[] = $payment_type;
        }

        $role = currentUserRole();
        $roleName = $role['role_name'] ?? '';
        if ($roleName !== 'admin') {
            $branchFilter = PermissionHelper::filterByWarehouseBranch('', $_SESSION['user_id'], 'pi');
            $baseQuery .= $branchFilter;
            $countQuery .= $branchFilter;
        }
        
        $baseQuery .= " ORDER BY pi.id DESC";
        $pagination = Pagination::paginate($db, $baseQuery, $countQuery, $params, $page, 20);
        $invoices = $pagination['data'];
        
        $suppliers = $db->query("SELECT id, name FROM suppliers ORDER BY name")->fetchAll();
        $this->view('purchase.index', compact('invoices', 'pagination', 'suppliers', 'supplier_id', 'from_date', 'to_date', 'payment_type'));
    }
    
    public function create() {
        requireLogin();
        $db = getDB();
        $supplierModel = new Supplier();
        $suppliers = $supplierModel->all();
        $productModel = new Product();
        $products = $productModel->allWithUnit();
        $warehouses = $db->query("SELECT id, name FROM warehouses ORDER BY name")->fetchAll();
        $this->view('purchase.form', compact('suppliers', 'products', 'warehouses'));
    }
    
    public function store() {
        requireLogin();
        $this->verifyCSRF();
        $db = getDB();

        $supplierId = (int) ($_POST['supplier_id'] ?? 0);
        if ($supplierId <= 0) {
            $_SESSION['error'] = 'يجب اختيار مورد من قائمة نتائج البحث أولاً';
            redirect('/purchases/create');
        }
        $checkSupplier = $db->prepare("SELECT id FROM suppliers WHERE id = ?");
        $checkSupplier->execute([$supplierId]);
        if (!$checkSupplier->fetch()) {
            $_SESSION['error'] = 'المورد المحدد غير موجود في قاعدة البيانات';
            redirect('/purchases/create');
        }

        $warehouseId = (int) ($_POST['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            $_SESSION['error'] = 'يجب اختيار المخزن';
            redirect('/purchases/create');
        }

        $db->beginTransaction();
        try {
            $invoiceModel = new PurchaseInvoice();
            $invoiceNumber = $invoiceModel->generateInvoiceNumber();
            $total = max(0, (float) ($_POST['total'] ?? 0));
            if ($total <= 0) throw new Exception('المبلغ الإجمالي يجب أن يكون أكبر من صفر');
            $invoiceId = $invoiceModel->insert([
                'invoice_number' => $invoiceNumber,
                'date' => $_POST['date'],
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'payment_type' => $_POST['payment_type'],
                'total' => $total,
                'notes' => $_POST['notes'],
                'created_by' => $this->userId
            ]);
            // إضافة تفاصيل الفاتورة
            $products = json_decode($_POST['products_json'], true);
            require_once MODELS_PATH . 'StockModel.php';
            $stockModel = new StockModel();
            foreach ($products as $item) {
                $stmt = $db->prepare("INSERT INTO purchase_invoice_items (invoice_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$invoiceId, $item['product_id'], $item['quantity'], $item['unit_price']]);
                
                // تحديث المخزون في المخزن المختار
                $stockModel->updateStock($item['product_id'], $warehouseId, $item['quantity'], 'add');
                
                // سجل حركات المخزون
                $stmt2 = $db->prepare("INSERT INTO stock_movements (product_id, warehouse_id, type, quantity, reference, reference_id) VALUES (?, ?, 'in', ?, 'purchase', ?)");
                $stmt2->execute([$item['product_id'], $warehouseId, $item['quantity'], $invoiceId]);
            }
            $db->commit();
            logAudit($this->userId, 'إضافة فاتورة مشتريات', 'purchase_invoices', $invoiceId, null, json_encode(['supplier_id' => $_POST['supplier_id'], 'total' => $_POST['total'], 'invoice_number' => $invoiceNumber]));
            $_SESSION['success'] = 'تم حفظ فاتورة المشتريات';
            redirect('/purchases');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'حدث خطأ: ' . $e->getMessage();
            redirect('/purchases/create');
        }
    }
    
    public function show($id) {
        requireLogin();
        $invoiceModel = new PurchaseInvoice();
        $invoice = $invoiceModel->getWithSupplier($id);
        $items = $invoiceModel->getItems($id);
        $this->view('purchase.show', ['invoice' => $invoice, 'items' => $items]);
    }
}
