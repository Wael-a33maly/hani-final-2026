<?php requireRole('admin'); $pageTitle = $warehouse ? 'تعديل مخزن' : 'إضافة مخزن'; ob_start(); ?>
<div class="bg-white rounded-xl shadow p-6 max-w-2xl mx-auto">
    <h2 class="text-xl font-bold text-gray-700 mb-6 flex items-center gap-2">
        <i class="fas fa-warehouse text-blue-500"></i>
        <?php echo $warehouse ? 'تعديل مخزن' : 'إضافة مخزن جديد'; ?>
    </h2>

    <form method="POST" action="<?php echo APP_URL; ?>/warehouses/<?php echo $warehouse ? 'update/' . $warehouse['id'] : 'store'; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">اسم المخزن <span class="text-red-500">*</span></label>
                <input type="text" name="name"
                       value="<?php echo htmlspecialchars($warehouse['name'] ?? ''); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                       required>
            </div>

            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">الفرع التابع له <span class="text-red-500">*</span></label>
                <select name="branch_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400" required>
                    <option value="">-- اختر الفرع --</option>
                    <?php foreach ($branches as $b): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo ($warehouse['branch_id'] ?? '') == $b['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">العنوان</label>
                <textarea name="address" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"><?php echo htmlspecialchars($warehouse['address'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
            <a href="<?php echo APP_URL; ?>/warehouses"
               class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                إلغاء
            </a>
            <button type="submit"
                    class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-save"></i> حفظ
            </button>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
