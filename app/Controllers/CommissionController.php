<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once __DIR__ . '/../Helpers/PDFHelper.php';
if (!function_exists('currentUserRole') && file_exists(__DIR__ . '/../Helpers/PermissionHelper.php')) {
    require_once __DIR__ . '/../Helpers/PermissionHelper.php';
}

class CommissionController extends Controller
{
    public function index()
    {
        requireRole('admin');
        $db = getDB();
        $role = currentUserRole();
        $roleName = $role['role_name'] ?? '';
        $userId = $_SESSION['user_id'];

        $userFilter = '';
        $repFilter = '';
        if ($roleName === 'sales_rep' || $roleName === 'collector') {
            $repFilter = " AND sc.user_id = $userId";
            $userFilter = " AND u.id = $userId";
        } elseif ($roleName === 'branch_manager') {
            $branchIds = implode(',', array_map('intval', PermissionHelper::getUserBranches($userId)));
            $userFilter = " AND (u.branch_id IN ($branchIds) OR u.id IN (SELECT user_id FROM user_branches WHERE branch_id IN ($branchIds)))";
        }

        try {
            $totalPendingSales = $db->query("SELECT COALESCE(SUM(total_commission), 0) FROM sales_commissions WHERE status = 'pending'$repFilter")->fetchColumn();
            $totalPendingCollection = $db->query("SELECT COALESCE(SUM(commission_amount), 0) FROM collection_commissions WHERE status = 'pending'$repFilter")->fetchColumn();
            $totalPending = round($totalPendingSales + $totalPendingCollection, 2);

            $monthStart = date('Y-m-01');
            $monthEnd = date('Y-m-t');
            $paidThisMonthStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM commission_payments WHERE payment_date BETWEEN ? AND ?");
            $paidThisMonthStmt->execute([$monthStart, $monthEnd]);
            $paidThisMonth = round($paidThisMonthStmt->fetchColumn(), 2);

            $repsCount = $db->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name IN ('sales_rep', 'admin', 'branch_manager') AND u.is_active = 1$userFilter")->fetchColumn();

            $agentQuery = "
                SELECT u.id, u.full_name, u.phone, u.collection_commission_rate,
                    COALESCE(sc.pending_sales, 0) as pending_sales,
                    COALESCE(cc.pending_collection, 0) as pending_collection
                FROM users u
                LEFT JOIN (
                    SELECT user_id, COALESCE(SUM(total_commission), 0) as pending_sales
                    FROM sales_commissions WHERE status = 'pending'
                    GROUP BY user_id
                ) sc ON u.id = sc.user_id
                LEFT JOIN (
                    SELECT user_id, COALESCE(SUM(commission_amount), 0) as pending_collection
                    FROM collection_commissions WHERE status = 'pending'
                    GROUP BY user_id
                ) cc ON u.id = cc.user_id
                WHERE u.is_active = 1 $userFilter
                ORDER BY u.full_name ASC
            ";
            $agents = $db->query($agentQuery)->fetchAll();
        } catch (Exception $e) {
            error_log("فشل تحميل صفحة العمولات: " . $e->getMessage());
            $_SESSION['error'] = 'لم يتم استيراد جداول العمولات بعد. الرجاء استيراد ملف commissions.sql في phpMyAdmin.';
            redirect('/dashboard');
        }

        $this->view('commissions.index', compact('totalPending', 'paidThisMonth', 'repsCount', 'agents'));
    }

