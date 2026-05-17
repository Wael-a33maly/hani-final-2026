<?php
// افتراض وجود متغيرات من الـ Controller
$pageTitle = $pageTitle ?? 'الرئيسية';
$breadcrumb = $breadcrumb ?? [];
$userName = $_SESSION['user_name'] ?? 'مستخدم';
$userInitial = mb_substr($userName, 0, 1, 'UTF-8');
// جلب عدد الإشعارات (الأقساط المتأخرة مثلاً) من قاعدة البيانات أو من الـ Session
$notificationsCount = $_SESSION['overdue_installments'] ?? 0;
?>

<header x-data="header()" 
        x-init="init()"
        class="bg-white shadow-sm sticky top-0 z-40 border-b border-gray-200">
    <div class="flex items-center justify-between h-16 px-4 lg:px-6">
        <!-- الجانب الأيمن (زر هامبرغر + اسم الصفحة) -->
        <div class="flex items-center gap-3">
            <!-- زر هامبرغر للموبايل -->
            <button @click="toggleMobileSidebar()" class="lg:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors">
                <i class="fas fa-bars text-gray-600 text-xl"></i>
            </button>
            
            <!-- اسم الصفحة + Breadcrumb -->
            <div class="hidden md:block">
                <h1 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($pageTitle) ?></h1>
                <?php if (!empty($breadcrumb)): ?>
                <div class="flex items-center gap-1 text-xs text-gray-500 mt-0.5">
                    <?php foreach ($breadcrumb as $index => $crumb): ?>
                        <?php if ($index > 0): ?>
                            <i class="fas fa-chevron-left text-[10px] mx-1"></i>
                        <?php endif; ?>
                        <span><?= htmlspecialchars($crumb) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="md:hidden">
                <h1 class="text-base font-semibold text-gray-800"><?= htmlspecialchars($pageTitle) ?></h1>
            </div>
        </div>

        <!-- الساعة والتاريخ -->
        <div class="hidden lg:flex items-center gap-4 text-sm">
            <div class="text-center">
                <div class="text-gray-800 font-bold text-base" id="headerTime"></div>
                <div class="text-gray-500 text-xs"><?php
                    $days = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
                    echo $days[date('w')] . ' — ' . date('Y/m/d');
                ?></div>
            </div>
            <div class="w-px h-8 bg-gray-200"></div>
        </div>

        <!-- الجانب الأيسر (بحث، إشعارات، مستخدم) -->
        <div class="flex items-center gap-2 lg:gap-4">
            <!-- شريط البحث المدمج -->
            <div x-show="searchOpen" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="absolute left-0 right-0 top-16 bg-white shadow-lg p-3 mx-2 rounded-xl z-50 md:relative md:top-auto md:bg-transparent md:shadow-none md:p-0 md:mx-0 md:flex md:w-auto">
                <div class="relative w-full">
                    <i class="fas fa-search absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                    <input type="text" 
                           x-model="searchQuery"
                           @input="performSearch"
                           placeholder="بحث عن منتج، فاتورة، عميل..."
                           class="w-full pr-10 pl-4 py-2.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 text-sm">
                    <!-- نتائج البحث -->
                    <div x-show="searchResults.length > 0" 
                         x-cloak
                         x-transition
                         class="absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-lg border border-gray-100 max-h-80 overflow-y-auto z-50">
                        <template x-for="result in searchResults" :key="result.id">
                            <a :href="result.url" class="flex items-center gap-2 p-3 hover:bg-gray-50 border-b border-gray-100 transition">
                                <i :class="result.icon" class="text-gray-400 w-5"></i>
                                <div class="flex-1">
                                    <div x-text="result.title" class="text-sm font-medium"></div>
                                    <div x-text="result.subtitle" class="text-xs text-gray-500"></div>
                                </div>
                            </a>
                        </template>
                    </div>
                </div>
            </div>
            <button @click="searchOpen = !searchOpen" 
                    class="p-2 rounded-lg hover:bg-gray-100 transition-colors relative">
                <i class="fas fa-search text-gray-600 text-lg"></i>
            </button>

            <!-- الإشعارات -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="p-2 rounded-lg hover:bg-gray-100 transition-colors relative">
                    <i class="fas fa-bell text-gray-600 text-lg"></i>
                    <?php if ($notificationsCount > 0): ?>
                    <span class="absolute -top-1 -left-1 bg-red-500 text-white text-[10px] font-bold rounded-full w-5 h-5 flex items-center justify-center">
                        <?= min($notificationsCount, 99) ?>
                    </span>
                    <?php endif; ?>
                </button>
                <div x-show="open" 
                     @click.away="open = false"
                     x-cloak
                     x-transition
                     class="absolute left-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-100 z-50 origin-top-right">
                    <div class="p-3 border-b border-gray-100 font-semibold text-gray-800">الإشعارات</div>
                    <div class="max-h-96 overflow-y-auto">
                        <!-- يمكن ملء الإشعارات ديناميكياً من PHP أو AJAX -->
                        <div class="p-4 text-center text-gray-500 text-sm">لا توجد إشعارات جديدة</div>
                    </div>
                    <div class="p-2 border-t border-gray-100 text-center">
                        <a href="<?= APP_URL ?>/installments?status=overdue" class="text-blue-600 text-sm">عرض الكل</a>
                    </div>
                </div>
            </div>

            <!-- قائمة المستخدم -->
            <div class="relative" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="flex items-center gap-2 p-1.5 rounded-lg hover:bg-gray-100 transition-colors">
                    <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-sm">
                        <?= htmlspecialchars($userInitial) ?>
                    </div>
                    <div class="hidden lg:block text-right">
                        <p class="text-sm font-medium text-gray-800"><?= htmlspecialchars($userName) ?></p>
                        <p class="text-xs text-gray-500"><?php
                            $roleNames = ['admin'=>'مدير النظام','branch_manager'=>'مدير فرع','sales_rep'=>'مندوب مبيعات','collector'=>'محصل'];
                            $r = currentUserRole();
                            echo $roleNames[$r['role_name'] ?? ''] ?? 'مستخدم';
                        ?></p>
                    </div>
                    <i class="fas fa-chevron-down hidden lg:block text-gray-400 text-xs"></i>
                </button>
                <div x-show="open" 
                     @click.away="open = false"
                     x-cloak
                     x-transition
                     class="absolute left-0 mt-2 w-56 bg-white rounded-xl shadow-xl border border-gray-100 z-50">
                    <a href="<?= APP_URL ?>/settings" class="flex items-center gap-3 p-3 hover:bg-gray-50 transition rounded-t-xl">
                        <i class="fas fa-cog w-5 text-gray-500"></i>
                        <span class="text-sm">الإعدادات</span>
                    </a>
                    <hr class="my-1 border-gray-100">
                    <a href="<?= APP_URL ?>/logout" class="flex items-center gap-3 p-3 hover:bg-red-50 transition text-red-600 rounded-b-xl">
                        <i class="fas fa-sign-out-alt w-5"></i>
                        <span class="text-sm">تسجيل خروج</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
(function() {
    const el = document.getElementById('headerTime');
    if (el) {
        function update() {
            const now = new Date();
            el.textContent = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
        }
        update();
        setInterval(update, 1000);
    }
})();
function header() {
    return {
        searchOpen: false,
        searchQuery: '',
        searchResults: [],
        init() {
            // أي عمليات تهيئة إضافية للهيدر
        },
        performSearch() {
            if (this.searchQuery.length < 2) {
                this.searchResults = [];
                return;
            }
            // مثال: طلب AJAX لجلب نتائج البحث (يمكن تعديل المسار حسب هيكل التطبيق)
            fetch(`<?= APP_URL ?>/api/search?q=${encodeURIComponent(this.searchQuery)}`)
                .then(res => res.json())
                .catch(() => [])
                .then(data => this.searchResults = data);
        },
        toggleMobileSidebar() {
            // استدعاء دالة من sidebar إذا كانت موجودة في النطاق العام
            if (window.dispatchEvent) {
                window.dispatchEvent(new CustomEvent('toggle-mobile-sidebar'));
            }
        }
    }
}
</script>
