<?php
$isEdit = isset($expense) && $expense;
$pageTitle = $isEdit ? 'تعديل المصروف' : 'إضافة مصروف جديد';
ob_start();
?>
<div class="max-w-2xl mx-auto bg-white rounded-xl shadow p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6"><?php echo $pageTitle; ?></h2>

    <form action="<?php echo APP_URL; ?>/expenses/<?php echo $isEdit ? 'update/'.$expense['id'] : 'store'; ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="space-y-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">اسم البند</label>
                <input type="text" name="name" value="<?php echo $isEdit ? htmlspecialchars($expense['name']) : ''; ?>" required
                       class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">الفئة</label>
                <select name="category_id" required class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500">
                    <option value="">-- اختر الفئة --</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ($isEdit && $expense['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">الفرع المخصص (اختياري)</label>
                <select name="branch_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500">
                    <option value="">-- عام (لجميع الفروع) --</option>
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['id']; ?>" <?php echo ($isEdit && $expense['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($branch['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-gray-500 mt-1">إذا تركته فارغاً، سيكون البند متاحاً لجميع الفروع.</p>
            </div>

            <div class="pt-4 border-t mt-6 flex justify-end gap-2">
                <a href="<?php echo APP_URL; ?>/expenses" class="bg-gray-100 text-gray-700 px-6 py-2 rounded text-sm hover:bg-gray-200 transition">إلغاء</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded text-sm hover:bg-blue-700 transition">
                    <i class="fas fa-save"></i> حفظ
                </button>
            </div>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
