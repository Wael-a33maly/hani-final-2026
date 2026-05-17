<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'Expense.php';
require_once MODELS_PATH . 'Branch.php';
require_once __DIR__ . '/../Core/Pagination.php';
if (!function_exists('currentUserRole') && file_exists(__DIR__ . '/../Helpers/PermissionHelper.php')) {
    require_once __DIR__ . '/../Helpers/PermissionHelper.php';
}

class ExpenseController extends Controller {
    // فئات المصروفات
    public function categories() {
        requireRole('admin');
        $model = new ExpenseCategory();
        $categories = $model->getAll();
        $this->view('expenses.categories', ['categories' => $categories]);
    }
    
    public function storeCategory() {
        requireRole('admin');
        $this->verifyCSRF();
        $model = new ExpenseCategory();
        $newId = $model->insert(['name' => trim($_POST['name'])]);
        logAudit($this->userId, 'إضافة فئة مصروف', 'expense_categories', $newId, null, json_encode(['name' => $_POST['name']]));
        $_SESSION['success'] = 'تم إضافة الفئة';
        redirect('/expenses/categories');
    }
    
    public function deleteCategory($id) {
        requireRole('admin');
        $this->verifyCSRF();
        $model = new ExpenseCategory();
        $oldData = json_encode($model->find($id));
        $model->delete($id);
        logAudit($this->userId, 'حذف فئة مصروف', 'expense_categories', $id, $oldData, null);
        $_SESSION['success'] = 'تم الحذف';
        redirect('/expenses/categories');
    }
    
    // المصروفات (الأسماء)
    public function index() {
        requireRole('admin');
        $model = new Expense();
        $expenses = $model->allWithCategory();
        $this->view('expenses.index', ['expenses' => $expenses]);
    }
    
    public function create() {
        requireRole('admin');
        $categoryModel = new ExpenseCategory();
        $categories = $categoryModel->getAll();
        $branchModel = new Branch();
        $branches = $branchModel->getOptions();
        $this->view('expenses.form', ['categories' => $categories, 'branches' => $branches, 'expense' => null]);
    }

    public function store() {
        requireRole('admin');
        $this->verifyCSRF();
        $model = new Expense();
        $newId = $model->insert([
            'name' => trim($_POST['name']),
            'category_id' => $_POST['category_id'],
            'branch_id' => $_POST['branch_id']
        ]);
        logAudit($this->userId, 'إضافة مصروف', 'expenses', $newId, null, json_encode($_POST));
        $_SESSION['success'] = 'تم إضافة المصروف';
        redirect('/expenses');
    }
    
    public function edit($id) {
        requireRole('admin');
        $model = new Expense();
        $expense = $model->find($id);
        $categoryModel = new ExpenseCategory();
        $categories = $categoryModel->getAll();
        $branchModel = new Branch();
        $branches = $branchModel->getOptions();
        $this->view('expenses.form', compact('expense', 'categories', 'branches'));
    }
    
    public function update($id) {
        requireRole('admin');
        $this->verifyCSRF();
        $model = new Expense();
        $oldData = json_encode($model->find($id));
        $model->update($id, [
            'name' => trim($_POST['name']),
            'category_id' => $_POST['category_id'],
            'branch_id' => $_POST['branch_id']
        ]);
        logAudit($this->userId, 'تعديل مصروف', 'expenses', $id, $oldData, json_encode($_POST));
        $_SESSION['success'] = 'تم التحديث';
        redirect('/expenses');
    }
    
    public function delete($id) {
        requireRole('admin');
        $this->verifyCSRF();
        $model = new Expense();
        $oldData = json_encode($model->find($id));
        $model->delete($id);
        logAudit($this->userId, 'حذف مصروف', 'expenses', $id, $oldData, null);
        $_SESSION['success'] = 'تم الحذف';
        redirect('/expenses');
    }
    
