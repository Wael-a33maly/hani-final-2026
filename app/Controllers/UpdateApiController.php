<?php
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'VersionMigration.php';
require_once MODELS_PATH . 'CompanySetting.php';
require_once __DIR__ . '/../Helpers/UpdateHelper.php';

class UpdateApiController extends Controller {

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function currentVersion() {
        requireLogin();
        $settingsModel = new CompanySetting();
        $settings = $settingsModel->getSettings();
        $this->jsonResponse([
            'success' => true,
            'version' => $settings['app_version'] ?? '1.0.0',
            'last_update' => $settings['last_update_at'] ?? null,
            'last_check' => $settings['last_check_at'] ?? null,
        ]);
    }

    public function latest() {
        requireLogin();
        requireRole('admin');
        $settingsModel = new CompanySetting();
        $settings = $settingsModel->getSettings();
        $model = new VersionMigration();
        $latest = $model->getLatest();

        $updateUrl = APP_URL . '/updates';
        if (isset($settings['update_server_url']) && !empty($settings['update_server_url'])) {
            $updateUrl = $settings['update_server_url'];
        }

        $ch = curl_init($updateUrl . '/api/updates/latest');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);
        $remoteResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($remoteResponse === false || $httpCode !== 200) {
            $this->jsonResponse([
                'success' => true,
                'current_version' => $settings['app_version'] ?? '1.0.0',
                'latest_version' => $settings['app_version'] ?? '1.0.0',
                'update_available' => false,
                'message' => 'تعذر الاتصال بخادم التحديثات',
            ]);
        }

        $remoteData = json_decode($remoteResponse, true);
        $currentVer = $settings['app_version'] ?? '1.0.0';
        $latestVer = $remoteData['version'] ?? $currentVer;
        $updateAvailable = version_compare($latestVer, $currentVer, '>');

        $this->jsonResponse([
            'success' => true,
            'current_version' => $currentVer,
            'latest_version' => $latestVer,
            'update_available' => $updateAvailable,
            'release_date' => $remoteData['release_date'] ?? null,
            'changelog' => $remoteData['changelog'] ?? null,
            'download_url' => $remoteData['download_url'] ?? null,
            'size' => $remoteData['size'] ?? null,
        ]);
    }

    public function check() {
        requireLogin();
        $settingsModel = new CompanySetting();
        $settings = $settingsModel->getSettings();

        $settingsModel->updateSettings([
            'id' => $settings['id'],
            'last_check_at' => date('Y-m-d H:i:s'),
        ]);

        $model = new VersionMigration();
        $latest = $model->getLatest();

        $currentVer = $settings['app_version'] ?? '1.0.0';
        $latestVer = $latest ? $latest['version'] : $currentVer;
        $updateAvailable = version_compare($latestVer, $currentVer, '>');

        $this->jsonResponse([
            'success' => true,
            'current_version' => $currentVer,
            'latest_version' => $latestVer,
            'update_available' => $updateAvailable,
            'last_check' => date('Y-m-d H:i:s'),
            'migrations_count' => $model->countByStatus('completed'),
            'disk_free_mb' => round(disk_free_space(BASE_PATH) / 1024 / 1024, 1),
        ]);
    }
}
