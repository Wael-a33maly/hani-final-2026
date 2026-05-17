<?php requireRole('admin'); $pageTitle = 'الأدوار'; ob_start(); ?>
<div class="bg-white rounded-xl shadow">
    <div class="p-5 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-700"><i class="fas fa-user-tag ml-2 text-indigo-500"></i>إدارة الأدوار</h2>
        <a href="<?= APP_URL ?>/role-permissions/roles/create" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> دور جديد
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs">
                <tr>
                    <th class="px-4 py-3 text-right">#</th>
                    <th class="px-4 py-3 text-right">الدور</th>
                    <th class="px-4 py-3 text-right hidden md:table-cell">الوصف</th>
                    <th class="px-4 py-3 text-center">الصلاحيات</th>
                    <th class="px-4 py-3 text-center">المستخدمون</th>
                    <th class="px-4 py-3 text-center hidden sm:table-cell">نظام</th>
                    <th class="px-4 py-3 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($roles as $r): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-500"><?= $r['id'] ?></td>
                    <td class="px-4 py-3">
                        <span class="font-bold text-gray-800"><?= htmlspecialchars($r['display_name']) ?></span>
                        <div class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($r['name']) ?></div>
                    </td>
                    <td class="px-4 py-3 text-gray-600 hidden md:table-cell"><?= htmlspecialchars($r['description'] ?? '—') ?></td>
                    <td class="px-4 py-3 text-center font-mono text-indigo-600 font-bold"><?= $r['permissions_count'] ?></td>
                    <td class="px-4 py-3 text-center"><?= $r['users_count'] ?></td>
                    <td class="px-4 py-3 text-center hidden sm:table-cell">
                        <?php if ($r['is_system']): ?>
                            <span class="bg-gray-200 text-gray-600 px-2 py-0.5 rounded text-xs">نظام</span>
                        <?php else: ?>
                            <span class="text-gray-300">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <a href="<?= APP_URL ?>/role-permissions/roles/edit/<?= $r['id'] ?>" class="text-blue-500 hover:text-blue-700" title="تعديل"><i class="fas fa-edit"></i></a>
                            <?php if (!$r['is_system'] && $r['users_count'] == 0): ?>
                            <form method="POST" action="<?= APP_URL ?>/role-permissions/roles/delete/<?= $r['id'] ?>" class="inline" onsubmit="return confirm('حذف الدور؟')">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700" title="حذف"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../../layouts/main.php'; ?>
