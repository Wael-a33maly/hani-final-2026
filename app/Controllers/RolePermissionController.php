<?php
require_once __DIR__ . '/../Core/Controller.php';

class RolePermissionController extends Controller
{
    // ========== الأدوار ==========

    public function rolesIndex()
    {
        requireRole('admin');
        $db = getDB();
        $roles = $db->query("
            SELECT r.*,
                   (SELECT COUNT(*) FROM role_permissions WHERE role_id = r.id) as permissions_count,
                   (SELECT COUNT(*) FROM users WHERE role_id = r.id) as users_count
            FROM roles r
            ORDER BY r.id
        ")->fetchAll();
        $this->view('role-permissions.roles.index', compact('roles'));
    }

    public function rolesCreate()
    {
        requireRole('admin');
        $db = getDB();
        $modules = $db->query("
            SELECT module, GROUP_CONCAT(id) as perm_ids,
                   GROUP_CONCAT(CONCAT(name,'::',display_name) SEPARATOR '||') as perms
            FROM permissions
            GROUP BY module
            ORDER BY module
        ")->fetchAll();
        $permissions = $db->query("SELECT * FROM permissions ORDER BY module, action")->fetchAll();
        $this->view('role-permissions.roles.form', compact('modules', 'permissions'));
    }

    public function rolesStore()
    {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();

        $name = trim($_POST['name']);
        $displayName = trim($_POST['display_name']);
        $description = trim($_POST['description'] ?? '');
        $permIds = $_POST['permissions'] ?? [];

        if (empty($name) || empty($displayName)) {
            $_SESSION['error'] = 'اسم الدور والاسم الظاهر مطلوبان';
            redirect('/role-permissions/roles/create');
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("INSERT INTO roles (name, display_name, description, is_system) VALUES (?, ?, ?, 0)");
            $stmt->execute([$name, $displayName, $description]);
            $roleId = $db->lastInsertId();

            $stmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permIds as $pid) {
                $stmt->execute([$roleId, $pid]);
            }

            $db->commit();
            $_SESSION['success'] = 'تم إنشاء الدور بنجاح';
            redirect('/role-permissions/roles');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/role-permissions/roles/create');
        }
    }

    public function rolesEdit($id)
    {
        requireRole('admin');
        $db = getDB();
        $role = $db->prepare("SELECT * FROM roles WHERE id = ?");
        $role->execute([$id]);
        $role = $role->fetch();
        if (!$role) redirect('/role-permissions/roles');

        $permissions = $db->query("SELECT * FROM permissions ORDER BY module, action")->fetchAll();
        $rolePerms = $db->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
        $rolePerms->execute([$id]);
        $rolePermIds = $rolePerms->fetchAll(PDO::FETCH_COLUMN);

        $this->view('role-permissions.roles.form', compact('role', 'permissions', 'rolePermIds'));
    }

    public function rolesUpdate($id)
    {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();

        $role = $db->prepare("SELECT * FROM roles WHERE id = ?");
        $role->execute([$id]);
        $role = $role->fetch();
        if (!$role) redirect('/role-permissions/roles');

        if ($role['is_system']) {
            $_SESSION['error'] = 'لا يمكن تعديل أدوار النظام';
            redirect('/role-permissions/roles');
        }

        $displayName = trim($_POST['display_name']);
        $description = trim($_POST['description'] ?? '');
        $permIds = $_POST['permissions'] ?? [];

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE roles SET display_name = ?, description = ? WHERE id = ?");
            $stmt->execute([$displayName, $description, $id]);

            $db->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$id]);
            $stmt = $db->prepare("INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)");
            foreach ($permIds as $pid) {
                $stmt->execute([$id, $pid]);
            }

