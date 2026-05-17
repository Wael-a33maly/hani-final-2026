<?php requireRole('admin'); $pageTitle = 'إعدادات التحديثات'; ob_start(); ?>

<div class="max-w-2xl mx-auto">

    <div class="bg-white rounded-xl shadow p-6 mb-6">
        <h2 class="text-lg font-bold text-gray-700 mb-5 flex items-center gap-2 border-b pb-3">
            <i class="fas fa-sync-alt text-purple-500"></i> إعدادات التحديثات
        </h2>

        <form method="POST" action="<?php echo APP_URL; ?>/settings/update-settings">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="space-y-5">

                <!-- تفعيل التحديثات التلقائية -->
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-gray-700 font-medium">تفعيل التحديثات التلقائية</label>
                        <p class="text-xs text-gray-400">السماح للنظام بالتحقق من التحديثات وتطبيقها تلقائياً</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_updates" value="1" <?php echo ($settings['auto_updates'] ?? 0) ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <!-- التحقق التلقائي من التحديثات -->
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-gray-700 font-medium">التحقق التلقائي من التحديثات</label>
                        <p class="text-xs text-gray-400">التحقق من وجود تحديث جديد بشكل يومي</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_check_updates" value="1" <?php echo ($settings['auto_check_updates'] ?? 1) ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <!-- إشعار الأدمن عند توفر تحديث جديد -->
                <div class="flex items-center justify-between">
                    <div>
                        <label class="text-gray-700 font-medium">إشعار الأدمن عند توفر تحديث جديد</label>
                        <p class="text-xs text-gray-400">إرسال إشعار (بريد/إشعار داخلي) عند توفر تحديث</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="notify_admin_update" value="1" <?php echo ($settings['notify_admin_update'] ?? 1) ? 'checked' : ''; ?> class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                    </label>
                </div>

                <hr class="border-gray-200">

                <!-- الحد الأقصى للنسخ الاحتياطية المحفوظة -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-1">الحد الأقصى للنسخ الاحتياطية المحفوظة</label>
                    <p class="text-xs text-gray-400 mb-2">عدد النسخ الاحتياطية التي يتم الاحتفاظ بها قبل التحديثات (سيتم حذف الأقدم تلقائياً)</p>
                    <input type="number" name="max_backups" value="<?php echo htmlspecialchars($settings['max_backups'] ?? 5); ?>"
                           min="1" max="50"
                           class="w-32 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>

                <!-- حجم التحديث الأقصى المسموح -->
                <div>
                    <label class="block text-gray-700 text-sm font-medium mb-1">حجم التحديث الأقصى المسموح</label>
                    <p class="text-xs text-gray-400 mb-2">الحجم الأقصى لملف التحديث (بالميجابايت)</p>
                    <div class="flex items-center gap-2">
                        <input type="number" name="max_update_size" value="<?php echo htmlspecialchars($settings['max_update_size'] ?? 512); ?>"
                               min="10" max="2048"
                               class="w-32 border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-400">
                        <span class="text-sm text-gray-500">ميجابايت</span>
                    </div>
                </div>

            </div>

            <div class="mt-6 pt-4 border-t border-gray-200 flex items-center gap-3">
                <button type="submit" class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700 transition flex items-center gap-2 text-sm">
                    <i class="fas fa-save"></i> حفظ الإعدادات
                </button>
                <a href="<?php echo APP_URL; ?>/settings"
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                    إلغاء
                </a>
            </div>
        </form>
    </div>

    <!-- معلومات النظام -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold text-gray-700 mb-4 flex items-center gap-2 border-b pb-3">
            <i class="fas fa-info-circle text-blue-500"></i> معلومات النظام
        </h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div class="bg-gray-50 rounded-lg p-3">
                <span class="text-gray-500 block">الإصدار الحالي</span>
                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($settings['app_version'] ?? '1.0.0'); ?></span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <span class="text-gray-500 block">آخر تحديث</span>
                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($settings['last_update_at'] ?? 'لم يتم'); ?></span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <span class="text-gray-500 block">آخر فحص</span>
                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($settings['last_check_at'] ?? 'لم يتم'); ?></span>
            </div>
            <div class="bg-gray-50 rounded-lg p-3">
                <span class="text-gray-500 block">عدد النسخ الاحتياطية</span>
                <span class="font-bold text-gray-800"><?php echo htmlspecialchars($settings['backup_count'] ?? '0'); ?></span>
            </div>
        </div>
    </div>

</div>

<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
