<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <meta name="theme-color" content="#2563eb">
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <title><?= APP_NAME ?> | <?= $pageTitle ?? 'الرئيسية' ?></title>
    
    <?php
    $faviconUrl = "data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'><rect width='32' height='32' rx='4' fill='%232563eb'/><text x='16' y='23' font-size='20' text-anchor='middle' fill='white' font-family='Arial'>H</text></svg>";
    try {
        $setModel = new CompanySetting();
        $setData = $setModel->getSettings();
        if (!empty($setData['logo_path'])) {
            $localFile = PUBLIC_PATH . ltrim($setData['logo_path'], '/');
            if (file_exists($localFile)) {
                $faviconUrl = rtrim(APP_URL, '/') . '/public/' . ltrim($setData['logo_path'], '/');
            }
        }
    } catch (\Throwable $e) {}
    ?>
    <link rel="icon" type="image/svg+xml" href="<?= $faviconUrl ?>">
    <link rel="manifest" href="<?= rtrim(APP_URL, '/') ?>/public/manifest.json">
    <link rel="apple-touch-icon" href="<?= rtrim(APP_URL, '/') ?>/public/icons/icon-192.svg">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- ملفات التصميم المحلية -->
    <link rel="stylesheet" type="text/css" href="<?= rtrim(APP_URL, '/') ?>/public/css/app.css?v=<?= time() ?>">
    <link rel="stylesheet" type="text/css" href="<?= rtrim(APP_URL, '/') ?>/public/css/mobile.css?v=<?= time() ?>">
    <link rel="stylesheet" type="text/css" href="<?= rtrim(APP_URL, '/') ?>/public/css/print.css?v=<?= time() ?>" media="print">
    
    <!-- اختبار التحميل (للتشخيص) -->
    <script>
        window.addEventListener('load', function() {
            const cssLoaded = Array.from(document.styleSheets).some(s => s.href && s.href.includes('app.css'));
            if (!cssLoaded) console.warn('تحذير: لم يتم تحميل ملف app.css بنجاح!');
        });
    </script>
    
    <!-- تخصيص Tailwind للـ RTL -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Tahoma', 'Arial', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
        @media print {
            .no-print, nav, aside, header, footer, .bottom-nav, .bottom-sheet { display: none !important; }
            main { margin: 0 !important; padding: 0 !important; width: 100% !important; overflow: visible !important; }
            body { background: white !important; }
            .h-screen { height: auto !important; overflow: visible !important; }
            .flex { display: block !important; }
        }
    </style>
</head>
<body>
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar (للكمبيوتر اللوحي والديسكتوب) -->
        <div class="no-print h-full">
            <?php include __DIR__ . '/sidebar.php'; ?>
        </div>
        
        <!-- المنطقة اليمنى (الهيدر + المحتوى) -->
        <div class="flex-1 flex flex-col min-w-0 bg-gray-100 overflow-hidden">
            <!-- Header -->
            <div class="no-print">
                <?php include __DIR__ . '/header.php'; ?>
            </div>
            
            <!-- Content Area -->
            <main class="flex-1 overflow-y-auto overflow-x-hidden p-4 md:p-6 pb-20 md:pb-6 custom-scrollbar">
                <!-- بار التنقل السفلي للموبايل (يظهر فقط على الشاشات الصغيرة) -->
                <div class="md:hidden mb-4 no-print">
                    <!-- يمكن وضع تنبيهات أو معلومات سريعة هنا -->
                </div>

                <div class="page-transition">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="mb-4 p-4 bg-green-50 border-r-4 border-green-500 text-green-700 rounded-lg shadow-sm no-print">
                            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="mb-4 p-4 bg-red-50 border-r-4 border-red-500 text-red-700 rounded-lg shadow-sm no-print">
                            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?= $content ?? '' ?>
                </div>
            </main>
        </div>

        <!-- بار التنقل السفلي للموبايل (fixed) -->
        <div class="bottom-nav no-print md:hidden fixed bottom-0 left-0 right-0 bg-white border-t z-50 flex justify-around items-center h-16 shadow-lg">
            <a href="<?= APP_URL ?>/dashboard" class="bottom-nav-item <?= strpos($_SERVER['REQUEST_URI'], 'dashboard') !== false ? 'active' : '' ?> flex flex-col items-center text-xs gap-1">
                <i class="fas fa-tachometer-alt text-lg"></i><span>الرئيسية</span>
            </a>
            <a href="<?= APP_URL ?>/sales/create" class="bottom-nav-item flex flex-col items-center text-xs gap-1">
                <i class="fas fa-cash-register text-lg"></i><span>مبيعات</span>
            </a>
            <a href="<?= APP_URL ?>/installments" class="bottom-nav-item flex flex-col items-center text-xs gap-1">
                <i class="fas fa-calendar-alt text-lg"></i><span>أقساط</span>
            </a>
            <a href="#" id="moreMenuBtn" class="bottom-nav-item flex flex-col items-center text-xs gap-1">
                <i class="fas fa-bars text-lg"></i><span>المزيد</span>
            </a>
        </div>
    </div>

    <!-- ملفات JavaScript -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="<?= rtrim(APP_URL, '/') ?>/public/js/toast.js?v=<?= time() ?>"></script>
    <script src="<?= rtrim(APP_URL, '/') ?>/public/js/app.js?v=<?= time() ?>"></script>
    
    <script>
        // زر "المزيد" يفتح السايدبار الكامل على الموبايل
        const moreBtn = document.getElementById('moreMenuBtn');
        if (moreBtn) {
            moreBtn.addEventListener('click', (e) => {
                e.preventDefault();
                window.dispatchEvent(new CustomEvent('toggle-mobile-sidebar'));
            });
        }
    </script>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/public/sw.js');
            });
        }
    </script>
</body>
</html>
