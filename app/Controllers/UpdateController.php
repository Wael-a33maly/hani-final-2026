<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'VersionMigration.php';
require_once MODELS_PATH . 'CompanySetting.php';
require_once __DIR__ . '/../Helpers/UpdateHelper.php';

class UpdateController extends Controller {

    private function requireAdmin() {
        requireRole('admin');
    }

    public function index() {
        $this->requireAdmin();
        try {
            $model = new VersionMigration();
            $settingsModel = new CompanySetting();
            $settings = $settingsModel->getSettings();
            $keep = max(3, (int)($settings['max_backups'] ?? 5));
            $model->cleanupOldRecords($keep);
            $migrations = $model->all();
        } catch (Exception $e) {
            $migrations = [];
        }
        $disk = UpdateHelper::getDiskSpaceStatus();
        $this->view('updates.index', [
            'migrations' => $migrations,
            'settings' => $settings ?? [],
            'disk' => $disk,
        ]);
    }

    public function uploadForm() {
        $this->requireAdmin();
        $settingsModel = new CompanySetting();
        $settings = $settingsModel->getSettings();
        UpdateHelper::ensureDirs();
        $this->view('updates.form', ['settings' => $settings]);
    }

    public function upload() {
        $this->requireAdmin();
        $this->verifyCSRF();

        if (!isset($_FILES['update_file']) || $_FILES['update_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'يرجى اختيار ملف تحديث صالح';
            redirect('/updates/form');
        }

        $settingsModel = new CompanySetting();
        $settings = $settingsModel->getSettings();
        $maxSizeMB = (int)($settings['max_update_size'] ?? 512);

        $disk = UpdateHelper::getDiskSpaceStatus();
        if ($disk['is_low']) {
            $_SESSION['error'] = 'المساحة التخزينية غير كافية (' . $disk['free_mb'] . ' MB متبقي). يرجى حذف النسخ القديمة والمحاولة لاحقاً.';
            redirect('/updates/form');
        }

        $tmpName = $_FILES['update_file']['tmp_name'];
        $origName = $_FILES['update_file']['name'];

        $validation = UpdateHelper::validateZip($tmpName, $maxSizeMB);
        if (!$validation['valid']) {
            $_SESSION['error'] = $validation['error'];
            @unlink($tmpName);
            redirect('/updates/form');
        }

        $destDir = UPDATES_PATH . 'pending_' . time();
        $extractResult = UpdateHelper::extractZip($tmpName, $destDir);
        if (!$extractResult['success']) {
            $_SESSION['error'] = $extractResult['error'];
            @unlink($tmpName);
            redirect('/updates/form');
        }

        $skipMsg = '';
        if (!empty($validation['skipped']) && $validation['skipped'] > 0) {
            $skipMsg = ' (تم تخطي ' . $validation['skipped'] . ' ملف محظور)';
        }
        $extractWarn = '';
        if (!empty($extractResult['warning'])) {
            $extractWarn = ' تنبيه: ' . $extractResult['warning'];
        }

        $version = date('YmdHis');
        $model = new VersionMigration();
        $migrationId = $model->insert([
            'version' => $version,
            'zip_file' => $origName,
            'zip_hash' => md5_file($tmpName),
            'status' => 'pending',
            'executed_by' => $this->userId,
        ]);

        $zipDest = UPDATES_PATH . $migrationId . '_' . $origName;
        rename($tmpName, $zipDest);

        $_SESSION['pending_update_id'] = $migrationId;
        $_SESSION['pending_extract_path'] = $destDir;
        $_SESSION['pending_zip_path'] = $zipDest;
        $_SESSION['success'] = 'تم رفع ملف التحديث بنجاح. يمكنك معاينة التغييرات قبل التنفيذ.' . $skipMsg . $extractWarn;
        logAudit($this->userId, 'رفع ملف تحديث', 'version_migrations', $migrationId, null, "الإصدار: $version, الملف: $origName");
        redirect('/updates/preview');
    }

    public function preview() {
        $this->requireAdmin();
        $migrationId = $_SESSION['pending_update_id'] ?? null;
        $extractPath = $_SESSION['pending_extract_path'] ?? null;

        if (!$migrationId || !$extractPath || !is_dir($extractPath)) {
            $_SESSION['error'] = 'لا يوجد تحديث معلق للمعاينة. يرجى رفع ملف التحديث أولاً.';
            redirect('/updates/form');
        }

        $model = new VersionMigration();
        $migration = $model->find($migrationId);
        if (!$migration) {
            $_SESSION['error'] = 'سجل التحديث غير موجود';
            redirect('/updates/form');
        }

        $files = [];
        $sqlFiles = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $relative = substr($file->getRealPath(), strlen($extractPath) + 1);
            $ext = strtolower($file->getExtension());
            if ($ext === 'sql') {
                $sqlFiles[] = $relative;
            } else {
                $files[] = $relative;
            }
        }

        $this->view('updates.preview', [
            'migration' => $migration,
            'files' => $files,
            'sqlFiles' => $sqlFiles,
            'extractPath' => $extractPath,
        ]);
    }

