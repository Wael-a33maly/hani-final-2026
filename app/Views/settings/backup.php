<?php requireRole('admin'); $pageTitle = 'استعادة نسخة احتياطية'; ob_start(); ?>

<div class="bg-white rounded-xl shadow p-6 max-w-lg mx-auto">
    <h2 class="text-lg font-bold text-gray-700 mb-5 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-upload text-indigo-500"></i> استعادة قاعدة البيانات
    </h2>

    <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded-lg p-4 mb-5 text-sm flex gap-2">
        <i class="fas fa-exclamation-triangle mt-0.5 flex-shrink-0"></i>
        <span>تحذير: استعادة ملف SQL ستستبدل قاعدة البيانات الحالية بالكامل. تأكد من وجود نسخة احتياطية حديثة.</span>
    </div>

    <form method="POST" action="<?php echo APP_URL; ?>/settings/import-backup" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <div class="mb-5">
            <label class="block text-gray-600 text-sm font-medium mb-2">اختر ملف SQL المُصدَّر مسبقاً</label>
            <input type="file" name="backup_file" accept=".sql"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" required>
            <p class="text-xs text-gray-400 mt-1">الملف يجب أن يكون بامتداد .sql</p>
        </div>
        <div class="flex justify-between items-center">
            <a href="<?php echo APP_URL; ?>/settings"
               class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                إلغاء
            </a>
            <button type="submit"
                    onclick="return confirm('هل أنت متأكد؟ سيتم استبدال قاعدة البيانات الحالية.')"
                    class="bg-indigo-600 text-white px-5 py-2 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-database"></i> استعادة
            </button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
