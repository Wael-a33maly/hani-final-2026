<?php
/**
 * app/Controllers/BranchController.php
 */
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'Branch.php';
require_once MODELS_PATH . 'User.php';

require_once __DIR__ . '/../Core/Pagination.php';

class BranchController extends Controller {

    public function index() {
        requireRole('admin');
        $db = getDB();
        $page = (int)($_GET['page'] ?? 1);
        $search = trim($_GET['search'] ?? '');
        
        $baseQuery = "SELECT b.*, u.full_name as manager_name FROM branches b LEFT JOIN users u ON b.manager_id = u.id WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM branches WHERE 1=1";
        $params = [];
        
        if (!empty($search)) {
            $baseQuery .= " AND b.name LIKE ?";
            $countQuery .= " AND name LIKE ?";
            $params[] = "%$search%";
        }
        
        $baseQuery .= " ORDER BY b.id DESC";
        $pagination = Pagination::paginate($db, $baseQuery, $countQuery, $params, $page, 20);
        $branches = $pagination['data'];
        
        $this->view('branches.index', [
            'branches' => $branches, 
            'pagination' => $pagination,
            'search' => $search
        ]);
    }

    public function create() {
        requireRole('admin');
        $userModel = new User();
        $users     = $userModel->all();
        $this->view('branches.form', ['users' => $users, 'branch' => null]);
    }

    public function store() {
        requireRole('admin');
        $this->verifyCSRF();

        $db = getDB();
        $name      = trim($_POST['name'] ?? '');
        $managerId = !empty($_POST['manager_id']) ? $_POST['manager_id'] : null;
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');

        if (empty($name)) {
            $_SESSION['error'] = 'اسم الفرع مطلوب';
            redirect('/branches/create');
        }

        // توليد كود تلقائي
        $last = $db->query("SELECT MAX(id) as last_id FROM branches")->fetch()['last_id'];
        $code = 'BR' . str_pad(($last ?? 0) + 1, 3, '0', STR_PAD_LEFT);

        $branchModel = new Branch();
        $branchModel->insert([
            'code'       => $code,
            'name'       => $name,
            'manager_id' => $managerId,
            'phone'      => $phone,
            'address'    => $address,
        ]);

        logAudit($this->userId, 'إضافة فرع جديد', 'branches', null, null, json_encode(['code' => $code, 'name' => $name]));
        $_SESSION['success'] = "تم إضافة الفرع بنجاح (الكود: $code)";
        redirect('/branches');
    }

    public function edit($id) {
        requireRole('admin');
        $branchModel = new Branch();
        $branch      = $branchModel->find($id);
        if (!$branch) {
            $_SESSION['error'] = 'الفرع غير موجود';
            redirect('/branches');
        }
        $userModel = new User();
        $users     = $userModel->all();
        $this->view('branches.form', ['users' => $users, 'branch' => $branch]);
    }

    public function update($id) {
        requireRole('admin');
        $this->verifyCSRF();

        $branchModel = new Branch();
        $branch      = $branchModel->find($id);
        if (!$branch) {
            $_SESSION['error'] = 'الفرع غير موجود';
            redirect('/branches');
        }

        $code      = trim($_POST['code'] ?? '');
        $name      = trim($_POST['name'] ?? '');
        $managerId = !empty($_POST['manager_id']) ? $_POST['manager_id'] : null;
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');

        $branchModel->update($id, [
            'code'       => $code,
            'name'       => $name,
            'manager_id' => $managerId,
            'phone'      => $phone,
            'address'    => $address,
        ]);

        logAudit($this->userId, 'تعديل فرع', 'branches', $id);
        $_SESSION['success'] = 'تم تحديث الفرع بنجاح';
        redirect('/branches');
    }

    public function delete($id) {
        requireRole('admin');
        $this->verifyCSRF();

        $branchModel = new Branch();
        $branchModel->delete($id);
        logAudit($this->userId, 'حذف فرع', 'branches', $id);
        $_SESSION['success'] = 'تم حذف الفرع بنجاح';
        redirect('/branches');
    }
}
