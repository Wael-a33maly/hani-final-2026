<?php
$pageTitle = 'المصروفات (بنود الصرف)';
ob_start();
?>
<div class="bg-white rounded-xl shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">إدارة بنود المصروفات</h2>
        <div class="flex gap-2">
            <a href="<?php echo APP_URL; ?>/expenses/categories" class="bg-gray-100 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-200 transition"><i class="fas fa-tags"></i> الفئات</a>
            <a href="<?php echo APP_URL; ?>/expenses/create" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition"><i class="fas fa-plus"></i> إضافة بند جديد</a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs">
                <tr>
                    <th class="px-4 py-3 text-right">م</th>
                    <th class="px-4 py-3 text-right">اسم البند</th>
                    <th class="px-4 py-3 text-right">الفئة</th>
                    <th class="px-4 py-3 text-center">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($expenses as $index => $expense): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600"><?php echo $index + 1; ?></td>
                    <td class="px-4 py-3 text-gray-800 font-semibold"><?php echo htmlspecialchars($expense['name']); ?></td>
                    <td class="px-4 py-3 text-gray-600">
                        <span class="bg-gray-100 px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($expense['category_name'] ?? 'بدون فئة'); ?></span>
                    </td>
                    <td class="px-4 py-3 text-center space-x-2 space-x-reverse">
                        <a href="<?php echo APP_URL; ?>/expenses/edit/<?php echo $expense['id']; ?>" class="text-blue-500 hover:bg-blue-50 p-1 rounded transition" title="تعديل">
                            <i class="fas fa-edit"></i>
                        </a>
                        <form action="<?php echo APP_URL; ?>/expenses/delete/<?php echo $expense['id']; ?>" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <button type="submit" class="text-red-500 hover:bg-red-50 p-1 rounded transition" title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($expenses)): ?>
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-gray-500">لا توجد بنود مصروفات مضافة بعد.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
