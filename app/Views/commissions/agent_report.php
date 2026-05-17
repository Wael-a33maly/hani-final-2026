<?php requireRole('admin'); $pageTitle = 'كشف حساب - ' . $agent['full_name']; ob_start(); ?>
<div class="space-y-6">
    <!-- رأس الصفحة -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-tie text-blue-600 text-2xl"></i>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($agent['full_name']) ?></h2>
                    <p class="text-gray-500 text-sm"><?= htmlspecialchars($agent['phone'] ?? '') ?> | نسبة التحصيل: <?= $agent['collection_commission_rate'] ?>%</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="<?= APP_URL ?>/commissions" class="bg-gray-100 text-gray-600 px-4 py-2 rounded-lg text-sm hover:bg-gray-200 transition">
                    <i class="fas fa-arrow-right"></i> عودة
                </a>
                <a href="<?= APP_URL ?>/commissions/pay/<?= $agent['id'] ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-green-700 transition">
                    <i class="fas fa-hand-holding-usd"></i> صرف عمولة
                </a>
                <button type="button" onclick="document.getElementById('closeModal').classList.remove('hidden'); document.getElementById('closeModal').classList.add('flex');" class="bg-red-700 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-800 transition">
                    <i class="fas fa-lock"></i> إغلاق الحساب
                </button>
                <a href="<?= APP_URL ?>/commissions/agent/<?= $agent['id'] ?>/export-pdf?from=<?= $from ?>&to=<?= $to ?>" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-red-700 transition" target="_blank">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
            </div>
        </div>

        <!-- فلتر الفترة -->
        <form method="GET" class="mt-4 flex flex-wrap gap-3 items-end">
            <input type="hidden" name="from" id="from_hidden" value="<?= $from ?>">
            <input type="hidden" name="to" id="to_hidden" value="<?= $to ?>">
            <div>
                <label class="block text-sm text-gray-600 mb-1">من تاريخ</label>
                <input type="date" name="from" value="<?= $from ?>" class="border rounded-lg px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm text-gray-600 mb-1">إلى تاريخ</label>
                <input type="date" name="to" value="<?= $to ?>" class="border rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700 transition">
                <i class="fas fa-search"></i> بحث
            </button>
        </form>
    </div>

    <!-- جدول عمولات المبيعات -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center gap-2">
            <i class="fas fa-shopping-cart text-orange-500"></i>
            <h3 class="font-bold text-gray-800">عمولات المبيعات</h3>
            <span class="bg-orange-100 text-orange-600 text-xs px-2 py-1 rounded-full mr-auto"><?= count($salesCommissions) ?> عملية</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-right">
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">التاريخ</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">رقم الفاتورة</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">اسم المادة</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">الكمية</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">عمولة/وحدة</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">الإجمالي</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($salesCommissions) > 0): ?>
                        <?php foreach ($salesCommissions as $sc): ?>
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $sc['commission_date'] ?></td>
                                <td class="px-4 py-3">
                                    <a href="<?= APP_URL ?>/sales/show/<?= $sc['sale_id'] ?>" class="text-blue-600 hover:underline"><?= $sc['invoice_number'] ?></a>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars($sc['product_name']) ?></td>
                                <td class="px-4 py-3"><?= $sc['quantity'] ?></td>
                                <td class="px-4 py-3"><?= number_format($sc['commission_amount_per_unit'], 2) ?></td>
                                <td class="px-4 py-3 font-bold"><?= number_format($sc['total_commission'], 2) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($sc['status'] == 'pending'): ?>
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs">معلقة</span>
                                    <?php elseif ($sc['status'] == 'paid'): ?>
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs">مدفوعة</span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs">ملغية</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">لا توجد عمولات مبيعات في هذه الفترة</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- جدول عمولات التحصيل -->
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center gap-2">
            <i class="fas fa-hand-holding-usd text-blue-500"></i>
            <h3 class="font-bold text-gray-800">عمولات التحصيل</h3>
            <span class="bg-blue-100 text-blue-600 text-xs px-2 py-1 rounded-full mr-auto"><?= count($collectionCommissions) ?> عملية</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 text-right">
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">التاريخ</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">رقم الفاتورة</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">اسم العميل</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">المبلغ المحصّل</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">النسبة%</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">العمولة</th>
                        <th class="px-4 py-3 text-sm font-semibold text-gray-600">الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($collectionCommissions) > 0): ?>
                        <?php foreach ($collectionCommissions as $cc): ?>
                            <tr class="border-t border-gray-100 hover:bg-gray-50">
                                <td class="px-4 py-3"><?= $cc['collection_date'] ?></td>
                                <td class="px-4 py-3">
                                    <a href="<?= APP_URL ?>/sales/show/<?= $cc['sale_id'] ?>" class="text-blue-600 hover:underline"><?= $cc['invoice_number'] ?></a>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars($cc['customer_name']) ?></td>
                                <td class="px-4 py-3"><?= number_format($cc['collected_amount'], 2) ?></td>
                                <td class="px-4 py-3"><?= $cc['commission_rate'] ?>%</td>
                                <td class="px-4 py-3 font-bold"><?= number_format($cc['commission_amount'], 2) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($cc['status'] == 'pending'): ?>
                                        <span class="bg-yellow-100 text-yellow-700 px-2 py-1 rounded-full text-xs">معلقة</span>
                                    <?php elseif ($cc['status'] == 'paid'): ?>
                                        <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-xs">مدفوعة</span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-xs">ملغية</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500">لا توجد عمولات تحصيل في هذه الفترة</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ملخص المستحقات -->
    <div class="bg-white rounded-xl shadow-sm p-5">
        <h3 class="font-bold text-gray-800 mb-4">ملخص المستحقات</h3>
        <div class="max-w-md mr-auto">
            <div class="space-y-2">
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">إجمالي عمولات المبيعات</span>
                    <span class="font-bold text-orange-600"><?= number_format($salesSummary['sales_total'], 2) ?> ج.م</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">إجمالي عمولات التحصيل</span>
                    <span class="font-bold text-blue-600"><?= number_format($collectionSummary['collection_total'], 2) ?> ج.م</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-200 font-bold text-lg">
                    <span class="text-gray-800">الإجمالي الكلي</span>
                    <span class="text-gray-800"><?= number_format($salesSummary['sales_total'] + $collectionSummary['collection_total'], 2) ?> ج.م</span>
                </div>
                <div class="flex justify-between py-2 border-b border-gray-100">
                    <span class="text-gray-600">المدفوع سابقاً</span>
                    <span class="font-bold text-green-600"><?= number_format($paidAmount, 2) ?> ج.م</span>
                </div>
                <div class="flex justify-between py-3 text-xl font-bold">
                    <span class="text-gray-800">💰 الصافي المستحق</span>
                    <span class="<?= $netDue > 0 ? 'text-green-600' : 'text-gray-400' ?>"><?= number_format($netDue, 2) ?> ج.م</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- مودال إغلاق الحساب -->