    public function agentReport($userId)
    {
        try {
            requireRole('admin');
            $role = currentUserRole();
            $roleName = $role['role_name'] ?? '';

            if ($roleName === 'sales_rep' || $roleName === 'collector') {
                if ((int)$userId !== (int)$_SESSION['user_id']) {
                    $_SESSION['error'] = 'لا يمكنك الاطلاع على عمولات مندوب آخر';
                    redirect('/commissions');
                }
            }

            $db = getDB();

            $stmt = $db->prepare("SELECT id, full_name, phone, COALESCE(collection_commission_rate, 0) as collection_commission_rate FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $agent = $stmt->fetch();

            if (!$agent) {
                $_SESSION['error'] = 'المندوب غير موجود';
                redirect('/commissions');
            }

            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');

            $stmt = $db->prepare("SELECT sc.*, s.invoice_number FROM sales_commissions sc JOIN sales_invoices s ON sc.sale_id = s.id WHERE sc.user_id = ? AND sc.commission_date BETWEEN ? AND ? ORDER BY sc.commission_date ASC");
            $stmt->execute([$userId, $from, $to]);
            $salesCommissions = $stmt->fetchAll();

            $stmt = $db->prepare("SELECT cc.*, s.invoice_number FROM collection_commissions cc JOIN sales_invoices s ON cc.sale_id = s.id WHERE cc.user_id = ? AND cc.collection_date BETWEEN ? AND ? ORDER BY cc.collection_date ASC");
            $stmt->execute([$userId, $from, $to]);
            $collectionCommissions = $stmt->fetchAll();

            $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN status='pending' THEN total_commission END), 0) as sales_pending, COALESCE(SUM(total_commission), 0) as sales_total FROM sales_commissions WHERE user_id = ? AND commission_date BETWEEN ? AND ?");
            $stmt->execute([$userId, $from, $to]);
            $salesSummary = $stmt->fetch();

            $stmt = $db->prepare("SELECT COALESCE(SUM(CASE WHEN status='pending' THEN commission_amount END), 0) as collection_pending, COALESCE(SUM(commission_amount), 0) as collection_total FROM collection_commissions WHERE user_id = ? AND collection_date BETWEEN ? AND ?");
            $stmt->execute([$userId, $from, $to]);
            $collectionSummary = $stmt->fetch();

            $stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM commission_payments WHERE user_id = ? AND payment_date BETWEEN ? AND ?");
            $stmt->execute([$userId, $from, $to]);
            $paidAmount = round($stmt->fetchColumn(), 2);

            $totalDue = round((float)$salesSummary['sales_pending'] + (float)$collectionSummary['collection_pending'], 2);
            $netDue = round($totalDue - (float)$paidAmount, 2);

            $this->view('commissions.agent_report', compact('agent', 'from', 'to', 'salesCommissions', 'collectionCommissions', 'salesSummary', 'collectionSummary', 'paidAmount', 'totalDue', 'netDue'));

        } catch (\Throwable $e) {
            $msg = $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine();
            error_log("agentReport CRASH: " . $msg);
            $_SESSION['error'] = $msg;
            redirect('/commissions');
        }
    }

    public function showPayForm($userId)
    {
        requireRole('admin');

        try {
            $db = getDB();
            $agent = $db->prepare("SELECT id, full_name, phone, collection_commission_rate FROM users WHERE id = ?");
            $agent->execute([$userId]);
            $agent = $agent->fetch();
        } catch (Exception $e) {
            $_SESSION['error'] = 'لم يتم استيراد جداول العمولات بعد';
            redirect('/commissions');
        }

        if (!$agent) {
            $_SESSION['error'] = 'المندوب غير موجود';
            redirect('/commissions');
        }

        $this->view('commissions.pay_form', compact('agent'));
    }

    public function pay($userId)
    {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();
        $db->beginTransaction();

        try {
            $from = $_POST['period_from'];
            $to = $_POST['period_to'];
            $amount = (float) ($_POST['amount'] ?? 0);
            $paymentType = $_POST['payment_type'] ?? 'cash';
            $notes = $_POST['notes'] ?? '';
            $differenceReason = $_POST['difference_reason'] ?? '';

            if ($amount <= 0) {
                throw new Exception('المبلغ المصروف يجب أن يكون أكبر من صفر');
            }

            // حساب المستحق في الفترة
            $pendingSales = $db->prepare("SELECT COALESCE(SUM(total_commission), 0) FROM sales_commissions WHERE user_id = ? AND status = 'pending' AND commission_date BETWEEN ? AND ?");
            $pendingSales->execute([$userId, $from, $to]);
            $pendingSales = round($pendingSales->fetchColumn(), 2);

            $pendingCollection = $db->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM collection_commissions WHERE user_id = ? AND status = 'pending' AND collection_date BETWEEN ? AND ?");
            $pendingCollection->execute([$userId, $from, $to]);
            $pendingCollection = round($pendingCollection->fetchColumn(), 2);

            $totalDue = round($pendingSales + $pendingCollection, 2);

            // تسجيل سند الصرف
            $stmt = $db->prepare("
                INSERT INTO commission_payments
                (user_id, payment_date, amount, payment_type, period_from, period_to, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $_POST['payment_date'] ?? date('Y-m-d'),
                $amount,
                $paymentType,
                $from,
                $to,
                trim($notes . ($differenceReason ? "\nسبب الفرق: " . $differenceReason : '')),
                $this->userId
            ]);
            $paymentId = $db->lastInsertId();

            // تحديث حالة العمولات المدفوعة في الفترة
            $updateSales = $db->prepare("UPDATE sales_commissions SET status = 'paid', paid_at = NOW(), notes = CONCAT(COALESCE(notes,''), ' | صرف في سند رقم: ', ?) WHERE user_id = ? AND status = 'pending' AND commission_date BETWEEN ? AND ?");
            $updateSales->execute([$paymentId, $userId, $from, $to]);

            $updateCollection = $db->prepare("UPDATE collection_commissions SET status = 'paid', paid_at = NOW(), notes = CONCAT(COALESCE(notes,''), ' | صرف في سند رقم: ', ?) WHERE user_id = ? AND status = 'pending' AND collection_date BETWEEN ? AND ?");
            $updateCollection->execute([$paymentId, $userId, $from, $to]);

            $db->commit();

            logAudit($this->userId, 'صرف عمولة مندوب', 'commission_payments', $paymentId, null, json_encode([
                'user_id' => $userId,
                'amount' => $amount,
                'period_from' => $from,
                'period_to' => $to,
                'total_due' => $totalDue
            ]));

            $_SESSION['success'] = "تم صرف مبلغ {$amount} ج.م للمندوب بنجاح" . ($amount != $totalDue ? " (المستحق كان {$totalDue} ج.م)" : "");
            redirect('/commissions/agent/' . $userId);
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/commissions/pay/' . $userId);
        }
    }

    public function closeAccount($userId)
    {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT id, full_name FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $agent = $stmt->fetch();
            if (!$agent) throw new Exception('المندوب غير موجود');

            $amount = (float) ($_POST['amount'] ?? 0);
            $paymentType = $_POST['payment_type'] ?? 'cash';
            $notes = $_POST['notes'] ?? '';
            $differenceReason = $_POST['difference_reason'] ?? '';
            if ($amount < 0) throw new Exception('المبلغ غير صحيح');

            $ps = $db->prepare("SELECT COALESCE(SUM(total_commission), 0) FROM sales_commissions WHERE user_id = ? AND status = 'pending'");
            $ps->execute([$userId]);
            $pendingSales = round((float)$ps->fetchColumn(), 2);

            $pc = $db->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM collection_commissions WHERE user_id = ? AND status = 'pending'");
            $pc->execute([$userId]);
            $pendingCollection = round((float)$pc->fetchColumn(), 2);

            $totalDue = round($pendingSales + $pendingCollection, 2);

            // سند الصرف
            $insert = $db->prepare("INSERT INTO commission_payments (user_id, payment_date, amount, payment_type, period_from, period_to, notes, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $userId,
                date('Y-m-d'),
                $amount,
                $paymentType,
                '2020-01-01',
                date('Y-m-d'),
                trim('إغلاق حساب المندوب - ' . $notes . ($differenceReason ? ' | سبب الفرق: ' . $differenceReason : '')),
                $this->userId
            ]);
            $paymentId = $db->lastInsertId();

            // إقفال كل العمولات المعلقة
            $db->prepare("UPDATE sales_commissions SET status = 'paid', paid_at = NOW(), notes = CONCAT(IFNULL(notes,''), ' | إغلاق حساب سند رقم: ', ?) WHERE user_id = ? AND status = 'pending'")->execute([$paymentId, $userId]);
            $db->prepare("UPDATE collection_commissions SET status = 'paid', paid_at = NOW(), notes = CONCAT(IFNULL(notes,''), ' | إغلاق حساب سند رقم: ', ?) WHERE user_id = ? AND status = 'pending'")->execute([$paymentId, $userId]);

            $db->commit();

            logAudit($this->userId, 'إغلاق حساب مندوب', 'commission_payments', $paymentId, null, json_encode([
                'user_id' => $userId, 'amount' => $amount, 'total_due' => $totalDue,
                'difference' => round($amount - $totalDue, 2), 'reason' => $differenceReason
            ]));

            $_SESSION['success'] = "تم إغلاق حساب {$agent['full_name']} بمبلغ {$amount} ج.م" . ($totalDue != $amount ? " (المستحق كان {$totalDue} ج.م - الفرق: " . round($amount - $totalDue, 2) . " ج.م)" : "");
            redirect('/commissions/agent/' . $userId);
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/commissions/agent/' . $userId);
        }
    }

    public function calculatePeriod($userId)
    {
        requireRole('admin');
        $db = getDB();

        $from = $_GET['from'] ?? date('Y-m-01');
        $to = $_GET['to'] ?? date('Y-m-d');

        header('Content-Type: application/json');
        try {
            $pendingSales = $db->prepare("SELECT COALESCE(SUM(total_commission), 0) FROM sales_commissions WHERE user_id = ? AND status = 'pending' AND commission_date BETWEEN ? AND ?");
            $pendingSales->execute([$userId, $from, $to]);
            $pendingSales = round($pendingSales->fetchColumn(), 2);

            $pendingCollection = $db->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM collection_commissions WHERE user_id = ? AND status = 'pending' AND collection_date BETWEEN ? AND ?");
            $pendingCollection->execute([$userId, $from, $to]);
            $pendingCollection = round($pendingCollection->fetchColumn(), 2);

            $totalDue = round($pendingSales + $pendingCollection, 2);

            echo json_encode([
                'success' => true,
                'sales' => $pendingSales,
                'collection' => $pendingCollection,
                'total' => $totalDue
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'sales' => 0,
                'collection' => 0,
                'total' => 0,
                'error' => 'جداول العمولات غير موجودة'
            ]);
        }
    }

    public function exportPDF($userId)
    {
        requireRole('admin');

        try {
            $db = getDB();

            $agent = $db->prepare("SELECT id, full_name, phone, collection_commission_rate FROM users WHERE id = ?");
            $agent->execute([$userId]);
            $agent = $agent->fetch();

            if (!$agent) {
                $_SESSION['error'] = 'المندوب غير موجود';
                redirect('/commissions');
            }

            $from = $_GET['from'] ?? date('Y-m-01');
            $to = $_GET['to'] ?? date('Y-m-d');

            $stmt = $db->prepare("SELECT sc.*, s.invoice_number FROM sales_commissions sc JOIN sales_invoices s ON sc.sale_id = s.id WHERE sc.user_id = ? AND sc.commission_date BETWEEN ? AND ? ORDER BY sc.commission_date ASC");
            $stmt->execute([$userId, $from, $to]);
            $salesCommissions = $stmt->fetchAll();

            $stmt2 = $db->prepare("SELECT cc.*, s.invoice_number FROM collection_commissions cc JOIN sales_invoices s ON cc.sale_id = s.id WHERE cc.user_id = ? AND cc.collection_date BETWEEN ? AND ? ORDER BY cc.collection_date ASC");
            $stmt2->execute([$userId, $from, $to]);
            $collectionCommissions = $stmt2->fetchAll();
        } catch (Exception $e) {
            $_SESSION['error'] = 'لم يتم استيراد جداول العمولات بعد';
            redirect('/commissions/agent/' . $userId);
        }

        $salesTotal = array_sum(array_column($salesCommissions, 'total_commission'));
        $collectionTotal = array_sum(array_column($collectionCommissions, 'commission_amount'));
        $grandTotal = round($salesTotal + $collectionTotal, 2);

        require_once MODELS_PATH . 'CompanySetting.php';
        $settingsModel = new CompanySetting();
        $company = $settingsModel->getSettings();

        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator($company['company_name'] ?? 'نظام ERP');
        $pdf->SetTitle('كشف عمولات مندوب - ' . $agent['full_name']);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage('P', 'A4');
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->SetRTL(true);

        $logoPath = !empty($company['logo_path']) ? PUBLIC_PATH . $company['logo_path'] : '';
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 170, 5, 25, 0);
        }

        $pdf->SetY(8);
        $pdf->SetFont('dejavusans', 'B', 16);
        $pdf->Cell(0, 8, $company['company_name'] ?? 'الشركة', 0, 1, 'C');
        $pdf->SetFont('dejavusans', 'B', 14);
        $pdf->Cell(0, 8, 'كشف عمولات مندوب', 0, 1, 'C');
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(0, 5, $company['address'] ?? '', 0, 1, 'C');
        $pdf->Cell(0, 5, 'هاتف: ' . ($company['phone'] ?? ''), 0, 1, 'C');
        $pdf->Ln(3);

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->Cell(0, 6, "اسم المندوب: {$agent['full_name']}    |    الهاتف: {$agent['phone']}    |    نسبة التحصيل: {$agent['collection_commission_rate']}%", 0, 1, 'R');
        $pdf->Cell(0, 6, "الفترة من: {$from}  إلى: {$to}", 0, 1, 'R');
        $pdf->Ln(5);

        // جدول عمولات المبيعات
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(0, 7, 'أولاً: عمولات المبيعات', 0, 1, 'R');
        $pdf->SetFont('dejavusans', '', 8);

        $html = '<table border="1" cellpadding="4" style="border-collapse:collapse; width:100%; font-size:8pt;">
                    <thead>
                        <tr style="background-color:#e2e8f0; font-weight:bold;">
                            <th width="14%">التاريخ</th>
                            <th width="16%">رقم الفاتورة</th>
                            <th width="28%">المادة</th>
                            <th width="10%">الكمية</th>
                            <th width="16%">عمولة/قطعة</th>
                            <th width="16%">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>';
        if (count($salesCommissions) > 0) {
            foreach ($salesCommissions as $sc) {
                $html .= '<tr>
                            <td style="text-align:center;">' . $sc['commission_date'] . '</td>
                            <td style="text-align:center;">' . $sc['invoice_number'] . '</td>
                            <td>' . htmlspecialchars($sc['product_name']) . '</td>
                            <td style="text-align:center;">' . $sc['quantity'] . '</td>
                            <td style="text-align:center;">' . number_format($sc['commission_amount_per_unit'], 2) . '</td>
                            <td style="text-align:center;">' . number_format($sc['total_commission'], 2) . '</td>
                        </tr>';
            }
        } else {
            $html .= '<tr><td colspan="6" style="text-align:center;">لا توجد عمولات مبيعات في هذه الفترة</td></tr>';
        }
        $html .= '<tr style="background-color:#f8fafc; font-weight:bold;">
                    <td colspan="5" style="text-align:left;">الإجمالي</td>
                    <td style="text-align:center;">' . number_format($salesTotal, 2) . '</td>
                  </tr>';
        $html .= '</tbody></table>';
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(5);

        // جدول عمولات التحصيل
        $pdf->SetFont('dejavusans', 'B', 11);
        $pdf->Cell(0, 7, 'ثانياً: عمولات التحصيل', 0, 1, 'R');
        $pdf->SetFont('dejavusans', '', 8);

        $html2 = '<table border="1" cellpadding="4" style="border-collapse:collapse; width:100%; font-size:8pt;">
                    <thead>
                        <tr style="background-color:#e2e8f0; font-weight:bold;">
                            <th width="14%">التاريخ</th>
                            <th width="16%">رقم الفاتورة</th>
                            <th width="24%">العميل</th>
                            <th width="16%">المبلغ المحصّل</th>
                            <th width="14%">النسبة%</th>
                            <th width="16%">العمولة</th>
                        </tr>
                    </thead>
                    <tbody>';
        if (count($collectionCommissions) > 0) {
            foreach ($collectionCommissions as $cc) {
                $html2 .= '<tr>
                            <td style="text-align:center;">' . $cc['collection_date'] . '</td>
                            <td style="text-align:center;">' . $cc['invoice_number'] . '</td>
                            <td>' . htmlspecialchars($cc['customer_name']) . '</td>
                            <td style="text-align:center;">' . number_format($cc['collected_amount'], 2) . '</td>
                            <td style="text-align:center;">' . $cc['commission_rate'] . '%</td>
                            <td style="text-align:center;">' . number_format($cc['commission_amount'], 2) . '</td>
                        </tr>';
            }
        } else {
            $html2 .= '<tr><td colspan="6" style="text-align:center;">لا توجد عمولات تحصيل في هذه الفترة</td></tr>';
        }
        $html2 .= '<tr style="background-color:#f8fafc; font-weight:bold;">
                    <td colspan="5" style="text-align:left;">الإجمالي</td>
                    <td style="text-align:center;">' . number_format($collectionTotal, 2) . '</td>
                  </tr>';
        $html2 .= '</tbody></table>';
        $pdf->writeHTML($html2, true, false, true, false, '');
        $pdf->Ln(5);

        // ملخص المستحقات
        try {
            $paidStmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM commission_payments WHERE user_id = ? AND payment_date BETWEEN ? AND ?");
            $paidStmt->execute([$userId, $from, $to]);
            $paidAmount = round($paidStmt->fetchColumn(), 2);
        } catch (Exception $e) {
            $paidAmount = 0;
        }
        $netDue = round($grandTotal - $paidAmount, 2);

        $html3 = '
        <table border="1" cellpadding="5" style="border-collapse:collapse; width:60%; font-size:9pt; margin-left:auto; margin-right:0;">
            <tr style="background-color:#f1f5f9;">
                <td width="60%"><strong>إجمالي عمولات المبيعات</strong></td>
                <td width="40%" style="text-align:center;">' . number_format($salesTotal, 2) . ' ج.م</td>
            </tr>
            <tr>
                <td><strong>إجمالي عمولات التحصيل</strong></td>
                <td style="text-align:center;">' . number_format($collectionTotal, 2) . ' ج.م</td>
            </tr>
            <tr style="background-color:#f8fafc; font-weight:bold;">
                <td><strong>الإجمالي الكلي</strong></td>
                <td style="text-align:center;">' . number_format($grandTotal, 2) . ' ج.م</td>
            </tr>
            <tr>
                <td><strong>المدفوع سابقاً</strong></td>
                <td style="text-align:center;">' . number_format($paidAmount, 2) . ' ج.م</td>
            </tr>
            <tr style="background-color:#dcfce7; font-weight:bold;">
                <td><strong>💰 الصافي المستحق</strong></td>
                <td style="text-align:center; font-size:11pt;">' . number_format($netDue, 2) . ' ج.م</td>
            </tr>
        </table>';

        $pdf->writeHTML($html3, true, false, true, false, '');
        $pdf->Ln(15);

        // توقيع
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->Cell(80, 6, 'توقيع المندوب: ________________', 0, 0, 'R');
        $pdf->Cell(80, 6, 'توقيع المسؤول: ________________', 0, 1, 'L');

        $pdf->Output("commission_report_{$agent['id']}.pdf", 'D');
        exit;
    }
}
