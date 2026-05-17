<?php requireLogin(); $pageTitle = 'فاتورة مبيعات جديدة'; ob_start(); ?>
<div class="bg-white rounded-2xl shadow-sm p-6" x-data="salesForm()" x-init="init()">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-file-invoice-dollar text-blue-600"></i> فاتورة مبيعات جديدة
        </h2>
        <span class="text-sm text-gray-500 font-mono"><?= date('Y-m-d') ?></span>
    </div>

    <form method="POST" action="<?= APP_URL ?>/sales/store">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="products_json" :value="JSON.stringify(items)">
        
        <!-- Header Info -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
            <div>
                <label class="block text-xs text-gray-500 mb-1">التاريخ</label>
                <input type="date" name="date" value="<?= date('Y-m-d') ?>" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none" required>
            </div>
            <div class="relative">
                <label class="block text-xs text-gray-500 mb-1">العميل (بحث بالاسم/الهاتف/...)</label>
                <div class="relative">
                    <input type="text" x-model="customerSearch" @input="searchCustomer()" 
                           :class="customerId ? 'border-green-500 bg-green-50' : (customerSearch.length > 2 ? 'border-red-300' : 'border-gray-200')"
                           placeholder="ابحث عن عميل..." class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none transition-all">
                    <div x-show="customerId" class="absolute left-3 top-2 text-green-600">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <input type="hidden" name="customer_id" :value="customerId">
                
                <div x-show="customerResults.length" class="absolute bg-white border shadow-xl z-50 w-full mt-1 max-h-60 overflow-y-auto rounded-lg" @click.away="customerResults = []">
                    <template x-for="c in customerResults">
                        <div @click="selectCustomer(c)" class="p-3 hover:bg-blue-50 cursor-pointer border-b last:border-0">
                            <div class="font-bold text-gray-800" x-text="c.name"></div>
                            <div class="text-xs text-gray-500 flex justify-between">
                                <span x-text="c.phone"></span>
                                <span x-text="c.area"></span>
                            </div>
                        </div>
                    </template>
                </div>
                <div x-show="customerSearch.length > 2 && !customerId && !customerResults.length" class="text-[10px] text-red-500 mt-1">
                    <i class="fas fa-exclamation-triangle"></i> يرجى اختيار عميل من القائمة
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">المخزن</label>
                <select name="warehouse_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none" required>
                    <?php foreach($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>"><?= htmlspecialchars($wh['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">المندوب</label>
                <select name="sales_rep_id" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none">
                    <option value="">اختيار المندوب</option>
                    <?php foreach($reps as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1 font-bold text-blue-600">نوع الدفع</label>
                <select name="payment_type" x-model="paymentType" @change="calculateInstallments" class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-400 outline-none font-bold bg-blue-50">
                    <option value="cash">نقدي</option>
                    <option value="installment">تقسيط</option>
                    <option value="credit">آجل</option>
                    <option value="vodafone">فودافون كاش</option>
                    <option value="instapay">انستا باي</option>
                    <option value="bank">بنك</option>
                </select>
            </div>
        </div>

        <!-- Customer Quick View -->
        <div x-show="customerData.name" class="bg-blue-50 p-3 rounded-lg mb-6 border border-blue-100 flex items-center gap-6 animate-pulse-once">
            <div class="text-blue-600"><i class="fas fa-user-circle text-2xl"></i></div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 flex-1 text-sm">
                <div><span class="text-gray-500">العميل:</span> <strong x-text="customerData.name"></strong></div>
                <div><span class="text-gray-500">الهاتف:</span> <strong x-text="customerData.phone"></strong></div>
                <div><span class="text-gray-500">المنطقة/العنوان:</span> <strong x-text="customerData.area + ' - ' + customerData.address"></strong></div>
            </div>
        </div>
        
        <h3 class="font-bold text-gray-700 mb-3 flex items-center gap-2">
            <i class="fas fa-shopping-cart text-green-500"></i> قائمة الأصناف
        </h3>
        <div class="overflow-x-auto border rounded-xl mb-4 shadow-sm">
            <table class="w-full text-right">
                <thead class="bg-gray-100 text-gray-600 text-sm">
                    <tr>
                        <th class="p-3">الصنف</th>
                        <th class="p-3 w-32">الكمية</th>
                        <th class="p-3 w-40">السعر</th>
                        <th class="p-3 w-40">الإجمالي</th>
                        <th class="p-3 w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <template x-for="(item, idx) in items" :key="idx">
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="p-2 relative">
                                <input type="text" x-model="item.search" @input="searchProduct(idx)" placeholder="ابحث بالاسم أو الباركود..." class="w-full border-none focus:ring-0 p-2 bg-transparent outline-none">
                                <!-- قائمة النتائج تظهر للأعلى لضمان الوضوح -->
                                <div x-show="item.searchResults.length" class="absolute left-0 bottom-full mb-1 bg-white border border-blue-200 shadow-2xl z-[100] w-72 md:w-full max-h-60 overflow-y-auto rounded-xl animate-fade-in">
                                    <template x-for="res in item.searchResults">
                                        <div @click="selectProduct(idx, res)" class="p-3 hover:bg-blue-50 cursor-pointer border-b last:border-0 flex justify-between items-center group">
                                            <div>
                                                <div class="font-bold text-gray-800 group-hover:text-blue-600 transition-colors" x-text="res.name"></div>
                                                <div class="text-[10px] text-gray-400" x-text="'كود: ' + (res.barcode || res.id)"></div>
                                            </div>
                                            <div class="text-right">
                                                <div class="text-sm font-bold text-green-600 font-mono" x-text="formatNumber(res.selling_price)"></div>
                                                <div class="text-[9px] text-gray-400" x-text="'المخزون: ' + res.stock"></div>
                                            </div>
                                        </div>
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
                                <button type="button" @click="removeItem(idx)" class="w-8 h-8 rounded-full text-red-400 hover:bg-red-50 hover:text-red-600 transition-all">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <button type="button" @click="addItem()" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-blue-600 hover:text-white transition-all font-medium flex items-center gap-2">
            <i class="fas fa-plus-circle"></i> إضافة صنف آخر
        </button>
        
        <!-- Totals and Installments Section (Bottom) -->
        <div class="mt-8 border-t pt-6">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Notes -->
                <div>
                    <label class="block font-semibold text-gray-700 mb-2">ملاحظات الفاتورة</label>
                    <textarea name="notes" rows="4" class="w-full border rounded-xl px-4 py-3 focus:ring-2 focus:ring-blue-400 outline-none" placeholder="أي تفاصيل إضافية عن الفاتورة..."></textarea>
                </div>

                <!-- Calculations & Installments -->
                <div class="bg-gray-50 rounded-2xl p-6 border border-gray-100 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <span class="text-gray-500 font-medium">إجمالي الفاتورة</span>
                        <div class="text-3xl font-black text-blue-600 font-mono"><span x-text="formatNumber(total)"></span> <small class="text-sm font-normal">ج.م</small></div>
                    </div>

                    <!-- Installments UI -->
                    <div x-show="paymentType == 'installment'" x-cloak class="space-y-4 pt-4 border-t border-gray-200 animate-fade-in">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">المقدم المدفوع</label>
                                <input type="number" step="0.01" x-model="paidUpfront" @input="calculateInstallments" class="w-full border rounded-lg px-3 py-2 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs text-blue-500 mb-1 font-bold">المتبقي للأقساط</label>
                                <div class="w-full bg-blue-100 rounded-lg px-3 py-2 font-bold text-blue-700 font-mono text-center" x-text="formatNumber(remainingForInstallments)"></div>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">عدد الأقساط</label>
                                <input type="number" x-model="numInstallments" @input="calculateInstallments" min="1" class="w-full border rounded-lg px-3 py-2 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">قيمة القسط</label>
                                <input type="number" step="0.01" x-model="installmentValue" @input="calculateInstallments" class="w-full border rounded-lg px-3 py-2 font-mono">
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">تاريخ أول قسط</label>
                                <input type="date" x-model="firstDate" @change="calculateInstallments" class="w-full border rounded-lg px-3 py-2 text-xs">
                            </div>
                        </div>

                        <!-- Table Preview -->
                        <div x-show="installmentsTable.length > 0" class="mt-4 border rounded-xl overflow-hidden bg-white shadow-sm">
                            <table class="w-full text-xs text-center">
                                <thead class="bg-gray-100 text-gray-500">
                                    <tr>
                                        <th class="p-2">#</th>
                                        <th class="p-2">التاريخ</th>
                                        <th class="p-2 text-left px-4">المبلغ</th>
                                        <th class="p-2">الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(inst, idx) in installmentsTable" :key="idx">
                                        <tr class="border-b last:border-0" :class="{'bg-blue-50/30': inst.isLast}">
                                            <td class="p-2 text-gray-400" x-text="inst.number"></td>
                                            <td class="p-2 font-mono" x-text="inst.dueDate"></td>
                                            <td class="p-2 text-left px-4 font-bold" x-text="formatNumber(inst.amount)"></td>
                                            <td class="p-2">
                                                <template x-if="inst.isLast">
                                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold" 
                                                          :class="{
                                                            'bg-red-100 text-red-600': inst.amount < installmentValue, 
                                                            'bg-orange-100 text-orange-600': inst.amount > installmentValue, 
                                                            'bg-green-100 text-green-600': inst.amount == installmentValue
                                                          }" x-text="inst.amount < installmentValue ? 'قسط مخفض' : (inst.amount > installmentValue ? 'قسط زائد' : 'قسط متساوي')">
                                                    </span>
                                                </template>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                        <div x-show="lastInstallmentAmount < 0" class="p-2 bg-red-100 text-red-600 text-[10px] rounded text-center font-bold">
                            ⚠️ تنبيه: مجموع الأقساط يتجاوز المتبقي!
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="total" :value="total">
        <input type="hidden" name="paid_upfront" :value="paidUpfront">
        <input type="hidden" name="num_installments" :value="numInstallments">
        <input type="hidden" name="installment_value" :value="installmentValue">
        <input type="hidden" name="first_installment_date" :value="firstDate">

        <div class="mt-8 flex justify-end gap-3 border-t pt-6">
            <a href="<?= APP_URL ?>/sales" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition-all font-semibold">إلغاء</a>
            <button type="submit" 
                    :disabled="!customerId || (paymentType == 'installment' && lastInstallmentAmount < 0)"
                    :class="{'opacity-50 cursor-not-allowed': !customerId || (paymentType == 'installment' && lastInstallmentAmount < 0)}"
                    class="px-8 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all font-bold text-lg">
                <i class="fas fa-check-circle ml-1"></i> حفظ الفاتورة
            </button>
        </div>
    </form>
</div>

<script>
function salesForm() {
    return {
        items: [], 
        total: 0, 
        paymentType: 'cash', 
        paidUpfront: 0, 
        numInstallments: 1, 
        installmentValue: 0, 
        firstDate: new Date().toISOString().slice(0,10),
        installmentsTable: [],
        customerSearch: '', customerId: '', customerName: '', customerResults: [], customerData: {},

        get remainingForInstallments() { 
            return Math.max(0, parseFloat(this.total) - parseFloat(this.paidUpfront)); 
        },
        get lastInstallmentAmount() { 
            if (this.installmentsTable.length === 0) return 0;
            return this.installmentsTable[this.installmentsTable.length-1].amount;
        },

        init() { this.addItem(); },
        addItem() { 
            this.items.push({product_id: '', search: '', searchResults: [], quantity: 1, unit_price: 0}); 
            this.updateTotals(); 
        },
        removeItem(idx) { 
            this.items.splice(idx,1); 
            this.updateTotals(); 
        },
        updateTotals() { 
            this.total = this.items.reduce((sum,item) => sum + (item.quantity * item.unit_price), 0); 
            this.calculateInstallments();
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
            if(this.customerSearch.length < 2) { 
                this.customerResults = []; 
                this.customerId = ''; 
                this.customerData = {};
                return; 
            }
            if (this.customerSearch !== this.customerName) {
                this.customerId = '';
                this.customerData = {};
            }
            fetch('<?= APP_URL ?>/customers/search?q=' + encodeURIComponent(this.customerSearch))
                .then(res => res.json()).then(data => { this.customerResults = data; });
        },
        selectCustomer(c) {
            this.customerId = c.id;
            this.customerName = c.name;
            this.customerSearch = c.name;
            this.customerResults = [];
            this.customerData = c;
        },
        calculateInstallments() {
            if (this.paymentType !== 'installment') {
                this.installmentsTable = [];
                return;
            }
            
            let remaining = this.remainingForInstallments;
            let n = parseInt(this.numInstallments);
            let val = parseFloat(this.installmentValue);

            if (remaining <= 0 || n <= 0 || val <= 0) {
                this.installmentsTable = [];
                return;
            }

            let table = [];
            let remainingCents = Math.round(remaining * 100);
            let installmentValueCents = Math.round(val * 100);
            
            for (let i = 1; i <= n; i++) {
                let amountCents = installmentValueCents;
                if (i === n) {
                    amountCents = remainingCents - (installmentValueCents * (n - 1));
                }

                let dueDate = new Date(this.firstDate);
                dueDate.setMonth(dueDate.getMonth() + (i - 1));

                table.push({
                    number: i,
                    dueDate: dueDate.toISOString().slice(0, 10),
                    amount: amountCents / 100,
                    isLast: (i === n)
                });
            }
            this.installmentsTable = table;
        },
        formatNumber(num) {
            return Number(num).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }
}
</script>
<?php $content = ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
