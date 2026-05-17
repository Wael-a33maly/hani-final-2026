<?php requireLogin(); $pageTitle = 'الموردين'; ob_start(); ?>
<div class="bg-white rounded-xl shadow">
    <div class="p-5 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-700">
            <i class="fas fa-truck ml-2 text-orange-500"></i>قائمة الموردين
        </h2>
        <a href="<?php echo APP_URL; ?>/suppliers/create"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> مورد جديد
        </a>
    </div>
    
    <!-- نموذج الفلترة -->
    <div class="p-4 border-b bg-gray-50">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" placeholder="بحث باسم المورد أو الهاتف" value="<?= htmlspecialchars($search ?? '') ?>" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-orange-400 outline-none flex-1 max-w-sm">
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 transition font-bold text-sm flex items-center gap-2">
                <i class="fas fa-search"></i> بحث
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-right">كود المورد</th>
                    <th class="px-4 py-3 text-right">الاسم</th>
                    <th class="px-4 py-3 text-right hidden sm:table-cell">الهاتف</th>
                    <th class="px-4 py-3 text-right">الرصيد الحالي</th>
                    <th class="px-4 py-3 text-right">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($suppliers as $s): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono text-xs text-gray-500 whitespace-nowrap"><?php echo htmlspecialchars($s['code']); ?></td>
                    <td class="px-4 py-3 font-medium text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($s['name']); ?></td>
                    <td class="px-4 py-3 text-gray-600 hidden sm:table-cell whitespace-nowrap"><?php echo htmlspecialchars($s['phone']); ?></td>
                    <td class="px-4 py-3 font-bold whitespace-nowrap <?php echo $s['balance'] > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo number_format($s['balance'], 2); ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <a href="<?php echo APP_URL; ?>/suppliers/statement/<?php echo $s['id']; ?>"
                               class="text-indigo-500 hover:text-indigo-700 transition" title="كشف حساب">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                            <a href="<?php echo APP_URL; ?>/suppliers/edit/<?php echo $s['id']; ?>"
                               class="text-blue-500 hover:text-blue-700 transition" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($suppliers)): ?>
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">لا يوجد موردون</td></tr>
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
