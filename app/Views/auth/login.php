<?php
$pageTitle = 'تسجيل الدخول';
$reason = $_GET['reason'] ?? '';
$errorMessage = '';
if ($reason === 'security') {
    $errorMessage = 'انتهت جلستك لأسباب أمنية، الرجاء تسجيل الدخول مجدداً';
} elseif ($reason === 'timeout') {
    $errorMessage = 'انتهت مدة الجلسة، الرجاء تسجيل الدخول مجدداً';
}
$logoPath = '';
if (isset($company) && !empty($company['logo_path'])) {
    $logoPath = APP_URL . '/public/' . ltrim($company['logo_path'], '/');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
    <title><?= APP_NAME ?> - تسجيل الدخول</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='4' fill='%232563eb'/><text x='16' y='23' font-size='20' text-anchor='middle' fill='white' font-family='Arial'>H</text></svg>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            position: relative;
            overflow: hidden;
            font-family: 'Segoe UI', Tahoma, sans-serif;
            padding: 16px;
        }
        .bg-grid {
            position: fixed; inset: 0;
            background-image:
                radial-gradient(circle at 20% 50%, rgba(102, 126, 234, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(118, 75, 162, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(37, 99, 235, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 70% 70%, rgba(99, 102, 241, 0.1) 0%, transparent 50%);
            pointer-events: none; z-index: 0;
        }
        .bg-orb {
            position: fixed; border-radius: 50%; pointer-events: none; z-index: 0;
            filter: blur(80px); opacity: 0.4;
        }
        .bg-orb:nth-child(1) {
            width: 500px; height: 500px;
            background: #667eea; top: -150px; left: -100px;
            animation: orbFloat1 25s ease-in-out infinite;
        }
        .bg-orb:nth-child(2) {
            width: 400px; height: 400px;
            background: #764ba2; bottom: -100px; right: -80px;
            animation: orbFloat2 30s ease-in-out infinite;
        }
        .bg-orb:nth-child(3) {
            width: 300px; height: 300px;
            background: #4f46e5; top: 40%; left: 50%;
            animation: orbFloat3 20s ease-in-out infinite;
        }
        @keyframes orbFloat1 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(80px, 60px) scale(1.1); }
            66% { transform: translate(-40px, 30px) scale(0.9); }
        }
        @keyframes orbFloat2 {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(-60px, -40px) scale(1.15); }
            66% { transform: translate(40px, -20px) scale(0.85); }
        }
        @keyframes orbFloat3 {
            0%, 100% { transform: translate(-50%, -50%) scale(1); }
            33% { transform: translate(-30%, -60%) scale(1.2); }
            66% { transform: translate(-60%, -40%) scale(0.8); }
        }
        .login-card {
            position: relative; z-index: 1;
            width: 100%; max-width: 420px;
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px 32px 32px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
            animation: cardEnter 0.6s ease-out;
        }
        @keyframes cardEnter {
            from { opacity: 0; transform: translateY(30px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-card::before {
            content: ''; position: absolute; inset: -1px; z-index: -1;
            border-radius: 24px;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), transparent, rgba(255,255,255,0.1));
            mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            -webkit-mask-composite: xor;
            padding: 1px;
        }
        .logo-wrap {
            width: 72px; height: 72px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.15);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden;
        }
        .logo-wrap img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
        .logo-wrap i { font-size: 28px; color: rgba(255,255,255,0.7); }
        .inp-wrap {
            position: relative; margin-bottom: 18px;
            direction: rtl;
        }
        .inp-wrap .icon {
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%);
            color: rgba(255,255,255,0.45); font-size: 15px;
            transition: color 0.2s; pointer-events: none; z-index: 1;
        }
        .inp-wrap .toggle-pass {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: rgba(255,255,255,0.45); font-size: 15px;
            background: none; border: none; cursor: pointer; padding: 4px;
            transition: color 0.2s;
        }
        .inp-wrap .toggle-pass:hover { color: rgba(255,255,255,0.8); }
        .inp-wrap input {
            width: 100%; direction: rtl; text-align: right;
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 14px;
            padding: 14px 46px 14px 46px;
            color: #fff;
            font-size: 15px;
            outline: none;
            transition: all 0.25s;
        }
        .inp-wrap input:focus {
            background: rgba(255,255,255,0.12);
            border-color: rgba(167, 139, 250, 0.5);
            box-shadow: 0 0 0 4px rgba(167, 139, 250, 0.1);
        }
        .inp-wrap input::placeholder { color: rgba(255,255,255,0.35); }
        .inp-wrap input:-webkit-autofill,
        .inp-wrap input:-webkit-autofill:hover,
        .inp-wrap input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px rgba(48, 43, 99, 0.9) inset !important;
            -webkit-text-fill-color: #fff !important;
            caret-color: #fff;
        }
        .btn-login {
            width: 100%; margin-top: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none; border-radius: 14px;
            padding: 15px; font-size: 16px; font-weight: 700;
            color: #fff; cursor: pointer;
            transition: all 0.3s;
            position: relative; overflow: hidden;
        }
        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
        }
        .btn-login:active:not(:disabled) { transform: translateY(0); }
        .btn-login:disabled { opacity: 0.6; cursor: not-allowed; }
        .btn-login .ripple {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: scale(0); animation: rippleAnim 0.6s ease-out;
        }
        @keyframes rippleAnim { to { transform: scale(4); opacity: 0; } }
        .alert-msg {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px; padding: 12px 16px;
            color: #fca5a5; font-size: 14px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
            animation: shake 0.4s ease;
            direction: rtl;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-6px); }
            40% { transform: translateX(6px); }
            60% { transform: translateX(-4px); }
            80% { transform: translateX(4px); }
        }
        .alert-msg i { font-size: 16px; flex-shrink: 0; }
        .default-hint {
            text-align: center; margin-top: 24px;
            color: rgba(255,255,255,0.25); font-size: 12px;
            direction: rtl;
        }
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="bg-orb"></div>
    <div class="bg-orb"></div>
    <div class="bg-orb"></div>

    <div class="login-card" x-data="loginForm()">
        <div class="text-center mb-6">
            <div class="logo-wrap">
                <?php if ($logoPath): ?>
                    <img src="<?= htmlspecialchars($logoPath) ?>" alt="شعار">
                <?php else: ?>
                    <i class="fas fa-building"></i>
                <?php endif; ?>
            </div>
            <h1 style="font-size: 22px; font-weight: 800; color: #fff; letter-spacing: -0.3px;"><?= APP_NAME ?></h1>
            <p style="color: rgba(255,255,255,0.45); font-size: 13px; margin-top: 4px;">تسجيل الدخول إلى النظام</p>
        </div>

        <?php if ($errorMessage): ?>
            <div class="alert-msg" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <i class="fas fa-shield-alt"></i>
                <span><?= $errorMessage ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-msg" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= APP_URL ?>/login" @submit.prevent="submitForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <div class="inp-wrap">
                <i class="fas fa-user icon"></i>
                <input type="text" name="username" x-model="username" required placeholder="اسم المستخدم" autocomplete="username">
            </div>

            <div class="inp-wrap">
                <i class="fas fa-lock icon"></i>
                <input :type="showPassword ? 'text' : 'password'" name="password" x-model="password" required placeholder="كلمة المرور" autocomplete="current-password">
                <button type="button" @click="togglePassword" class="toggle-pass">
                    <i class="fas" :class="showPassword ? 'fa-eye-slash' : 'fa-eye'"></i>
                </button>
            </div>

            <button type="submit" :disabled="loading" class="btn-login" @click="addRipple">
                <span x-show="!loading">تسجيل الدخول</span>
                <span x-show="loading"><i class="fas fa-spinner fa-spin ml-1"></i> جاري...</span>
            </button>
        </form>

        <div class="default-hint">admin / password</div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script>
        function loginForm() {
            return {
                username: '',
                password: '',
                showPassword: false,
                loading: false,
                togglePassword() {
                    this.showPassword = !this.showPassword;
                },
                addRipple(e) {
                    if (this.loading) return;
                    const btn = e.currentTarget;
                    const rect = btn.getBoundingClientRect();
                    const ripple = document.createElement('span');
                    ripple.className = 'ripple';
                    const size = Math.max(rect.width, rect.height);
                    ripple.style.width = ripple.style.height = size + 'px';
                    ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
                    ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
                    btn.appendChild(ripple);
                    setTimeout(() => ripple.remove(), 600);
                },
                submitForm() {
                    this.loading = true;
                    setTimeout(() => this.$el.submit(), 200);
                }
            }
        }
    </script>
</body>
</html>
