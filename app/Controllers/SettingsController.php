<?php
/**
 * app/Controllers/SettingsController.php
 */
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'CompanySetting.php';

class SettingsController extends Controller {

    // عرض صفحة الإعدادات
    public function index() {
        requireRole('admin');
        $settingsModel = new CompanySetting();
        $settings      = $settingsModel->getSettings();
        $this->view('settings.index', ['settings' => $settings]);
    }

    // تحديث بيانات الشركة
    public function updateCompany() {
        requireRole('admin');
        $this->verifyCSRF();

        $settingsModel = new CompanySetting();
        $settings      = $settingsModel->getSettings();

        $data = [
            'id'           => $settings['id'],
            'company_name' => trim($_POST['company_name'] ?? ''),
            'phone'        => trim($_POST['phone'] ?? ''),
            'address'      => trim($_POST['address'] ?? ''),
            'whatsapp'     => trim($_POST['whatsapp'] ?? ''),
            'facebook'     => trim($_POST['facebook'] ?? ''),
            'instagram'    => trim($_POST['instagram'] ?? ''),
        ];

        $settingsModel->updateSettings($data);
        logAudit($this->userId, 'تحديث بيانات الشركة', 'company_settings', $settings['id']);
        $_SESSION['success'] = 'تم تحديث بيانات الشركة بنجاح';
        redirect('/settings');
    }

    // عرض manifest.json الديناميكي لـ PWA
    public function manifest() {
        header('Content-Type: application/json');
        header('Cache-Control: public, max-age=3600');

        $settingsModel = new CompanySetting();
        $settings = $settingsModel->getSettings();

        $icon192 = rtrim(APP_URL, '/') . '/public/icons/icon-192.svg';
        $icon512 = rtrim(APP_URL, '/') . '/public/icons/icon-512.svg';
        $mime = 'image/svg+xml';

        if (!empty($settings['logo_path'])) {
            $localFile = PUBLIC_PATH . ltrim($settings['logo_path'], '/');
            if (file_exists($localFile)) {
                $logoUrl = rtrim(APP_URL, '/') . '/public/' . ltrim($settings['logo_path'], '/');
                $icon192 = $logoUrl;
                $icon512 = $logoUrl;
                $ext = strtolower(pathinfo($localFile, PATHINFO_EXTENSION));
                $mime = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/jpeg');
            }
        }

        $manifest = [
            'name' => 'Aqsati',
            'short_name' => 'Aqsati',
            'description' => 'نظام إدارة المبيعات والأقساط',
            'start_url' => '/dashboard',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'theme_color' => '#2563eb',
            'background_color' => '#ffffff',
            'lang' => 'ar',
            'dir' => 'rtl',
            'icons' => [
                ['src' => $icon192, 'sizes' => '192x192', 'type' => $mime, 'purpose' => 'any maskable'],
                ['src' => $icon512, 'sizes' => '512x512', 'type' => $mime, 'purpose' => 'any maskable'],
            ],
            'categories' => ['business', 'finance'],
            'prefer_related_applications' => false,
        ];

        echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // رفع الشعار
    public function uploadLogo() {
        requireRole('admin');
        $this->verifyCSRF();

        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'حدث خطأ في رفع الملف';
            redirect('/settings');
        }

        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($_FILES['logo']['type'], $allowed)) {
            $_SESSION['error'] = 'نوع الملف غير مسموح (JPEG, PNG, GIF فقط)';
            redirect('/settings');
        }

        $maxSize = 2 * 1024 * 1024; // 2MB
        if ($_FILES['logo']['size'] > $maxSize) {
            $_SESSION['error'] = 'حجم الملف يجب أن لا يتجاوز 2 ميجابايت';
            redirect('/settings');
        }

        // إنشاء مجلد الشعارات إذا لم يوجد
        $logoDir = UPLOADS_PATH . 'logo/';
        if (!is_dir($logoDir)) {
            mkdir($logoDir, 0755, true);
        }

        $extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
        $filename  = 'logo_' . time() . '.' . strtolower($extension);
        $filepath  = $logoDir . $filename;

        if (move_uploaded_file($_FILES['logo']['tmp_name'], $filepath)) {
            $settingsModel = new CompanySetting();
            $settings      = $settingsModel->getSettings();

            // حذف الشعار القديم
            if (!empty($settings['logo_path'])) {
                $oldPath = PUBLIC_PATH . ltrim($settings['logo_path'], '/');
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            $relativePath = 'uploads/logo/' . $filename;
            $settingsModel->updateSettings(['id' => $settings['id'], 'logo_path' => $relativePath]);

            // نسخ الشعار كأيقونة PWA
            $iconsDir = PUBLIC_PATH . 'icons/';
            if (!is_dir($iconsDir)) mkdir($iconsDir, 0755, true);
            $ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
            $iconMime = $ext === 'png' ? 'image/png' : ($ext === 'gif' ? 'image/gif' : 'image/jpeg');
            foreach (['icon-192', 'icon-512'] as $iconName) {
                $iconPath = $iconsDir . $iconName . '.png';
                @copy($filepath, $iconPath);
            }

            logAudit($this->userId, 'رفع شعار الشركة', 'company_settings', $settings['id']);
            $_SESSION['success'] = 'تم رفع الشعار بنجاح';
        } else {
            $_SESSION['error'] = 'فشل في رفع الملف، تحقق من صلاحيات مجلد uploads';
        }
        redirect('/settings');
    }

    // صفحة الاستعادة
    public function backup() {
        requireRole('admin');
        $this->view('settings.backup');
    }

