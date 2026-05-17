<?php
// الحصول على اسم الصفحة الحالية لتحديد active
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';
$activeClass = 'bg-blue-600/20 text-blue-400 border-r-4 border-blue-500';
?>

<div x-data="sidebar()" 
     x-init="init()"
     @toggle-mobile-sidebar.window="mobileOpen = !mobileOpen">

    <!-- Overlay للموبايل (داخل نطاق Alpine) -->
    <div x-show="mobileOpen && windowWidth < 1024" 
         x-cloak
         x-transition.opacity.duration.200ms
         @click="mobileOpen = false"
         class="fixed inset-0 bg-black/50 z-[90] lg:hidden"></div>

    <!-- لوحة السايدبار -->
    <div :class="{
            'w-72': !isCollapsed, 
            'w-20': isCollapsed && windowWidth >= 1024, 
            'fixed inset-y-0 right-0 z-[100]': windowWidth < 1024,
            'translate-x-0': mobileOpen || windowWidth >= 1024,
            'translate-x-full': !mobileOpen && windowWidth < 1024,
            'relative': windowWidth >= 1024
         }"
         class="bg-gradient-to-b from-slate-900 to-slate-800 text-white h-screen max-h-screen transition-all duration-300 shadow-xl flex-shrink-0 overflow-y-auto overflow-x-hidden"
         style="transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s ease;">
        
        <!-- Logo وزر الطي -->
        <div class="flex items-center justify-between p-4 border-b border-slate-700/50">
            <div x-show="!isCollapsed || windowWidth < 1024" x-cloak class="flex items-center gap-2">
                <i class="fas fa-building text-2xl text-blue-400"></i>
                <span class="font-bold text-lg truncate">نظام ERP</span>
            </div>
            <div x-show="isCollapsed && windowWidth >= 1024" x-cloak class="w-full text-center">
                <i class="fas fa-building text-2xl text-blue-400"></i>
            </div>
            <button @click="toggleSidebar()" class="p-2 rounded-lg hover:bg-slate-700 transition-colors hidden lg:block">
                <i class="fas fa-chevron-right" :class="{'rotate-180': isCollapsed}"></i>
            </button>
            <!-- زر إغلاق للموبايل -->
            <button @click="mobileOpen = false" class="p-2 rounded-lg hover:bg-slate-700 lg:hidden">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <!-- روابط التنقل -->
        <nav class="p-3 space-y-1">
            <?php
            $userRole = currentUserRole();
            $roleName = $userRole['role_name'] ?? '';
            $isAdmin = ($roleName === 'admin');
            $isBranchMgr = ($roleName === 'branch_manager');
            $isSalesRep = ($roleName === 'sales_rep');
            $isCollector = ($roleName === 'collector');

            $menuItems = [
                ['url' => '/dashboard', 'icon' => 'tachometer-alt', 'label' => 'الرئيسية', 'module' => 'dashboard', 'perm' => true],
                ['icon' => 'users-cog', 'label' => 'إدارة المستخدمين', 'module' => 'users|branches', 'perm' => $isAdmin || $isBranchMgr, 'sub' => [
                    ['url' => '/users', 'label' => 'المستخدمون', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/branches', 'label' => 'الفروع', 'perm' => $isAdmin],
                ]],
                ['icon' => 'warehouse', 'label' => 'إدارة المخازن والمواد', 'module' => 'warehouses|products|reports|salesrep|units', 'perm' => $isAdmin || $isBranchMgr || $isSalesRep, 'sub' => [
                    ['url' => '/warehouses', 'label' => 'المخازن', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/products', 'label' => 'المواد', 'perm' => $isAdmin || $isBranchMgr || $isSalesRep],
                    ['url' => '/units', 'label' => 'الوحدات', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/reports/product-stock', 'label' => 'جرد مادة', 'perm' => true],
                    ['url' => '/reports/product-movements', 'label' => 'حركة مادة', 'perm' => true],
                    ['url' => '/reports/warehouse-stock', 'label' => 'جرد مستودع', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/reports/branch-stock', 'label' => 'جرد فرع', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/reports/transfers', 'label' => 'تحويلات المخازن', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/salesrep', 'label' => 'عهدة المندوبين', 'perm' => $isAdmin || $isBranchMgr || $isSalesRep],
                ]],
                ['icon' => 'truck', 'label' => 'الموردين والمشتريات', 'module' => 'suppliers|supplier-payments|purchases', 'perm' => $isAdmin || $isBranchMgr, 'sub' => [
                    ['url' => '/suppliers', 'label' => 'الموردين', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/supplier-payments', 'label' => 'مدفوعات الموردين', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/purchases', 'label' => 'المشتريات', 'perm' => $isAdmin || $isBranchMgr],
                ]],
                ['icon' => 'cash-register', 'label' => 'المبيعات والعملاء', 'module' => 'customers|sales|installments|print', 'perm' => true, 'sub' => [
                    ['url' => '/customers', 'label' => 'العملاء', 'perm' => true],
                    ['url' => '/sales', 'label' => 'المبيعات', 'perm' => true],
                    ['url' => '/installments', 'label' => 'الأقساط', 'perm' => true],
                    ['url' => '/print/installments', 'label' => 'طباعة الأقساط', 'perm' => $isAdmin || $isBranchMgr],
                ]],
                ['url' => '/commissions', 'icon' => 'percentage', 'label' => 'عمولات المندوبين', 'module' => 'commissions', 'perm' => $isAdmin || $isBranchMgr || $isSalesRep || $isCollector],
                ['icon' => 'hand-holding-usd', 'label' => 'المقبوضات والمدفوعات', 'module' => 'payments|expenses', 'perm' => $isAdmin || $isBranchMgr || $isCollector, 'sub' => [
                    ['url' => '/payments', 'label' => 'سندات قبض وصرف', 'perm' => $isAdmin || $isBranchMgr || $isCollector],
                    ['url' => '/expenses/vouchers', 'label' => 'سندات صرف (مصروفات)', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/expenses/vouchers/create', 'label' => 'إنشاء سند صرف', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/expenses/categories', 'label' => 'تصنيفات المصاريف', 'perm' => $isAdmin || $isBranchMgr],
                ]],
                ['icon' => 'cog', 'label' => 'الإعدادات والتحديثات', 'module' => 'settings|updates|role-permissions', 'perm' => $isAdmin || $isBranchMgr, 'sub' => [
                    ['url' => '/role-permissions/roles', 'label' => 'الأدوار والصلاحيات', 'perm' => $isAdmin],
                    ['url' => '/role-permissions/permissions', 'label' => 'الصلاحيات', 'perm' => $isAdmin],
                    ['url' => '/settings', 'label' => 'إعدادات الشركة', 'perm' => $isAdmin || $isBranchMgr],
                    ['url' => '/settings/update-settings', 'label' => 'إعدادات التحديثات', 'perm' => $isAdmin],
                    ['url' => '/updates', 'label' => 'إدارة التحديثات', 'perm' => $isAdmin],
                ]],
            ];
            ?>
            
            <?php foreach ($menuItems as $item): ?>
                <?php if (isset($item['sub'])): ?>
                    <?php
                    $hasVisible = false;
                    $isGroupActive = false;
                    foreach ($item['sub'] as $sub) {
                        if ($sub['perm']) $hasVisible = true;
                        if (!$isGroupActive && !empty($sub['url']) && strpos($currentUrl, $sub['url']) !== false) {
                            $isGroupActive = true;
                        }
                    }
                    if (!$hasVisible) continue;
                    ?>
                    <div x-data="{ open: <?= $isGroupActive ? 'true' : 'false' ?> }">
                        <button @click="open = !open" class="w-full flex items-center justify-between p-3 rounded-lg transition-all group <?= $isGroupActive ? 'bg-slate-700/50 text-blue-400' : 'hover:bg-slate-700' ?>">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-<?= $item['icon'] ?> w-5 text-slate-400 group-hover:text-blue-400 transition"></i>
                                <span x-show="!isCollapsed || windowWidth < 1024" x-cloak class="truncate"><?= $item['label'] ?></span>
                            </div>
                            <i class="fas fa-chevron-down text-xs transition-transform" :class="{'rotate-180': open}"></i>
                        </button>
                        <div x-show="open" x-cloak x-collapse class="mr-8 space-y-1 mt-1">
                            <?php foreach ($item['sub'] as $sub): ?>
                                <?php if (!$sub['perm']) continue; ?>
                                <a href="<?= APP_URL . $sub['url'] ?>" 
                                   class="flex items-center gap-3 p-2 rounded-lg text-sm transition <?= strpos($currentUrl, $sub['url']) !== false ? 'bg-blue-600/20 text-blue-400' : 'hover:bg-slate-700/50' ?>">
                                    <i class="fas fa-circle text-[8px] <?= strpos($currentUrl, $sub['url']) !== false ? 'text-blue-400' : 'text-slate-500' ?>"></i>
                                    <span x-show="!isCollapsed || windowWidth < 1024" x-cloak><?= $sub['label'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- رابط عادي -->
                    <a href="<?= APP_URL . $item['url'] ?>" 
                       class="flex items-center gap-3 p-3 rounded-lg transition-all group relative"
                       :class="{'bg-blue-600/20 text-blue-400': '<?= strpos($currentUrl, $item['module']) !== false ?>', 'hover:bg-slate-700': true}">
                        <i class="fas fa-<?= $item['icon'] ?> w-5 text-slate-400 group-hover:text-blue-400 transition-transform group-hover:scale-110"></i>
                        <span x-show="!isCollapsed || windowWidth < 1024" x-cloak class="truncate"><?= $item['label'] ?></span>
                        <!-- Tooltip عند طي السايدبار -->
                        <div x-show="isCollapsed && windowWidth >= 1024" x-cloak
                             class="absolute right-full mr-3 bg-slate-800 text-white text-sm px-2 py-1 rounded whitespace-nowrap opacity-0 group-hover:opacity-100 transition pointer-events-none z-50">
                            <?= $item['label'] ?>
                        </div>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <!-- زر تسجيل الخروج -->
            <div class="pt-4 mt-4 border-t border-slate-700/50">
                <a href="<?= APP_URL ?>/logout" class="flex items-center gap-3 p-3 rounded-lg hover:bg-red-600/20 text-red-400 transition">
                    <i class="fas fa-sign-out-alt w-5"></i>
                    <span x-show="!isCollapsed || windowWidth < 1024" x-cloak>تسجيل خروج</span>
                </a>
            </div>
        </nav>
    </div>
</div>

<script>
function sidebar() {
    return {
        isCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        mobileOpen: false,
        windowWidth: window.innerWidth,
        init() {
            window.addEventListener('resize', () => {
                this.windowWidth = window.innerWidth;
                if (this.windowWidth >= 1024) {
                    this.mobileOpen = false;
                }
            });
        },
        toggleSidebar() {
            this.isCollapsed = !this.isCollapsed;
            localStorage.setItem('sidebarCollapsed', this.isCollapsed);
        },
        toggleMobile() {
            this.mobileOpen = !this.mobileOpen;
        }
    }
}
</script>
