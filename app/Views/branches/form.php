<?php requireRole('admin'); $pageTitle = $branch ? 'تعديل فرع' : 'إضافة فرع'; ob_start(); ?>

<div class="bg-white rounded-xl shadow p-6 max-w-2xl mx-auto">
    <h2 class="text-xl font-bold text-gray-700 mb-6 flex items-center gap-2">
        <i class="fas fa-store text-green-500"></i>
        <?php echo $branch ? 'تعديل فرع' : 'إضافة فرع جديد'; ?>
    </h2>

    <form method="POST" action="<?php echo APP_URL; ?>/branches/<?php echo $branch ? 'update/' . $branch['id'] : 'store'; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- كود الفرع (تلقائي للإضافة - يدوي للتعديل) -->
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">كود الفرع</label>
                <?php if ($branch): ?>
                <input type="text" name="code"
                       value="<?php echo htmlspecialchars($branch['code']); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 transition font-mono"
                       required>
                <?php else: ?>
                <input type="text" readonly
                       value="يتولد تلقائياً عند الحفظ"
                       class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 font-mono text-gray-500 cursor-not-allowed">
                <?php endif; ?>
            </div>

            <!-- اسم الفرع -->
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">اسم الفرع <span class="text-red-500">*</span></label>
                <input type="text" name="name"
                       value="<?php echo htmlspecialchars($branch['name'] ?? ''); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 transition"
                       required placeholder="مثال: فرع القاهرة">
            </div>

            <!-- المدير -->
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">مدير الفرع</label>
                <select name="manager_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 transition">
                    <option value="">-- بدون مدير --</option>
                    <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>"
                            <?php echo ($branch['manager_id'] ?? '') == $user['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- هاتف الفرع -->
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">هاتف الفرع</label>
                <input type="text" name="phone"
                       value="<?php echo htmlspecialchars($branch['phone'] ?? ''); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 transition"
                       placeholder="01xxxxxxxxx">
            </div>

            <!-- العنوان -->
            <div class="md:col-span-2">
                <label class="block text-gray-600 text-sm font-medium mb-1">العنوان</label>
                <textarea name="address" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-400 transition resize-none"
                          placeholder="عنوان الفرع التفصيلي"><?php echo htmlspecialchars($branch['address'] ?? ''); ?></textarea>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
            <a href="<?php echo APP_URL; ?>/branches"
               class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                إلغاء
            </a>
            <button type="submit"
                    class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-save"></i> حفظ
            </button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