            $db->commit();
            $_SESSION['success'] = 'تم تحديث الدور';
            redirect('/role-permissions/roles');
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
            redirect('/role-permissions/roles/edit/' . $id);
        }
    }

    public function rolesDelete($id)
    {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();

        $role = $db->prepare("SELECT * FROM roles WHERE id = ?");
        $role->execute([$id]);
        $role = $role->fetch();

        if (!$role) redirect('/role-permissions/roles');
        if ($role['is_system']) {
            $_SESSION['error'] = 'لا يمكن حذف أدوار النظام';
            redirect('/role-permissions/roles');
        }

        $userCount = $db->prepare("SELECT COUNT(*) FROM users WHERE role_id = ?");
        $userCount->execute([$id]);
        if ($userCount->fetchColumn() > 0) {
            $_SESSION['error'] = 'لا يمكن حذف دور مستخدم من قبل مستخدمين';
            redirect('/role-permissions/roles');
        }

        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM role_permissions WHERE role_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM roles WHERE id = ?")->execute([$id]);
            $db->commit();
            $_SESSION['success'] = 'تم حذف الدور';
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
        }
        redirect('/role-permissions/roles');
    }

    // ========== الصلاحيات ==========

    public function permissionsIndex()
    {
        requireRole('admin');
        $db = getDB();
        $modules = $db->query("
            SELECT module, COUNT(*) as count
            FROM permissions
            GROUP BY module
            ORDER BY module
        ")->fetchAll();
        $permissions = $db->query("SELECT * FROM permissions ORDER BY module, action")->fetchAll();
        $this->view('role-permissions.permissions.index', compact('modules', 'permissions'));
    }

    // ========== صلاحيات المستخدمين ==========

    public function userPermissionsEdit($userId)
    {
        requireRole('admin');
        $db = getDB();
        $user = $db->prepare("SELECT u.*, r.display_name as role_name FROM users u LEFT JOIN roles r ON u.role_id = r.id WHERE u.id = ?");
        $user->execute([$userId]);
        $user = $user->fetch();
        if (!$user) redirect('/users');

        $permissions = $db->query("SELECT * FROM permissions ORDER BY module, action")->fetchAll();

        $rolePerms = [];
        if ($user['role_id']) {
            $stmt = $db->prepare("SELECT permission_id FROM role_permissions WHERE role_id = ?");
            $stmt->execute([$user['role_id']]);
            $rolePerms = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $userPerms = $db->prepare("SELECT permission_id, type FROM user_permissions WHERE user_id = ?");
        $userPerms->execute([$userId]);
        $userPermsData = [];
        while ($row = $userPerms->fetch()) {
            $userPermsData[$row['permission_id']] = $row['type'];
        }

        $this->view('role-permissions.users.permissions', compact('user', 'permissions', 'rolePerms', 'userPermsData'));
    }

    public function userPermissionsUpdate($userId)
    {
        requireRole('admin');
        $this->verifyCSRF();
        $db = getDB();

        $grants = $_POST['grant'] ?? [];
        $denies = $_POST['deny'] ?? [];

        $db->beginTransaction();
        try {
            $db->prepare("DELETE FROM user_permissions WHERE user_id = ?")->execute([$userId]);

            $stmt = $db->prepare("INSERT INTO user_permissions (user_id, permission_id, type) VALUES (?, ?, ?)");
            foreach ($grants as $pid) {
                $stmt->execute([$userId, $pid, 'grant']);
            }
            foreach ($denies as $pid) {
                $stmt->execute([$userId, $pid, 'deny']);
            }

            $addedPerms = array_keys(array_merge($grants, $denies));
            $stmt = $db->prepare("SELECT name FROM permissions WHERE id IN (" . implode(',', array_map('intval', $addedPerms ?: [0])) . ")");
            $stmt->execute();
            $permNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $db->prepare("
                INSERT INTO permission_audit_log (user_id, changed_by, action_type, added_permissions, removed_permissions)
                VALUES (?, ?, 'custom_permissions', ?, NULL)
            ");
            $stmt->execute([$userId, $this->userId, json_encode($permNames)]);

            $db->commit();
            PermissionHelper::clearCache($userId);
            $_SESSION['success'] = 'تم تحديث صلاحيات المستخدم';
        } catch (Exception $e) {
            $db->rollBack();
            $_SESSION['error'] = 'خطأ: ' . $e->getMessage();
        }
        redirect('/users');
    }
}
