<?php requireRole('admin'); $pageTitle = $supplier ? 'تعديل مورد' : 'إضافة مورد'; ob_start(); ?>
<div class="bg-white rounded-lg shadow p-6 max-w-2xl mx-auto">
    <h2 class="text-xl font-bold mb-6"><?= $supplier ? 'تعديل مورد' : 'إضافة مورد جديد' ?></h2>
    <form method="POST" action="<?= APP_URL ?>/suppliers/<?= $supplier ? 'update/'.$supplier['id'] : 'store' ?>">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><label>الكود</label><input type="text" name="code" value="<?= htmlspecialchars($supplier['code'] ?? '') ?>" class="w-full border rounded px-3 py-2" readonly><small>يتولد تلقائياً</small></div>
            <div><label>الاسم *</label><input type="text" name="name" value="<?= htmlspecialchars($supplier['name'] ?? '') ?>" required class="w-full border rounded px-3 py-2"></div>
            <div><label>الهاتف</label><input type="text" name="phone" value="<?= htmlspecialchars($supplier['phone'] ?? '') ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label>العنوان</label><textarea name="address" rows="2" class="w-full border rounded px-3 py-2"><?= htmlspecialchars($supplier['address'] ?? '') ?></textarea></div>
        </div>
        
        <h3 class="text-md font-bold border-b pb-1 mb-3 mt-4">رصيد أول المدة</h3>
        <div class="grid grid-cols-2 gap-4">
            <div><label>تاريخ الرصيد</label><input type="date" name="opening_date" value="<?= $supplier_opening_date ?? date('Y-m-d') ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label>المبلغ (رصيد لصالح المورد)</label><input type="number" step="0.01" name="opening_amount" value="<?= $supplier_opening_amount ?? 0 ?>" class="w-full border rounded px-3 py-2"></div>
        </div>
        <p class="text-xs text-gray-500 mt-1">المبلغ الموجب يعني أن المورد له علينا (ذمة), السالب يعني لديه رصيد لدينا.</p>
        
        <div class="flex justify-end mt-6 gap-2">
            <a href="<?= APP_URL ?>/suppliers" class="bg-gray-300 px-4 py-2 rounded">إلغاء</a>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">حفظ</button>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
