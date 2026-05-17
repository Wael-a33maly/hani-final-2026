<?php
$pageTitle = 'التقارير والمخازن';
ob_start();
?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

    <!-- كارت: جرد مادة -->
    <a href="<?php echo APP_URL; ?>/reports/product-stock" class="block bg-white rounded-xl shadow-sm hover:shadow-md transition p-6 border-t-4 border-blue-500">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-box text-blue-600 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">جرد مادة</h3>
                <p class="text-sm text-gray-500 mt-1">عرض رصيد مادة معينة في جميع المخازن.</p>
            </div>
        </div>
    </a>

    <!-- كارت: حركة مادة -->
    <a href="<?php echo APP_URL; ?>/reports/product-movements" class="block bg-white rounded-xl shadow-sm hover:shadow-md transition p-6 border-t-4 border-indigo-500">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-exchange-alt text-indigo-600 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">حركة مادة (كارت صنف)</h3>
                <p class="text-sm text-gray-500 mt-1">تتبع الوارد والمنصرف لمادة خلال فترة.</p>
            </div>
        </div>
    </a>

    <!-- كارت: جرد مخزن -->
    <a href="<?php echo APP_URL; ?>/reports/warehouse-stock" class="block bg-white rounded-xl shadow-sm hover:shadow-md transition p-6 border-t-4 border-purple-500">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-warehouse text-purple-600 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">جرد مخزن</h3>
                <p class="text-sm text-gray-500 mt-1">عرض أرصدة جميع المواد في مخزن محدد.</p>
            </div>
        </div>
    </a>

    <!-- كارت: جرد فرع -->
    <a href="<?php echo APP_URL; ?>/reports/branch-stock" class="block bg-white rounded-xl shadow-sm hover:shadow-md transition p-6 border-t-4 border-green-500">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-store text-green-600 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">جرد فرع</h3>
                <p class="text-sm text-gray-500 mt-1">تجميع أرصدة المخازن التابعة لفرع معين.</p>
            </div>
        </div>
    </a>

    <!-- كارت: التحويل بين المخازن -->
    <a href="<?php echo APP_URL; ?>/reports/transfers" class="block bg-white rounded-xl shadow-sm hover:shadow-md transition p-6 border-t-4 border-yellow-500">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-truck-loading text-yellow-600 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">التحويل بين المخازن</h3>
                <p class="text-sm text-gray-500 mt-1">نقل البضاعة وعرض سجل التحويلات.</p>
            </div>
        </div>
    </a>

    <!-- كارت: عهدة المناديب -->
    <a href="<?php echo APP_URL; ?>/salesrep" class="block bg-white rounded-xl shadow-sm hover:shadow-md transition p-6 border-t-4 border-red-500">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                <i class="fas fa-user-tie text-red-600 text-2xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-800">عهدة المناديب</h3>
                <p class="text-sm text-gray-500 mt-1">إدارة البضائع المسلمة والمباعة بواسطة المناديب.</p>
            </div>
        </div>
    </a>

</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
