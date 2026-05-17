<?php requireLogin(); $pageTitle = 'استيراد عملاء من CSV'; ob_start(); ?>
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl shadow">
        <div class="p-5 border-b flex items-center gap-2">
            <i class="fas fa-file-import text-indigo-500"></i>
            <h2 class="text-lg font-bold text-gray-700">استيراد عملاء من ملف CSV</h2>
        </div>

        <div class="p-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6 text-sm text-blue-800">
                <p class="font-bold mb-2"><i class="fas fa-info-circle ml-1"></i>تعليمات الاستيراد:</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>قم بتحميل <a href="<?= APP_URL ?>/customers/import-sample" class="text-blue-700 underline font-bold">ملف العينة</a> واملأ بيانات العملاء فيه</li>
                    <li>الملف يجب أن يكون بصيغة CSV (يفتح بواسطة Excel)</li>
                    <li>العمود الأول "الاسم" إلزامي، باقي الأعمدة اختيارية</li>
                    <li>أكواد العملاء يتم إنشاؤها تلقائياً</li>
                    <li>يمكنك اختيار مندوب افتراضي لجميع العملاء المستوردين</li>
                </ul>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">اختر ملف CSV</label>
                    <input type="file" name="csv_file" accept=".csv,.txt" required
                           class="block w-full text-sm text-gray-600 border border-gray-300 rounded-lg cursor-pointer focus:outline-none file:bg-indigo-50 file:border-0 file:px-4 file:py-2 file:text-indigo-700 file:font-semibold hover:file:bg-indigo-100">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">المندوب الافتراضي (اختياري)</label>
                    <select name="default_sales_rep_id" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
                        <option value="">بدون مندوب</option>
                        <?php foreach($reps as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition text-sm flex items-center gap-2">
                        <i class="fas fa-upload"></i> استيراد
                    </button>
                    <a href="<?= APP_URL ?>/customers/import-sample" class="bg-emerald-600 text-white px-6 py-2 rounded-lg hover:bg-emerald-700 transition text-sm flex items-center gap-2">
                        <i class="fas fa-download"></i> تحميل ملف عينة
                    </a>
                    <a href="<?= APP_URL ?>/customers" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm">رجوع</a>
                </div>
            </form>
        </div>
    </div>

    <?php if (!empty($_SESSION['import_errors'])): ?>
    <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-4">
        <p class="font-bold text-red-700 text-sm mb-2"><i class="fas fa-exclamation-triangle ml-1"></i>تم تخطي بعض الأسطر بسبب أخطاء:</p>
        <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
            <?php foreach ($_SESSION['import_errors'] as $err): ?>
            <li><?= htmlspecialchars($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php unset($_SESSION['import_errors']); ?>
    <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
