<?php requireRole('admin'); $pageTitle = 'عمولات المندوبين'; ob_start(); ?>
<div class="space-y-6">
    <!-- كروت الملخص -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-orange-500">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-clock text-orange-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">إجمالي عمولات معلقة</p>
                    <p class="text-2xl font-bold text-gray-800"><?= number_format($totalPending, 2) ?> ج.م</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-green-500">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">مدفوعة هذا الشهر</p>
                    <p class="text-2xl font-bold text-gray-800"><?= number_format($paidThisMonth, 2) ?> ج.م</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-5 border-r-4 border-blue-500">
            <div class="flex items-center gap-3">
                <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500">عدد المندوبين</p>
                    <p class="text-2xl font-bold text-gray-800"><?= $repsCount ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول المندوبين -->
    <div class="bg-white rounded-xl shadow-sm">
        <div class="p-5 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800">قائمة المندوبين</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-right">
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">الاسم</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">الهاتف</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">عمولات مبيعات معلقة</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">عمولات تحصيل معلقة</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">الإجمالي</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($agents) > 0): ?>
                        <?php foreach ($agents as $a): ?>
                            <?php $totalAgent = round($a['pending_sales'] + $a['pending_collection'], 2); ?>
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars($a['full_name']) ?></td>
                                <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars($a['phone'] ?? '-') ?></td>
                                <td class="px-4 py-3 text-orange-600"><?= number_format($a['pending_sales'], 2) ?></td>
                                <td class="px-4 py-3 text-blue-600"><?= number_format($a['pending_collection'], 2) ?></td>
                                <td class="px-4 py-3 font-bold <?= $totalAgent > 0 ? 'text-green-600' : 'text-gray-400' ?>"><?= number_format($totalAgent, 2) ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex gap-2">
                                        <a href="<?= APP_URL ?>/commissions/agent/<?= $a['id'] ?>" class="bg-blue-50 text-blue-600 px-3 py-1 rounded-lg text-sm hover:bg-blue-100 transition">
                                            <i class="fas fa-file-alt"></i> كشف الحساب
                                        </a>
                                        <?php if ($totalAgent > 0): ?>
                                        <a href="<?= APP_URL ?>/commissions/pay/<?= $a['id'] ?>" class="bg-green-50 text-green-600 px-3 py-1 rounded-lg text-sm hover:bg-green-100 transition">
                                            <i class="fas fa-hand-holding-usd"></i> صرف عمولة
                                        </a>
                                        <button onclick="window.location.href='<?= APP_URL ?>/commissions/agent/<?= $a['id'] ?>'" class="bg-red-50 text-red-600 px-3 py-1 rounded-lg text-sm hover:bg-red-100 transition cursor-pointer">
                                            <i class="fas fa-lock"></i> إغلاق الحساب
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">لا يوجد مندوبون بعد</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
