<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'Customer.php';
require_once MODELS_PATH . 'User.php';
require_once MODELS_PATH . 'CompanySetting.php';
require_once __DIR__ . '/../Helpers/PDFHelper.php';
require_once __DIR__ . '/../Core/Pagination.php';

class CustomerController extends Controller {
    public function index() {
        requireLogin();
        $db = getDB();
        $page = (int)($_GET['page'] ?? 1);
        
        $search = trim($_GET['search'] ?? '');
        $area = $_GET['area'] ?? '';
        $sales_rep_id = $_GET['sales_rep_id'] ?? '';
        
        $baseQuery = "SELECT c.*, u.full_name as rep_name FROM customers c LEFT JOIN users u ON c.sales_rep_id = u.id WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM customers c WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $baseQuery .= " AND (c.name LIKE ? OR c.phone LIKE ?)";
            $countQuery .= " AND (c.name LIKE ? OR c.phone LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if (!empty($area)) {
            $baseQuery .= " AND c.area = ?";
            $countQuery .= " AND c.area = ?";
            $params[] = $area;
        }
        if (!empty($sales_rep_id)) {
            $baseQuery .= " AND c.sales_rep_id = ?";
            $countQuery .= " AND c.sales_rep_id = ?";
            $params[] = $sales_rep_id;
        }
        
        $baseQuery .= " ORDER BY c.id DESC";
        $pagination = Pagination::paginate($db, $baseQuery, $countQuery, $params, $page, 20);
        $customers = $pagination['data'];
        
        $reps = $db->query("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('sales_rep', 'admin', 'branch_manager') ORDER BY u.full_name")->fetchAll();
        $areas = $db->query("SELECT DISTINCT area FROM customers WHERE area IS NOT NULL AND area != '' ORDER BY area")->fetchAll();
        
