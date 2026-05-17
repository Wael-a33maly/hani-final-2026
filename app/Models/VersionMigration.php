<?php
require_once __DIR__ . '/../Core/Model.php';

class VersionMigration extends Model {
    protected $table = 'version_migrations';

    public function getLatest() {
        $stmt = $this->db->query("SELECT * FROM version_migrations ORDER BY id DESC LIMIT 1");
        return $stmt->fetch();
    }

    public function getByStatus($status) {
        $stmt = $this->db->prepare("SELECT * FROM version_migrations WHERE status = ? ORDER BY id DESC");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    public function getCompleted() {
        return $this->getByStatus('completed');
    }

    public function updateStatus($id, $status, $errorMessage = null) {
        $sql = "UPDATE version_migrations SET status = ?";
        $params = [$status];
        if ($status === 'running') {
            $sql .= ", started_at = NOW()";
        } elseif (in_array($status, ['completed', 'failed', 'rolled_back'])) {
            $sql .= ", completed_at = NOW()";
        }
        if ($errorMessage !== null) {
            $sql .= ", error_message = ?";
            $params[] = $errorMessage;
        }
        $sql .= " WHERE id = ?";
        $params[] = $id;
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function setBackupPath($id, $path) {
        $stmt = $this->db->prepare("UPDATE version_migrations SET backup_path = ? WHERE id = ?");
        return $stmt->execute([$path, $id]);
    }

    public function countByStatus($status) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as cnt FROM version_migrations WHERE status = ?");
        $stmt->execute([$status]);
        return (int)$stmt->fetch()['cnt'];
    }

    /**
     * حذف سجل تحديث مع حذف ملفاته المرتبطة (نسخ احتياطية، ZIP)
     */
    public function deleteWithFiles($id) {
        $record = $this->find($id);
        if (!$record) return false;

        if (!empty($record['backup_path'])) {
            $paths = explode('|', $record['backup_path']);
            foreach ($paths as $p) {
                $p = trim($p);
                if ($p && file_exists($p)) @unlink($p);
            }
        }

        $pattern = UPDATES_PATH . $id . '_*';
        foreach (glob($pattern) as $f) {
            @unlink($f);
        }

        return $this->delete($id);
    }

    /**
     * التنظيف التلقائي: يحتفظ بآخر N سجل يمكن استعادتهم، ويحذف الأقدم
     */
    public function cleanupOldRecords($keep = 3) {
        $stmt = $this->db->prepare("
            SELECT id, backup_path, version FROM version_migrations
            WHERE status IN ('completed', 'rolled_back')
              AND backup_path IS NOT NULL AND backup_path != ''
            ORDER BY id DESC
        ");
        $stmt->execute();
        $records = $stmt->fetchAll();

        $deleted = [];
        $toDelete = array_slice($records, $keep);
        foreach ($toDelete as $rec) {
            $this->deleteWithFiles($rec['id']);
            $deleted[] = $rec['id'];
        }

        foreach ($deleted as $id) {
            error_log("Auto-cleanup: deleted version_migration id=$id");
        }

        return $deleted;
    }
}
