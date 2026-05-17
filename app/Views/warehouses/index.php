<?php requireLogin(); $pageTitle = 'المخازن'; ob_start(); ?>
<div class="bg-white rounded-xl shadow">
    <div class="p-5 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-700">
            <i class="fas fa-warehouse ml-2 text-blue-500"></i>قائمة المخازن
        </h2>
        <a href="<?php echo APP_URL; ?>/warehouses/create"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> مخزن جديد
        </a>
    </div>
    
    <!-- نموذج الفلترة -->
    <div class="p-4 border-b bg-gray-50">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <input type="text" name="search" placeholder="بحث باسم المخزن" value="<?= htmlspecialchars($search ?? '') ?>" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
            <select name="branch_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                <option value="">كل الفروع</option>
                <?php foreach($branches as $b): ?>
                <option value="<?= $b['id'] ?>" <?= (isset($branch_id) && $branch_id == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition font-bold text-sm flex items-center justify-center gap-2">
                <i class="fas fa-search"></i> بحث
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-right">#</th>
                    <th class="px-4 py-3 text-right">اسم المخزن</th>
                    <th class="px-4 py-3 text-right">العنوان</th>
                    <th class="px-4 py-3 text-right">الفرع</th>
                    <th class="px-4 py-3 text-right">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($warehouses as $w): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-500"><?php echo $w['id']; ?></td>
                    <td class="px-4 py-3 font-medium text-gray-800"><?php echo htmlspecialchars($w['name']); ?></td>
                    <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($w['address'] ?? '—'); ?></td>
                    <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($w['branch_name']); ?></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <a href="<?php echo APP_URL; ?>/warehouses/edit/<?php echo $w['id']; ?>"
                               class="text-blue-500 hover:text-blue-700 transition">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo APP_URL; ?>/warehouses/delete/<?php echo $w['id']; ?>"
                                  class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المخزن؟')">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($warehouses)): ?>
                <tr><td colspan="5" class="px-4 py-8 text-center text-gray-400">لا يوجد مخازن</td></tr>
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