<div id="closeModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50" x-data="closeAccount()" x-init="initClose(<?= $netDue ?>)">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md mx-4 shadow-2xl">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fas fa-lock text-red-600"></i> إغلاق حساب المندوب
        </h3>
        <p class="text-sm text-gray-600 mb-4">سيتم إقفال جميع العمولات المعلقة للمندوب <strong><?= htmlspecialchars($agent['full_name']) ?></strong> وإصدار سند صرف نهائي.</p>
        <form method="POST" action="<?= APP_URL ?>/commissions/close-account/<?= $agent['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <div class="bg-gray-50 rounded-lg p-3 mb-4">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-600">إجمالي المستحق (كل المعلقات):</span>
                    <span class="font-bold text-green-600" x-text="numberFormat(totalDue)"></span>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">المبلغ المصروف <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="amount" x-model="amount" class="w-full border rounded-lg px-3 py-2 text-lg font-bold" required>
                <p class="text-xs text-gray-500 mt-1">يمكنك صرف أكثر (مكافأة) أو أقل (خصم) من المستحق</p>
            </div>

            <div x-show="parseFloat(amount) !== parseFloat(totalDue)" x-cloak x-transition class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                    سبب الفرق (زيادة/نقص)
                </label>
                <textarea name="difference_reason" rows="2" class="w-full border border-yellow-300 rounded-lg px-3 py-2 text-sm" placeholder="مكافأة - خصم - سلفة - ..."></textarea>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">طريقة الصرف</label>
                <select name="payment_type" class="w-full border rounded-lg px-3 py-2">
                    <option value="cash">نقدي</option>
                    <option value="transfer">تحويل بنكي</option>
                    <option value="vodafone">فودافون كاش</option>
                    <option value="instapay">إنستا باي</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                <textarea name="notes" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm" placeholder="اختياري"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t">
                <button type="button" onclick="document.getElementById('closeModal').classList.add('hidden')" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg text-sm">إلغاء</button>
                <button type="submit" class="bg-red-700 text-white px-5 py-2 rounded-lg text-sm hover:bg-red-800 transition flex items-center gap-2">
                    <i class="fas fa-lock"></i> تأكيد الإغلاق
                </button>
            </div>
        </form>
    </div>
</div>
<script>
function closeAccount() {
    return {
        totalDue: 0,
        amount: 0,
        initClose(netDue) {
            this.totalDue = netDue < 0 ? 0 : netDue;
            this.amount = this.totalDue;
        },
        numberFormat(num) {
            return parseFloat(num || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ج.م';
        }
    }
}
</script>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
