<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'Warehouse.php';
require_once MODELS_PATH . 'Branch.php';
require_once __DIR__ . '/../Core/Pagination.php';
if (!function_exists('currentUserRole') && file_exists(__DIR__ . '/../Helpers/PermissionHelper.php')) {
    require_once __DIR__ . '/../Helpers/PermissionHelper.php';
}

class WarehouseController extends Controller {
    public function index() {
        requireLogin();
        $db = getDB();
        $page = (int)($_GET['page'] ?? 1);
        $search = trim($_GET['search'] ?? '');
        $branch_id = $_GET['branch_id'] ?? '';
        
        $baseQuery = "SELECT w.*, b.name as branch_name FROM warehouses w LEFT JOIN branches b ON w.branch_id = b.id WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM warehouses w WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $baseQuery .= " AND w.name LIKE ?";
            $countQuery .= " AND w.name LIKE ?";
            $params[] = "%$search%";
        }
        if (!empty($branch_id)) {
            $baseQuery .= " AND w.branch_id = ?";
            $countQuery .= " AND w.branch_id = ?";
            $params[] = $branch_id;
        }

        $role = currentUserRole();
        $roleName = $role['role_name'] ?? '';
        if ($roleName !== 'admin') {
            $branchFilter = PermissionHelper::filterByBranch('', $_SESSION['user_id'], 'w', 'branch_id');
            $baseQuery .= $branchFilter;
            $countQuery .= $branchFilter;
        }
        
        $baseQuery .= " ORDER BY w.id DESC";
        $pagination = Pagination::paginate($db, $baseQuery, $countQuery, $params, $page, 20);
        $warehouses = $pagination['data'];
        
        $branches = $db->query("SELECT id, name FROM branches ORDER BY name")->fetchAll();
        $this->view('warehouses.index', compact('warehouses', 'pagination', 'search', 'branch_id', 'branches'));
    }
    
    public function create() {
        requireRole('admin');
        $branchModel = new Branch();
        $branches = $branchModel->getOptions();
        $this->view('warehouses.form', ['branches' => $branches, 'warehouse' => null]);
    }
    
    public function store() {
        requireRole('admin');
        $this->verifyCSRF();
        $model = new Warehouse();
        $newId = $model->insert([
            'name' => trim($_POST['name']),
            'address' => trim($_POST['address']),
            'branch_id' => $_POST['branch_id']
        ]);
        logAudit($this->userId, 'إضافة مخزن', 'warehouses', $newId, null, json_encode($_POST));
        $_SESSION['success'] = 'تم إضافة المخزن';
        redirect('/warehouses');
    }
    
    public function edit($id) {
        requireRole('admin');
        $model = new Warehouse();
        $warehouse = $model->find($id);
        if (!$warehouse) redirect('/warehouses');
        $branchModel = new Branch();
        $branches = $branchModel->getOptions();
        $this->view('warehouses.form', ['branches' => $branches, 'warehouse' => $warehouse]);
    }
    
    public function update($id) {
        requireRole('admin');
        $this->verifyCSRF();
        $model = new Warehouse();
        $oldData = json_encode($model->find($id));
        $model->update($id, [
            'name' => trim($_POST['name']),
            'address' => trim($_POST['address']),
            'branch_id' => $_POST['branch_id']
        ]);
        logAudit($this->userId, 'تعديل مخزن', 'warehouses', $id, $oldData, json_encode($_POST));
        $_SESSION['success'] = 'تم التحديث';
        redirect('/warehouses');
    }
    
    public function delete($id) {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM current_stock WHERE warehouse_id = ? AND quantity > 0");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = 'لا يمكن حذف المخزن لأنه يحتوي على أرصدة';
            redirect('/warehouses');
        }
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE id IN (SELECT product_id FROM current_stock WHERE warehouse_id = ?)");
        $stmt->execute([$id]);
        $model = new Warehouse();
        $oldData = json_encode($model->find($id));
        $model->delete($id);
        logAudit($this->userId, 'حذف مخزن', 'warehouses', $id, $oldData, null);
        $_SESSION['success'] = 'تم الحذف';
        redirect('/warehouses');
    }
}
