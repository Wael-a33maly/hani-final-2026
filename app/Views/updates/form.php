<?php requireRole('admin'); $pageTitle = 'رفع تحديث جديد'; ob_start(); ?>

<div class="max-w-lg mx-auto">
    <div class="bg-white rounded-xl shadow p-6">
        <h2 class="text-lg font-bold text-gray-700 mb-5 flex items-center gap-2 border-b pb-3">
            <i class="fas fa-upload text-purple-500"></i> رفع ملف التحديث
        </h2>

        <div class="bg-blue-50 border border-blue-200 text-blue-700 rounded-lg p-4 mb-5 text-sm flex gap-2">
            <i class="fas fa-shield-alt mt-0.5 flex-shrink-0"></i>
            <span>
                سيتم التحقق من ملف ZIP قبل الرفع. الحد الأقصى للحجم:
                <strong><?php echo htmlspecialchars($settings['max_update_size'] ?? 512); ?> MB</strong>.
                الملفات الحساسة (config.php, .env) محظورة.
            </span>
        </div>

        <form method="POST" action="<?php echo APP_URL; ?>/updates/upload" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="mb-5">
                <label class="block text-gray-600 text-sm font-medium mb-2">اختر ملف التحديث (ZIP)</label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-purple-400 transition cursor-pointer"
                     onclick="document.getElementById('update_file').click()">
                    <i class="fas fa-file-archive text-4xl text-gray-300 mb-2 block"></i>
                    <p class="text-sm text-gray-500">اضغط لاختيار ملف أو اسحبه وأفلته هنا</p>
                    <p class="text-xs text-gray-400 mt-1">ZIP فقط</p>
                </div>
                <input type="file" name="update_file" id="update_file" accept=".zip"
                       class="hidden" required
                       onchange="document.querySelector('label').textContent = this.files[0].name">
                <p class="text-xs text-gray-400 mt-1" id="file_name">لم يتم اختيار ملف</p>
            </div>

            <div class="flex justify-between items-center">
                <a href="<?php echo APP_URL; ?>/updates"
                   class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                    إلغاء
                </a>
                <button type="submit"
                        class="bg-purple-600 text-white px-5 py-2 rounded-lg hover:bg-purple-700 transition flex items-center gap-2 text-sm">
                    <i class="fas fa-check-circle"></i> رفع ومعاينة
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('update_file').addEventListener('change', function() {
    document.getElementById('file_name').textContent = this.files[0] ? this.files[0].name : 'لم يتم اختيار ملف';
});
</script>

<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
