<?php requireLogin(); $pageTitle = 'العملاء'; ob_start(); ?>
<div class="bg-white rounded-xl shadow">
    <div class="p-5 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-700">
            <i class="fas fa-user-friends ml-2 text-indigo-500"></i>قائمة العملاء
        </h2>
        <div class="flex gap-2">
            <a href="<?php echo APP_URL; ?>/customers/import"
               class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-file-import"></i> استيراد من إكسيل
            </a>
            <a href="<?php echo APP_URL; ?>/customers/bulk-create"
               class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-users"></i> إضافة مجمعين
            </a>
            <a href="<?php echo APP_URL; ?>/customers/create"
               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-plus"></i> عميل جديد
            </a>
        </div>
    </div>
    
    <!-- نموذج الفلترة -->
    <div class="p-4 border-b bg-gray-50">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search" placeholder="بحث بالاسم أو الهاتف" value="<?= htmlspecialchars($search ?? '') ?>" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
            <select name="area" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
                <option value="">كل المناطق</option>
                <?php foreach($areas as $a): ?>
                <option value="<?= $a['area'] ?>" <?= (isset($area) && $area == $a['area']) ? 'selected' : '' ?>><?= htmlspecialchars($a['area']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="sales_rep_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
                <option value="">كل المناديب</option>
                <?php foreach($reps as $r): ?>
                <option value="<?= $r['id'] ?>" <?= (isset($sales_rep_id) && $sales_rep_id == $r['id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition font-bold text-sm flex items-center justify-center gap-2">
                <i class="fas fa-search"></i> بحث
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-right">كود العميل</th>
                    <th class="px-4 py-3 text-right">الاسم</th>
                    <th class="px-4 py-3 text-right hidden sm:table-cell">المنطقة</th>
                    <th class="px-4 py-3 text-right hidden md:table-cell">المندوب</th>
                    <th class="px-4 py-3 text-right">الهاتف</th>
                    <th class="px-4 py-3 text-right">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($customers as $c): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono text-xs text-gray-500 whitespace-nowrap"><?php echo htmlspecialchars($c['code']); ?></td>
                    <td class="px-4 py-3 font-medium text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($c['name']); ?></td>
                    <td class="px-4 py-3 text-gray-600 hidden sm:table-cell whitespace-nowrap"><?php echo htmlspecialchars($c['area']); ?></td>
                    <td class="px-4 py-3 text-gray-600 text-xs hidden md:table-cell whitespace-nowrap"><?php echo htmlspecialchars($c['rep_name'] ?? '—'); ?></td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap"><?php echo htmlspecialchars($c['phone']); ?></td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <a href="<?php echo APP_URL; ?>/customers/statement/<?php echo $c['id']; ?>"
                               class="text-indigo-500 hover:text-indigo-700 transition" title="كشف حساب">
                                <i class="fas fa-file-invoice"></i>
                            </a>
                            <a href="<?php echo APP_URL; ?>/customers/edit/<?php echo $c['id']; ?>"
                               class="text-blue-500 hover:text-blue-700 transition" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($customers)): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">لا يوجد عملاء</td></tr>
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
