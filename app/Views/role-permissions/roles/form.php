<?php requireRole('admin'); $pageTitle = $role ? 'تعديل دور' : 'دور جديد'; ob_start(); ?>
<div class="bg-white rounded-xl shadow p-6 max-w-4xl mx-auto">
    <h2 class="text-xl font-bold text-gray-700 mb-6 flex items-center gap-2">
        <i class="fas fa-<?= $role ? 'edit' : 'plus' ?> text-indigo-500"></i>
        <?= $role ? 'تعديل دور' : 'دور جديد' ?>
    </h2>

    <form method="POST" action="<?= APP_URL ?>/role-permissions/roles/<?= $role ? 'update/' . $role['id'] : 'store' ?>">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">اسم الدور <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="<?= htmlspecialchars($role['name'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-400 outline-none" <?= $role ? 'readonly style="background:#f9fafb"' : 'required' ?>>
            </div>
            <div>
                <label class="block text-gray-600 text-sm font-medium mb-1">الاسم الظاهر <span class="text-red-500">*</span></label>
                <input type="text" name="display_name" value="<?= htmlspecialchars($role['display_name'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-400 outline-none" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-gray-600 text-sm font-medium mb-1">الوصف</label>
                <textarea name="description" rows="2" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-400 outline-none"><?= htmlspecialchars($role['description'] ?? '') ?></textarea>
            </div>
        </div>

        <h3 class="font-bold text-gray-700 mb-3 border-b pb-2">الصلاحيات</h3>

        <?php
        $grouped = [];
        foreach ($permissions as $p):
            $grouped[$p['module']][] = $p;
        endforeach;
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <?php foreach ($grouped as $module => $perms): ?>
            <div class="border rounded-lg p-4 bg-gray-50">
                <label class="flex items-center gap-2 mb-3 cursor-pointer" onclick="toggleModule(this, 'mod_<?= $module ?>')">
                    <input type="checkbox" class="module-check" data-module="mod_<?= $module ?>">
                    <span class="font-bold text-gray-700 text-sm"><?= htmlspecialchars($module) ?></span>
                </label>
                <div class="space-y-2" id="mod_<?= $module ?>">
                    <?php foreach ($perms as $p):
                        $checked = isset($rolePermIds) && in_array($p['id'], $rolePermIds);
                    ?>
                    <label class="flex items-center gap-2 text-sm cursor-pointer hover:text-indigo-600 transition">
                        <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" <?= $checked ? 'checked' : '' ?> class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span><?= htmlspecialchars($p['display_name']) ?></span>
                        <span class="text-xs text-gray-400 font-mono">(<?= htmlspecialchars($p['name']) ?>)</span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="<?= APP_URL ?>/role-permissions/roles" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 transition text-sm">إلغاء</a>
            <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-save"></i> حفظ
            </button>
        </div>
    </form>
</div>
<script>
function toggleModule(el, moduleId) {
    const checked = el.querySelector('.module-check').checked;
    document.querySelectorAll('#' + moduleId + ' input[type="checkbox"]').forEach(cb => cb.checked = checked);
}
document.querySelectorAll('.module-check').forEach(cb => {
    cb.addEventListener('change', function() {
        const moduleId = this.dataset.module;
        document.querySelectorAll('#' + moduleId + ' input[type="checkbox"]').forEach(c => c.checked = this.checked);
    });
});
</script>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../../layouts/main.php'; ?>