    // سندات الصرف
    public function vouchers() {
        requireLogin();
        $db = getDB();
        $page = (int)($_GET['page'] ?? 1);
        
        $filters = [
            'from_date' => $_GET['from_date'] ?? '',
            'to_date' => $_GET['to_date'] ?? '',
            'branch_id' => $_GET['branch_id'] ?? null,
            'expense_id' => $_GET['expense_id'] ?? null,
            'category_id' => $_GET['category_id'] ?? null
        ];

        $sql = "SELECT ev.*, e.name as expense_name, ec.name as category_name, b.name as branch_name, u.full_name as created_by_name
                FROM expense_vouchers ev
                LEFT JOIN expenses e ON ev.expense_id = e.id
                LEFT JOIN expense_categories ec ON e.category_id = ec.id
                LEFT JOIN branches b ON ev.branch_id = b.id
                LEFT JOIN users u ON ev.created_by = u.id
                WHERE 1=1";
        $params = [];
        
        if (!empty($filters['from_date'])) {
            $sql .= " AND ev.date >= ?";
            $params[] = $filters['from_date'];
        }
        if (!empty($filters['to_date'])) {
            $sql .= " AND ev.date <= ?";
            $params[] = $filters['to_date'];
        }
        if (!empty($filters['branch_id'])) {
            $sql .= " AND ev.branch_id = ?";
            $params[] = $filters['branch_id'];
        }
        if (!empty($filters['expense_id'])) {
            $sql .= " AND ev.expense_id = ?";
            $params[] = $filters['expense_id'];
        }
        if (!empty($filters['category_id'])) {
            $sql .= " AND ec.id = ?";
            $params[] = $filters['category_id'];
        }

        $role = currentUserRole();
        $roleName = $role['role_name'] ?? '';

        $countQuery = "SELECT COUNT(*) FROM expense_vouchers ev LEFT JOIN expenses e ON ev.expense_id = e.id LEFT JOIN expense_categories ec ON e.category_id = ec.id WHERE 1=1";
        $countParams = [];
        if (!empty($filters['from_date'])) { $countQuery .= " AND ev.date >= ?"; $countParams[] = $filters['from_date']; }
        if (!empty($filters['to_date'])) { $countQuery .= " AND ev.date <= ?"; $countParams[] = $filters['to_date']; }
        if (!empty($filters['branch_id'])) { $countQuery .= " AND ev.branch_id = ?"; $countParams[] = $filters['branch_id']; }
        if (!empty($filters['expense_id'])) { $countQuery .= " AND ev.expense_id = ?"; $countParams[] = $filters['expense_id']; }
        if (!empty($filters['category_id'])) { $countQuery .= " AND ec.id = ?"; $countParams[] = $filters['category_id']; }

        if ($roleName !== 'admin') {
            $branchFilter = PermissionHelper::filterByBranch('', $_SESSION['user_id'], 'ev', 'branch_id');
            $sql .= $branchFilter;
            $countQuery .= $branchFilter;
        }
        $sql .= " ORDER BY ev.date DESC";
        if (!empty($filters['from_date'])) { $countQuery .= " AND ev.date >= ?"; $countParams[] = $filters['from_date']; }
        if (!empty($filters['to_date'])) { $countQuery .= " AND ev.date <= ?"; $countParams[] = $filters['to_date']; }
        if (!empty($filters['branch_id'])) { $countQuery .= " AND ev.branch_id = ?"; $countParams[] = $filters['branch_id']; }
        if (!empty($filters['expense_id'])) { $countQuery .= " AND ev.expense_id = ?"; $countParams[] = $filters['expense_id']; }
        if (!empty($filters['category_id'])) { $countQuery .= " AND ec.id = ?"; $countParams[] = $filters['category_id']; }

        $pagination = Pagination::paginate($db, $sql, $countQuery, $params, $page, 20);
        $vouchers = $pagination['data'];
        
        $categoryModel = new ExpenseCategory();
        $categories = $categoryModel->getAll();
        $branchModel = new Branch();
        $branches = $branchModel->getOptions();
        
        $this->view('expenses.vouchers', compact('vouchers', 'filters', 'branches', 'categories', 'pagination'));
    }
    
    public function createVoucher() {
        requireLogin();
        $expenseModel = new Expense();
        $expenses = $expenseModel->allWithCategory();
        $branchModel = new Branch();
        $branches = $branchModel->getOptions();
        $this->view('expenses.voucher_form', compact('expenses', 'branches'));
    }
    
    public function storeVoucher() {
        requireLogin();
        $this->verifyCSRF();
        $amount = max(0, (float) ($_POST['amount'] ?? 0));
        if ($amount <= 0) {
            $_SESSION['error'] = 'المبلغ يجب أن يكون أكبر من صفر';
            redirect('/expenses/vouchers/create');
        }
        $voucherModel = new ExpenseVoucher();
        $voucherNumber = $voucherModel->generateVoucherNumber();
        $newId = $voucherModel->insert([
            'voucher_number' => $voucherNumber,
            'expense_id' => $_POST['expense_id'],
            'amount' => $amount,
            'payment_type' => $_POST['payment_type'],
            'branch_id' => $_POST['branch_id'],
            'notes' => $_POST['notes'],
            'date' => $_POST['date'],
            'created_by' => $this->userId
        ]);
        logAudit($this->userId, 'إضافة سند صرف', 'expense_vouchers', $newId, null, json_encode($_POST));
        $_SESSION['success'] = 'تم إضافة سند الصرف';
        redirect('/expenses/vouchers');
    }
    
    public function printVoucher($id) {
        requireLogin();
        $voucherModel = new ExpenseVoucher();
        $voucher = $voucherModel->find($id);
        $this->view('expenses.print_voucher', ['voucher' => $voucher]);
    }
}
