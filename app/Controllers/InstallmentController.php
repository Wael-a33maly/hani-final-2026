<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'Installment.php';
require_once MODELS_PATH . 'InstallmentPayment.php';
require_once MODELS_PATH . 'SalesInvoice.php';

require_once __DIR__ . '/../Core/Pagination.php';

class InstallmentController extends Controller
{
    public function index()
    {
        requireLogin();
        $db = getDB();
        $page = (int) ($_GET['page'] ?? 1);

        try {
            $installments = [];
            $branches = [];
            $reps = [];
            $statsPending = 0;
            $statsPaid = 0;
            $statsOverdue = 0;
            $statsTotal = 0;

            // بناء الاستعلام مع الفلاتر
            $sql = "SELECT i.*, si.invoice_number, si.date as invoice_date, si.total, si.paid_upfront,
                           c.id as customer_id, c.name as customer_name, c.phone, c.area,
                           u.full_name as sales_rep_name, b.name as branch_name
                    FROM installments i
                    LEFT JOIN sales_invoices si ON i.sales_invoice_id = si.id
                    LEFT JOIN customers c ON si.customer_id = c.id
                    LEFT JOIN users u ON COALESCE(si.sales_rep_id, c.sales_rep_id) = u.id
                    LEFT JOIN warehouses w ON si.warehouse_id = w.id
                    LEFT JOIN branches b ON w.branch_id = b.id
                    WHERE 1=1";
            $params = [];

            $today = date('Y-m-d');
            if (!empty($_GET['status'])) {
                if ($_GET['status'] === 'overdue') {
                    $sql .= " AND i.status = 'pending' AND i.due_date < '{$today}'";
                } else {
                    $sql .= " AND i.status = ?";
                    $params[] = $_GET['status'];
                }
            }
            if (!empty($_GET['branch_id'])) {
                $sql .= " AND si.warehouse_id IN (SELECT id FROM warehouses WHERE branch_id = ?)";
                $params[] = $_GET['branch_id'];
            }
            if (!empty($_GET['sales_rep_id'])) {
                $sql .= " AND c.sales_rep_id = ?";
                $params[] = $_GET['sales_rep_id'];
            }
            if (!empty($_GET['from_date'])) {
                $sql .= " AND i.due_date >= ?";
                $params[] = $_GET['from_date'];
            }
            if (!empty($_GET['to_date'])) {
                $sql .= " AND i.due_date <= ?";
                $params[] = $_GET['to_date'];
            }

            $roleName = $_SESSION['user_role'] ?? '';
            if (function_exists('currentUserRole')) {
                $role = currentUserRole();
                $roleName = $role['role_name'] ?? $roleName;
            }
            $isNotAdmin = $roleName !== 'admin';
            if ($isNotAdmin && class_exists('PermissionHelper')) {
                $branchFilter = PermissionHelper::filterByBranch('', $_SESSION['user_id'], 'b');
                $sql .= $branchFilter;
            }
            $sql .= " ORDER BY i.due_date ASC";

            $countQuery = "SELECT COUNT(*) FROM (" . $sql . ") as sub";
            $pagination = Pagination::paginate($db, $sql, $countQuery, $params, $page, 20);
            $installments = $pagination['data'];

        // جلب أرصدة افتتاحية (أقساط من قبل التطبيق) - مع الحماية من عدم وجود الجدول
        try {
            $openingStmt = $db->query("
                SELECT cob.id, cob.customer_id, c.name as customer_name, c.phone, c.area,
                       NULL as invoice_number, NULL as installment_number,
                       cob.installment_date as due_date, cob.amount as amount,
                       cob.paid_amount, cob.status, cob.notes as notes,
                       NULL as sales_invoice_id, NULL as sales_rep_name, NULL as branch_name,
                       NULL as invoice_date, NULL as total, NULL as paid_upfront,
                       1 as is_opening
                FROM customer_opening_balance cob
                JOIN customers c ON cob.customer_id = c.id
                ORDER BY cob.installment_date DESC
            ");
            $openingInstallments = $openingStmt->fetchAll();
        } catch (\Throwable $e) {
            $openingInstallments = [];
        }

        // دمج الأرصدة الافتتاحية مع الأقساط العادية
        $installments = array_merge($installments, $openingInstallments);
        usort($installments, function ($a, $b) {
            return strcmp($a['due_date'], $b['due_date']);
        });
        $pagination['data'] = $installments;

        // إحصائيات الأقساط للكروت
        $branchJoin = '';
        if ($roleName !== 'admin' && class_exists('PermissionHelper')) {
            $userBranches = PermissionHelper::getUserBranches($_SESSION['user_id']);
            if (!empty($userBranches)) {
                $branchIds = implode(',', array_map('intval', $userBranches));
                $branchJoin = " AND i.sales_invoice_id IN (SELECT si.id FROM sales_invoices si JOIN warehouses w ON si.warehouse_id = w.id WHERE w.branch_id IN ({$branchIds}))";
            }
        }
        $statsPending  = $db->query("SELECT COUNT(*) FROM installments WHERE status = 'pending' AND due_date >= '{$today}'{$branchJoin}")->fetchColumn();
        $statsPaid     = $db->query("SELECT COUNT(*) FROM installments WHERE status = 'paid'{$branchJoin}")->fetchColumn();
        $statsOverdue  = $db->query("SELECT COUNT(*) FROM installments WHERE status = 'pending' AND due_date < '{$today}'{$branchJoin}")->fetchColumn();
        $openingPending = 0; $openingPaid = 0; $openingOverdue = 0;
        foreach ($openingInstallments as $op) {
            if ($op['status'] === 'paid') {
                $openingPaid++;
            } else {
                $openingPending++;
                if ($op['due_date'] < $today) {
                    $openingOverdue++;
                }
            }
        }
        $statsPaid    += $openingPaid;
        $statsPending += $openingPending;
        $statsOverdue += $openingOverdue;
        $statsTotal    = $statsPending + $statsPaid;

        // جلب الفروع والمندوبين للفلاتر
        $branches = $db->query("SELECT id, name FROM branches")->fetchAll();
        $reps = $db->query("SELECT u.id, u.full_name FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('sales_rep', 'branch_manager', 'admin')")->fetchAll();

        } catch (\Throwable $e) {
            $installments = [];
            $branches = [];
            $reps = [];
            $pagination = ['data' => [], 'total' => 0, 'current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'links' => ''];
            $statsPending = 0; $statsPaid = 0; $statsOverdue = 0; $statsTotal = 0;
            error_log('InstallmentController::index error: ' . $e->getMessage());
        }

        $this->view('installments.index', compact('installments', 'branches', 'reps', 'pagination', 'statsPending', 'statsPaid', 'statsOverdue', 'statsTotal'));
    }

    public function pay($id)
    {
        requireLogin();
        $this->verifyCSRF();
        $amount = $_POST['amount'] ?? 0;
        $notes = $_POST['notes'] ?? '';
        if ($amount <= 0) {
            $_SESSION['error'] = 'المبلغ غير صحيح';
            redirect('/installments');
        }

        $db = getDB();
        $installmentModel = new Installment();
        $installment = $installmentModel->find($id);

        if ($installment) {
            if ($installment['status'] === 'paid') {
                $_SESSION['error'] = 'هذا القسط مدفوع بالفعل';
                redirect('/installments');
            }
            // قسط عادي
            $oldData = json_encode($installment);
            $result = $installmentModel->pay($id, $amount, $this->userId, $notes);
            if ($result) {
                logAudit($this->userId, 'دفع قسط', 'installments', $id, $oldData, json_encode(['amount' => $amount, 'notes' => $notes]));
                $this->recordCollectionCommission($id, $amount);
                $_SESSION['success'] = 'تم تسجيل الدفع بنجاح';
            } else {
                $_SESSION['error'] = 'حدث خطأ أثناء الدفع';
            }
        } else {
            // رصيد افتتاحي
            $stmt = $db->prepare("SELECT * FROM customer_opening_balance WHERE id = ?");
            $stmt->execute([$id]);
            $opening = $stmt->fetch();
            if (!$opening) {
                $_SESSION['error'] = 'القسط غير موجود';
                redirect('/installments');
            }
            if ($opening['status'] === 'paid') {
                $_SESSION['error'] = 'هذا الرصيد الافتتاحي مدفوع بالفعل';
                redirect('/installments');
            }

            $newPaid = $opening['paid_amount'] + $amount;
            $status = ($newPaid >= $opening['amount']) ? 'paid' : 'partial';

            $db->beginTransaction();
            try {
                $stmt = $db->prepare("UPDATE customer_opening_balance SET paid_amount = ?, status = ? WHERE id = ?");
                $stmt->execute([$newPaid, $status, $id]);

                $stmt = $db->prepare("INSERT INTO customer_opening_payments (opening_id, amount, payment_date, notes, received_by) VALUES (?, ?, CURDATE(), ?, ?)");
                $stmt->execute([$id, $amount, $notes, $this->userId]);

                $db->commit();
                logAudit($this->userId, 'دفع رصيد افتتاحي', 'customer_opening_balance', $id, json_encode($opening), json_encode(['amount' => $amount, 'notes' => $notes]));
                $_SESSION['success'] = 'تم تسجيل دفع الرصيد الافتتاحي بنجاح';
            } catch (Exception $e) {
                $db->rollBack();
                $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            }
        }
        redirect('/installments');
    }

    public function payMultiple()
    {
        requireLogin();
        $this->verifyCSRF();
        $ids = $_POST['installment_ids'] ?? [];
        if (empty($ids)) {
            $_SESSION['error'] = 'لم يتم اختيار أي أقساط';
            redirect('/installments');
        }

        $db = getDB();
        $db->beginTransaction();
        try {
            $installmentModel = new Installment();
            $count = 0;
            foreach ($ids as $id) {
                $installment = $installmentModel->find($id);
                if ($installment && $installment['status'] != 'paid') {
                    $remaining = $installment['amount'] - $installment['paid_amount'];
                    if ($remaining > 0) {
                        if ($installmentModel->pay($id, $remaining, $this->userId, 'دفع متعدد')) {
                            $count++;
                            logAudit($this->userId, 'دفع قسط (متعدد)', 'installments', $id, json_encode($installment), json_encode(['amount' => $remaining, 'notes' => 'دفع متعدد']));

                            // حساب عمولة التحصيل للمندوب
                            $this->recordCollectionCommission($id, $remaining);
                        } else {
                            throw new Exception("فشل في دفع القسط رقم $id");
                        }
                    }
                }
            }
            $db->commit();
            $_SESSION['success'] = "تم دفع $count قسط/أقساط بنجاح";
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
        }
        redirect('/installments');
    }

    /**
     * تسجيل عمولة التحصيل للمندوب عند دفع قسط
     */
    private function recordCollectionCommission($installmentId, $paidAmount)
    {
        try {
            $db = getDB();

            $stmt = $db->prepare("
                SELECT i.*, si.id as sale_id, si.sales_rep_id, si.customer_id,
                       c.name as customer_name
                FROM installments i
                LEFT JOIN sales_invoices si ON i.sales_invoice_id = si.id
                LEFT JOIN customers c ON si.customer_id = c.id
                WHERE i.id = ?
            ");
            $stmt->execute([$installmentId]);
            $data = $stmt->fetch();

            if (!$data || !$data['sales_rep_id'])
                return;

            $rateStmt = $db->prepare("SELECT collection_commission_rate FROM users WHERE id = ?");
            $rateStmt->execute([$data['sales_rep_id']]);
            $userData = $rateStmt->fetch();
            $rate = (float) ($userData['collection_commission_rate'] ?? 0);

            if ($rate > 0) {
                $commissionAmount = round($paidAmount * $rate / 100, 2);

                $payStmt = $db->prepare("SELECT id FROM installment_payments WHERE installment_id = ? ORDER BY id DESC LIMIT 1");
                $payStmt->execute([$installmentId]);
                $payment = $payStmt->fetch();

                $commInsert = $db->prepare("
                    INSERT INTO collection_commissions
                    (installment_id, installment_payment_id, sale_id, user_id,
                     customer_id, customer_name, collected_amount, commission_rate,
                     commission_amount, collection_date, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $commInsert->execute([
                    $installmentId,
                    $payment['id'] ?? null,
                    $data['sale_id'],
                    $data['sales_rep_id'],
                    $data['customer_id'],
                    $data['customer_name'] ?? '',
                    $paidAmount,
                    $rate,
                    $commissionAmount,
                    date('Y-m-d')
                ]);
            }
        } catch (Exception $e) {
            error_log("فشل تسجيل عمولة التحصيل: " . $e->getMessage());
        }
    }

    public function delete($id)
    {
        requireRole('admin');
        $this->verifyCSRF();
        $installmentModel = new Installment();
        $installment = $installmentModel->find($id);
        if ($installment && $installment['status'] == 'paid') {
            $_SESSION['error'] = 'لا يمكن حذف قسط مدفوع';
        } else {
            $oldData = json_encode($installment);
            $installmentModel->delete($id);
            logAudit($this->userId, 'حذف قسط', 'installments', $id, $oldData, null);
            $_SESSION['success'] = 'تم حذف القسط';
        }
        redirect('/installments');
    }

    public function viewInvoice($id)
    {
        requireLogin();
        $installmentModel = new Installment();
        $installment = $installmentModel->getWithInvoiceAndCustomer($id);
        if (!$installment) {
            $_SESSION['error'] = 'القسط غير موجود';
            redirect('/installments');
        }
        // التحويل إلى شاشة عرض الفاتورة الأصلية في قسم المبيعات
        redirect('/sales/show/' . $installment['sales_invoice_id']);
    }
}
