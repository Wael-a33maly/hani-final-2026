<?php
if (!defined('SENSITIVE_BLACKLIST')) define('SENSITIVE_BLACKLIST', 'config.php,.env,.htaccess,composer.json,composer.lock,package.json');
if (!defined('REDLIST_DIRS')) define('REDLIST_DIRS', 'storage,public/uploads,public/libs');

class UpdateHelper {

    public static function ensureDirs() {
        foreach ([STORAGE_PATH, BACKUPS_PATH, UPDATES_PATH] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        $htaccess = STORAGE_PATH . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Deny from all\n");
        }
    }

    public static function isFileAllowed($normalized) {
        $basename = basename($normalized);
        if (in_array($basename, explode(',', SENSITIVE_BLACKLIST))) {
            return ['allowed' => false, 'reason' => 'القائمة السوداء'];
        }
        foreach (explode(',', REDLIST_DIRS) as $red) {
            if (strpos($normalized, trim($red) . '/') === 0) {
                return ['allowed' => false, 'reason' => 'القائمة الحمراء'];
            }
        }
        if (strpos($normalized, '..') !== false) {
            return ['allowed' => false, 'reason' => 'مسار غير آمن'];
        }
        return ['allowed' => true, 'reason' => ''];
    }

    public static function validateZip($filePath, $maxSizeMB = 512) {
        $maxBytes = $maxSizeMB * 1024 * 1024;
        if (!file_exists($filePath)) {
            return ['valid' => false, 'error' => 'الملف غير موجود'];
        }
        $size = filesize($filePath);
        if ($size <= 0) {
            return ['valid' => false, 'error' => 'الملف فارغ'];
        }
        if ($size > $maxBytes) {
            return ['valid' => false, 'error' => "حجم الملف يتجاوز الحد المسموح ($maxSizeMB MB)"];
        }
        $zip = new ZipArchive();
        $res = $zip->open($filePath);
        if ($res !== true) {
            return ['valid' => false, 'error' => 'ملف ZIP تالف أو غير صالح'];
        }
        $numFiles = $zip->numFiles;
        if ($numFiles === 0) {
            $zip->close();
            return ['valid' => false, 'error' => 'ملف ZIP فارغ'];
        }
        $allowed = 0;
        $skipped = 0;
        for ($i = 0; $i < $numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $normalized = str_replace('\\', '/', $name);
            if (substr($normalized, -1) === '/') continue;
            $check = self::isFileAllowed($normalized);
            if ($check['allowed']) {
                $allowed++;
            } else {
                $skipped++;
            }
        }
        $zip->close();
        if ($allowed === 0) {
            return ['valid' => false, 'error' => 'جميع الملفات في الحزمة محظورة (لا يوجد ملفات صالحة للتحديث)'];
        }
        return ['valid' => true, 'files' => $allowed, 'skipped' => $skipped];
    }

