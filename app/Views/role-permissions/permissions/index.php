<?php requireRole('admin'); $pageTitle = 'الصلاحيات'; ob_start(); ?>
<div class="bg-white rounded-xl shadow">
    <div class="p-5 border-b">
        <h2 class="text-lg font-bold text-gray-700"><i class="fas fa-shield-alt ml-2 text-green-500"></i>قائمة الصلاحيات</h2>
        <p class="text-sm text-gray-500 mt-1">هذه الصلاحيات تُستخدم لبناء الأدوار. لا يمكن تعديلها أو حذفها لأنها أساس النظام.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-5">
        <?php
        $grouped = [];
        foreach ($permissions as $p):
            $grouped[$p['module']][] = $p;
        endforeach;
        ?>
        <?php foreach ($grouped as $module => $perms): ?>
        <div class="border rounded-lg p-4 bg-gray-50">
            <h4 class="font-bold text-gray-700 mb-3 text-sm border-b pb-2 flex items-center gap-2">
                <i class="fas fa-cube text-gray-400"></i> <?= htmlspecialchars($module) ?>
                <span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full"><?= count($perms) ?></span>
            </h4>
            <div class="space-y-1">
                <?php foreach ($perms as $p): ?>
                <div class="text-sm flex items-center gap-2 py-1">
                    <span class="w-2 h-2 rounded-full <?= $p['action'] === 'view' ? 'bg-blue-400' : ($p['action'] === 'create' ? 'bg-green-400' : ($p['action'] === 'edit' ? 'bg-orange-400' : ($p['action'] === 'delete' ? 'bg-red-400' : 'bg-gray-400'))) ?>"></span>
                    <span><?= htmlspecialchars($p['display_name']) ?></span>
                    <span class="text-xs text-gray-400 font-mono">(<?= htmlspecialchars($p['name']) ?>)</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../../layouts/main.php'; ?>