        $this->view('customers.index', compact('customers', 'pagination', 'search', 'area', 'sales_rep_id', 'reps', 'areas'));
    }
    
    public function create() {
        requireLogin();
        $userModel = new User();
        $reps = $userModel->all();
        $opening_installments = [];
        $this->view('customers.form', ['customer' => null, 'reps' => $reps, 'opening_installments' => $opening_installments]);
    }

    public function store() {
        requireLogin();
        $this->verifyCSRF();
        $db = getDB();
        $model = new Customer();
        $code = trim($_POST['code']);
        if (empty($code)) $code = $model->generateCode();
        
        $db->beginTransaction();
        try {
            $customerId = $model->insert([
                'code' => $code,
                'name' => trim($_POST['name']),
                'phone' => trim($_POST['phone']),
                'area' => trim($_POST['area']),
                'address' => trim($_POST['address']),
                'sales_rep_id' => $_POST['sales_rep_id'] ?: null
            ]);
            
            $openingInstallments = json_decode($_POST['opening_installments_json'] ?? '[]', true);
            foreach ($openingInstallments as $inst) {
                if (!empty($inst['amount']) && $inst['amount'] > 0) {
                    $stmt = $db->prepare("INSERT INTO customer_opening_balance (customer_id, installment_date, amount, notes) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$customerId, $inst['date'], $inst['amount'], $inst['notes']]);
                }
            }
            $db->commit();
            logAudit($this->userId, 'إضافة عميل وأرصدة افتتاحية', 'customers', $customerId, null, json_encode($_POST));
            $_SESSION['success'] = 'تم إضافة العميل بنجاح';
            redirect('/customers');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/customers/create');
        }
    }

    public function edit($id) {
        requireLogin();
        $model = new Customer();
        $customer = $model->find($id);
        if (!$customer) redirect('/customers');
        $userModel = new User();
        $reps = $userModel->all();
        
        $db = getDB();
        $stmt = $db->prepare("SELECT installment_date as date, amount, notes FROM customer_opening_balance WHERE customer_id = ?");
        $stmt->execute([$id]);
        $opening_installments = $stmt->fetchAll();
        
        $this->view('customers.form', ['customer' => $customer, 'reps' => $reps, 'opening_installments' => $opening_installments]);
    }

    public function update($id) {
        requireLogin();
        $this->verifyCSRF();
        $db = getDB();
        $model = new Customer();
        $oldData = json_encode($model->find($id));
        
        $db->beginTransaction();
        try {
            $model->update($id, [
                'code' => trim($_POST['code']),
                'name' => trim($_POST['name']),
                'phone' => trim($_POST['phone']),
                'area' => trim($_POST['area']),
                'address' => trim($_POST['address']),
                'sales_rep_id' => $_POST['sales_rep_id'] ?: null
            ]);
            
            $db->prepare("DELETE FROM customer_opening_balance WHERE customer_id = ?")->execute([$id]);
            $openingInstallments = json_decode($_POST['opening_installments_json'] ?? '[]', true);
            foreach ($openingInstallments as $inst) {
                if (!empty($inst['amount']) && $inst['amount'] > 0) {
                    $stmt = $db->prepare("INSERT INTO customer_opening_balance (customer_id, installment_date, amount, notes) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id, $inst['date'], $inst['amount'], $inst['notes']]);
                }
            }
            $db->commit();
            logAudit($this->userId, 'تعديل عميل وأرصدة افتتاحية', 'customers', $id, $oldData, json_encode($_POST));
            $_SESSION['success'] = 'تم التحديث بنجاح';
            redirect('/customers');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/customers/edit/' . $id);
        }
    }
    
    public function delete($id) {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM sales_invoices WHERE customer_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'لا يمكن حذف العميل لأن لديه فواتير مبيعات';
            redirect('/customers');
        }
        $stmt = $db->prepare("SELECT COUNT(*) FROM installments i JOIN sales_invoices si ON i.sales_invoice_id = si.id WHERE si.customer_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'لا يمكن حذف العميل لأن لديه أقساط';
            redirect('/customers');
        }
        $model = new Customer();
        $oldData = json_encode($model->find($id));
        $model->delete($id);
        logAudit($this->userId, 'حذف عميل', 'customers', $id, $oldData, null);
        $_SESSION['success'] = 'تم الحذف';
        redirect('/customers');
    }
    
    public function statement($id) {
        requireLogin();
        $model = new Customer();
        $customer = $model->find($id);
        if (!$customer) redirect('/customers');
        
        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');
        
        $db = getDB();

        $stmt_inst = $db->prepare("SELECT * FROM customer_opening_balance WHERE customer_id = ? ORDER BY installment_date");
        $stmt_inst->execute([$id]);
        $openingInstallments = $stmt_inst->fetchAll();
        $openingFromInst = array_sum(array_column($openingInstallments, 'amount'));

        $stmt_open = $db->prepare("
            SELECT (
                COALESCE((SELECT SUM(total) FROM sales_invoices WHERE customer_id = ? AND date < ?), 0)
                - COALESCE((SELECT SUM(ip.amount) FROM installment_payments ip 
                   JOIN installments i ON ip.installment_id = i.id 
                   JOIN sales_invoices si ON i.sales_invoice_id = si.id 
                   WHERE si.customer_id = ? AND ip.payment_date < ?), 0)
            ) as opening_balance
        ");
        $stmt_open->execute([$id, $from, $id, $from]);
        $opening = $stmt_open->fetchColumn() ?: 0;

        $stmt = $db->prepare("
            SELECT 'invoice' as type, date, invoice_number as ref, total as amount, 0 as paid
            FROM sales_invoices WHERE customer_id = ? AND date BETWEEN ? AND ?
            UNION ALL
            SELECT 'payment' as type, ip.payment_date as date, si.invoice_number as ref, 0 as amount, ip.amount as paid
            FROM installment_payments ip
            JOIN installments i ON ip.installment_id = i.id
            JOIN sales_invoices si ON i.sales_invoice_id = si.id
            WHERE si.customer_id = ? AND ip.payment_date BETWEEN ? AND ?
            ORDER BY date
        ");
        $stmt->execute([$id, $from, $to, $id, $from, $to]);
        $transactions = $stmt->fetchAll();

        $totalPaid = $opening < 0 ? abs($opening) : 0;
        $running = $opening;
        foreach ($transactions as $t) {
            if ($t['type'] == 'invoice') $running += $t['amount'];
            else $totalPaid += $t['paid'];
        }
        $totalOpening = $openingFromInst + $opening;
        $totalRemaining = ($totalOpening > 0 ? $totalOpening : 0) + array_sum(array_column($transactions, 'amount'));

        if (isset($_GET['pdf'])) {
            $settingsModel = new CompanySetting();
            $company = $settingsModel->getSettings();
            require __DIR__ . '/../Views/customers/statement_print.php';
            exit;
        }

        $this->view('customers.statement', compact('customer', 'transactions', 'from', 'to', 'opening', 'openingInstallments', 'openingFromInst', 'totalOpening', 'totalPaid', 'totalRemaining'));
    }

    public function search() {
        requireLogin();
        $q = trim($_GET['q'] ?? '');
        $db = getDB();
        // البحث في الاسم، الهاتف، العنوان، والمنطقة لضمان الشمولية
        $stmt = $db->prepare("SELECT * FROM customers WHERE name LIKE ? OR phone LIKE ? OR address LIKE ? OR area LIKE ? OR code LIKE ? LIMIT 20");
        $term = "%$q%";
        $stmt->execute([$term, $term, $term, $term, $term]);
        echo json_encode($stmt->fetchAll());
    }

    public function bulkCreate() {
        requireLogin();
        $db = getDB();
        $reps = $db->query("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('sales_rep', 'admin', 'branch_manager') ORDER BY u.full_name")->fetchAll();
        $this->view('customers.bulk_form', compact('reps'));
    }

    public function bulkStore() {
        requireLogin();
        $this->verifyCSRF();
        $db = getDB();
        $model = new Customer();
        $rows = json_decode($_POST['customers_json'] ?? '[]', true);

        if (empty($rows)) {
            $_SESSION['error'] = 'لم يتم إدخال أي عملاء';
            redirect('/customers/bulk-create');
        }

        $codes = $model->generateCodesBatch(count($rows));

        $db->beginTransaction();
        try {
            $inserted = 0;
            foreach ($rows as $i => $row) {
                $name = trim($row['name'] ?? '');
                if (empty($name)) continue;

                $model->insert([
                    'code' => $codes[$inserted],
                    'name' => $name,
                    'phone' => trim($row['phone'] ?? ''),
                    'area' => trim($row['area'] ?? ''),
                    'address' => trim($row['address'] ?? ''),
                    'sales_rep_id' => !empty($row['sales_rep_id']) ? $row['sales_rep_id'] : null
                ]);
                $inserted++;
            }
            $db->commit();
            logAudit($this->userId, 'إضافة عملاء مجمعين', 'customers', 0, null, json_encode(['count' => $inserted]));
            $_SESSION['success'] = "تم إضافة {$inserted} عميل/عملاء بنجاح";
            redirect('/customers');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/customers/bulk-create');
        }
    }

    public function importForm() {
        requireLogin();
        $db = getDB();
        $reps = $db->query("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('sales_rep', 'admin', 'branch_manager') ORDER BY u.full_name")->fetchAll();
        $this->view('customers.import', compact('reps'));
    }

    public function downloadSample() {
        requireLogin();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sample_customers.csv"');
        header('Content-Encoding: UTF-8');
        echo "\xEF\xBB\xBF";
        $fh = fopen('php://output', 'w');
        fputcsv($fh, ['الاسم', 'الهاتف', 'المنطقة', 'العنوان']);
        fputcsv($fh, ['محمد أحمد', '01012345678', 'القاهرة', 'شارع النصر، مدينة نصر']);
        fputcsv($fh, ['علي حسن', '01123456789', 'الجيزة', 'شارع الهرم']);
        fputcsv($fh, ['سارة خالد', '01234567890', 'الإسكندرية', 'طريق الجيش، سيدي بشر']);
        fclose($fh);
        exit;
    }

    public function importStore() {
        requireLogin();
        $this->verifyCSRF();

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'يرجى اختيار ملف CSV صالح';
            redirect('/customers/import');
        }

        $ext = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv' && $ext !== 'txt') {
            $_SESSION['error'] = 'الملف يجب أن يكون بصيغة CSV';
            redirect('/customers/import');
        }

        $defaultRepId = $_POST['default_sales_rep_id'] ?? null;

        $db = getDB();
        $model = new Customer();

        $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$handle) {
            $_SESSION['error'] = 'تعذر فتح الملف';
            redirect('/customers/import');
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            $_SESSION['error'] = 'ملف فارغ أو غير صالح';
            redirect('/customers/import');
        }

        $header = array_map('trim', $header);
        $colMap = ['الاسم' => 'name', 'الهاتف' => 'phone', 'المنطقة' => 'area', 'العنوان' => 'address'];
        $mapped = [];
        foreach ($header as $h) {
            $mapped[] = $colMap[$h] ?? null;
        }

        $rows = [];
        $lineErrors = [];
        $lineNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;
            $data = [];
            $hasName = false;
            foreach ($mapped as $i => $col) {
                $val = trim($row[$i] ?? '');
                if ($col === 'name') {
                    if (!empty($val)) $hasName = true;
                    $data['name'] = $val;
                } elseif ($col) {
                    $data[$col] = $val;
                }
            }
            if (!$hasName) {
                $lineErrors[] = "سطر {$lineNum}: الاسم مطلوب";
                continue;
            }
            $rows[] = $data;
        }
        fclose($handle);

        if (empty($rows)) {
            $_SESSION['error'] = 'لا توجد بيانات صالحة للاستيراد';
            if (!empty($lineErrors)) $_SESSION['import_errors'] = $lineErrors;
            redirect('/customers/import');
        }

        $codes = $model->generateCodesBatch(count($rows));

        $db->beginTransaction();
        try {
            $inserted = 0;
            foreach ($rows as $i => $row) {
                $model->insert([
                    'code' => $codes[$inserted],
                    'name' => $row['name'],
                    'phone' => $row['phone'] ?? '',
                    'area' => $row['area'] ?? '',
                    'address' => $row['address'] ?? '',
                    'sales_rep_id' => $defaultRepId ?: null
                ]);
                $inserted++;
            }
            $db->commit();
            logAudit($this->userId, 'استيراد عملاء من CSV', 'customers', 0, null, json_encode(['count' => $inserted]));
            $_SESSION['success'] = "تم استيراد {$inserted} عميل/عملاء بنجاح";
            if (!empty($lineErrors)) $_SESSION['import_errors'] = $lineErrors;
            redirect('/customers');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/customers/import');
        }
    }
}
