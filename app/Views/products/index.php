<?php requireLogin(); $pageTitle = 'المواد (المنتجات)'; ob_start(); ?>
<div class="bg-white rounded-xl shadow">
    <div class="p-5 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-700">
            <i class="fas fa-boxes ml-2 text-yellow-500"></i>قائمة المواد
        </h2>
        <a href="<?php echo APP_URL; ?>/products/create"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> مادة جديدة
        </a>
    </div>
    
    <!-- نموذج الفلترة -->
    <div class="p-4 border-b bg-gray-50">
        <form method="GET" class="flex gap-3">
            <input type="text" name="search" placeholder="بحث بالاسم أو الباركود" value="<?= htmlspecialchars($search ?? '') ?>" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none flex-1 max-w-sm">
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded-lg hover:bg-gray-900 transition font-bold text-sm flex items-center gap-2">
                <i class="fas fa-search"></i> بحث
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-right">الباركود</th>
                    <th class="px-4 py-3 text-right">الاسم</th>
                    <th class="px-4 py-3 text-right hidden sm:table-cell">الوحدة</th>
                    <th class="px-4 py-3 text-right hidden md:table-cell">سعر الشراء</th>
                    <th class="px-4 py-3 text-right">سعر البيع</th>
                    <th class="px-4 py-3 text-right">الرصيد</th>
                    <th class="px-4 py-3 text-right">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($products as $p): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono text-xs text-gray-500 whitespace-nowrap"><?php echo htmlspecialchars($p['barcode']); ?></td>
                    <td class="px-4 py-3 font-medium text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($p['name']); ?></td>
                    <td class="px-4 py-3 text-gray-600 hidden sm:table-cell whitespace-nowrap"><?php echo htmlspecialchars($p['unit_name']); ?></td>
                    <td class="px-4 py-3 text-gray-600 font-semibold hidden md:table-cell whitespace-nowrap"><?php echo number_format($p['purchase_price'], 2); ?></td>
                    <td class="px-4 py-3 text-blue-600 font-bold whitespace-nowrap"><?php echo number_format($p['selling_price'], 2); ?></td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="<?= $p['total_stock'] > 0 ? 'text-green-600 font-bold' : 'text-red-400' ?>">
                            <?= number_format($p['total_stock'], 3) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <a href="<?php echo APP_URL; ?>/products/edit/<?php echo $p['id']; ?>"
                               class="text-blue-500 hover:text-blue-700 transition">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="<?php echo APP_URL; ?>/products/delete/<?php echo $p['id']; ?>"
                                  class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه المادة؟')">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 transition">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">لا يوجد مواد</td></tr>
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
