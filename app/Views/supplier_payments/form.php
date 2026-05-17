<?php requireLogin(); $pageTitle = 'تسجيل دفعة لمورد'; ob_start(); ?>
<div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2 border-b pb-4">
        <i class="fas fa-money-bill-wave text-green-600"></i> تسجيل دفعة نقدية لمورد
    </h2>

    <form method="POST" action="<?= APP_URL ?>/supplier-payments/store" class="space-y-6">
        <?= csrf_field() ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">المورد المستلم *</label>
                <select name="supplier_id" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-green-400 outline-none select2" required>
                    <option value="">-- اختر المورد --</option>
                    <?php foreach($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2 font-bold">المبلغ المدفوع *</label>
                <div class="relative">
                    <input type="number" step="0.01" name="amount" class="w-full border border-gray-300 rounded-lg p-2.5 pr-10 focus:ring-2 focus:ring-green-400 outline-none font-bold text-lg text-red-600" required placeholder="0.00">
                    <span class="absolute left-3 top-3 text-gray-400">ج.م</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">تاريخ العملية *</label>
                <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-green-400 outline-none" required>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">طريقة الدفع</label>
                <select name="payment_type" class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-green-400 outline-none">
                    <option value="cash">نقدي (خزينة)</option>
                    <option value="vodafone">فودافون كاش</option>
                    <option value="instapay">InstaPay</option>
                    <option value="bank">تحويل بنكي</option>
                </select>
            </div>
        </div>
        
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">بيان الدفعة / ملاحظات</label>
            <textarea name="notes" rows="3" placeholder="اكتب تفاصيل إضافية عن الدفعة..." class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-green-400 outline-none"></textarea>
        </div>
        
        <div class="flex justify-end gap-3 pt-6 border-t mt-4">
            <a href="<?= APP_URL ?>/supplier-payments" class="bg-gray-200 text-gray-700 px-6 py-2.5 rounded-lg hover:bg-gray-300 transition text-center min-w-[120px]">إلغاء</a>
            <button type="submit" class="bg-green-600 text-white px-8 py-2.5 rounded-lg hover:bg-green-700 transition font-bold min-w-[120px]">تأكيد وحفظ الدفعة</button>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
