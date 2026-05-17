<?php
/**
 * app/Controllers/AuthController.php
 */
require_once __DIR__ . '/../Core/Controller.php';
require_once MODELS_PATH . 'User.php';

class AuthController extends Controller {

    public function loginForm() {
        // إذا كان مسجلاً يحول للداشبورد
        if (isLoggedIn()) {
            redirect('/dashboard');
        }
        $this->view('auth.login');
    }

    public function login() {
        $this->verifyCSRF();
        
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        // التحقق من عدد المحاولات الفاشلة
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([$ip]);
        $attempts = $stmt->fetch()['attempts'];
        
        if ($attempts >= LOGIN_ATTEMPTS_LIMIT) {
            $_SESSION['error'] = 'تم تجاوز عدد المحاولات المسموح بها. يرجى المحاولة بعد 15 دقيقة.';
            redirect('/login');
        }
        
        if (empty($username) || empty($password)) {
            $this->logFailedAttempt($ip, $username);
            $_SESSION['error'] = 'يرجى إدخال اسم المستخدم وكلمة المرور';
            redirect('/login');
        }
        
        $userModel = new User();
        $user = $userModel->findByUsername($username);
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active'] != 1) {
                $this->logFailedAttempt($ip, $username);
                $_SESSION['error'] = 'الحساب غير نشط، يرجى التواصل مع المدير';
                redirect('/login');
            }
            
            // تسجيل دخول ناجح: مسح محاولات هذا الـ IP
            $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?")->execute([$ip]);
            
            // حماية من Session Fixation
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['branch_id'] = $user['branch_id'];
            $_SESSION['user_role_id'] = $user['role_id'];
            if (class_exists('PermissionHelper')) PermissionHelper::clearCache($user['id']);
            
            // تخزين بيانات إضافية للتحقق
            $_SESSION['user_agent'] = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
            $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
            $_SESSION['login_time'] = time();
            
            $userModel->updateLastLogin($user['id']);
            logAudit($user['id'], 'تسجيل دخول');
            
            redirect('/dashboard');
        } else {
            $this->logFailedAttempt($ip, $username);
            $_SESSION['error'] = 'اسم المستخدم أو كلمة المرور غير صحيحة';
            redirect('/login');
        }
    }

    private function logFailedAttempt($ip, $username) {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
        $stmt->execute([$ip, $username]);
    }

    public function logout() {
        if (isLoggedIn()) {
            if (class_exists('PermissionHelper')) PermissionHelper::clearCache($_SESSION['user_id']);
            logAudit($_SESSION['user_id'], 'تسجيل خروج');
        }
        session_regenerate_id(true);
        session_destroy();
        redirect('/login');
    }
}
