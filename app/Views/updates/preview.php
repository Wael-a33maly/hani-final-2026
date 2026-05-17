<?php requireRole('admin'); $pageTitle = 'معاينة التحديث'; ob_start(); ?>

<div class="max-w-3xl mx-auto">

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-700 mb-5 flex items-center gap-2 border-b pb-3">
            <i class="fas fa-eye text-purple-500"></i> معاينة التحديث
        </h2>

        <!-- معلومات التحديث -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-gray-50 rounded-lg p-3">
                <span class="text-gray-500 text-xs block">رقم الإصدار</span>
                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($migration['version']); ?></span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <span class="text-gray-500 text-xs block">الملف</span>
                <span class="font-bold text-gray-800 text-sm"><?php echo htmlspecialchars($migration['zip_file'] ?? '-'); ?></span>
            </div>
        </div>

        <!-- تحذير -->
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded-lg p-4 mb-5 text-sm flex gap-2">
            <i class="fas fa-exclamation-triangle mt-0.5 flex-shrink-0"></i>
            <span>
                <strong>تنبيه:</strong> قبل تنفيذ التحديث، سيتم أخذ نسخة احتياطية كاملة من قاعدة البيانات والملفات.
                في حال فشل التحديث، يمكنك الرجوع للنسخة السابقة.
            </span>
        </div>

        <!-- قائمة الملفات -->
        <div class="mb-6">
            <h3 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-folder-open text-blue-500"></i> الملفات
            </h3>
            <?php if (empty($files) && empty($sqlFiles)): ?>
                <p class="text-gray-400 text-sm">لا توجد ملفات في هذا التحديث</p>
            <?php else: ?>
                <?php if (!empty($sqlFiles)): ?>
                    <div class="mb-3">
                        <p class="text-xs text-indigo-600 font-medium mb-2"><i class="fas fa-database"></i> ملفات SQL:</p>
                        <ul class="space-y-1">
                            <?php foreach ($sqlFiles as $f): ?>
                                <li class="text-sm bg-indigo-50 rounded px-3 py-1.5 text-indigo-700 font-mono text-xs"><?php echo htmlspecialchars($f); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if (!empty($files)): ?>
                    <div>
                        <p class="text-xs text-gray-600 font-medium mb-2"><i class="fas fa-file"></i> ملفات:</p>
                        <ul class="space-y-1 max-h-60 overflow-y-auto">
                            <?php foreach ($files as $f): ?>
                                <li class="text-sm bg-gray-50 rounded px-3 py-1.5 text-gray-600 font-mono text-xs"><?php echo htmlspecialchars($f); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- أزرار التنفيذ -->
        <div class="border-t pt-4 flex justify-between items-center">
            <a href="<?php echo APP_URL; ?>/updates"
               class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                <i class="fas fa-times"></i> إلغاء
            </a>
            <div class="flex gap-3">
                <a href="<?php echo APP_URL; ?>/updates/form"
                   class="bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600 transition text-sm">
                    <i class="fas fa-redo"></i> إعادة رفع
                </a>
                <form method="POST" action="<?php echo APP_URL; ?>/updates/execute"
                      onsubmit="return confirm('تأكيد تنفيذ التحديث؟\n\nسيتم أخذ نسخة احتياطية أولاً.\nفي حال فشل التحديث، يمكن الرجوع للنسخة السابقة.\n\nهل أنت متأكد؟')">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700 transition flex items-center gap-2 text-sm">
                        <i class="fas fa-play"></i> تنفيذ التحديث
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
