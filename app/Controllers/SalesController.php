<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'SalesInvoice.php';
require_once MODELS_PATH . 'Customer.php';
require_once MODELS_PATH . 'Product.php';
require_once MODELS_PATH . 'Warehouse.php';
require_once MODELS_PATH . 'Installment.php';
require_once MODELS_PATH . 'User.php';
require_once __DIR__ . '/../Core/Pagination.php';
if (!function_exists('currentUserRole') && file_exists(__DIR__ . '/../Helpers/PermissionHelper.php')) {
    require_once __DIR__ . '/../Helpers/PermissionHelper.php';
}

class SalesController extends Controller
{
    public function index()
    {
        requireLogin();
        $db = getDB();
        $page = (int) ($_GET['page'] ?? 1);

        $customer_id = $_GET['customer_id'] ?? '';
        $from_date = $_GET['from_date'] ?? '';
        $to_date = $_GET['to_date'] ?? '';
        $payment_type = $_GET['payment_type'] ?? '';

        $baseQuery = "SELECT si.*, c.name as customer_name,
                       (SELECT COUNT(*) FROM installments WHERE sales_invoice_id = si.id) as total_installments,
                       (si.paid_upfront + (SELECT COALESCE(SUM(paid_amount), 0) FROM installments WHERE sales_invoice_id = si.id)) as total_paid,
                       (si.total - (si.paid_upfront + (SELECT COALESCE(SUM(paid_amount), 0) FROM installments WHERE sales_invoice_id = si.id))) as total_remaining
                FROM sales_invoices si 
                LEFT JOIN customers c ON si.customer_id = c.id 
                WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM sales_invoices si WHERE 1=1";
        $params = [];

        if (!empty($customer_id)) {
            $baseQuery .= " AND si.customer_id = ?";
            $countQuery .= " AND si.customer_id = ?";
            $params[] = $customer_id;
        }
        if (!empty($from_date)) {
            $baseQuery .= " AND si.date >= ?";
            $countQuery .= " AND si.date >= ?";
            $params[] = $from_date;
        }
        if (!empty($to_date)) {
            $baseQuery .= " AND si.date <= ?";
            $countQuery .= " AND si.date <= ?";
            $params[] = $to_date;
        }
        if (!empty($payment_type)) {
            $baseQuery .= " AND si.payment_type = ?";
            $countQuery .= " AND si.payment_type = ?";
            $params[] = $payment_type;
        }

        $role = currentUserRole();
        $roleName = $role['role_name'] ?? '';
        if ($roleName !== 'admin') {
            $branchFilter = PermissionHelper::filterByWarehouseBranch('', $_SESSION['user_id'], 'si');
            $baseQuery .= $branchFilter;
            $countQuery .= $branchFilter;
        }

        $baseQuery .= " ORDER BY si.id DESC";
        $pagination = Pagination::paginate($db, $baseQuery, $countQuery, $params, $page, 20);
        $invoices = $pagination['data'];

        $customers = $db->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
        $this->view('sales.index', compact('invoices', 'pagination', 'customers', 'customer_id', 'from_date', 'to_date', 'payment_type'));
    }

    public function create()
    {
        requireLogin();
        $customerModel = new Customer();
        $customers = $customerModel->allWithRep();
        $productModel = new Product();
        $products = $productModel->allWithUnit();
        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->getOptions();
        $userModel = new User();
        $reps = $userModel->all(); // جلب كافة المستخدمين (المناديب)

        $this->view('sales.form', [
            'customers' => $customers,
            'products' => $products,
            'warehouses' => $warehouses,
            'reps' => $reps,
            'invoice' => null
        ]);
    }

