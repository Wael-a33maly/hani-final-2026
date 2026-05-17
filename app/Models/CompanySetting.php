<?php
/**
 * app/Models/CompanySetting.php
 */
require_once __DIR__ . '/../Core/Model.php';

class CompanySetting extends Model {
    protected $table = 'company_settings';

    // جلب الإعدادات (يوجد سجل واحد فقط)
    public function getSettings() {
        $stmt = $this->db->query("SELECT * FROM company_settings LIMIT 1");
        return $stmt->fetch();
    }

    // تحديث الإعدادات
    public function updateSettings($data) {
        $id = $data['id'] ?? 1;
        $fields = '';
        $params = [];
        foreach ($data as $key => $value) {
            if ($key === 'id') continue;
            $fields      .= "$key = :$key, ";
            $params[$key] = $value;
        }
        $fields      = rtrim($fields, ', ');
        $params['id'] = $id;
        $stmt = $this->db->prepare(
            "UPDATE company_settings SET $fields WHERE id = :id"
        );
        return $stmt->execute($params);
    }
}
