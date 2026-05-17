<?php requireRole('admin'); $pageTitle = 'إدارة التحديثات'; ob_start(); ?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-700 flex items-center gap-2">
        <i class="fas fa-sync-alt text-purple-500"></i> إدارة التحديثات
    </h1>
    <a href="<?php echo APP_URL; ?>/updates/form"
       class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition flex items-center gap-2 text-sm">
        <i class="fas fa-upload"></i> رفع تحديث جديد
    </a>
</div>

<!-- حالة النظام -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow p-4">
        <span class="text-gray-500 text-xs block">الإصدار الحالي</span>
        <span class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($settings['app_version'] ?? '1.0.0'); ?></span>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <span class="text-gray-500 text-xs block">آخر تحديث</span>
        <span class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($settings['last_update_at'] ?? 'لم يتم'); ?></span>
    </div>
    <div class="bg-white rounded-xl shadow p-4">
        <span class="text-gray-500 text-xs block">آخر فحص</span>
        <span class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($settings['last_check_at'] ?? 'لم يتم'); ?></span>
    </div>
    <div class="bg-white rounded-xl shadow p-4 <?php echo $disk['is_low'] ? 'border border-red-300' : ''; ?>">
        <span class="text-gray-500 text-xs block">المساحة التخزينية</span>
        <span class="text-xl font-bold <?php echo $disk['is_low'] ? 'text-red-600' : 'text-gray-800'; ?>">
            <?php echo $disk['free_mb']; ?> MB
        </span>
        <?php if ($disk['is_low']): ?>
            <p class="text-xs text-red-500 mt-1">منخفضة - يرجى حذف النسخ القديمة</p>
        <?php endif; ?>
    </div>
</div>

<!-- جدول سجل التحديثات -->
<div class="bg-white rounded-xl shadow p-6">
    <h2 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-history text-gray-500"></i> سجل التحديثات
    </h2>

    <?php if (empty($migrations)): ?>
        <div class="text-center py-8 text-gray-400">
            <i class="fas fa-box-open text-5xl mb-3 block"></i>
            لا توجد تحديثات سابقة
        </div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b">
                        <th class="text-right p-3 font-medium">الإصدار</th>
                        <th class="text-right p-3 font-medium">الملف</th>
                        <th class="text-center p-3 font-medium">الحالة</th>
                        <th class="text-right p-3 font-medium">التاريخ</th>
                        <th class="text-right p-3 font-medium">المنفذ</th>
                        <th class="text-center p-3 font-medium">إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($migrations as $m): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="p-3 font-mono text-xs"><?php echo htmlspecialchars($m['version']); ?></td>
                            <td class="p-3 text-xs"><?php echo htmlspecialchars($m['zip_file']); ?></td>
                            <td class="p-3 text-center">
                                <?php
                                $statusColors = [
                                    'pending' => 'bg-yellow-100 text-yellow-700',
                                    'running' => 'bg-blue-100 text-blue-700',
                                    'completed' => 'bg-green-100 text-green-700',
                                    'failed' => 'bg-red-100 text-red-700',
                                    'rolled_back' => 'bg-gray-100 text-gray-700',
                                ];
                                $color = $statusColors[$m['status']] ?? 'bg-gray-100 text-gray-700';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-medium <?php echo $color; ?>">
                                    <?php
                                    $labels = ['pending' => 'معلق', 'running' => 'قيد التنفيذ', 'completed' => 'مكتمل', 'failed' => 'فشل', 'rolled_back' => 'تم الرجوع'];
                                    echo $labels[$m['status']] ?? $m['status'];
                                    ?>
                                </span>
                            </td>
                            <td class="p-3 text-xs"><?php echo htmlspecialchars($m['created_at']); ?></td>
                            <td class="p-3 text-xs"><?php echo htmlspecialchars($m['executed_by'] ?? '-'); ?></td>
                            <td class="p-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <?php if ($m['status'] === 'completed' && !empty($m['backup_path'])): ?>
                                        <form method="POST" action="<?php echo APP_URL; ?>/updates/rollback/<?php echo $m['id']; ?>"
                                              onsubmit="return confirm('هل أنت متأكد من الرجوع عن هذا التحديث؟ سيتم استعادة النسخة السابقة.');"
                                              class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs flex items-center gap-1" title="رجوع">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($m['status'] === 'failed' && !empty($m['error_message'])): ?>
                                        <span class="text-xs text-gray-400 cursor-help" title="<?php echo htmlspecialchars($m['error_message']); ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-300">-</span>
                                    <?php endif; ?>

                                    <form method="POST" action="<?php echo APP_URL; ?>/updates/delete/<?php echo $m['id']; ?>"
                                          onsubmit="return confirm('هل أنت متأكد من حذف سجل التحديث هذا؟');"
                                          class="inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="text-gray-400 hover:text-red-600 text-xs" title="حذف">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php if ($m['status'] === 'failed' && !empty($m['error_message'])): ?>
                            <tr class="bg-red-50">
                                <td colspan="6" class="p-3 text-xs text-red-700">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <?php echo htmlspecialchars($m['error_message']); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
