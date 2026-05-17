<?php requireLogin(); $pageTitle = 'فاتورة مشتريات جديدة'; ob_start(); ?>
<div class="bg-white rounded shadow p-4" x-data="purchaseForm()" x-init="addItem()">
    <h2 class="text-xl font-bold mb-4">فاتورة مشتريات جديدة</h2>
    <form method="POST" action="<?= APP_URL ?>/purchases/store">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <input type="hidden" name="products_json" :value="JSON.stringify(items)">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 text-right">
            <div><label>التاريخ</label><input type="date" name="date" value="<?= date('Y-m-d') ?>" class="w-full border rounded px-2 py-1" required></div>
            <div class="relative">
                <label>المورد</label>
                <input type="text" x-model="supplierSearch" @input="searchSupplier()" placeholder="ابحث عن مورد..." class="w-full border rounded px-2 py-1">
                <input type="hidden" name="supplier_id" :value="supplierId" required>
                <div x-show="supplierSearching" class="absolute bg-white border shadow z-50 w-full mt-1 rounded p-2 text-sm text-gray-400">جاري البحث...</div>
                <div x-show="supplierResults.length" class="absolute bg-white border shadow z-50 w-full mt-1 max-h-48 overflow-y-auto rounded">
                    <template x-for="s in supplierResults">
                        <div @click="selectSupplier(s)" class="p-2 hover:bg-gray-100 cursor-pointer border-b last:border-0">
                            <div class="font-medium text-gray-800" x-text="s.name"></div>
                            <div class="text-xs text-gray-500" x-text="s.phone ? s.phone : ''"></div>
                        </div>
                    </template>
                </div>
                <div x-show="supplierName" class="text-xs text-green-600 mt-1">المورد المختار: <span x-text="supplierName"></span></div>
            </div>
            <div>
                <label>المخزن</label>
                <select name="warehouse_id" class="w-full border rounded px-2 py-1" required>
                    <option value="">اختر المخزن</option>
                    <?php foreach ($warehouses as $w): ?>
                    <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>نوع الدفع</label><select name="payment_type" class="w-full border rounded px-2 py-1"><option value="cash">نقدي</option><option value="credit">آجل</option><option value="vodafone">فودافون كاش</option><option value="instapay">انستا باي</option></select></div>
        </div>
        
        <h3 class="font-bold">المنتجات</h3>
        <table class="w-full border mb-2 text-right">
            <thead class="bg-gray-100"><tr><th class="p-2 text-right">المنتج</th><th class="p-2 text-right">الكمية</th><th class="p-2 text-right">سعر الشراء</th><th class="p-2 text-right">الإجمالي</th><th class="p-2"></th></tr></thead>
            <tbody>
                <template x-for="(item, idx) in items" :key="idx">
                    <tr>
                        <td class="p-1 relative">
                            <input type="text" x-model="item.search" @input="searchProduct(idx)" placeholder="باركود أو اسم" class="border rounded p-1 w-full">
                            <div x-show="item.searchResults.length === 0 && productSearching[idx]" class="absolute bg-white border shadow z-50 w-full mt-1 rounded p-2 text-sm text-gray-400">جاري البحث...</div>
                            <div x-show="item.searchResults.length" class="absolute bg-white border shadow z-50 w-full mt-1 max-h-48 overflow-y-auto rounded">
                                <template x-for="res in item.searchResults">
                                    <div @click="selectProduct(idx, res)" class="p-2 hover:bg-gray-100 cursor-pointer border-b last:border-0">
                                        <div class="font-medium text-gray-800" x-text="res.name"></div>
                                        <div class="text-xs text-gray-500"><span x-text="res.barcode"></span> | <span x-text="res.unit_name ? res.unit_name : ''"></span></div>
                                    </div>
                                </template>
                            </div>
                        </td>
                        <td class="p-1"><input type="number" x-model="item.quantity" @input="updateTotals" step="0.01" class="border rounded w-20 p-1"></td>
                        <td class="p-1"><input type="number" x-model="item.unit_price" @input="updateTotals" step="0.01" class="border rounded w-24 p-1"></td>
                        <td class="p-1" x-text="(item.quantity * item.unit_price).toFixed(2)"></td>
                        <td class="p-1 text-center"><button type="button" @click="removeItem(idx)" class="text-red-600"><i class="fas fa-times"></i></button></td>
                    </tr>
                </template>
            </tbody>
        </table>
        <button type="button" @click="addItem()" class="bg-gray-200 px-3 py-1 rounded"><i class="fas fa-plus"></i> إضافة منتج</button>
        <div class="text-left mt-2"><strong>الإجمالي: </strong><span x-text="total.toFixed(2)"></span></div>
        <input type="hidden" name="total" x-model="total">
        <div class="mt-4"><label>ملاحظات</label><textarea name="notes" rows="2" class="w-full border rounded px-2 py-1"></textarea></div>
        <div class="mt-4 flex justify-end gap-2">
            <a href="<?= APP_URL ?>/purchases" class="bg-gray-300 px-4 py-2 rounded">إلغاء</a>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded">حفظ الفاتورة</button>
        </div>
    </form>
</div>
<script>
function purchaseForm() {
    return {
        items: [], total: 0,
        supplierSearch: '', supplierId: '', supplierName: '', supplierResults: [],
        supplierSearching: false, productSearching: [],
        supplierDebounce: null, productDebounce: [],
        addItem() { this.items.push({product_id: '', search: '', searchResults: [], quantity: 1, unit_price: 0}); this.productDebounce.push(null); this.productSearching.push(false); this.updateTotals(); },
        removeItem(idx) { this.items.splice(idx,1); this.productDebounce.splice(idx,1); this.productSearching.splice(idx,1); this.updateTotals(); },
        updateTotals() { this.total = this.items.reduce((sum,item) => sum + (item.quantity * item.unit_price), 0); },
        searchProduct(idx) {
            let search = this.items[idx].search;
            if(search.length === 0) { this.items[idx].searchResults = []; this.productSearching[idx] = false; return; }
            clearTimeout(this.productDebounce[idx]);
            this.productSearching[idx] = true;
            this.productDebounce[idx] = setTimeout(() => {
                fetch('<?= APP_URL ?>/products/search?q=' + encodeURIComponent(search))
                    .then(res => res.json()).then(data => { this.items[idx].searchResults = data; this.productSearching[idx] = false; });
            }, 300);
        },
        selectProduct(idx, product) {
            this.items[idx].product_id = product.id;
            this.items[idx].search = product.name;
            this.items[idx].unit_price = product.purchase_price || 0;
            this.items[idx].searchResults = [];
            this.updateTotals();
        },
        searchSupplier() {
            if(this.supplierSearch.length === 0) { this.supplierResults = []; this.supplierSearching = false; return; }
            clearTimeout(this.supplierDebounce);
            this.supplierSearching = true;
            this.supplierDebounce = setTimeout(() => {
                fetch('<?= APP_URL ?>/suppliers/search?q=' + encodeURIComponent(this.supplierSearch))
                    .then(res => res.json()).then(data => { this.supplierResults = data; this.supplierSearching = false; });
            }, 300);
        },
        selectSupplier(s) {
            this.supplierId = s.id;
            this.supplierName = s.name;
            this.supplierSearch = s.name;
            this.supplierResults = [];
        }
    }
}
</script>
<?php $content = ob_get_clean(); require __DIR__.'/../layouts/main.php'; ?>
