<?php
/**
 * config.php - إعدادات قاعدة البيانات والتطبيق
 * يتم تعديلها يدوياً حسب استضافة Hostinger
 */

// هيدرات الأمان
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// إعدادات قاعدة البيانات
define('DB_HOST', 'localhost');
define('DB_NAME', 'u905425928_hani');
define('DB_USER', 'u905425928_hani');
define('DB_PASS', 'WEGSMs@1983');
define('DB_CHARSET', 'utf8mb4');

// إعدادات التطبيق
define('APP_NAME', 'Aqsati');
define('APP_URL', 'https://hani.alryamikw.com');

define('DEFAULT_TIMEZONE', 'Asia/Kuwait');
define('SESSION_NAME', 'hani_erp_session');

// إعدادات الأمان
define('CSRF_TOKEN_NAME', 'csrf_token');
define('LOGIN_ATTEMPTS_LIMIT', 5);
define('LOGIN_LOCKOUT_TIME', 15); // دقائق

// المسارات
define('BASE_PATH', dirname(__FILE__));
define('CONTROLLERS_PATH', BASE_PATH . '/app/Controllers/');
define('MODELS_PATH', BASE_PATH . '/app/Models/');
define('VIEWS_PATH', BASE_PATH . '/app/Views/');
define('PUBLIC_PATH', BASE_PATH . '/public/');
define('UPLOADS_PATH', PUBLIC_PATH . 'uploads/');
define('LIBS_PATH', PUBLIC_PATH . 'libs/');
define('STORAGE_PATH', BASE_PATH . '/storage/');
define('BACKUPS_PATH', STORAGE_PATH . 'backups/');
define('UPDATES_PATH', STORAGE_PATH . 'updates/');

// بدء الجلسة
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
session_name(SESSION_NAME);
session_start();

// ضبط المنطقة الزمنية
date_default_timezone_set(DEFAULT_TIMEZONE);

// دالة الاتصال بقاعدة البيانات (PDO)
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);
        } catch (PDOException $e) {
            die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
        }
    }
    return $pdo;
}

// دالة لتوليد CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

// دالة للتحقق من CSRF Token
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION[CSRF_TOKEN_NAME] ?? '') . '">';
}

// دالة لتسجيل عمليات audit
function logAudit($userId, $action, $tableName = null, $recordId = null, $oldData = null, $newData = null) {
    $db  = getDB();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '';
    $stmt = $db->prepare(
        "INSERT INTO audit_log (user_id, action, table_name, record_id, old_data, new_data, ip_address) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$userId, $action, $tableName, $recordId, $oldData, $newData, $ip]);
}

// دالة التحقق من تسجيل الدخول والصلاحيات
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_role']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . APP_URL . '/login');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if ($_SESSION['user_role'] === 'admin') return;

    $userRole = currentUserRole();
    $roleName = $userRole['role_name'] ?? '';

    if ($roleName === 'admin') return;

    $allowedRoles = [
        'admin' => ['admin', 'branch_manager'],
        'user' => ['user', 'admin', 'branch_manager', 'sales_rep', 'collector'],
    ];

    if ($role === 'admin' && $roleName === 'branch_manager') return;

    if (isset($allowedRoles[$role])) {
        if (in_array($roleName, $allowedRoles[$role])) return;
    }

    if ($_SESSION['user_role'] === $role) return;
    if ($roleName === $role) return;

    header('HTTP/1.0 403 Forbidden');
    if (file_exists(VIEWS_PATH . 'errors/403.php')) {
        require_once VIEWS_PATH . 'errors/403.php';
    } else {
        die('غير مسموح بالوصول');
    }
    exit;
}

// دالة لتنظيف المدخلات (تستخدم للـ trim فقط - PDO يتولى الـ escaping)
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return trim($data);
}

// دالة مساعدة للـ redirect
function redirect($url) {
    header('Location: ' . APP_URL . '/' . ltrim($url, '/'));
    exit;
}

// دالة لتصدير قاعدة البيانات كـ SQL
function exportDatabaseToSQL() {
    $db = getDB();
    $tables = [];
    $stmt = $db->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    
    $output = "-- Backup generated on " . date('Y-m-d H:i:s') . "\n";
    $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $output .= "START TRANSACTION;\n\n";
    
    foreach ($tables as $table) {
        // الهيكل
        $stmt = $db->query("SHOW CREATE TABLE `$table`");
        $create = $stmt->fetch(PDO::FETCH_ASSOC);
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $create['Create Table'] . ";\n\n";
        
        // البيانات
        $stmtData = $db->query("SELECT * FROM `$table`");
        $rows = $stmtData->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 0) {
            $columns = array_keys($rows[0]);
            $output .= "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES\n";
            $values = [];
            foreach ($rows as $row) {
                $escaped = [];
                foreach ($row as $value) {
                    if ($value === null) {
                        $escaped[] = 'NULL';
                    } else {
                        $escaped[] = $db->quote($value);
                    }
                }
                $values[] = "(" . implode(', ', $escaped) . ")";
            }
            $output .= implode(",\n", $values) . ";\n\n";
        }
    }
    $output .= "COMMIT;\n";
    return $output;
}


// التأكد من وجود المجلدات الأساسية
$directories = [
    PUBLIC_PATH . 'uploads',
    PUBLIC_PATH . 'uploads/logo',
    BASE_PATH . '/storage/logs'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// ملف .htaccess في مجلد uploads لمنع تنفيذ الملفات الضارة
$htaccessContent = "Options -Indexes\n";
$htaccessContent .= "<FilesMatch \"\\.(php|phtml|php3|php4|php5|phps|shtml|jsp|asp|aspx|pl|py|cgi)$\">\n";
$htaccessContent .= "    Require all denied\n";
$htaccessContent .= "</FilesMatch>\n";

$htaccessPath = PUBLIC_PATH . 'uploads/.htaccess';
if (!file_exists($htaccessPath)) {
    @file_put_contents($htaccessPath, $htaccessContent);
}