    // تصدير نسخة احتياطية (ملف SQL)
    public function exportBackup() {
        requireRole('admin');
        $this->verifyCSRF();

        $sql      = exportDatabaseToSQL();
        $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
    }

    // استعادة نسخة احتياطية من ملف SQL باستخدام القراءة المتدفقة (Stream)
    public function importBackup() {
        requireRole('admin');
        $this->verifyCSRF();
        
        if (!isset($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'يرجى اختيار ملف SQL صالح';
            redirect('/settings/backup');
        }
        
        $tmpFile = $_FILES['backup_file']['tmp_name'];
        if (strtolower(pathinfo($_FILES['backup_file']['name'], PATHINFO_EXTENSION)) !== 'sql') {
            $_SESSION['error'] = 'الملف يجب أن يكون بصيغة SQL';
            redirect('/settings/backup');
        }
        
        $result = $this->importSQLFileStream($tmpFile);
        if ($result['success']) {
            logAudit($this->userId, 'استعادة نسخة احتياطية', null, null, null, "عدد الاستعلامات: {$result['count']}");
            $_SESSION['success'] = "تمت استعادة قاعدة البيانات بنجاح (تم تنفيذ {$result['count']} استعلام)";
            if (!empty($result['errors'])) {
                $_SESSION['warning'] = "بعض الاستعلامات لم تنفذ: " . count($result['errors']) . " أخطاء";
            }
        } else {
            $_SESSION['error'] = 'حدث خطأ أثناء استعادة الملف: ' . $result['message'];
        }
        redirect('/settings/backup');
    }

    private function importSQLFileStream($filePath) {
        $db = getDB();
        $handle = fopen($filePath, 'r');
        if (!$handle) return ['success' => false, 'message' => 'تعذر فتح الملف'];
        
        $query = '';
        $count = 0;
        $errors = [];
        
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        $db->beginTransaction();
        try {
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line === false) break;
                $line = trim($line);
                
                // تجاهل التعليقات والأسطر الفارغة
                if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) continue;
                
                $query .= $line;
                
                if (substr(trim($query), -1) === ';') {
                    try {
                        $db->exec($query);
                        $count++;
                    } catch (PDOException $e) {
                        $errors[] = substr($query, 0, 100) . '... Error: ' . $e->getMessage();
                    }
                    $query = '';
                }
            }
            $db->commit();
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            fclose($handle);
            return ['success' => true, 'count' => $count, 'errors' => $errors];
        } catch (Exception $e) {
            $db->rollBack();
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            fclose($handle);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // عرض صفحة إعدادات التحديثات
    public function updateSettings() {
        requireRole('admin');
        $settingsModel = new CompanySetting();
        $settings      = $settingsModel->getSettings();
        $this->view('settings.update-settings', ['settings' => $settings]);
    }

    // حفظ إعدادات التحديثات
    public function saveUpdateSettings() {
        requireRole('admin');
        $this->verifyCSRF();

        $settingsModel = new CompanySetting();
        $settings      = $settingsModel->getSettings();

        $data = [
            'id'                => $settings['id'],
            'auto_updates'      => isset($_POST['auto_updates']) ? 1 : 0,
            'auto_check_updates' => isset($_POST['auto_check_updates']) ? 1 : 0,
            'notify_admin_update' => isset($_POST['notify_admin_update']) ? 1 : 0,
            'max_backups'       => max(1, (int)($_POST['max_backups'] ?? 5)),
            'max_update_size'   => max(1, (int)($_POST['max_update_size'] ?? 512)),
        ];

        $settingsModel->updateSettings($data);
        logAudit($this->userId, 'تحديث إعدادات التحديثات', 'company_settings', $settings['id']);
        $_SESSION['success'] = 'تم حفظ إعدادات التحديثات بنجاح';
        redirect('/settings/update-settings');
    }

    // صفحة مسح البيانات
    public function wipe() {
        requireRole('admin');
        $this->view('settings.wipe');
    }

    // تنفيذ مسح البيانات
    public function executeWipe() {
        requireRole('admin');
        $this->verifyCSRF();

        $securityCode = $_POST['security_code'] ?? '';

        $db   = getDB();
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$this->userId]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($securityCode, $admin['password'])) {
            $_SESSION['error'] = 'رمز الأمان غير صحيح (أدخل كلمة مرور المدير)';
            redirect('/settings/wipe');
        }

        $tablesToTruncate = [
            'commission_payments', 'collection_commissions', 'sales_commissions',
            'sales_rep_return', 'sales_rep_sales', 'sales_rep_stock',
            'warehouse_transfers', 'audit_log', 'expense_vouchers', 'expenses',
            'expense_categories', 'installment_payments', 'installments',
            'sales_invoice_items', 'sales_invoices', 'customer_opening_balance',
            'customers', 'purchase_invoice_items', 'purchase_invoices',
            'supplier_opening_balance', 'suppliers', 'product_opening_balance',
            'products', 'units', 'warehouses', 'branches',
            'permission_audit_log', 'user_permissions', 'user_branches',
        ];

        try {
            $db->exec("SET FOREIGN_KEY_CHECKS = 0");
            foreach ($tablesToTruncate as $table) {
                $check = $db->query("SHOW TABLES LIKE '$table'")->fetch();
                if ($check) {
                    $db->exec("DELETE FROM `$table`");
                }
            }
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            logAudit($this->userId, 'مسح جميع بيانات التطبيق');
            $_SESSION['success'] = 'تم مسح جميع البيانات بنجاح (ما عدا المستخدمين والإعدادات)';
        } catch (Exception $e) {
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            $_SESSION['error'] = 'حدث خطأ أثناء المسح: ' . $e->getMessage();
        }
        redirect('/settings/wipe');
    }
}
