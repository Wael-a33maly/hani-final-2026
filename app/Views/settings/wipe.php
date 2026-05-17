<?php requireRole('admin'); $pageTitle = 'مسح البيانات'; ob_start(); ?>

<div class="bg-white rounded-xl shadow p-6 max-w-lg mx-auto border-2 border-red-300">
    <div class="text-center mb-6">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
            <i class="fas fa-exclamation-triangle text-4xl text-red-600"></i>
        </div>
        <h2 class="text-2xl font-bold text-red-700">تحذير! مسح كامل للبيانات</h2>
        <p class="text-gray-600 text-sm mt-2">هذه العملية <strong>لا يمكن التراجع عنها</strong>. سيتم مسح:</p>
    </div>

    <div class="bg-red-50 rounded-lg p-4 mb-5 text-sm text-gray-700">
        <div class="grid grid-cols-2 gap-1">
            <div class="flex items-center gap-1"><i class="fas fa-times text-red-400"></i> الفروع والمخازن</div>
            <div class="flex items-center gap-1"><i class="fas fa-times text-red-400"></i> المواد والأرصدة</div>
            <div class="flex items-center gap-1"><i class="fas fa-times text-red-400"></i> الموردين والمشتريات</div>
            <div class="flex items-center gap-1"><i class="fas fa-times text-red-400"></i> العملاء والمبيعات</div>
            <div class="flex items-center gap-1"><i class="fas fa-times text-red-400"></i> الأقساط والمدفوعات</div>
            <div class="flex items-center gap-1"><i class="fas fa-times text-red-400"></i> المصروفات وسندات الصرف</div>
            <div class="flex items-center gap-1"><i class="fas fa-times text-red-400"></i> تحويلات المخازن</div>
            <div class="flex items-center gap-1"><i class="fas fa-times text-red-400"></i> عهدة المندوبين</div>
        </div>
    </div>

    <div class="bg-green-50 border border-green-300 rounded-lg p-3 mb-5 text-sm text-green-700 flex items-center gap-2">
        <i class="fas fa-check-circle"></i>
        سيتم الاحتفاظ بـ: <strong>المستخدمين وإعدادات الشركة</strong>
    </div>

    <form method="POST" action="<?php echo APP_URL; ?>/settings/execute-wipe"
          onsubmit="return confirm('آخر تحذير! هل أنت متأكد تماماً من مسح جميع البيانات؟')">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <div class="mb-5">
            <label class="block text-gray-700 text-sm font-medium mb-2">
                أدخل كلمة مرور المدير لتأكيد عملية المسح
            </label>
            <input type="password" name="security_code"
                   class="w-full border-2 border-red-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-400"
                   placeholder="كلمة مرور المدير" required>
        </div>
        <div class="flex justify-between items-center">
            <a href="<?php echo APP_URL; ?>/settings"
               class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                إلغاء - عودة للإعدادات
            </a>
            <button type="submit"
                    class="bg-red-600 text-white px-5 py-2 rounded-lg hover:bg-red-700 transition text-sm flex items-center gap-2">
                <i class="fas fa-trash-alt"></i> نعم، امسح جميع البيانات
            </button>
        </div>
    </form>
</div>

<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
