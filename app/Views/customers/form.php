<?php requireLogin(); $pageTitle = $customer ? 'تعديل عميل' : 'إضافة عميل'; ob_start(); ?>
<div class="bg-white rounded-lg shadow p-6 max-w-5xl mx-auto" x-data="customerForm()">
    <h2 class="text-xl font-bold mb-6"><?= $customer ? 'تعديل عميل' : 'إضافة عميل جديد' ?></h2>
    <form method="POST" action="<?= APP_URL ?>/customers/<?= $customer ? 'update/'.$customer['id'] : 'store' ?>">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="opening_installments_json" :value="JSON.stringify(openingInstallments)">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div><label>كود العميل</label><input type="text" name="code" value="<?= htmlspecialchars($customer['code'] ?? '') ?>" class="w-full border rounded px-3 py-2" readonly><small class="text-gray-500">يتولد تلقائياً</small></div>
            <div><label>الاسم <span class="text-red-600">*</span></label><input type="text" name="name" value="<?= htmlspecialchars($customer['name'] ?? '') ?>" required class="w-full border rounded px-3 py-2"></div>
            <div><label>رقم الهاتف</label><input type="text" name="phone" value="<?= htmlspecialchars($customer['phone'] ?? '') ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label>المنطقة</label><input type="text" name="area" value="<?= htmlspecialchars($customer['area'] ?? '') ?>" class="w-full border rounded px-3 py-2"></div>
            <div class="md:col-span-2"><label>العنوان</label><textarea name="address" rows="2" class="w-full border rounded px-3 py-2"><?= htmlspecialchars($customer['address'] ?? '') ?></textarea></div>
            <div><label>المندوب</label><select name="sales_rep_id" class="w-full border rounded px-3 py-2"><option value="">بدون مندوب</option><?php foreach($reps as $rep): ?><option value="<?= $rep['id'] ?>" <?= (($customer['sales_rep_id'] ?? '') == $rep['id']) ? 'selected' : '' ?>><?= htmlspecialchars($rep['full_name']) ?></option><?php endforeach; ?></select></div>
        </div>
        
        <h3 class="text-md font-bold border-b pb-1 mb-3 mt-4">رصيد أول المدة (أقساط سابقة)</h3>
        <div class="mb-2">
            <template x-for="(inst, idx) in openingInstallments" :key="idx">
                <div class="flex flex-wrap gap-3 mb-2 items-end bg-gray-50 p-2 rounded">
                    <div class="w-40"><label class="text-sm">تاريخ القسط</label><input type="date" x-model="inst.date" class="w-full border rounded px-2 py-1"></div>
                    <div class="w-36"><label class="text-sm">المبلغ</label><input type="number" step="0.01" x-model="inst.amount" @input="updateTotal" class="w-full border rounded px-2 py-1"></div>
                    <div class="flex-1"><label class="text-sm">ملاحظات</label><input type="text" x-model="inst.notes" class="w-full border rounded px-2 py-1"></div>
                    <button type="button" @click="removeOpeningInstallment(idx)" class="text-red-600"><i class="fas fa-trash"></i></button>
                </div>
            </template>
            <button type="button" @click="addOpeningInstallment()" class="bg-gray-200 px-3 py-1 rounded text-sm"><i class="fas fa-plus"></i> إضافة قسط افتتاحي</button>
            <div class="text-right mt-2"><strong>إجمالي رصيد أول المدة: </strong><span x-text="totalOpeningAmount.toFixed(2)"></span></div>
        </div>
        
        <div class="flex justify-end mt-6 gap-2">
            <a href="<?= APP_URL ?>/customers" class="bg-gray-300 px-4 py-2 rounded">إلغاء</a>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">حفظ</button>
        </div>
    </form>
</div>
<script>
function customerForm() {
    return {
        openingInstallments: <?php echo json_encode($opening_installments ?? []); ?>,
        addOpeningInstallment() { this.openingInstallments.push({date: new Date().toISOString().slice(0,10), amount: 0, notes: ''}); },
        removeOpeningInstallment(idx) { this.openingInstallments.splice(idx, 1); },
        get totalOpeningAmount() { return this.openingInstallments.reduce((sum, i) => sum + (parseFloat(i.amount) || 0), 0); }
    }
}
</script>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
