<?php requireRole('admin'); $pageTitle = 'إعدادات الشركة'; ob_start(); ?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

    <!-- بيانات الشركة -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold text-gray-700 mb-5 flex items-center gap-2 border-b pb-3">
            <i class="fas fa-building text-blue-500"></i> بيانات الشركة
        </h2>
        <form method="POST" action="<?php echo APP_URL; ?>/settings/update-company">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="mb-4">
                <label class="block text-gray-600 text-sm font-medium mb-1">اسم الشركة</label>
                <input type="text" name="company_name"
                       value="<?php echo htmlspecialchars($settings['company_name'] ?? ''); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="mb-4">
                <label class="block text-gray-600 text-sm font-medium mb-1">رقم الهاتف</label>
                <input type="text" name="phone"
                       value="<?php echo htmlspecialchars($settings['phone'] ?? ''); ?>"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>
            <div class="mb-4">
                <label class="block text-gray-600 text-sm font-medium mb-1">العنوان</label>
                <textarea name="address" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400 resize-none"><?php echo htmlspecialchars($settings['address'] ?? ''); ?></textarea>
            </div>
            <div class="grid grid-cols-3 gap-3 mb-4">
                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1"><i class="fab fa-whatsapp text-green-500"></i> واتساب</label>
                    <input type="text" name="whatsapp" value="<?php echo htmlspecialchars($settings['whatsapp'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1"><i class="fab fa-facebook text-blue-600"></i> فيسبوك</label>
                    <input type="text" name="facebook" value="<?php echo htmlspecialchars($settings['facebook'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-gray-600 text-sm font-medium mb-1"><i class="fab fa-instagram text-pink-500"></i> انستجرام</label>
                    <input type="text" name="instagram" value="<?php echo htmlspecialchars($settings['instagram'] ?? ''); ?>"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-5 py-2 rounded-lg hover:bg-blue-700 transition flex items-center gap-2 text-sm">
                <i class="fas fa-save"></i> حفظ البيانات
            </button>
        </form>
    </div>

    <!-- رفع الشعار -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold text-gray-700 mb-5 flex items-center gap-2 border-b pb-3">
            <i class="fas fa-image text-green-500"></i> شعار الشركة
        </h2>

        <?php if (!empty($settings['logo_path'])): ?>
        <div class="mb-5 text-center bg-gray-50 rounded-lg p-4">
            <img src="<?php echo APP_URL . '/' . htmlspecialchars($settings['logo_path']); ?>"
                 alt="شعار الشركة" class="max-h-36 mx-auto object-contain">
            <p class="text-xs text-gray-400 mt-2">الشعار الحالي</p>
        </div>
        <?php else: ?>
        <div class="mb-5 text-center bg-gray-50 rounded-lg p-8 text-gray-400">
            <i class="fas fa-image text-5xl mb-2 block"></i>
            لا يوجد شعار مرفوع
        </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo APP_URL; ?>/settings/upload-logo" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <div class="mb-4">
                <label class="block text-gray-600 text-sm font-medium mb-1">اختر صورة (JPEG, PNG, GIF — حد أقصى 2MB)</label>
                <input type="file" name="logo" accept="image/jpeg,image/png,image/gif"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
            </div>
            <button type="submit" class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-2 text-sm">
                <i class="fas fa-upload"></i> رفع الشعار
            </button>
        </form>
    </div>
</div>

<!-- النسخ الاحتياطي + مسح البيانات -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

    <!-- التحديثات -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold text-gray-700 mb-3 flex items-center gap-2 border-b pb-3">
            <i class="fas fa-sync-alt text-purple-500"></i> التحديثات
        </h2>
        <p class="text-sm text-gray-500 mb-4">إعدادات التحديثات التلقائية والنسخ الاحتياطية وحجم التحديث المسموح.</p>
        <a href="<?php echo APP_URL; ?>/settings/update-settings"
           class="inline-flex items-center gap-2 bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700 transition text-sm">
            <i class="fas fa-cog"></i> إعدادات التحديثات
        </a>
    </div>

    <!-- النسخ الاحتياطي -->
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold text-gray-700 mb-3 flex items-center gap-2 border-b pb-3">
            <i class="fas fa-database text-indigo-500"></i> النسخ الاحتياطي
        </h2>
        <p class="text-sm text-gray-500 mb-4">تصدير نسخة كاملة من قاعدة البيانات أو استعادة نسخة سابقة.</p>
        <div class="flex flex-wrap gap-3">
            <form method="POST" action="<?php echo APP_URL; ?>/settings/export-backup">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center gap-2">
                    <i class="fas fa-download"></i> تصدير SQL
                </button>
            </form>
            <a href="<?php echo APP_URL; ?>/settings/backup"
               class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-upload"></i> استعادة من ملف
            </a>
        </div>
    </div>

    <!-- مسح البيانات -->
    <div class="bg-white rounded-xl shadow p-6 border border-red-200">
        <h2 class="text-lg font-bold text-red-700 mb-3 flex items-center gap-2 border-b border-red-200 pb-3">
            <i class="fas fa-trash-alt text-red-500"></i> مسح البيانات
        </h2>
        <p class="text-sm text-gray-500 mb-4">
            مسح جميع البيانات (عملاء، فواتير، مواد...) مع الاحتفاظ بالمستخدمين والإعدادات.
            <span class="text-red-600 font-semibold">لا يمكن التراجع!</span>
        </p>
        <a href="<?php echo APP_URL; ?>/settings/wipe"
           class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm flex items-center gap-2 w-fit">
            <i class="fas fa-exclamation-triangle"></i> مسح البيانات
        </a>
    </div>

</div>

<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
