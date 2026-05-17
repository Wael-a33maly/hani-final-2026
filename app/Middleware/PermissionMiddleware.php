<?php

class PermissionMiddleware
{
    public static function check($permission)
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        $role = PermissionHelper::getUserRole($_SESSION['user_id']);
        if ($role && $role['role_name'] === 'admin') {
            return true;
        }

        if (!PermissionHelper::hasPermission($_SESSION['user_id'], $permission)) {
            http_response_code(403);
            if (file_exists(VIEWS_PATH . 'errors/403.php')) {
                require_once VIEWS_PATH . 'errors/403.php';
            } else {
                echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>غير مسموح</title>';
                echo '<style>body{font-family:Tahoma,Arial,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f1f5f9;color:#1e293b;text-align:center;direction:rtl;}';
                echo '.container{background:white;padding:40px;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);max-width:420px;}';
                echo 'h1{font-size:48px;color:#dc2626;margin:0 0 8px;}h2{font-size:18px;margin:0 0 12px;}p{color:#64748b;font-size:14px;margin:0 0 24px;}';
                echo 'a{display:inline-block;background:#1e40af;color:white;padding:10px 24px;border-radius:8px;text-decoration:none;font-size:14px;}</style></head><body>';
                echo '<div class="container"><h1>403</h1><h2>غير مسموح بالوصول</h2>';
                echo '<p>ليس لديك الصلاحية الكافية للوصول إلى هذه الصفحة.</p>';
                echo '<a href="' . APP_URL . '/dashboard">العودة للرئيسية</a></div></body></html>';
            }
            exit;
        }

        return true;
    }

    public static function requireBranchAccess($branchId)
    {
        if (!isset($_SESSION['user_id'])) return false;
        $role = PermissionHelper::getUserRole($_SESSION['user_id']);
        if ($role && $role['role_name'] === 'admin') return true;
        return PermissionHelper::canAccessBranch($_SESSION['user_id'], $branchId);
    }
}
