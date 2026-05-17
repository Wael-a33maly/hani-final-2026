<?php requireLogin(); $pageTitle = 'المقبوضات والمدفوعات'; ob_start(); ?>
<div x-data="{ tab: 'receipts' }" class="bg-white rounded-xl shadow-sm p-6">
    <!-- تبويبات رئيسية -->
    <div class="flex items-center gap-4 border-b mb-6">
        <button @click="tab='receipts'" 
                :class="{'border-b-4 border-blue-600 text-blue-600 font-bold': tab=='receipts', 'text-gray-500 hover:text-gray-700': tab!='receipts'}" 
                class="px-4 py-3 transition text-sm flex items-center gap-2">
            <i class="fas fa-hand-holding-usd"></i> سندات قبض (تحصيل)
        </button>
        <button @click="tab='payments'" 
                :class="{'border-b-4 border-red-600 text-red-600 font-bold': tab=='payments', 'text-gray-500 hover:text-gray-700': tab!='payments'}" 
                class="px-4 py-3 transition text-sm flex items-center gap-2">
            <i class="fas fa-money-bill-wave"></i> سندات صرف (خارج)
        </button>
    </div>

    <!-- فلتر التاريخ -->
    <div class="mb-6 bg-gray-50 p-4 rounded-lg flex flex-wrap gap-3 items-end">
        <form method="GET" class="flex flex-wrap gap-3 items-end w-full">
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">من تاريخ</label>
                <input type="date" name="from_date" value="<?=$filters['from_date']?>" class="border rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">إلى تاريخ</label>
                <input type="date" name="to_date" value="<?=$filters['to_date']?>" class="border rounded-lg p-2 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-gray-500 mb-1">الفرع</label>
                <select name="branch_id" class="border rounded-lg p-2 text-sm">
                    <option value="">كل الفروع</option>
                    <?php foreach($branches as $b): ?>
                        <option value="<?=$b['id']?>" <?php echo ($filters['branch_id'] ?? '') == $b['id'] ? 'selected' : ''; ?>><?=$b['name']?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold text-sm hover:bg-blue-700 transition">
                <i class="fas fa-sync-alt ml-1"></i> تحديث
            </button>
        </form>
    </div>

    <!-- ========== سندات القبض ========== -->
    <div x-show="tab=='receipts'" x-transition>
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-gray-700 text-sm">📋 سندات قبض - تحصيل الأقساط</h3>
            <div class="text-lg font-bold text-green-700 bg-green-50 px-4 py-1 rounded-lg border border-green-100">
                الإجمالي: <?=number_format($totalReceipts, 2)?> ج.م
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-100 text-gray-600 text-xs">
                    <tr>
                        <th class="px-4 py-3">العميل</th>
                        <th class="px-4 py-3">رقم الفاتورة</th>
                        <th class="px-4 py-3">رقم القسط</th>
                        <th class="px-4 py-3">المبلغ</th>
                        <th class="px-4 py-3">تاريخ الدفع</th>
                        <th class="px-4 py-3">ملاحظات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach($receipts as $r): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800"><?=htmlspecialchars($r['customer_name'])?></td>
                        <td class="px-4 py-3 text-gray-500 font-mono"><?=$r['invoice_number']?></td>
                        <td class="px-4 py-3"><span class="bg-gray-200 px-2 py-0.5 rounded text-xs">قسط <?=$r['installment_number']?></span></td>
                        <td class="px-4 py-3 font-bold text-green-600"><?=number_format($r['amount'], 2)?></td>
                        <td class="px-4 py-3 text-gray-600"><?=$r['payment_date']?></td>
                        <td class="px-4 py-3 text-xs text-gray-400 italic"><?=htmlspecialchars($r['notes'] ?? '—')?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($receipts)): ?>
                    <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">لا توجد تحصيلات في هذه الفترة</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========== سندات الصرف ========== -->
    <div x-show="tab=='payments'" x-transition>
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-bold text-gray-700 text-sm">📋 سندات صرف - جميع الخوارج</h3>
            <div class="text-lg font-bold text-red-700 bg-red-50 px-4 py-1 rounded-lg border border-red-100">
                الإجمالي: <?=number_format($totalPayments, 2)?> ج.م
            </div>
        </div>

        <div class="mb-4 flex gap-2">
            <a href="<?= APP_URL ?>/expenses/vouchers/create" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-indigo-700 transition">
                <i class="fas fa-plus"></i> سند صرف جديد
            </a>
            <a href="<?= APP_URL ?>/expenses/categories" class="bg-gray-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-700 transition">
                <i class="fas fa-tags"></i> تصنيفات المصاريف
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- سندات صرف المصروفات -->
            <div class="border rounded-xl overflow-hidden shadow-sm">
                <div class="bg-indigo-50 p-3 border-b border-indigo-100 text-indigo-700 font-bold text-xs uppercase flex justify-between">
                    <span><i class="fas fa-receipt ml-1"></i> سندات صرف (مصروفات)</span>
                    <span><?=count($expenses)?> سند</span>
                </div>
                <table class="w-full text-xs text-right">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr><th class="p-2">التصنيف</th><th class="p-2">المبلغ</th><th class="p-2">التاريخ</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($expenses as $e): ?>
                        <tr>
                            <td class="p-2 font-medium"><?=htmlspecialchars($e['expense_name'])?></td>
                            <td class="p-2 font-bold text-red-600"><?=number_format($e['amount'], 2)?></td>
                            <td class="p-2"><?=$e['date']?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($expenses)): ?>
                        <tr><td colspan="3" class="p-4 text-center text-gray-400">لا توجد سندات صرف</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- مدفوعات الموردين -->
            <div class="border rounded-xl overflow-hidden shadow-sm">
                <div class="bg-orange-50 p-3 border-b border-orange-100 text-orange-700 font-bold text-xs uppercase flex justify-between">
                    <span><i class="fas fa-truck ml-1"></i> مدفوعات الموردين</span>
                    <span><?=count($supplierPayments)?> عملية</span>
                </div>
                <table class="w-full text-xs text-right">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr><th class="p-2">المورد</th><th class="p-2">المبلغ</th><th class="p-2">التاريخ</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($supplierPayments as $sp): ?>
                        <tr><td class="p-2 font-medium"><?=htmlspecialchars($sp['supplier_name'])?></td><td class="p-2 font-bold text-red-600"><?=number_format($sp['amount'], 2)?></td><td class="p-2"><?=$sp['payment_date']?></td></tr>
                        <?php endforeach; ?>
                        <?php if (empty($supplierPayments)): ?>
                        <tr><td colspan="3" class="p-4 text-center text-gray-400">لا توجد مدفوعات موردين</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- عمولات المناديب -->
            <div class="border rounded-xl overflow-hidden shadow-sm">
                <div class="bg-purple-50 p-3 border-b border-purple-100 text-purple-700 font-bold text-xs uppercase flex justify-between">
                    <span><i class="fas fa-percentage ml-1"></i> عمولات المناديب</span>
                    <span><?=count($commissionPayments)?> عملية</span>
                </div>
                <table class="w-full text-xs text-right">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr><th class="p-2">المندوب</th><th class="p-2">المبلغ</th><th class="p-2">التاريخ</th><th class="p-2">الطريقة</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach($commissionPayments as $cp): ?>
                        <tr>
                            <td class="p-2 font-medium"><?=htmlspecialchars($cp['agent_name'])?></td>
                            <td class="p-2 font-bold text-purple-600"><?=number_format($cp['amount'], 2)?></td>
                            <td class="p-2"><?=$cp['payment_date']?></td>
                            <td class="p-2"><?php $types = ['cash'=>'نقدي','transfer'=>'تحويل','vodafone'=>'فودافون','instapay'=>'إنستا باي']; echo $types[$cp['payment_type']] ?? $cp['payment_type']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($commissionPayments)): ?>
                        <tr><td colspan="4" class="p-4 text-center text-gray-400">لا توجد عمولات مدفوعة</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- تصنيفات المصاريف (سريع) -->
            <div class="border rounded-xl overflow-hidden shadow-sm">
                <div class="bg-teal-50 p-3 border-b border-teal-100 text-teal-700 font-bold text-xs uppercase flex justify-between">
                    <span><i class="fas fa-tags ml-1"></i> تصنيفات المصاريف</span>
                    <span><?=count($categories)?> تصنيف</span>
                </div>
                <div class="p-3">
                    <?php if (count($categories) > 0): ?>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach($categories as $cat): ?>
                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-xs"><?=htmlspecialchars($cat['name'])?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-400 text-xs text-center py-4">لا توجد تصنيفات - <a href="<?= APP_URL ?>/expenses/categories" class="text-blue-600 underline">أضف تصنيفات</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
