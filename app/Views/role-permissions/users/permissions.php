<?php requireRole('admin'); $pageTitle = 'صلاحيات ' . htmlspecialchars($user['full_name']); ob_start(); ?>
<div class="bg-white rounded-xl shadow p-6 max-w-5xl mx-auto">
    <h2 class="text-xl font-bold text-gray-700 mb-2 flex items-center gap-2">
        <i class="fas fa-user-shield text-indigo-500"></i>
        صلاحيات: <?= htmlspecialchars($user['full_name']) ?>
    </h2>
    <p class="text-sm text-gray-500 mb-6">
        الدور: <strong class="text-indigo-600"><?= htmlspecialchars($user['role_name'] ?? 'بدون دور') ?></strong>
        — الصلاحيات المظللة بالأخضر هي صلاحيات الدور الأساسية. يمكنك إضافة صلاحيات إضافية (منح) أو حظر صلاحيات (رفض).
    </p>

    <form method="POST" action="<?= APP_URL ?>/role-permissions/users/permissions/update/<?= $user['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

        <?php
        $grouped = [];
        foreach ($permissions as $p):
            $grouped[$p['module']][] = $p;
        endforeach;
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($grouped as $module => $perms): ?>
            <div class="border rounded-lg p-4 <?= in_array($perms[0]['id'] ?? 0, $rolePerms) ? 'bg-green-50 border-green-200' : 'bg-gray-50' ?>">
                <h4 class="font-bold text-gray-700 text-sm mb-3 border-b pb-2 flex items-center gap-2">
                    <?= htmlspecialchars($module) ?>
                    <?php if (in_array($perms[0]['id'] ?? 0, $rolePerms)): ?>
                        <span class="text-xs bg-green-200 text-green-700 px-2 py-0.5 rounded-full">من الدور</span>
                    <?php endif; ?>
                </h4>
                <div class="space-y-2">
                    <?php foreach ($perms as $p):
                        $isRolePerm = in_array($p['id'], $rolePerms);
                        $userType = $userPermsData[$p['id']] ?? null;
                    ?>
                    <div class="flex items-center justify-between text-sm py-1 <?= $isRolePerm ? 'text-green-800' : '' ?>">
                        <span><?= htmlspecialchars($p['display_name'])?></span>
                        <div class="flex gap-2 items-center">
                            <?php if ($isRolePerm): ?>
                                <label class="text-xs text-gray-500"><input type="checkbox" name="deny[]" value="<?= $p['id'] ?>" <?= $userType === 'deny' ? 'checked' : '' ?> class="rounded border-red-300 text-red-600"> رفض</label>
                            <?php else: ?>
                                <label class="text-xs text-blue-600"><input type="checkbox" name="grant[]" value="<?= $p['id'] ?>" <?= $userType === 'grant' ? 'checked' : '' ?> class="rounded border-blue-300 text-blue-600"> منح</label>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
            <a href="<?= APP_URL ?>/users" class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 transition text-sm">إلغاء</a>
            <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-save"></i> حفظ الصلاحيات
            </button>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../../layouts/main.php'; ?>
