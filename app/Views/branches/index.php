<?php requireRole('admin'); $pageTitle = 'الفروع'; ob_start(); ?>

<div class="bg-white rounded-xl shadow">
    <div class="p-5 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-700">
            <i class="fas fa-store ml-2 text-green-500"></i>قائمة الفروع
        </h2>
        <a href="<?php echo APP_URL; ?>/branches/create"
           class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> فرع جديد
        </a>
    </div>
    
    <!-- نموذج الفلترة -->
    <div class="p-4 border-b bg-gray-50">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" placeholder="بحث باسم الفرع" value="<?= htmlspecialchars($search ?? '') ?>" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none flex-1 max-w-sm">
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 transition font-bold text-sm flex items-center gap-2">
                <i class="fas fa-search"></i> بحث
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-right">#</th>
                    <th class="px-4 py-3 text-right">الكود</th>
                    <th class="px-4 py-3 text-right">اسم الفرع</th>
                    <th class="px-4 py-3 text-right">المدير</th>
                    <th class="px-4 py-3 text-right">الهاتف</th>
                    <th class="px-4 py-3 text-right">العنوان</th>
                    <th class="px-4 py-3 text-right">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($branches as $branch): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-500"><?php echo $branch['id']; ?></td>
                    <td class="px-4 py-3 font-mono font-semibold text-gray-700"><?php echo htmlspecialchars($branch['code']); ?></td>
                    <td class="px-4 py-3 font-medium text-gray-800"><?php echo htmlspecialchars($branch['name']); ?></td>
                    <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($branch['manager_name'] ?? '—'); ?></td>
                    <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($branch['phone'] ?? '—'); ?></td>
                    <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate"><?php echo htmlspecialchars($branch['address'] ?? '—'); ?></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-3">
                            <a href="<?php echo APP_URL; ?>/branches/edit/<?php echo $branch['id']; ?>"
                               class="text-blue-500 hover:text-blue-700 transition" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo APP_URL; ?>/branches/delete/<?php echo $branch['id']; ?>"
                                  class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الفرع؟')">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 transition" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($branches)): ?>
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا يوجد فروع</td></tr>
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
