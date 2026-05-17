<?php requireLogin(); $pageTitle = 'تعديل فاتورة مبيعات #' . $invoice['invoice_number']; ob_start(); ?>
<div class="bg-white rounded-2xl shadow-sm p-6" x-data="editSalesForm()" x-init="init()">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-edit text-orange-500"></i> تعديل فاتورة مبيعات
        </h2>
        <span class="text-sm text-gray-500 font-mono">رقم الفاتورة: <?= htmlspecialchars($invoice['invoice_number']) ?></span>
    </div>

    <form method="POST" action="<?= APP_URL ?>/sales/update/<?= $invoice['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="products_json" :value="JSON.stringify(items)">
        <input type="hidden" name="installments_json" :value="JSON.stringify(installmentsTable)">
        <input type="hidden" name="total" :value="total">
        
        <!-- Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
            <div>
                <label class="block text-xs text-gray-500 mb-1">التاريخ</label>
                <input type="date" name="date" x-model="invoiceDate" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none" required>
            </div>
            <div class="relative">
                <label class="block text-xs text-gray-500 mb-1">العميل</label>
                <div class="relative">
                    <input type="text" x-model="customerSearch" @input="searchCustomer()" 
                           :class="customerId ? 'border-green-500 bg-green-50' : 'border-gray-200'"
                           class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none transition-all">
                </div>
                <input type="hidden" name="customer_id" :value="customerId">
                <div x-show="customerResults.length" class="absolute bg-white border shadow-xl z-50 w-full mt-1 max-h-60 overflow-y-auto rounded-lg">
                    <template x-for="c in customerResults">
                        <div @click="selectCustomer(c)" class="p-3 hover:bg-blue-50 cursor-pointer border-b last:border-0">
                            <div class="font-bold text-gray-800" x-text="c.name"></div>
                        </div>
                    </template>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">المخزن</label>
                <select name="warehouse_id" x-model="warehouseId" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none" required>
                    <?php foreach($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">المندوب</label>
                <select name="sales_rep_id" x-model="salesRepId" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none">
                    <option value="">اختيار المندوب</option>
                    <?php foreach($reps as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">نوع الدفع</label>
                <select name="payment_type" x-model="paymentType" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none font-bold text-blue-600">
                    <option value="cash">نقدي</option>
                    <option value="installment">تقسيط</option>
                    <option value="credit">آجل</option>
                </select>
            </div>
        </div>

        <!-- Products Table -->
        <div class="overflow-x-auto border rounded-xl mb-4 shadow-sm">
            <table class="w-full text-right">
                <thead class="bg-gray-800 text-white text-xs">
                    <tr>
                        <th class="p-3">الصنف / المنتج</th>
                        <th class="p-3 w-24 text-center">الكمية</th>
                        <th class="p-3 w-40 text-center">السعر</th>
                        <th class="p-3 w-40 text-center">الإجمالي</th>
                        <th class="p-3 w-16 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <template x-for="(item, idx) in items" :key="idx">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-2 relative">
                                <input type="text" x-model="item.search" @input="searchProduct(idx)" placeholder="ابحث..." class="w-full border-none focus:ring-0 p-2 bg-transparent outline-none">
                                <div x-show="item.searchResults.length" class="absolute bg-white border shadow-xl z-50 w-full bottom-full mb-1 max-h-40 overflow-y-auto rounded-lg">
                                    <template x-for="res in item.searchResults">
                                        <div @click="selectProduct(idx, res)" class="p-2 hover:bg-blue-50 cursor-pointer border-b last:border-0" x-text="res.name"></div>
                                    </template>
                                </div>
                            </td>
                            <td class="p-2">
                                <input type="number" x-model="item.quantity" @input="updateTotals" step="0.01" class="w-full border border-gray-200 rounded-lg p-2 text-center font-mono">
                            </td>
                            <td class="p-2">
                                <input type="number" x-model="item.unit_price" @input="updateTotals" step="0.01" class="w-full border border-gray-200 rounded-lg p-2 text-center font-mono">
                            </td>
                            <td class="p-2 text-center font-bold text-gray-700 font-mono" x-text="formatNumber(item.quantity * item.unit_price)"></td>
                            <td class="p-2 text-center">
                                <button type="button" @click="removeItem(idx)" class="text-red-400 hover:text-red-600"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <button type="button" @click="addItem()" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-blue-600 hover:text-white transition-all font-medium mb-6">
            <i class="fas fa-plus"></i> إضافة صنف
        </button>

        <!-- Totals & Installments -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 border-t pt-6">
            <div>
                <label class="block font-semibold text-gray-700 mb-2">ملاحظات</label>
                <textarea name="notes" x-model="notes" rows="4" class="w-full border rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-blue-400"></textarea>
            </div>

            <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 shadow-sm">
                <div class="flex justify-between items-center mb-6">
                    <span class="text-gray-500 font-medium">إجمالي الفاتورة</span>
                    <div class="text-3xl font-black text-blue-600 font-mono"><span x-text="formatNumber(total)"></span> <small>ج.م</small></div>
                </div>

                <div x-show="paymentType == 'installment'" class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">المقدم</label>
                            <input type="number" name="paid_upfront" step="0.01" x-model="paidUpfront" @input="updateTotals" class="w-full border rounded-lg px-3 py-2 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs text-blue-500 mb-1 font-bold">المتبقي</label>
                            <div class="w-full bg-blue-100 rounded-lg px-3 py-2 font-bold text-blue-700 font-mono text-center" x-text="formatNumber(total - paidUpfront)"></div>
                        </div>
                    </div>

                    <!-- Installments Table Edit -->
                    <div class="mt-4 border rounded-xl overflow-hidden bg-white shadow-sm">
                        <table class="w-full text-xs">
                            <thead class="bg-gray-100 text-gray-500 text-center">
                                <tr>
                                    <th class="p-2">#</th>
                                    <th class="p-2">المبلغ</th>
                                    <th class="p-2">التاريخ</th>
                                    <th class="p-2">الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(inst, idx) in installmentsTable" :key="idx">
                                    <tr class="border-b last:border-0" :class="inst.status == 'paid' ? 'bg-green-50/30' : ''">
                                        <td class="p-2 text-center text-gray-400" x-text="inst.installment_number"></td>
                                        <td class="p-2">
                                            <input type="number" x-model="inst.amount" :readonly="inst.status == 'paid'" 
                                                   class="w-full border rounded p-1 text-center font-bold"
                                                   :class="inst.status == 'paid' ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-transparent' : 'border-gray-200'">
                                        </td>
                                        <td class="p-2">
                                            <input type="date" x-model="inst.due_date" :readonly="inst.status == 'paid'"
                                                   class="w-full border rounded p-1 text-xs"
                                                   :class="inst.status == 'paid' ? 'bg-gray-100 text-gray-500 cursor-not-allowed border-transparent' : 'border-gray-200'">
                                        </td>
                                        <td class="p-2 text-center">
                                            <span x-show="inst.status == 'paid'" class="text-[9px] bg-green-100 text-green-600 px-1.5 py-0.5 rounded font-bold uppercase">✅ مدفوع (مغلق)</span>
                                            <span x-show="inst.status != 'paid'" class="text-[9px] bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded font-bold uppercase">✏️ قابل للتعديل</span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="installmentSumError" class="p-2 bg-red-100 text-red-600 text-[10px] rounded text-center font-bold">
                        ⚠️ تنبيه: مجموع (المقدم + الأقساط) = <span x-text="formatNumber(parseFloat(paidUpfront) + installmentSum)"></span> ، وهو لا يساوي إجمالي الفاتورة (<span x-text="formatNumber(total)"></span>)!
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-3 border-t pt-6">
            <a href="<?= APP_URL ?>/sales/show/<?= $invoice['id'] ?>" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 font-semibold">إلغاء</a>
            <button type="submit" 
                    :disabled="installmentSumError || !customerId"
                    :class="(installmentSumError || !customerId) ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-8 py-2 bg-orange-600 text-white rounded-xl hover:bg-orange-700 shadow-lg shadow-orange-200 font-bold text-lg">
                تحديث الفاتورة
            </button>
        </div>
    </form>
</div>

<script>
function editSalesForm() {
    return {
        invoiceDate: '<?= $invoice['date'] ?>',
        customerId: '<?= $invoice['customer_id'] ?>',
        customerSearch: '<?= htmlspecialchars($invoice['customer_name']) ?>',
        customerName: '<?= htmlspecialchars($invoice['customer_name']) ?>',
        warehouseId: '<?= $invoice['warehouse_id'] ?>',
        salesRepId: '<?= $invoice['sales_rep_id'] ?>',
        paymentType: '<?= $invoice['payment_type'] ?>',
        paidUpfront: <?= $invoice['paid_upfront'] ?>,
        notes: `<?= addslashes($invoice['notes']) ?>`,
        total: <?= $invoice['total'] ?>,
        items: <?= json_encode($items) ?>,
        installmentsTable: <?= json_encode($installments) ?>,
        customerResults: [],

        init() {
            // إضافة حقول البحث للأصناف المحملة
            this.items = this.items.map(it => ({
                ...it,
                search: it.product_name,
                searchResults: []
            }));
        },
        addItem() {
            this.items.push({product_id: '', search: '', searchResults: [], quantity: 1, unit_price: 0});
        },
        removeItem(idx) {
            this.items.splice(idx, 1);
            this.updateTotals();
        },
        updateTotals() {
            this.total = this.items.reduce((sum, it) => sum + (parseFloat(it.quantity) * parseFloat(it.unit_price)), 0);
        },
        get installmentSum() {
            return this.installmentsTable.reduce((sum, it) => sum + parseFloat(it.amount), 0);
        },
        get installmentSumError() {
            if (this.paymentType !== 'installment') return false;
            let currentTotal = parseFloat(this.paidUpfront) + this.installmentSum;
            let diff = Math.abs(currentTotal - this.total);
            return diff > 0.01;
        },
        searchProduct(idx) {
            let search = this.items[idx].search;
            if(search.length < 2) { this.items[idx].searchResults = []; return; }
            fetch('<?= APP_URL ?>/products/search?q=' + encodeURIComponent(search))
                .then(res => res.json()).then(data => { this.items[idx].searchResults = data; });
        },
        selectProduct(idx, product) {
            this.items[idx].product_id = product.id;
            this.items[idx].search = product.name;
            this.items[idx].unit_price = product.selling_price;
            this.items[idx].searchResults = [];
            this.updateTotals();
        },
        searchCustomer() {
            if(this.customerSearch.length < 2) { this.customerResults = []; return; }
            fetch('<?= APP_URL ?>/customers/search?q=' + encodeURIComponent(this.customerSearch))
                .then(res => res.json()).then(data => { this.customerResults = data; });
        },
        selectCustomer(c) {
            this.customerId = c.id;
            this.customerName = c.name;
            this.customerSearch = c.name;
            this.customerResults = [];
        },
        formatNumber(num) {
            return Number(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }
}
</script>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