    public function execute() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $_SESSION['error'] = 'لا يمكن الوصول إلى هذا الرابط مباشرة. يرجى رفع ملف التحديث أولاً.';
            redirect('/updates/form');
        }

        $this->verifyCSRF();

        $migrationId = $_SESSION['pending_update_id'] ?? null;
        $extractPath = $_SESSION['pending_extract_path'] ?? null;
        $zipPath = $_SESSION['pending_zip_path'] ?? null;

        if (!$migrationId || !$extractPath || !is_dir($extractPath)) {
            $_SESSION['error'] = 'لا يوجد تحديث معلق للتنفيذ';
            redirect('/updates/form');
        }

        $model = new VersionMigration();
        $migration = $model->find($migrationId);
        if (!$migration || $migration['status'] !== 'pending') {
            $_SESSION['error'] = 'حالة التحديث غير صالحة للتنفيذ';
            redirect('/updates');
        }

        $db = getDB();
        try {
            $model->updateStatus($migrationId, 'running');

            $backupFile = UpdateHelper::createBackup($migration['version']);
            $filesBackup = UpdateHelper::filesystemBackup();
            $backupPath = '';
            if ($backupFile) $backupPath = $backupFile;
            if ($filesBackup) $backupPath = ($backupPath ? $backupPath . '|' : '') . $filesBackup;
            if ($backupPath) {
                $model->setBackupPath($migrationId, $backupPath);
            }

            $sqlWarnings = [];
            $sqlCount = 0;
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (strtolower($file->getExtension()) !== 'sql') continue;
                $sqlCount++;
                $result = UpdateHelper::executeSqlMigration($file->getRealPath());
                if (!$result['success']) {
                    $errors = implode(' | ', array_slice($result['errors'], 0, 5));
                    throw new Exception("فشل تنفيذ SQL ({$file->getFilename()}): $errors");
                }
                if (!empty($result['errors'])) {
                    $sqlWarnings[$file->getFilename()] = $result['errors'];
                    foreach ($result['errors'] as $err) {
                        error_log("SQL Warning in {$file->getFilename()}: $err");
                    }
                }
            }

            $copied = 0;
            $failedFiles = [];
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                if (strtolower($file->getExtension()) === 'sql') continue;
                $relative = substr($file->getRealPath(), strlen($extractPath) + 1);
                $normalized = str_replace('\\', '/', $relative);
                $check = UpdateHelper::isFileAllowed($normalized);
                if (!$check['allowed']) continue;
                $destFile = BASE_PATH . '/' . $normalized;
                $destDir = dirname($destFile);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                if (copy($file->getRealPath(), $destFile)) {
                    $copied++;
                } else {
                    $failedFiles[] = $normalized;
                }
            }

            if ($failedFiles) {
                $errMsg = 'فشل نسخ ' . count($failedFiles) . ' ملف: ';
                $errMsg .= implode(', ', array_slice($failedFiles, 0, 10));
                if (count($failedFiles) > 10) $errMsg .= '...';
                throw new Exception($errMsg);
            }

            $settingsModel = new CompanySetting();
            $settings = $settingsModel->getSettings();
            $settingsModel->updateSettings([
                'id' => $settings['id'],
                'app_version' => $migration['version'],
                'last_update_at' => date('Y-m-d H:i:s'),
            ]);

            $model->updateStatus($migrationId, 'completed');
            UpdateHelper::deleteDirectory($extractPath);
            if ($zipPath && file_exists($zipPath)) {
                @unlink($zipPath);
            }

            $settingsModel = new CompanySetting();
            $settings = $settingsModel->getSettings();
            $keep = max(3, (int)($settings['max_backups'] ?? 5));
            $cleaned = $model->cleanupOldRecords($keep);

            unset($_SESSION['pending_update_id'], $_SESSION['pending_extract_path'], $_SESSION['pending_zip_path']);
            logAudit($this->userId, 'تنفيذ تحديث', 'version_migrations', $migrationId, null, "الإصدار: {$migration['version']}");

            $msg = "تم تنفيذ التحديث بنجاح! ($copied ملف)";
            if ($sqlCount > 0) $msg .= " - تم تنفيذ $sqlCount ملف SQL";
            if ($sqlWarnings) $msg .= ' مع وجود تحذيرات في SQL';
            if ($cleaned) $msg .= ' - تم تنظيف ' . count($cleaned) . ' سجل قديم';
            $_SESSION['success'] = $msg;

        } catch (Exception $e) {
            $model->updateStatus($migrationId, 'failed', $e->getMessage());
            logAudit($this->userId, 'فشل تحديث', 'version_migrations', $migrationId, null, "خطأ: " . $e->getMessage());
            $_SESSION['error'] = 'فشل التحديث: ' . $e->getMessage() . '. تم تسجيل الخطأ. يرجى مراجعة السجلات أو استعادة النسخة الاحتياطية يدوياً.';
        }
        redirect('/updates');
    }

    public function rollback($id) {
        $this->requireAdmin();
        $this->verifyCSRF();

        $model = new VersionMigration();
        $migration = $model->find($id);
        if (!$migration) {
            $_SESSION['error'] = 'سجل التحديث غير موجود';
            redirect('/updates');
        }
        if (empty($migration['backup_path'])) {
            $_SESSION['error'] = 'لا توجد نسخة احتياطية لهذا التحديث للرجوع إليها';
            redirect('/updates');
        }

        $paths = explode('|', $migration['backup_path']);
        $sqlBackup = null;
        $filesBackup = null;
        foreach ($paths as $p) {
            $p = trim($p);
            if (strpos($p, '.sql') !== false) {
                $sqlBackup = $p;
            } elseif (strpos($p, '.zip') !== false) {
                $filesBackup = $p;
            }
        }

        $db = getDB();
        try {
            $model->updateStatus($id, 'running');

            if ($sqlBackup && file_exists($sqlBackup)) {
                $result = UpdateHelper::restoreBackup($sqlBackup);
                if (!$result['success']) {
                    throw new Exception("فشل استعادة قاعدة البيانات: " . ($result['message'] ?? ''));
                }
            } else {
                throw new Exception("ملف النسخة الاحتياطية لقاعدة البيانات غير موجود");
            }

            if ($filesBackup && file_exists($filesBackup)) {
                $result = UpdateHelper::restoreFilesystemBackup($filesBackup);
                if (!$result['success']) {
                    throw new Exception("فشل استعادة الملفات: " . ($result['error'] ?? ''));
                }
            }

            $model->updateStatus($id, 'rolled_back');
            logAudit($this->userId, 'رجوع عن تحديث', 'version_migrations', $id, null, "الإصدار: {$migration['version']}");
            $_SESSION['success'] = 'تم الرجوع عن التحديث بنجاح واستعادة النسخة السابقة.';

        } catch (Exception $e) {
            $model->updateStatus($id, 'failed', $e->getMessage());
            logAudit($this->userId, 'فشل الرجوع عن تحديث', 'version_migrations', $id, null, "خطأ: " . $e->getMessage());
            $_SESSION['error'] = 'فشل الرجوع عن التحديث: ' . $e->getMessage();
        }
        redirect('/updates');
    }

    public function deleteMigration($id) {
        $this->requireAdmin();
        $this->verifyCSRF();

        $model = new VersionMigration();
        $migration = $model->find($id);
        if (!$migration) {
            $_SESSION['error'] = 'سجل التحديث غير موجود';
            redirect('/updates');
        }

        if ($migration['status'] === 'running') {
            $model->updateStatus($id, 'failed', 'تم إلغاء التحديث يدوياً');
        }

        $oldData = json_encode($migration);
        $model->deleteWithFiles($id);
        logAudit($this->userId, 'حذف سجل تحديث', 'version_migrations', $id, $oldData, null);
        $_SESSION['success'] = 'تم حذف سجل التحديث';
        redirect('/updates');
    }
}
