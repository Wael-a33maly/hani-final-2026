<?php
$pageTitle = 'فئات المصروفات';
ob_start();
?>
<div class="bg-white rounded-xl shadow p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">إدارة فئات المصروفات</h2>
        <!-- Form to add category -->
        <form action="<?php echo APP_URL; ?>/expenses/categories/store" method="POST" class="flex gap-2">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="text" name="name" placeholder="اسم الفئة الجديدة" required class="border rounded px-3 py-2 text-sm focus:outline-none focus:border-blue-500">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition"><i class="fas fa-plus"></i> إضافة</button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs">
                <tr>
                    <th class="px-4 py-3 text-right">م</th>
                    <th class="px-4 py-3 text-right">اسم الفئة</th>
                    <th class="px-4 py-3 text-center">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($categories as $index => $cat): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600"><?php echo $index + 1; ?></td>
                    <td class="px-4 py-3 text-gray-800 font-semibold"><?php echo htmlspecialchars($cat['name']); ?></td>
                    <td class="px-4 py-3 text-center space-x-2 space-x-reverse">
                        <form action="<?php echo APP_URL; ?>/expenses/categories/delete/<?php echo $cat['id']; ?>" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <button type="submit" class="text-red-500 hover:bg-red-50 p-1 rounded transition" title="حذف">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