    public static function extractZip($filePath, $destDir) {
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return ['success' => false, 'error' => 'فشل فتح ملف ZIP'];
        }
        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }
        $extracted = 0;
        $skipped = 0;
        $failedFiles = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $normalized = str_replace('\\', '/', $name);
            if (substr($normalized, -1) === '/') continue;
            $check = self::isFileAllowed($normalized);
            if (!$check['allowed']) {
                $skipped++;
                continue;
            }
            $destFile = $destDir . '/' . $normalized;
            $destParent = dirname($destFile);
            if (!is_dir($destParent)) {
                mkdir($destParent, 0755, true);
            }
            $contents = $zip->getFromIndex($i);
            if ($contents === false) {
                $failedFiles[] = $normalized;
                continue;
            }
            file_put_contents($destFile, $contents);
            $extracted++;
        }
        $zip->close();
        $result = ['success' => true, 'extracted' => $extracted, 'skipped' => $skipped];
        if ($failedFiles) {
            $result['failed'] = $failedFiles;
            $result['warning'] = 'فشل استخراج ' . count($failedFiles) . ' من ' . (count($failedFiles) + $extracted) . ' ملف';
        }
        if ($extracted === 0 && $failedFiles) {
            $result['success'] = false;
            $result['error'] = 'فشل استخراج جميع الملفات من الحزمة';
        }
        return $result;
    }

    public static function createBackup($version) {
        self::ensureDirs();
        $db = getDB();
        $sql = exportDatabaseToSQL();
        $filename = 'backup_v' . $version . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = BACKUPS_PATH . $filename;
        file_put_contents($filepath, $sql);
        $settingsModel = new CompanySetting();
        $settings = $settingsModel->getSettings();
        $maxBackups = (int)($settings['max_backups'] ?? 5);
        $backups = glob(BACKUPS_PATH . 'backup_*.sql');
        usort($backups, function ($a, $b) {
            return filemtime($a) - filemtime($b);
        });
        while (count($backups) > $maxBackups) {
            $oldest = array_shift($backups);
            @unlink($oldest);
        }
        return $filepath;
    }

    public static function restoreBackup($backupPath) {
        if (!file_exists($backupPath)) {
            return ['success' => false, 'message' => 'ملف النسخة الاحتياطية غير موجود'];
        }
        $db = getDB();
        $handle = fopen($backupPath, 'r');
        if (!$handle) {
            return ['success' => false, 'message' => 'تعذر فتح ملف النسخة الاحتياطية'];
        }
        $query = '';
        $count = 0;
        $errors = [];
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        if (!$db->inTransaction()) {
            $db->beginTransaction();
        }
        try {
            while (!feof($handle)) {
                $line = fgets($handle);
                if ($line === false) break;
                $line = trim($line);
                if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) continue;
                $query .= $line;
                if (substr(trim($query), -1) === ';') {
                    $skip = false;
                    $trimmed = strtoupper(trim($query));
                    if (strpos($trimmed, 'SET SQL_MODE') === 0 || strpos($trimmed, 'SET TIME_ZONE') === 0 || strpos($trimmed, 'START TRANSACTION') === 0 || strpos($trimmed, 'COMMIT') === 0) {
                        $skip = true;
                    }
                    if (!$skip) {
                        try {
                            $db->exec($query);
                            $count++;
                        } catch (PDOException $e) {
                            $errors[] = substr($query, 0, 100) . ' Error: ' . $e->getMessage();
                        }
                    }
                    $query = '';
                }
            }
            if ($db->inTransaction()) {
                $db->commit();
            }
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            fclose($handle);
            return ['success' => true, 'count' => $count, 'errors' => $errors];
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            fclose($handle);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public static function executeSqlMigration($filePath) {
        if (!file_exists($filePath)) {
            return ['success' => false, 'message' => 'ملف SQL غير موجود'];
        }
        $db = getDB();
        $sql = file_get_contents($filePath);
        if ($sql === false || trim($sql) === '') {
            return ['success' => false, 'message' => 'ملف SQL فارغ'];
        }
        $statements = explode(';', $sql);
        $count = 0;
        $errors = [];
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if (empty($stmt)) continue;
            // نزيل سطور التعليقات (--) و (/* */)
            $lines = explode("\n", $stmt);
            $cleanLines = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, '--') === 0 || strpos($line, '/*') === 0) continue;
                $cleanLines[] = $line;
            }
            $stmt = trim(implode("\n", $cleanLines));
            if (empty($stmt)) continue;
            try {
                $db->exec($stmt);
                $count++;
            } catch (PDOException $e) {
                $code = $e->getCode();
                $msg = $e->getMessage();
                $driverCode = isset($e->errorInfo[1]) ? (int)$e->errorInfo[1] : null;
                if ($code == '42S21' || $code == '42S01'
                    || $driverCode === 1061
                    || $driverCode === 1553
                    || stripos($msg, 'Duplicate column') !== false
                    || stripos($msg, 'already exists') !== false
                    || stripos($msg, 'Duplicate key name') !== false
                    || stripos($msg, 'errno: 121') !== false
                    || stripos($msg, 'Duplicate key on write or update') !== false
                    || stripos($msg, 'Can\'t create table') !== false
                    || stripos($msg, 'Cannot drop index') !== false
                ) {
                    $count++;
                    continue;
                }
                $errors[] = substr($stmt, 0, 150) . ' | Error: ' . $e->getMessage();
            }
        }
        $result = ['success' => empty($errors), 'count' => $count, 'errors' => $errors];
        if (!$result['success']) {
            $result['message'] = 'فشل تنفيذ ' . count($errors) . ' استعلامات';
        }
        return $result;
    }

    public static function filesystemBackup() {
        self::ensureDirs();
        $backupFile = BACKUPS_PATH . 'files_v' . date('Y-m-d_H-i-s') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE) !== true) {
            return null;
        }
        $basePath = BASE_PATH;
        $excludeDirs = [STORAGE_PATH, $basePath . '/storage'];
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            $filePath = $file->getRealPath();
            foreach ($excludeDirs as $exclude) {
                $exclude = rtrim(str_replace('\\', '/', $exclude), '/');
                if (strpos(str_replace('\\', '/', $filePath), $exclude) === 0) {
                    continue 2;
                }
            }
            $relativePath = substr($filePath, strlen($basePath) + 1);
            $zip->addFile($filePath, $relativePath);
        }
        $zip->close();
        return $backupFile;
    }

    public static function restoreFilesystemBackup($backupFile) {
        if (!file_exists($backupFile)) {
            return ['success' => false, 'error' => 'ملف النسخة الاحتياطية غير موجود'];
        }
        $zip = new ZipArchive();
        if ($zip->open($backupFile) !== true) {
            return ['success' => false, 'error' => 'فشل فتح ملف ZIP'];
        }
        $zip->extractTo(BASE_PATH);
        $zip->close();
        return ['success' => true];
    }

    public static function getDiskSpaceStatus() {
        $free = disk_free_space(BASE_PATH);
        $total = disk_total_space(BASE_PATH);
        $usedPercent = round((1 - $free / $total) * 100, 1);
        $freeMB = round($free / 1024 / 1024, 1);
        return [
            'free_mb' => $freeMB,
            'total_mb' => round($total / 1024 / 1024, 1),
            'used_percent' => $usedPercent,
            'is_low' => $freeMB < 100,
        ];
    }

    public static function deleteDirectory($dir) {
        if (!is_dir($dir)) return;
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getRealPath()) : @unlink($item->getRealPath());
        }
        @rmdir($dir);
    }
}
