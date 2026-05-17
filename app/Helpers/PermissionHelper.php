<?php

class PermissionHelper
{
    private static $userPermissionsCache = [];
    private static $userBranchesCache = [];

    public static function hasPermission($userId, $permissionName)
    {
        if (!$userId) return false;
        $perms = self::getAllPermissions($userId);

        if (isset($perms[$permissionName])) {
            return $perms[$permissionName];
        }

        return false;
    }

    public static function getAllPermissions($userId)
    {
        if (!$userId) return [];
        if (isset(self::$userPermissionsCache[$userId])) {
            return self::$userPermissionsCache[$userId];
        }

        $db = getDB();

        $stmt = $db->prepare("SELECT can_view_all_branches FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) return [];

        $rolePerms = [];
        $stmt = $db->prepare("
            SELECT p.name FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            JOIN users u ON u.role_id = rp.role_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        while ($row = $stmt->fetch()) {
            $rolePerms[$row['name']] = true;
        }

        $userPerms = [];
        $stmt = $db->prepare("
            SELECT p.name, up.type FROM user_permissions up
            JOIN permissions p ON up.permission_id = p.id
            WHERE up.user_id = ?
        ");
        $stmt->execute([$userId]);
        while ($row = $stmt->fetch()) {
            $userPerms[$row['name']] = $row['type'];
        }

        $result = [];
        foreach ($rolePerms as $name => $v) {
            if (isset($userPerms[$name])) {
                $result[$name] = ($userPerms[$name] === 'grant');
            } else {
                $result[$name] = true;
            }
        }

        foreach ($userPerms as $name => $type) {
            if (!isset($rolePerms[$name])) {
                $result[$name] = ($type === 'grant');
            }
        }

        self::$userPermissionsCache[$userId] = $result;
        return $result;
    }

    public static function getUserRole($userId)
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT r.id as role_id, r.name as role_name, r.display_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public static function getUserBranches($userId)
    {
        if (!$userId) return [];
        if (isset(self::$userBranchesCache[$userId])) {
            return self::$userBranchesCache[$userId];
        }

        $db = getDB();

        $stmt = $db->prepare("SELECT can_view_all_branches, branch_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) return [];

        if ($user['can_view_all_branches']) {
            $stmt = $db->query("SELECT id FROM branches");
            $branchIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            self::$userBranchesCache[$userId] = $branchIds;
            return $branchIds;
        }

        $stmt = $db->prepare("
            SELECT branch_id FROM user_branches WHERE user_id = ?
            UNION
            SELECT ? WHERE ? IS NOT NULL
        ");
        $stmt->execute([$userId, $user['branch_id'], $user['branch_id']]);
        $branchIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $branchIds = array_unique(array_filter($branchIds));
        self::$userBranchesCache[$userId] = $branchIds;
        return $branchIds;
    }

    public static function getPrimaryBranch($userId)
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT branch_id FROM user_branches WHERE user_id = ? AND is_primary = 1 LIMIT 1
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        if ($result) return (int)$result['branch_id'];

        $stmt = $db->prepare("SELECT branch_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ? (int)$user['branch_id'] : 0;
    }

    public static function canAccessBranch($userId, $branchId)
    {
        $branches = self::getUserBranches($userId);
        return in_array($branchId, $branches);
    }

    public static function filterByBranch($query, $userId, $tableAlias = null, $branchColumn = 'branch_id')
    {
        $branches = self::getUserBranches($userId);
        if (empty($branches)) {
            return $query . " AND 1=0";
        }
        $alias = $tableAlias ? $tableAlias . '.' : '';
        $ids = implode(',', array_map('intval', $branches));
        return $query . " AND {$alias}{$branchColumn} IN ({$ids})";
    }

    public static function getRolePermissions($roleId)
    {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT p.* FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = ?
            ORDER BY p.module, p.action
        ");
        $stmt->execute([$roleId]);
        return $stmt->fetchAll();
    }

    public static function assignRoleToUser($userId, $roleId)
    {
        $db = getDB();
        $stmt = $db->prepare("SELECT role_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $oldRoleId = $stmt->fetchColumn();

        $stmt = $db->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        $result = $stmt->execute([$roleId, $userId]);

        if ($result) {
            self::logPermissionChange($userId, 'role_change', $oldRoleId ?: null, $roleId);
        }

        self::clearCache($userId);
        return $result;
    }

    public static function grantPermission($userId, $permissionId, $type = 'grant')
    {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO user_permissions (user_id, permission_id, type)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE type = VALUES(type)
        ");
        $result = $stmt->execute([$userId, $permissionId, $type]);
        self::clearCache($userId);
        return $result;
    }

    public static function revokePermission($userId, $permissionId)
    {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM user_permissions WHERE user_id = ? AND permission_id = ?");
        $result = $stmt->execute([$userId, $permissionId]);
        self::clearCache($userId);
        return $result;
    }

    public static function syncUserBranches($userId, $branchIds, $primaryBranchId = null)
    {
        $db = getDB();
        $db->prepare("DELETE FROM user_branches WHERE user_id = ?")->execute([$userId]);

        if (empty($branchIds)) return true;

        $stmt = $db->prepare("INSERT INTO user_branches (user_id, branch_id, is_primary) VALUES (?, ?, ?)");
        foreach ($branchIds as $bid) {
            $isPrimary = ($primaryBranchId && $bid == $primaryBranchId) ? 1 : 0;
            $stmt->execute([$userId, $bid, $isPrimary]);
        }

        self::clearCache($userId);
        return true;
    }

    private static function logPermissionChange($userId, $actionType, $oldRoleId = null, $newRoleId = null)
    {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO permission_audit_log (user_id, changed_by, action_type, old_role_id, new_role_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $_SESSION['user_id'] ?? 0, $actionType, $oldRoleId, $newRoleId]);
    }

    public static function filterByWarehouseBranch($query, $userId, $tableAlias = null, $warehouseColumn = 'warehouse_id')
    {
        $branches = self::getUserBranches($userId);
        if (empty($branches)) {
            return $query . " AND 1=0";
        }
        $alias = $tableAlias ? $tableAlias . '.' : '';
        $ids = implode(',', array_map('intval', $branches));
        return $query . " AND {$alias}{$warehouseColumn} IN (SELECT id FROM warehouses WHERE branch_id IN ({$ids}))";
    }

    public static function filterBySalesRep($query, $userId, $tableAlias = null, $salesRepColumn = 'sales_rep_id')
    {
        $alias = $tableAlias ? $tableAlias . '.' : '';
        return $query . " AND {$alias}{$salesRepColumn} = " . (int)$userId;
    }

    public static function clearCache($userId = null)
    {
        if ($userId) {
            unset(self::$userPermissionsCache[$userId]);
            unset(self::$userBranchesCache[$userId]);
        } else {
            self::$userPermissionsCache = [];
            self::$userBranchesCache = [];
        }
    }

    public static function currentUserRole()
    {
        if (!isset($_SESSION['user_id'])) return null;
        try {
            $role = self::getUserRole($_SESSION['user_id']);
            if ($role && !empty($role['role_name'])) {
                return $role;
            }
        } catch (Exception $e) {
            // جدول roles قد لا يكون موجوداً (إصدار MySQL قديم)، نلجأ للـ session
        }
        $sessionRole = $_SESSION['user_role'] ?? '';
        $displayNames = [
            'admin' => 'مدير النظام',
            'branch_manager' => 'مدير فرع',
            'sales_rep' => 'مندوب مبيعات',
            'collector' => 'محصل',
        ];
        if ($sessionRole) {
            return [
                'role_id' => null,
                'role_name' => $sessionRole,
                'display_name' => $displayNames[$sessionRole] ?? $sessionRole,
            ];
        }
        return ['role_id' => null, 'role_name' => '', 'display_name' => 'مستخدم'];
    }

    public static function isAdmin($userId = null)
    {
        $uid = $userId ?: ($_SESSION['user_id'] ?? 0);
        $role = self::getUserRole($uid);
        return $role && $role['role_name'] === 'admin';
    }
}

function can($permission)
{
    if (!isset($_SESSION['user_id'])) return false;
    return PermissionHelper::hasPermission($_SESSION['user_id'], $permission);
}

function canAccessBranch($branchId)
{
    if (!isset($_SESSION['user_id'])) return false;
    return PermissionHelper::canAccessBranch($_SESSION['user_id'], $branchId);
}

function userBranches()
{
    if (!isset($_SESSION['user_id'])) return [];
    return PermissionHelper::getUserBranches($_SESSION['user_id']);
}

function currentUserRole()
{
    return PermissionHelper::currentUserRole();
}
