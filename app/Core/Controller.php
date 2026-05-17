<?php
/**
 * app/Core/Controller.php - كلاس أساسي لكل الـ Controllers
 */
class Controller {
    protected $userId;
    protected $userRole;
    protected $userBranchId;

    public function __construct() {
        $this->checkSessionIntegrity();
        
        // جلب بيانات المستخدم الحالي إذا كان مسجلاً
        if (isset($_SESSION['user_id'])) {
            $this->userId       = $_SESSION['user_id'];
            $this->userRole     = $_SESSION['user_role'];
            $this->userBranchId = $_SESSION['branch_id'] ?? null;
        }
    }

    protected function checkSessionIntegrity() {
        if (!isset($_SESSION['user_id'])) return;
        
        // التحقق من انتهاء الجلسة (12 ساعة)
        $timeout = 43200; // 12 hours
        if (!isset($_SESSION['login_time']) || (time() - $_SESSION['login_time']) > $timeout) {
            session_destroy();
            redirect('/login?reason=timeout');
            exit;
        }
        
        // تم تعطيل فحص الـ User-Agent مؤقتاً لتجنب الخروج المتكرر في الموبايل
    }

    // عرض view مع بيانات
    protected function view($viewName, $data = []) {
        $viewFile = VIEWS_PATH . str_replace('.', '/', $viewName) . '.php';
        if (!file_exists($viewFile)) {
            die("View '$viewName' not found");
        }
        // استخراج المتغيرات
        extract($data);
        require_once $viewFile;
    }

    // تحميل model
    protected function model($modelName) {
        $modelFile = MODELS_PATH . $modelName . '.php';
        if (!file_exists($modelFile)) {
            die("Model '$modelName' not found");
        }
        require_once $modelFile;
        return new $modelName();
    }

    // توليد CSRF token للـ forms
    public function csrfField() {
        $token = generateCSRFToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    // التحقق من CSRF
    protected function verifyCSRF() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                die("طلب غير مصرح به (CSRF)");
            }
        }
        return true;
    }
}
