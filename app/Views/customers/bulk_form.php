<?php requireLogin(); $pageTitle = 'إضافة عملاء مجمعين'; ob_start(); ?>
<div class="bg-white rounded-lg shadow p-6 max-w-7xl mx-auto" x-data="bulkCustomers()">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold flex items-center gap-2">
            <i class="fas fa-users text-blue-600"></i> إضافة عملاء مجمعين
        </h2>
        <span class="text-sm text-gray-500" x-text="`عدد العملاء: ${customers.length}`"></span>
    </div>

    <form method="POST" action="<?= APP_URL ?>/customers/bulk-store">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="customers_json" :value="JSON.stringify(customers)">

        <!-- جدول الإدخال -->
        <div class="overflow-x-auto border rounded-lg">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-3 py-2 w-20 text-center">#</th>
                        <th class="px-3 py-2">كود العميل</th>
                        <th class="px-3 py-2">الاسم <span class="text-red-600">*</span></th>
                        <th class="px-3 py-2">رقم الهاتف</th>
                        <th class="px-3 py-2">المنطقة</th>
                        <th class="px-3 py-2">العنوان</th>
                        <th class="px-3 py-2">المندوب</th>
                        <th class="px-3 py-2 w-16"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(c, idx) in customers" :key="idx">
                        <tr class="border-b hover:bg-gray-50">
                            <td class="px-3 py-2 text-center text-gray-500" x-text="idx + 1"></td>
                            <td class="px-3 py-2">
                                <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded" x-text="c.code"></span>
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" x-model="c.name" @input="updateRow(idx)" required
                                       class="w-full border rounded px-2 py-1.5 text-sm"
                                       placeholder="اسم العميل">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" x-model="c.phone" @input="updateRow(idx)"
                                       class="w-full border rounded px-2 py-1.5 text-sm"
                                       placeholder="رقم الهاتف">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" x-model="c.area" @input="updateRow(idx)"
                                       class="w-full border rounded px-2 py-1.5 text-sm"
                                       placeholder="المنطقة">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" x-model="c.address" @input="updateRow(idx)"
                                       class="w-full border rounded px-2 py-1.5 text-sm"
                                       placeholder="العنوان">
                            </td>
                            <td class="px-3 py-2">
                                <select x-model="c.sales_rep_id" @change="updateRow(idx)"
                                        class="w-full border rounded px-2 py-1.5 text-sm">
                                    <option value="">بدون</option>
                                    <?php foreach($reps as $rep): ?>
                                        <option value="<?= $rep['id'] ?>"><?= htmlspecialchars($rep['full_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button type="button" @click="removeRow(idx)" class="text-red-500 hover:text-red-700 transition"
                                        x-show="customers.length > 1" title="حذف">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- أزرار الإجراءات -->
        <div class="flex flex-wrap justify-between items-center mt-4 gap-3">
            <div class="flex gap-2">
                <button type="button" @click="addRow()"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm flex items-center gap-2">
                    <i class="fas fa-plus"></i> إضافة سطر
                </button>
                <button type="button" @click="addRows(5)"
                        class="bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200 transition text-sm flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> +5
                </button>
                <button type="button" @click="addRows(10)"
                        class="bg-green-100 text-green-700 px-4 py-2 rounded-lg hover:bg-green-200 transition text-sm flex items-center gap-2">
                    <i class="fas fa-plus-circle"></i> +10
                </button>
            </div>
            <div class="flex gap-2">
                <a href="<?= APP_URL ?>/customers" class="bg-gray-300 px-4 py-2 rounded-lg hover:bg-gray-400 transition text-sm">إلغاء</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2"
                        @click="return confirm('تأكيد حفظ ' + customers.length + ' عميل/عميلا؟')">
                    <i class="fas fa-save"></i> حفظ الكل
                </button>
            </div>
        </div>
    </form>

    <!-- إحصائيات سريعة -->
    <div class="mt-4 p-3 bg-gray-50 rounded-lg text-xs text-gray-500 flex gap-4">
        <span><i class="fas fa-file-alt ml-1"></i> أكواد التوليد: <span class="font-mono font-bold">CUST-XXXXXX</span></span>
        <span><i class="fas fa-check-circle ml-1 text-green-600"></i> <span x-text="filledCount"></span>/<span x-text="customers.length"></span> مكتمل</span>
    </div>
</div>

<script>
function bulkCustomers() {
    return {
        customers: [
            { code: '', name: '', phone: '', area: '', address: '', sales_rep_id: '' }
        ],
        codeCounter: 0,

        init() {
            this.loadNextCode();
        },

        loadNextCode() {
            const self = this;
            fetch('<?= APP_URL ?>/api/next-customer-code')
                .then(r => r.text())
                .then(code => {
                    self.codeCounter = parseInt(code.replace('CUST-', '')) || 1;
                    self.customers.forEach(c => { c.code = self.padCode(self.codeCounter++); });
                })
                .catch(() => {
                    self.customers.forEach(c => { c.code = 'CUST-001'; });
                });
        },

        padCode(num) {
            return 'CUST-' + String(num).padStart(6, '0');
        },

        addRow() {
            this.customers.push({
                code: this.padCode(this.codeCounter++),
                name: '',
                phone: '',
                area: '',
                address: '',
                sales_rep_id: ''
            });
        },

        addRows(count) {
            for (let i = 0; i < count; i++) {
                this.addRow();
            }
        },

        removeRow(idx) {
            this.customers.splice(idx, 1);
        },

        updateRow(idx) {
            // علامة لتحديث الحالة دون فعل إضافي
        },

        get filledCount() {
            return this.customers.filter(c => c.name.trim() !== '').length;
        }
    }
}
</script>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
