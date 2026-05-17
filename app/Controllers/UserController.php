<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'User.php';
require_once MODELS_PATH . 'Branch.php';
require_once __DIR__ . '/../Core/Pagination.php';
if (!class_exists('PermissionHelper') && file_exists(__DIR__ . '/../Helpers/PermissionHelper.php')) {
    require_once __DIR__ . '/../Helpers/PermissionHelper.php';
}

class UserController extends Controller
{
    public function index()
    {
        requireRole('admin');
        $db = getDB();
        $page = (int) ($_GET['page'] ?? 1);

        $search = trim($_GET['search'] ?? '');
        $role = $_GET['role'] ?? '';
        $branch_id = $_GET['branch_id'] ?? '';

        $baseQuery = "SELECT u.*, b.name as branch_name, r.display_name as role_display FROM users u LEFT JOIN branches b ON u.branch_id = b.id LEFT JOIN roles r ON u.role_id = r.id WHERE 1=1";
        $countQuery = "SELECT COUNT(*) FROM users u WHERE 1=1";
        $params = [];

        if (!empty($search)) {
            $baseQuery .= " AND (u.full_name LIKE ? OR u.username LIKE ?)";
            $countQuery .= " AND (u.full_name LIKE ? OR u.username LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        if (!empty($role)) {
            $baseQuery .= " AND u.role_id = ?";
            $countQuery .= " AND u.role_id = ?";
            $params[] = $role;
        }
        if (!empty($branch_id)) {
            $baseQuery .= " AND (u.branch_id = ? OR u.id IN (SELECT user_id FROM user_branches WHERE branch_id = ?))";
            $countQuery .= " AND (u.branch_id = ? OR u.id IN (SELECT user_id FROM user_branches WHERE branch_id = ?))";
            $params[] = $branch_id;
            $params[] = $branch_id;
        }

        $baseQuery .= " ORDER BY u.id DESC";
        $pagination = Pagination::paginate($db, $baseQuery, $countQuery, $params, $page, 20);
        $users = $pagination['data'];

        $branches = $db->query("SELECT id, name FROM branches ORDER BY name")->fetchAll();
        $roles = $db->query("SELECT id, display_name FROM roles ORDER BY id")->fetchAll();

        $this->view('users.index', [
            'users' => $users,
            'pagination' => $pagination,
            'branches' => $branches,
            'roles' => $roles,
            'search' => $search,
            'role' => $role,
            'branch_id' => $branch_id
        ]);
    }

    public function create()
    {
        requireRole('admin');
        $branchModel = new Branch();
        $branches = $branchModel->all();
        $db = getDB();
        $roles = $db->query("SELECT id, display_name, name FROM roles ORDER BY id")->fetchAll();
        $this->view('users.form', ['branches' => $branches, 'user' => null, 'roles' => $roles]);
    }

    public function store()
    {
        requireRole('admin');
        $this->verifyCSRF();

        $userModel = new User();
        $collectionRate = max(0, min(100, (float) ($_POST['collection_commission_rate'] ?? 0)));
        $data = [
            'username' => $_POST['username'],
            'full_name' => $_POST['full_name'],
            'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
            'role' => 'user',
            'role_id' => !empty($_POST['role_id']) ? $_POST['role_id'] : null,
            'branch_id' => $_POST['branch_id'] ?: null,
            'can_view_all_branches' => !empty($_POST['can_view_all_branches']) ? 1 : 0,
            'phone' => $_POST['phone'] ?? null,
            'collection_commission_rate' => $collectionRate,
            'is_active' => !empty($_POST['is_active']) ? 1 : 0
        ];

        $db = getDB();
        $db->beginTransaction();
        try {
            $userId = $userModel->insert($data);

            if (!empty($_POST['branch_id'])) {
                PermissionHelper::syncUserBranches($userId, [$_POST['branch_id']], $_POST['branch_id']);
            }

            if (!empty($_POST['branches']) && is_array($_POST['branches'])) {
                $branchIds = array_map('intval', $_POST['branches']);
                $primaryId = $_POST['primary_branch'] ?? $_POST['branch_id'] ?? null;
                PermissionHelper::syncUserBranches($userId, $branchIds, $primaryId);
            }

            $db->commit();
            logAudit($this->userId, 'إضافة مستخدم', 'users', $userId, null, json_encode($data));
            $_SESSION['success'] = 'تم إضافة المستخدم بنجاح';
            redirect('/users');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/users/create');
        }
    }

    public function edit($id)
    {
        requireRole('admin');
        $userModel = new User();
        $user = $userModel->find($id);
        $branchModel = new Branch();
        $branches = $branchModel->all();
        $db = getDB();
        $roles = $db->query("SELECT id, display_name, name FROM roles ORDER BY id")->fetchAll();

        $userBranchIds = PermissionHelper::getUserBranches($id);
        $primaryBranch = PermissionHelper::getPrimaryBranch($id);

        $this->view('users.form', ['user' => $user, 'branches' => $branches, 'roles' => $roles, 'userBranchIds' => $userBranchIds, 'primaryBranch' => $primaryBranch]);
    }

    public function update($id)
    {
        requireRole('admin');
        $this->verifyCSRF();

        $userModel = new User();
        $oldData = $userModel->find($id);
        $collectionRate = max(0, min(100, (float) ($_POST['collection_commission_rate'] ?? 0)));
        $data = [
            'username' => $_POST['username'],
            'full_name' => $_POST['full_name'],
            'role' => 'user',
            'role_id' => !empty($_POST['role_id']) ? $_POST['role_id'] : null,
            'branch_id' => $_POST['branch_id'] ?: null,
            'can_view_all_branches' => !empty($_POST['can_view_all_branches']) ? 1 : 0,
            'phone' => $_POST['phone'] ?? null,
            'collection_commission_rate' => $collectionRate,
            'is_active' => !empty($_POST['is_active']) ? 1 : 0
        ];

        if (!empty($_POST['password'])) {
            $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $db = getDB();
        $db->beginTransaction();
        try {
            $userModel->update($id, $data);

            if (!empty($_POST['branches']) && is_array($_POST['branches'])) {
                $branchIds = array_map('intval', $_POST['branches']);
                $primaryId = $_POST['primary_branch'] ?? $_POST['branch_id'] ?? null;
                PermissionHelper::syncUserBranches($id, $branchIds, $primaryId);
            } elseif (!empty($_POST['branch_id'])) {
                PermissionHelper::syncUserBranches($id, [$_POST['branch_id']], $_POST['branch_id']);
            }

            if ((int)($oldData['role_id'] ?? 0) !== (int)($data['role_id'] ?? 0)) {
                PermissionHelper::assignRoleToUser($id, $data['role_id']);
            }

            $db->commit();
            logAudit($this->userId, 'تعديل مستخدم', 'users', $id, json_encode($oldData), json_encode($data));
            $_SESSION['success'] = 'تم تحديث بيانات المستخدم';
            redirect('/users');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/users/edit/' . $id);
        }
    }

    public function delete($id)
    {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();
        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM user_branches WHERE user_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$id]);
            $userModel = new User();
            $userModel->delete($id);
            $db->commit();
            $_SESSION['success'] = 'تم حذف المستخدم';
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
        }
        redirect('/users');
    }
}
