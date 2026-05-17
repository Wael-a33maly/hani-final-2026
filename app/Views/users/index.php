<?php requireRole('admin'); $pageTitle = 'المستخدمون'; ob_start(); ?>

<div class="bg-white rounded-xl shadow">
    <div class="p-5 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-700">
            <i class="fas fa-users ml-2 text-blue-500"></i>قائمة المستخدمين
        </h2>
        <a href="<?php echo APP_URL; ?>/users/create"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> مستخدم جديد
        </a>
    </div>

    <!-- فلتر -->
    <div class="p-4 border-b bg-gray-50">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <input type="text" name="search" placeholder="بحث بالاسم أو اسم المستخدم" value="<?= htmlspecialchars($search ?? '') ?>" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
            <select name="role" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                <option value="">كل الأدوار</option>
                <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id'] ?>" <?= (isset($role) && $role == $r['id']) ? 'selected' : '' ?>><?= htmlspecialchars($r['display_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="branch_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                <option value="">كل الفروع</option>
                <?php foreach ($branches as $b): ?>
                <option value="<?= $b['id'] ?>" <?= (isset($branch_id) && $branch_id == $b['id']) ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition font-bold text-sm flex items-center justify-center gap-2">
                <i class="fas fa-search"></i> بحث
            </button>
            <a href="<?= APP_URL ?>/role-permissions/roles" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded-lg hover:bg-indigo-200 transition text-sm flex items-center justify-center gap-2">
                <i class="fas fa-user-tag"></i> إدارة الأدوار
            </a>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-right">#</th>
                    <th class="px-4 py-3 text-right">الاسم</th>
                    <th class="px-4 py-3 text-right">اسم المستخدم</th>
                    <th class="px-4 py-3 text-right hidden md:table-cell">الدور</th>
                    <th class="px-4 py-3 text-right hidden lg:table-cell">الفرع</th>
                    <th class="px-4 py-3 text-right hidden sm:table-cell">الهاتف</th>
                    <th class="px-4 py-3 text-right">الحالة</th>
                    <th class="px-4 py-3 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($users as $u): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 text-gray-500 whitespace-nowrap"><?php echo $u['id']; ?></td>
                    <td class="px-4 py-3 font-medium text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($u['full_name']); ?></td>
                    <td class="px-4 py-3 font-mono text-gray-600 whitespace-nowrap"><?php echo htmlspecialchars($u['username']); ?></td>
                    <td class="px-4 py-3 hidden md:table-cell whitespace-nowrap">
                        <?php if (!empty($u['role_display'])): ?>
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded text-xs font-medium"><?= htmlspecialchars($u['role_display']) ?></span>
                        <?php else: ?>
                            <span class="bg-gray-100 text-gray-500 px-2 py-0.5 rounded text-xs">بدون دور</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-gray-600 hidden lg:table-cell whitespace-nowrap"><?php echo htmlspecialchars($u['branch_name'] ?? '—'); ?></td>
                    <td class="px-4 py-3 text-gray-600 hidden sm:table-cell whitespace-nowrap"><?php echo htmlspecialchars($u['phone'] ?? '—'); ?></td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <?php if ($u['is_active']): ?>
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-xs font-medium">نشط</span>
                        <?php else: ?>
                            <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-medium">غير نشط</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center gap-3">
                            <a href="<?php echo APP_URL; ?>/users/edit/<?php echo $u['id']; ?>"
                               class="text-blue-500 hover:text-blue-700 transition" title="تعديل">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="<?= APP_URL ?>/role-permissions/users/permissions/<?= $u['id'] ?>"
                               class="text-indigo-500 hover:text-indigo-700 transition" title="الصلاحيات">
                                <i class="fas fa-shield-alt"></i>
                            </a>
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                            <form method="POST" action="<?php echo APP_URL; ?>/users/delete/<?php echo $u['id']; ?>"
                                  class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" class="text-red-500 hover:text-red-700 transition" title="حذف">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">لا يوجد مستخدمون</td></tr>
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