    public function store()
    {
        requireLogin();
        $this->verifyCSRF();
        $db = getDB();
        $db->beginTransaction();
        try {
            $invoiceModel = new SalesInvoice();
            $invoiceNumber = $invoiceModel->generateInvoiceNumber();
            $total = max(0, (float) ($_POST['total'] ?? 0));
            if ($total <= 0) throw new Exception('المبلغ الإجمالي يجب أن يكون أكبر من صفر');
            $paymentType = $_POST['payment_type'] ?? '';
            $paidUpfront = ($paymentType == 'installment') ? max(0, (float) ($_POST['paid_upfront'] ?? 0)) : 0;

            // ✅ تحويل إلى قروش (integers) لتجنب أخطاء التقريب
            $totalCents = (int) round($total * 100);
            $upfrontCents = (int) round($paidUpfront * 100);
            $remainingCents = $totalCents - $upfrontCents;

            // التحقق من وجود العميل
            if (empty($_POST['customer_id'])) {
                throw new Exception("يجب اختيار العميل من قائمة نتائج البحث أولاً");
            }

            $remainingInstallment = ($paymentType == 'installment') ? ($remainingCents / 100) : 0;
            $invoiceId = $invoiceModel->insert([
                'invoice_number' => $invoiceNumber,
                'date' => $_POST['date'],
                'customer_id' => $_POST['customer_id'],
                'sales_rep_id' => $_POST['sales_rep_id'] ?? null,
                'payment_type' => $_POST['payment_type'],
                'total' => $_POST['total'],
                'paid_upfront' => $_POST['paid_upfront'] ?? 0,
                'remaining_installment' => $remainingInstallment,
                'warehouse_id' => $_POST['warehouse_id'],
                'notes' => $_POST['notes'],
                'created_by' => $_SESSION['user_id']
            ]);

            // تفاصيل الفاتورة
            $products = json_decode($_POST['products_json'], true);
            if (empty($products)) throw new Exception('يجب إضافة مادة واحدة على الأقل');
            require_once MODELS_PATH . 'StockModel.php';
            $stockModel = new StockModel();
            foreach ($products as $item) {
                $qty = max(0, (float) ($item['quantity'] ?? 0));
                if ($qty <= 0) throw new Exception('الكمية يجب أن تكون أكبر من صفر');
                if (!$stockModel->checkStock($item['product_id'], $_POST['warehouse_id'], $qty)) {
                    $productModel = new Product();
                    $prod = $productModel->find($item['product_id']);
                    $current = $stockModel->getStock($item['product_id'], $_POST['warehouse_id']);
                    throw new Exception("الكمية المطلوبة للمادة (" . $prod['name'] . ") غير متوفرة. الرصيد الحالي: " . $current);
                }
                $stmt = $db->prepare("INSERT INTO sales_invoice_items (invoice_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$invoiceId, $item['product_id'], $item['quantity'], $item['unit_price']]);
                $saleItemId = $db->lastInsertId();

                // تخفيض المخزون الحالي
                $stockModel->updateStock($item['product_id'], $_POST['warehouse_id'], $item['quantity'], 'subtract');

                // سجل حركات المخزون
                $stmt2 = $db->prepare("INSERT INTO stock_movements (product_id, warehouse_id, type, quantity, reference, reference_id) VALUES (?, ?, 'out', ?, 'sale', ?)");
                $stmt2->execute([$item['product_id'], $_POST['warehouse_id'], $item['quantity'], $invoiceId]);

                // حساب وتسجيل عمولة المبيعات للمندوب (إن وجد)
                $salesRepId = $_POST['sales_rep_id'] ?? null;
                if ($salesRepId) {
                    try {
                        $commStmt = $db->prepare("SELECT commission_amount, name FROM products WHERE id = ?");
                        $commStmt->execute([$item['product_id']]);
                        $prodComm = $commStmt->fetch();
                        $commissionPerUnit = (float) ($prodComm['commission_amount'] ?? 0);

                        if ($commissionPerUnit > 0) {
                            $totalCommission = round($commissionPerUnit * (float) $item['quantity'], 2);
                            $commInsert = $db->prepare("
                                INSERT INTO sales_commissions
                                (sale_id, sale_item_id, user_id, product_id, product_name,
                                 quantity, commission_amount_per_unit, total_commission,
                                 commission_date, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                            ");
                            $commInsert->execute([
                                $invoiceId,
                                $saleItemId,
                                $salesRepId,
                                $item['product_id'],
                                $prodComm['name'],
                                $item['quantity'],
                                $commissionPerUnit,
                                $totalCommission,
                                date('Y-m-d')
                            ]);
                        }
                    } catch (Exception $e) {
                        error_log("فشل تسجيل عمولة المبيعات: " . $e->getMessage());
                    }
                }
            }

            // معالجة الأقساط (إذا كان تقسيط والمتبقي > 0)
            if ($paymentType == 'installment' && $remainingCents > 0) {
                $numInstallments = (int) $_POST['num_installments'];
                $installmentValueCents = (int) round((float) $_POST['installment_value'] * 100);
                $firstDate = $_POST['first_installment_date'];

                // التحقق من صحة المدخلات
                if ($numInstallments <= 0 || $installmentValueCents <= 0) {
                    throw new Exception("عدد الأقساط وقيمة القسط يجب أن تكون أكبر من صفر");
                }

                // حساب قيمة آخر قسط بالضبط
                $totalInstallmentsNormalCents = $installmentValueCents * ($numInstallments - 1);
                $lastInstallmentCents = $remainingCents - $totalInstallmentsNormalCents;

                // تخزين بيانات الأقساط
                for ($i = 1; $i <= $numInstallments; $i++) {
                    $dueDate = date('Y-m-d', strtotime("+" . ($i - 1) . " months", strtotime($firstDate)));
                    $amountCents = ($i == $numInstallments) ? $lastInstallmentCents : $installmentValueCents;
                    $amount = $amountCents / 100;
                    $stmt = $db->prepare("INSERT INTO installments (sales_invoice_id, installment_number, due_date, amount, status) VALUES (?, ?, ?, ?, 'pending')");
                    $stmt->execute([$invoiceId, $i, $dueDate, $amount]);
                }
            }

            $db->commit();
            logAudit($this->userId, 'إضافة فاتورة مبيعات', 'sales_invoices', $invoiceId, null, json_encode(['customer_id' => $_POST['customer_id'], 'total' => $_POST['total'], 'invoice_number' => $invoiceNumber]));
            $_SESSION['success'] = 'تم حفظ الفاتورة بنجاح';
            redirect('/sales');
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Sales invoice error: " . $e->getMessage());
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/sales/create');
        }
    }

    public function show($id)
    {
        requireLogin();
        $invoiceModel = new SalesInvoice();
        $invoice = $invoiceModel->getWithCustomer($id);
        if (!$invoice)
            redirect('/sales');

        $items = $invoiceModel->getItems($id);
        $installments = $invoiceModel->getInstallments($id);

        require_once MODELS_PATH . 'CompanySetting.php';
        $settingsModel = new CompanySetting();
        $settings = $settingsModel->getSettings();

        $this->view('sales.show', compact('invoice', 'items', 'installments', 'settings'));
    }

    public function getCustomerData($id)
    {
        requireLogin();
        $customerModel = new Customer();
        $customer = $customerModel->find($id);
        header('Content-Type: application/json');
        echo json_encode($customer);
    }

    public function edit($id)
    {
        requireLogin();
        $invoiceModel = new SalesInvoice();
        $invoice = $invoiceModel->getWithCustomer($id);
        if (!$invoice)
            redirect('/sales');

        $items = $invoiceModel->getItems($id);
        $installments = $invoiceModel->getInstallments($id);

        $customerModel = new Customer();
        $customers = $customerModel->allWithRep();
        $warehouseModel = new Warehouse();
        $warehouses = $warehouseModel->getOptions();
        $userModel = new User();
        $reps = $userModel->all();

        $this->view('sales.edit', compact('invoice', 'items', 'installments', 'customers', 'warehouses', 'reps'));
    }

    public function update($id)
    {
        requireLogin();
        $this->verifyCSRF();
        $db = getDB();
        $invoiceModel = new SalesInvoice();
        $oldInvoice = $invoiceModel->find($id);
        if (!$oldInvoice)
            redirect('/sales');

        $db->beginTransaction();
        try {
            $total = (float) $_POST['total'];
            $paymentType = $_POST['payment_type'];
            $paidUpfront = (float) ($_POST['paid_upfront'] ?? 0);

            // 1. تحديث الفاتورة الأساسية
            $invoiceModel->update($id, [
                'date' => $_POST['date'],
                'customer_id' => $_POST['customer_id'],
                'sales_rep_id' => $_POST['sales_rep_id'] ?? null,
                'payment_type' => $paymentType,
                'total' => $total,
                'paid_upfront' => $paidUpfront,
                'warehouse_id' => $_POST['warehouse_id'],
                'notes' => $_POST['notes']
            ]);

            // 2. تحديث الأصناف (تبسيطاً: حذف القديم وإضافة الجديد مع تعديل المخزون)
            require_once MODELS_PATH . 'StockModel.php';
            $stockModel = new StockModel();
            $oldItems = $invoiceModel->getItems($id);
            foreach ($oldItems as $oi) {
                $stockModel->updateStock($oi['product_id'], $oldInvoice['warehouse_id'], $oi['quantity'], 'add');
            }
            $db->prepare("DELETE FROM sales_invoice_items WHERE invoice_id = ?")->execute([$id]);

            // إلغاء العمولات القديمة وإعادة الإنشاء (مع try/catch لعدم كسر التعديل)
            try {
                $db->prepare("UPDATE sales_commissions SET status='cancelled', paid_at=NOW() WHERE sale_id = ? AND status='pending'")->execute([$id]);
            } catch (Exception $e) {
                error_log("فشل إلغاء العمولات القديمة: " . $e->getMessage());
            }

            $salesRepId = $_POST['sales_rep_id'] ?? null;
            $products = json_decode($_POST['products_json'], true);
            foreach ($products as $item) {
                $stmt = $db->prepare("INSERT INTO sales_invoice_items (invoice_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$id, $item['product_id'], $item['quantity'], $item['unit_price']]);
                $saleItemId = $db->lastInsertId();
                $stockModel->updateStock($item['product_id'], $_POST['warehouse_id'], $item['quantity'], 'subtract');

                // إعادة إنشاء عمولة المندوب لهذا السطر
                if ($salesRepId) {
                    try {
                        $prodStmt = $db->prepare("SELECT commission_amount, name FROM products WHERE id = ?");
                        $prodStmt->execute([$item['product_id']]);
                        $prodComm = $prodStmt->fetch();
                        $commissionPerUnit = (float) ($prodComm['commission_amount'] ?? 0);

                        if ($commissionPerUnit > 0) {
                            $totalCommission = round($commissionPerUnit * (float) $item['quantity'], 2);
                            $commInsert = $db->prepare("
                                INSERT INTO sales_commissions
                                (sale_id, sale_item_id, user_id, product_id, product_name,
                                 quantity, commission_amount_per_unit, total_commission,
                                 commission_date, status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                            ");
                            $commInsert->execute([
                                $id,
                                $saleItemId,
                                $salesRepId,
                                $item['product_id'],
                                $prodComm['name'],
                                $item['quantity'],
                                $commissionPerUnit,
                                $totalCommission,
                                date('Y-m-d')
                            ]);
                        }
                    } catch (Exception $e) {
                        error_log("فشل إعادة إنشاء عمولة المبيعات: " . $e->getMessage());
                    }
                }
            }

            // 4. معالجة الأقساط
            if ($paymentType == 'installment') {
                $instData = json_decode($_POST['installments_json'], true);
                $totalCents = (int) round($total * 100);
                $paidUpfrontCents = (int) round($paidUpfront * 100);
                $sumInstCents = 0;

                foreach ($instData as $inst) {
                    $amountCents = (int) round((float) $inst['amount'] * 100);
                    $sumInstCents += $amountCents;

                    // تحديث فقط الأقساط التي لم تدفع بعد
                    if (isset($inst['id']) && $inst['status'] !== 'paid') {
                        $stmt = $db->prepare("UPDATE installments SET amount = ?, due_date = ? WHERE id = ? AND status != 'paid'");
                        $stmt->execute([$inst['amount'], $inst['due_date'], $inst['id']]);
                    }
                }

                // التحقق: المقدم + مجموع الأقساط = الإجمالي
                if (($paidUpfrontCents + $sumInstCents) !== $totalCents) {
                    $expectedTotal = ($paidUpfrontCents + $sumInstCents) / 100;
                    throw new Exception("خطأ في التوازن: (المقدم: $paidUpfront + مجموع الأقساط: " . ($sumInstCents / 100) . " = $expectedTotal) لا يساوي إجمالي الفاتورة ($total)");
                }
            }

            $db->commit();
            $_SESSION['success'] = 'تم تحديث الفاتورة بنجاح';
            redirect('/sales/show/' . $id);
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/sales/edit/' . $id);
        }
    }
}
