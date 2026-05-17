<?php requireRole('admin'); $pageTitle = $user ? 'تعديل مستخدم' : 'إضافة مستخدم'; ob_start(); ?>

<div class="bg-white rounded-xl shadow p-6 max-w-2xl mx-auto">
    <h2 class="text-xl font-bold text-gray-700 mb-6 flex items-center gap-2">
        <i class="fas fa-user-<?php echo $user ? 'edit' : 'plus'; ?> text-blue-500"></i>
        <?php echo $user ? 'تعديل مستخدم' : 'إضافة مستخدم جديد'; ?>
    </h2>

    <form method="POST" action="<?php echo APP_URL; ?>/users/<?php echo $user ? 'update/' . $user['id'] : 'store'; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <label class="block text-gray-600 text-sm font-medium mb-1">الاسم كاملاً <span class="text-red-500">*</span></label>
                <input type="text" name="full_name"
                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
                       required>
            </div>

            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">اسم المستخدم <span class="text-red-500">*</span></label>
                <input type="text" name="username"
                       value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition font-mono"
                       <?php echo $user ? 'readonly style="background:#f9fafb;cursor:not-allowed"' : 'required'; ?>>
            </div>

            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">
                    كلمة المرور <?php echo $user ? '<span class="text-gray-400 text-xs">(اتركه فارغاً إذا لم تريد التغيير)</span>' : '<span class="text-red-500">*</span>'; ?>
                </label>
                <input type="password" name="password" minlength="6"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition"
                       <?php echo $user ? '' : 'required'; ?>>
            </div>

            <!-- الدور (RBAC) -->
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">الدور</label>
                <select name="role_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    <option value="">-- بدون دور --</option>
                    <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= ($user['role_id'] ?? '') == $r['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['display_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- الفرع الأساسي -->
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">الفرع الأساسي</label>
                <select name="branch_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                    <option value="">-- بدون فرع --</option>
                    <?php foreach ($branches as $branch): ?>
                    <option value="<?php echo $branch['id']; ?>"
                            <?php echo ($user['branch_id'] ?? '') == $branch['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($branch['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- الفروع المتعددة -->
            <div class="md:col-span-2">
                <label class="block text-gray-600 text-sm font-medium mb-1">فروع إضافية (يمكن للمستخدم الوصول لها)</label>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2 p-3 bg-gray-50 rounded-lg border">
                    <?php foreach ($branches as $branch): ?>
                    <label class="flex items-center gap-2 text-sm cursor-pointer hover:text-blue-600 transition">
                        <input type="checkbox" name="branches[]" value="<?= $branch['id'] ?>"
                               <?= (isset($userBranchIds) && in_array($branch['id'], $userBranchIds)) ? 'checked' : '' ?>
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span><?= htmlspecialchars($branch['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- الهاتف -->
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">رقم الهاتف</label>
                <input type="text" name="phone"
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
            </div>

            <!-- خيارات -->
            <div>
                <label class="flex items-center gap-2 cursor-pointer mt-6">
                    <input type="checkbox" name="can_view_all_branches" value="1"
                           <?= ($user['can_view_all_branches'] ?? 0) ? 'checked' : '' ?>
                           class="w-4 h-4 text-purple-600 rounded border-gray-300">
                    <span class="text-gray-600 text-sm font-medium">يرى جميع الفروع</span>
                </label>
            </div>

            <div>
                <label class="flex items-center gap-2 cursor-pointer mt-6">
                    <input type="checkbox" name="is_active" value="1"
                           <?= (!isset($user['is_active']) || $user['is_active']) ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 rounded border-gray-300">
                    <span class="text-gray-600 text-sm font-medium">حساب نشط</span>
                </label>
            </div>
        </div>

        <!-- عمولة التحصيل -->
        <div x-data="{ role: '<?= $user['role_id'] ?? '' ?>' }" class="border-t pt-4 mt-4">
            <h3 class="font-bold text-gray-700 mb-3">💰 إعدادات عمولة التحصيل</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1">نسبة عمولة التحصيل %</label>
                    <div class="relative">
                        <input type="number" name="collection_commission_rate" step="0.01" min="0" max="100" placeholder="0.00"
                               value="<?= $user['collection_commission_rate'] ?? 0 ?>"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 pl-10 focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">💡 النسبة المئوية من كل مبلغ يحصّله المندوب من الأقساط</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
            <a href="<?php echo APP_URL; ?>/users" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 transition text-sm">إلغاء</a>
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-save"></i> حفظ
            </button>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
