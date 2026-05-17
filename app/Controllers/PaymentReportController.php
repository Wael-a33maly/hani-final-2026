<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'InstallmentPayment.php';
require_once MODELS_PATH . 'Expense.php';

class PaymentReportController extends Controller {
    public function index() {
        requireLogin();
        $filters = [
            'from_date' => $_GET['from_date'] ?? date('Y-m-01'),
            'to_date' => $_GET['to_date'] ?? date('Y-m-d'),
            'branch_id' => $_GET['branch_id'] ?? null,
            'sales_rep_id' => $_GET['sales_rep_id'] ?? null
        ];
        $receiptsModel = new InstallmentPayment();
        $receipts = $receiptsModel->getAllWithDetails($filters);
        $totalReceipts = $receiptsModel->getTotalReceipts($filters);
        
        $db = getDB();
        // مدفوعات الموردين
        $supplierPaymentsStmt = $db->prepare("
            SELECT sp.*, s.name as supplier_name, 'supplier' as payment_type
            FROM supplier_payments sp
            LEFT JOIN suppliers s ON sp.supplier_id = s.id
            WHERE sp.payment_date BETWEEN ? AND ?
            ORDER BY sp.payment_date DESC
        ");
        $supplierPaymentsStmt->execute([$filters['from_date'], $filters['to_date']]);
        $supplierPayments = $supplierPaymentsStmt->fetchAll();
        
        // المصروفات
        $expenseModel = new ExpenseVoucher();
        $expenses = $expenseModel->allWithDetails(['from_date' => $filters['from_date'], 'to_date' => $filters['to_date']]);
        $totalExpenses = $expenseModel->getTotalExpenses($filters);
        $totalSupplierPayments = array_sum(array_column($supplierPayments, 'amount'));

        // عمولات المناديب المدفوعة
        try {
            $commissionPaymentsStmt = $db->prepare("
                SELECT cp.*, u.full_name as agent_name
                FROM commission_payments cp
                LEFT JOIN users u ON cp.user_id = u.id
                WHERE cp.payment_date BETWEEN ? AND ?
                ORDER BY cp.payment_date DESC
            ");
            $commissionPaymentsStmt->execute([$filters['from_date'], $filters['to_date']]);
            $commissionPayments = $commissionPaymentsStmt->fetchAll();
            $totalCommissionPayments = array_sum(array_column($commissionPayments, 'amount'));
        } catch (Exception $e) {
            $commissionPayments = [];
            $totalCommissionPayments = 0;
        }

        $totalPayments = $totalExpenses + $totalSupplierPayments + $totalCommissionPayments;

        // تصنيفات المصاريف
        $categories = $db->query("SELECT id, name FROM expense_categories ORDER BY name")->fetchAll();

        $branches = $db->query("SELECT id, name FROM branches")->fetchAll();
        $reps = $db->query("SELECT id, full_name FROM users WHERE role = 'user'")->fetchAll();

        $this->view('payments.index', compact('receipts', 'totalReceipts', 'supplierPayments', 'expenses', 'totalPayments', 'filters', 'branches', 'reps', 'commissionPayments', 'totalCommissionPayments', 'categories'));
    }
}
