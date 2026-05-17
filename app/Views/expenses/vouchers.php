<?php requireLogin(); $pageTitle = 'سندات الصرف'; ob_start(); ?>
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-receipt text-indigo-600"></i> سندات صرف المصروفات
        </h2>
        <a href="<?php echo APP_URL; ?>/expenses/vouchers/create"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> سند صرف جديد
        </a>
    </div>

    <!-- فلاتر البحث -->
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
        <select name="category_id" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-400">
            <option value="">كل التصنيفات</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?=$cat['id']?>" <?php echo ($filters['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>><?=$cat['name']?></option>
            <?php endforeach; ?>
        </select>
        <select name="expense_id" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-400">
            <option value="">كل التصنيفات</option>
            <?php foreach($categories as $cat): ?>
                <option value="<?=$cat['id']?>" <?php echo ($filters['expense_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>><?=$cat['name']?></option>
            <?php endforeach; ?>
        </select>
        <select name="branch_id" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-400">
            <option value="">كل الفروع</option>
            <?php foreach($branches as $b): ?>
                <option value="<?=$b['id']?>" <?php echo ($filters['branch_id'] ?? '') == $b['id'] ? 'selected' : ''; ?>><?=$b['name']?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="from_date" value="<?php echo $filters['from_date'] ?? ''; ?>" class="border border-gray-300 rounded-lg p-2 text-sm">
        <input type="date" name="to_date" value="<?php echo $filters['to_date'] ?? ''; ?>" class="border border-gray-300 rounded-lg p-2 text-sm">
        <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition font-bold text-sm">
            <i class="fas fa-filter ml-1"></i> فلترة
        </button>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-100 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3">رقم السند</th>
                    <th class="px-4 py-3">المصروف</th>
                    <th class="px-4 py-3">المبلغ</th>
                    <th class="px-4 py-3">الفرع</th>
                    <th class="px-4 py-3">التاريخ</th>
                    <th class="px-4 py-3">بواسطة</th>
                    <th class="px-4 py-3">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach($vouchers as $v): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono text-xs font-bold text-gray-500"><?=$v['voucher_number']?></td>
                    <td class="px-4 py-3">
                        <div class="font-medium text-gray-800"><?=htmlspecialchars($v['expense_name'])?></div>
                        <div class="text-xs text-gray-400"><?=htmlspecialchars($v['category_name'])?></div>
                    </td>
                    <td class="px-4 py-3 font-bold text-red-600"><?=number_format($v['amount'], 2)?></td>
                    <td class="px-4 py-3 text-gray-600"><?=$v['branch_name']?></td>
                    <td class="px-4 py-3 text-gray-600"><?=$v['date']?></td>
                    <td class="px-4 py-3 text-xs text-gray-500"><?=$v['created_by_name']?></td>
                    <td class="px-4 py-3">
                        <a href="<?=APP_URL?>/expenses/vouchers/print/<?=$v['id']?>" target="_blank" class="text-blue-500 hover:text-blue-700">
                            <i class="fas fa-print"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($vouchers)): ?>
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا يوجد سندات صرف</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pagination['links'])): ?>
        <div class="p-4 border-t">
            <?= $pagination['links'] ?>
        </div>
    <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
