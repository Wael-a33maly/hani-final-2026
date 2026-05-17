<?php
/**
 * index.php - Front Controller
 */
require_once __DIR__ . '/config.php';

// تحديد BASE_URL ديناميكياً (يمكن تحديثه عبر نظام التحديثات)
if (!defined('BASE_URL')) {
    $path = parse_url(APP_URL, PHP_URL_PATH);
    define('BASE_URL', rtrim($path ?: '', '/') . '/');
}

// تحديث قاعدة البيانات تلقائياً (مرة واحدة فقط)
define('DB_SCHEMA_VERSION', '2.0.0');
$needsMigration = false;
try {
    $db_mig = getDB();
    $stmt = $db_mig->query("SELECT `value` FROM company_settings WHERE `key` = 'schema_version'");
    $row = $stmt->fetch();
    $needsMigration = !$row || $row['value'] !== DB_SCHEMA_VERSION;
} catch (Exception $e) {
    $needsMigration = true;
}

if ($needsMigration) {
    try {
        $db_mig = getDB();
        $db_mig->exec("ALTER TABLE sales_invoices ADD COLUMN sales_rep_id INT NULL AFTER customer_id");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_mig->exec("ALTER TABLE products ADD COLUMN commission_amount DECIMAL(10,2) DEFAULT 0.00 COMMENT 'مبلغ العمولة الثابت للمندوب عند بيع قطعة واحدة'");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_mig->exec("ALTER TABLE users ADD COLUMN collection_commission_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT 'نسبة عمولة التحصيل % من الأقساط المحصّلة'");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_mig->exec("CREATE TABLE IF NOT EXISTS sales_commissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sale_id INT NOT NULL,
            sale_item_id INT NOT NULL,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity DECIMAL(10,3) NOT NULL,
            commission_amount_per_unit DECIMAL(10,2) NOT NULL,
            total_commission DECIMAL(10,2) NOT NULL,
            commission_date DATE NOT NULL,
            status ENUM('pending','paid','cancelled') DEFAULT 'pending',
            paid_at TIMESTAMP NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_sale (sale_id),
            INDEX idx_date (commission_date),
            INDEX idx_status (status),
            FOREIGN KEY (sale_id) REFERENCES sales_invoices(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (product_id) REFERENCES products(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_mig->exec("CREATE TABLE IF NOT EXISTS collection_commissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            installment_id INT NOT NULL,
            installment_payment_id INT NULL,
            sale_id INT NOT NULL,
            user_id INT NOT NULL,
            customer_id INT NOT NULL,
            customer_name VARCHAR(255) NOT NULL,
            collected_amount DECIMAL(10,2) NOT NULL,
            commission_rate DECIMAL(5,2) NOT NULL,
            commission_amount DECIMAL(10,2) NOT NULL,
            collection_date DATE NOT NULL,
            status ENUM('pending','paid','cancelled') DEFAULT 'pending',
            paid_at TIMESTAMP NULL,
            notes TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_installment (installment_id),
            INDEX idx_date (collection_date),
            INDEX idx_status (status),
            FOREIGN KEY (installment_id) REFERENCES installments(id) ON DELETE CASCADE,
            FOREIGN KEY (sale_id) REFERENCES sales_invoices(id),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (customer_id) REFERENCES customers(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_mig->exec("CREATE TABLE IF NOT EXISTS commission_payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            payment_date DATE NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_type ENUM('cash','transfer','vodafone','instapay') DEFAULT 'cash',
            period_from DATE NOT NULL,
            period_to DATE NOT NULL,
            notes TEXT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_date (payment_date),
            FOREIGN KEY (user_id) REFERENCES users(id),
            FOREIGN KEY (created_by) REFERENCES users(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_mig->exec("ALTER TABLE purchase_invoices ADD COLUMN warehouse_id INT NULL AFTER supplier_id");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_name = DB_NAME;
        $cols = [
            'auto_updates' => "ALTER TABLE company_settings ADD COLUMN auto_updates tinyint(1) NOT NULL DEFAULT 0",
            'auto_check_updates' => "ALTER TABLE company_settings ADD COLUMN auto_check_updates tinyint(1) NOT NULL DEFAULT 1",
            'notify_admin_update' => "ALTER TABLE company_settings ADD COLUMN notify_admin_update tinyint(1) NOT NULL DEFAULT 1",
            'max_backups' => "ALTER TABLE company_settings ADD COLUMN max_backups int(11) NOT NULL DEFAULT 5",
            'max_update_size' => "ALTER TABLE company_settings ADD COLUMN max_update_size int(11) NOT NULL DEFAULT 512",
            'app_version' => "ALTER TABLE company_settings ADD COLUMN app_version varchar(20) NOT NULL DEFAULT '1.0.0'",
            'last_update_at' => "ALTER TABLE company_settings ADD COLUMN last_update_at datetime DEFAULT NULL",
            'last_check_at' => "ALTER TABLE company_settings ADD COLUMN last_check_at datetime DEFAULT NULL",
            'backup_count' => "ALTER TABLE company_settings ADD COLUMN backup_count int(11) NOT NULL DEFAULT 0",
            'logo_path' => "ALTER TABLE company_settings ADD COLUMN logo_path varchar(255) DEFAULT NULL",
        ];
        foreach ($cols as $col => $ddl) {
            $stmt = $db_mig->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = '$db_name' AND table_name = 'company_settings' AND column_name = '$col'");
            if ((int)$stmt->fetchColumn() === 0) {
                $db_mig->exec($ddl);
            }
        }
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_mig->exec("CREATE TABLE IF NOT EXISTS `version_migrations` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `version` varchar(20) NOT NULL,
            `zip_file` varchar(255) NOT NULL,
            `zip_hash` varchar(64) DEFAULT NULL,
            `status` enum('pending','running','completed','failed','rolled_back') NOT NULL DEFAULT 'pending',
            `backup_path` varchar(255) DEFAULT NULL,
            `error_message` text DEFAULT NULL,
            `executed_by` int(11) DEFAULT NULL,
            `started_at` datetime DEFAULT NULL,
            `completed_at` datetime DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `executed_by` (`executed_by`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_mig->exec("CREATE TABLE IF NOT EXISTS `customer_opening_balance` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_id` int(11) NOT NULL,
            `installment_date` date NOT NULL,
            `amount` decimal(10,2) NOT NULL,
            `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
            `status` enum('pending','partial','paid') NOT NULL DEFAULT 'pending',
            `notes` text DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `customer_id` (`customer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_mig->exec("CREATE TABLE IF NOT EXISTS `customer_opening_payments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `opening_id` int(11) NOT NULL,
            `amount` decimal(10,2) NOT NULL,
            `payment_date` date NOT NULL,
            `notes` text DEFAULT NULL,
            `received_by` int(11) NOT NULL,
            `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `opening_id` (`opening_id`),
            KEY `received_by` (`received_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        $db_mig->exec("CREATE TABLE IF NOT EXISTS `current_stock` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `product_id` int(11) NOT NULL,
            `warehouse_id` int(11) NOT NULL,
            `quantity` decimal(10,3) NOT NULL DEFAULT '0.000',
            `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unique_product_warehouse` (`product_id`,`warehouse_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (Exception $e) {}
    try {
        $db_mig = getDB();
        require_once MODELS_PATH . 'StockModel.php';
        (new StockModel())->recalculateAllStock();
    } catch (Exception $e) {}

    // تحديثات RBAC
    try {
    $db_mig = getDB();
    $db_name = DB_NAME;
    $stmt = $db_mig->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = '$db_name' AND table_name = 'users' AND column_name = 'role_id'");
    if ((int)$stmt->fetchColumn() === 0) {
        $db_mig->exec("ALTER TABLE users ADD COLUMN `role_id` int(11) DEFAULT NULL AFTER `branch_id`");
    }
    $stmt = $db_mig->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE table_schema = '$db_name' AND table_name = 'users' AND column_name = 'can_view_all_branches'");
    if ((int)$stmt->fetchColumn() === 0) {
        $db_mig->exec("ALTER TABLE users ADD COLUMN `can_view_all_branches` tinyint(1) NOT NULL DEFAULT 0 AFTER `role_id`");
    }

    $db_mig->exec("CREATE TABLE IF NOT EXISTS `roles` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(100) NOT NULL, `display_name` varchar(100) NOT NULL,
      `description` text DEFAULT NULL, `is_system` tinyint(1) NOT NULL DEFAULT 0,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db_mig->exec("CREATE TABLE IF NOT EXISTS `permissions` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(100) NOT NULL, `display_name` varchar(100) NOT NULL,
      `description` text DEFAULT NULL, `module` varchar(50) NOT NULL, `action` varchar(50) NOT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`), UNIQUE KEY `name` (`name`), KEY `idx_module` (`module`), KEY `idx_action` (`action`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db_mig->exec("CREATE TABLE IF NOT EXISTS `role_permissions` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `role_id` int(11) NOT NULL, `permission_id` int(11) NOT NULL,
      PRIMARY KEY (`id`), UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
      KEY `idx_permission` (`permission_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db_mig->exec("CREATE TABLE IF NOT EXISTS `user_permissions` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `permission_id` int(11) NOT NULL,
      `type` enum('grant','deny') NOT NULL DEFAULT 'grant', `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`), UNIQUE KEY `unique_user_permission` (`user_id`,`permission_id`),
      KEY `idx_permission` (`permission_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db_mig->exec("CREATE TABLE IF NOT EXISTS `user_branches` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `branch_id` int(11) NOT NULL,
      `is_primary` tinyint(1) NOT NULL DEFAULT 0, `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`), UNIQUE KEY `unique_user_branch` (`user_id`,`branch_id`),
      KEY `idx_branch` (`branch_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $db_mig->exec("CREATE TABLE IF NOT EXISTS `permission_audit_log` (
      `id` int(11) NOT NULL AUTO_INCREMENT, `user_id` int(11) NOT NULL, `changed_by` int(11) NOT NULL,
      `action_type` varchar(50) NOT NULL, `old_role_id` int(11) DEFAULT NULL, `new_role_id` int(11) DEFAULT NULL,
      `added_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
      `removed_permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
      `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`), KEY `idx_user` (`user_id`), KEY `idx_date` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Insert default roles if not exist
    $stmt = $db_mig->query("SELECT COUNT(*) FROM roles");
    if ($stmt->fetchColumn() == 0) {
        $db_mig->exec("INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `is_system`) VALUES
            (1, 'admin', 'مدير النظام', 'صلاحيات كاملة - يرى جميع الفروع', 1),
            (2, 'branch_manager', 'مدير فرع', 'يدير فرعه بالكامل - مبيعات، مشتريات، عملاء، موردين، موظفين، تقارير', 1),
            (3, 'sales_rep', 'مندوب مبيعات', 'إنشاء فواتير بيع وأقساط، عهدة، عمولات', 1),
            (4, 'collector', 'محصل', 'تحصيل الأقساط، تسجيل المقبوضات', 1)");

        $db_mig->exec("INSERT INTO `permissions` (`id`, `name`, `display_name`, `module`, `action`) VALUES
            (1,'users.view','عرض المستخدمين','users','view'),(2,'users.create','إنشاء مستخدم','users','create'),
            (3,'users.edit','تعديل مستخدم','users','edit'),(4,'users.delete','حذف مستخدم','users','delete'),
            (5,'branches.view','عرض الفروع','branches','view'),(6,'branches.create','إنشاء فرع','branches','create'),
            (7,'branches.edit','تعديل فرع','branches','edit'),(8,'branches.delete','حذف فرع','branches','delete'),
            (9,'products.view','عرض المواد','products','view'),(10,'products.create','إنشاء مادة','products','create'),
            (11,'products.edit','تعديل مادة','products','edit'),(12,'products.delete','حذف مادة','products','delete'),
            (13,'warehouses.view','عرض المخازن','warehouses','view'),(14,'warehouses.create','إنشاء مخزن','warehouses','create'),
            (15,'warehouses.edit','تعديل مخزن','warehouses','edit'),(16,'warehouses.delete','حذف مخزن','warehouses','delete'),
            (17,'units.view','عرض الوحدات','units','view'),(18,'units.create','إنشاء وحدة','units','create'),
            (19,'units.edit','تعديل وحدة','units','edit'),(20,'units.delete','حذف وحدة','units','delete'),
            (21,'suppliers.view','عرض الموردين','suppliers','view'),(22,'suppliers.create','إنشاء مورد','suppliers','create'),
            (23,'suppliers.edit','تعديل مورد','suppliers','edit'),(24,'suppliers.delete','حذف مورد','suppliers','delete'),
            (25,'purchases.view','عرض المشتريات','purchases','view'),(26,'purchases.create','إنشاء فاتورة مشتريات','purchases','create'),
            (27,'purchases.edit','تعديل فاتورة مشتريات','purchases','edit'),(28,'customers.view','عرض العملاء','customers','view'),
            (29,'customers.create','إنشاء عميل','customers','create'),(30,'customers.edit','تعديل عميل','customers','edit'),
            (31,'customers.delete','حذف عميل','customers','delete'),(32,'sales.view','عرض فواتير البيع','sales','view'),
            (33,'sales.create','إنشاء فاتورة بيع','sales','create'),(34,'sales.edit','تعديل فاتورة بيع','sales','edit'),
            (35,'sales.delete','حذف فاتورة بيع','sales','delete'),(36,'installments.view','عرض الأقساط','installments','view'),
            (37,'installments.create','إنشاء قسط','installments','create'),(38,'installments.collect','تحصيل قسط','installments','collect'),
            (39,'installments.edit','تعديل قسط','installments','edit'),(40,'installments.delete','حذف قسط','installments','delete'),
            (41,'payments.view','عرض المقبوضات','payments','view'),(42,'payments.create','تسجيل مقبوض','payments','create'),
            (43,'expenses.view','عرض المصروفات','expenses','view'),(44,'expenses.create','إنشاء مصروف','expenses','create'),
            (45,'expenses.edit','تعديل مصروف','expenses','edit'),(46,'salesrep.view','عرض عهد المندوبين','salesrep','view'),
            (47,'salesrep.assign','إسناد عهدة','salesrep','assign'),(48,'salesrep.record_sale','تسجيل بيع من عهدة','salesrep','record_sale'),
            (49,'salesrep.return','استرداد عهدة','salesrep','return'),(50,'commissions.view','عرض العمولات','commissions','view'),
            (51,'commissions.pay','دفع عمولة','commissions','pay'),(52,'reports.view','عرض التقارير','reports','view'),
            (53,'settings.view','عرض الإعدادات','settings','view'),(54,'settings.edit','تعديل الإعدادات','settings','edit'),
            (55,'updates.view','عرض التحديثات','updates','view'),(56,'updates.execute','تنفيذ التحديثات','updates','execute')");

        $db_mig->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 1, id FROM permissions");
        $db_mig->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 2, id FROM permissions WHERE name NOT IN ('branches.create','branches.edit','branches.delete','settings.edit','updates.execute')");
        $db_mig->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 3, id FROM permissions WHERE name IN ('products.view','products.create','customers.view','customers.create','sales.view','sales.create','installments.view','installments.collect','salesrep.view','salesrep.record_sale','commissions.view','reports.view')");
        $db_mig->exec("INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) SELECT 4, id FROM permissions WHERE name IN ('customers.view','installments.view','installments.collect','payments.view','payments.create','commissions.view','reports.view')");

        // ترحيل صلاحيات المستخدمين القدامى (role -> role_id)
        $db_mig->exec("UPDATE users u JOIN roles r ON (u.role = 'admin' AND r.name = 'admin') SET u.role_id = r.id WHERE u.role_id IS NULL AND u.role = 'admin'");
        $db_mig->exec("UPDATE users u JOIN roles r ON (u.role = 'user' AND r.name = 'sales_rep') SET u.role_id = r.id WHERE u.role_id IS NULL AND u.role = 'user'");
    }
    } catch (Exception $e) {}

    // حفظ إصدار قاعدة البيانات
    try {
        $db_mig->exec("INSERT INTO company_settings (`key`, `value`) VALUES ('schema_version', '" . DB_SCHEMA_VERSION . "') ON DUPLICATE KEY UPDATE `value` = '" . DB_SCHEMA_VERSION . "'");
    } catch (Exception $e) {}
}

// إعادة حساب الرصيد في current_stock إذا كان الجدول فارغاً
try {
    $db_stock = getDB();
    $stockCount = $db_stock->query("SELECT COUNT(*) FROM current_stock")->fetchColumn();
    if ((int)$stockCount === 0) {
        require_once MODELS_PATH . 'StockModel.php';
        (new StockModel())->recalculateAllStock();
    }
} catch (Exception $e) {}

// تحميل نظام الصلاحيات (يتحقق من وجود الملفات أولاً)
$helperFile = __DIR__ . '/app/Helpers/PermissionHelper.php';
$middlewareFile = __DIR__ . '/app/Middleware/PermissionMiddleware.php';

if (!is_dir(dirname($helperFile))) @mkdir(dirname($helperFile), 0755, true);
if (!is_dir(dirname($middlewareFile))) @mkdir(dirname($middlewareFile), 0755, true);

if (file_exists($helperFile)) require_once $helperFile;

if (!class_exists('PermissionHelper')) {
    class PermissionHelper {
        public static function filterByBranch($prefix = '', $userId = null, $tableAlias = 'b', $column = 'branch_id') { return ''; }
        public static function filterByWarehouseBranch($prefix = '', $userId = null, $tableAlias = 'si') { return ''; }
        public static function getUserBranches($userId = null) { return []; }
        public static function getPrimaryBranch($userId = null) { return null; }
        public static function hasPermission($userId, $permission) { return true; }
        public static function canAccessBranch($userId, $branchId) { return true; }
        public static function currentUserRole() {
            $sessionRole = $_SESSION['user_role'] ?? '';
            $displayNames = ['admin'=>'مدير النظام','branch_manager'=>'مدير فرع','sales_rep'=>'مندوب مبيعات','collector'=>'محصل'];
            return ['role_id'=>null,'role_name'=>$sessionRole,'display_name'=>$displayNames[$sessionRole]??$sessionRole];
        }
        public static function getUserRole($userId) { return []; }
        public static function clearCache($userId = null) {}
        public static function syncUserBranches($userId, $branchIds, $primaryId = null) {}
        public static function assignRoleToUser($userId, $roleId) {}
    }
}
if (!function_exists('currentUserRole')) {
    function currentUserRole() { return PermissionHelper::currentUserRole(); }
    function can($permission) { return true; }
    function canAccessBranch($branchId) { return true; }
    function userBranches() { return []; }
}

if (file_exists($middlewareFile)) require_once $middlewareFile;

// معالج استثناءات عام
set_exception_handler(function ($e) {
    error_log("Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    if (file_exists(VIEWS_PATH . 'errors/500.php')) {
        require_once VIEWS_PATH . 'errors/500.php';
    } else {
        echo '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="UTF-8"><title>خطأ 500</title>';
        echo '<style>body{font-family:Tahoma,Arial;background:#f3f4f6;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;direction:rtl}';
        echo '.card{background:white;border-radius:16px;padding:40px;text-align:center;box-shadow:0 4px 20px rgba(0,0,0,0.1);max-width:400px}';
        echo 'h1{color:#ef4444;font-size:48px;margin:0}';
        echo 'p{color:#6b7280;margin-top:8px}</style></head>';
        echo '<body><div class="card"><h1>500</h1><p>حدث خطأ داخلي في الخادم. يرجى المحاولة لاحقاً.</p></div></body></html>';
    }
    exit;
});

// تحميل الـ Router
require_once __DIR__ . '/routes/web.php';

// معالجة طلب favicon.ico (إرجاع اللوجو المرفوع)
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/favicon.ico') {
    try {
        $favDb = getDB();
        $favStmt = $favDb->query("SELECT `value` FROM company_settings WHERE `key` = 'logo_path'");
        $favRow = $favStmt->fetch();
        if ($favRow && $favPath = $favRow['value']) {
            $favFile = PUBLIC_PATH . ltrim($favPath, '/');
            if (file_exists($favFile)) {
                $ext = strtolower(pathinfo($favFile, PATHINFO_EXTENSION));
                $mime = $ext === 'png' ? 'image/png' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/x-icon');
                header('Content-Type: ' . $mime);
                header('Cache-Control: public, max-age=86400');
                readfile($favFile);
                exit;
            }
        }
    } catch (Exception $e) {}
    // إذا لم يوجد لوجو -> SVG favicon صغير
    header('Content-Type: image/svg+xml');
    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><rect width="32" height="32" rx="6" fill="#2563eb"/><text x="16" y="22" font-size="18" font-family="Arial" fill="white" text-anchor="middle" font-weight="bold">H</text></svg>';
    exit;
}

// تشغيل الـ Router
$router->dispatch($_SERVER['REQUEST_URI']);
