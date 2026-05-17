<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'Installment.php';
require_once MODELS_PATH . 'SalesInvoice.php';
require_once MODELS_PATH . 'CompanySetting.php';
require_once __DIR__ . '/../Helpers/PDFHelper.php';

class PrintController extends Controller {
    // صفحة طباعة الأقساط مع فلاتر
    public function installments() {
        requireLogin();
        $db = getDB();
        $customers = $db->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
        $reps = $db->query("SELECT id, full_name FROM users WHERE role = 'user' ORDER BY full_name")->fetchAll();
        $branches = $db->query("SELECT id, name FROM branches ORDER BY name")->fetchAll();
        
        $filters = $_GET;
        $installments = [];
        
        if (!empty($filters)) {
            $sql = "SELECT i.*, si.invoice_number, c.name as customer_name, c.phone 
                    FROM installments i
                    JOIN sales_invoices si ON i.sales_invoice_id = si.id
                    JOIN customers c ON si.customer_id = c.id
                    WHERE 1=1";
            $params = [];
            
            if (!empty($filters['customer_id'])) { $sql .= " AND c.id = ?"; $params[] = $filters['customer_id']; }
            if (!empty($filters['status'])) { $sql .= " AND i.status = ?"; $params[] = $filters['status']; }
            if (!empty($filters['from_date'])) { $sql .= " AND i.due_date >= ?"; $params[] = $filters['from_date']; }
            if (!empty($filters['to_date'])) { $sql .= " AND i.due_date <= ?"; $params[] = $filters['to_date']; }
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $installments = $stmt->fetchAll();
        }
        
        $this->view('print.installments', compact('customers', 'reps', 'branches', 'filters', 'installments'));
    }
    
    // طباعة إيصال الرصيد الافتتاحي
    public function openingReceipt($openingId) {
        requireLogin();
        $db = getDB();

        $stmt = $db->prepare("
            SELECT cob.*, c.name as customer_name, c.phone, c.area, c.address
            FROM customer_opening_balance cob
            JOIN customers c ON cob.customer_id = c.id
            WHERE cob.id = ?
        ");
        $stmt->execute([$openingId]);
        $opening = $stmt->fetch();
        if (!$opening) {
            die('الرصيد الافتتاحي غير موجود');
        }

        // جلب إجمالي المدفوعات
        $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM customer_opening_payments WHERE opening_id = ?");
        $stmt->execute([$openingId]);
        $totalPaid = (float)$stmt->fetchColumn();

        $settingsModel = new CompanySetting();
        $company = $settingsModel->getSettings();

        $this->view('print.opening_receipt', [
            'opening' => $opening,
            'totalPaid' => $totalPaid,
            'company' => $company,
        ]);
    }

    // طباعة إيصال القسط الواحد (مع جدول المنتجات)
    public function receipt($installmentId) {
        requireLogin();
        $db = getDB();
        $installmentModel = new Installment();
        
        try {
            $inst = $installmentModel->getWithInvoiceAndCustomer($installmentId);
            if (!$inst) {
                die('القسط غير موجود');
            }

            // إثراء بيانات القسط
            $inst = $this->enrichInstallmentData($db, $inst);
            
            $invoiceModel = new SalesInvoice();
            $items = $invoiceModel->getItems($inst['sales_invoice_id']);
            
            $settingsModel = new CompanySetting();
            $company = $settingsModel->getSettings();
            
            $this->view('print.receipt', [
                'installment' => $inst,
                'items' => $items,
                'company' => $company
            ]);
        } catch (Throwable $e) {
            error_log("Receipt Print Error (ID $installmentId): " . $e->getMessage());
            die("حدث خطأ أثناء تجهيز بيانات الإيصال للطباعة. يرجى مراجعة بيانات الفاتورة المرتبطة.");
        }
    }
    
    // طباعة متسلسلة لكل الأقساط المحددة
    public function bulkReceipts() {
        requireLogin();
        $db = getDB();
        $ids = explode(',', $_GET['ids'] ?? '');
        if (empty($ids) || (count($ids) == 1 && $ids[0] == '')) {
            $_SESSION['error'] = 'لم يتم تحديد أقساط';
            redirect('/print/installments');
        }

        $installmentModel = new Installment();
        $invoiceModel = new SalesInvoice();
        $installments = [];
        
        // جلب الأقساط المحددة مرتبة
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT i.id FROM installments i 
                JOIN sales_invoices si ON i.sales_invoice_id = si.id
                JOIN customers c ON si.customer_id = c.id
                WHERE i.id IN ($placeholders)
                ORDER BY c.name ASC, i.due_date ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute($ids);
        $sortedIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($sortedIds as $id) {
            $inst = $installmentModel->getWithInvoiceAndCustomer($id);
            if ($inst) {
                $inst = $this->enrichInstallmentData($db, $inst);
                $inst['items'] = $invoiceModel->getItems($inst['sales_invoice_id']);
                $installments[] = $inst;
            }
        }

        $settingsModel = new CompanySetting();
        $company = $settingsModel->getSettings();
        
        $this->view('print.bulk_receipts', compact('installments', 'company'));
    }

    // وظيفة مساعدة لتوحيد منطق جلب البيانات الإضافية للقسط
    private function enrichInstallmentData($db, $inst) {
        // 1. إجمالي عدد الأقساط
        $stmt = $db->prepare("SELECT COUNT(*) FROM installments WHERE sales_invoice_id = ?");
        $stmt->execute([$inst['sales_invoice_id']]);
        $inst['total_installments_count'] = $stmt->fetchColumn() ?: 1;

        // 2. تاريخ القسط التالي
        $stmt = $db->prepare("SELECT due_date FROM installments WHERE sales_invoice_id = ? AND installment_number > ? ORDER BY installment_number ASC LIMIT 1");
        $stmt->execute([$inst['sales_invoice_id'], $inst['installment_number']]);
        $inst['next_installment_date'] = $stmt->fetchColumn();

        // 3. المتبقي بعد دفع هذا القسط
        $stmt = $db->prepare("SELECT SUM(amount) FROM installments WHERE sales_invoice_id = ? AND installment_number > ?");
        $stmt->execute([$inst['sales_invoice_id'], $inst['installment_number']]);
        $inst['remaining_after_this'] = $stmt->fetchColumn() ?: 0;

        // 4. هاتف المندوب
        $actualRepId = ($inst['invoice_rep_id'] ?? 0) ?: ($inst['customer_rep_id'] ?? 0);
        $stmt = $db->prepare("SELECT phone FROM users WHERE id = ?");
        $stmt->execute([(int)$actualRepId]);
        $inst['sales_rep_phone'] = $stmt->fetchColumn() ?: '-';

        // 5. بيانات الفرع
        $stmt = $db->prepare("
            SELECT b.* FROM branches b 
            JOIN warehouses w ON w.branch_id = b.id 
            WHERE w.id = ?
        ");
        $stmt->execute([(int)($inst['warehouse_id'] ?? 0)]);
        $inst['branch_data'] = $stmt->fetch() ?: [];

        return $inst;
    }
}
