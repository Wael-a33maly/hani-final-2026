<?php requireRole('admin'); $pageTitle = $product ? 'تعديل مادة' : 'إضافة مادة جديدة'; ob_start(); ?>
<div class="bg-white rounded-lg shadow p-6 max-w-5xl mx-auto" x-data="productForm()">
    <h2 class="text-xl font-bold mb-6"><?php echo $product ? 'تعديل مادة' : 'إضافة مادة جديدة'; ?></h2>
    <form method="POST" action="<?php echo APP_URL; ?>/products/<?php echo $product ? 'update/'.$product['id'] : 'store'; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="opening_balances_json" :value="JSON.stringify(openingBalances)">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-gray-700 mb-1">الباركود</label>
                <input type="text" name="barcode" value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>" class="w-full border rounded px-3 py-2" <?php echo $product ? 'readonly' : ''; ?>>
                <?php if(!$product): ?><p class="text-xs text-gray-500 mt-1">اتركه فارغاً للتوليد التلقائي</p><?php endif; ?>
            </div>
            <div>
                <label class="block text-gray-700 mb-1">اسم المادة <span class="text-red-600">*</span></label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-gray-700 mb-1">الوحدة</label>
                <select name="unit_id" class="w-full border rounded px-3 py-2">
                    <?php foreach($units as $unit): ?>
                    <option value="<?= $unit['id'] ?>" <?= (($product['unit_id'] ?? '') == $unit['id']) ? 'selected' : '' ?>><?= htmlspecialchars($unit['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <h3 class="text-md font-bold border-b pb-1 mb-3">أسعار المادة</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div><label>سعر الشراء</label><input type="number" step="0.01" name="purchase_price" value="<?= $product['purchase_price'] ?? 0 ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label>سعر البيع (قطاعي)</label><input type="number" step="0.01" name="selling_price" value="<?= $product['selling_price'] ?? 0 ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label>سعر الجملة</label><input type="number" step="0.01" name="wholesale_price" value="<?= $product['wholesale_price'] ?? 0 ?>" class="w-full border rounded px-3 py-2"></div>
            <div><label>سعر خاص (مندوبين)</label><input type="number" step="0.01" name="special_price" value="<?= $product['special_price'] ?? 0 ?>" class="w-full border rounded px-3 py-2"></div>
        </div>
        
        <h3 class="text-md font-bold border-b pb-1 mb-3">🏷️ إعدادات العمولة</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <label class="block text-gray-700 mb-1">عمولة المندوب عند البيع (لكل قطعة)</label>
                <input type="number"
                       name="commission_amount"
                       step="0.01"
                       min="0"
                       placeholder="0.00"
                       value="<?= $product['commission_amount'] ?? 0 ?>"
                       class="w-full border rounded px-3 py-2">
                <p class="text-sm text-gray-500 mt-1">
                    💡 المبلغ الثابت الذي يستحقه المندوب عن كل قطعة مباعة
                </p>
            </div>
        </div>
        
        <h3 class="text-md font-bold border-b pb-1 mb-3">رصيد أول المدة</h3>
        <div class="mb-2">
            <template x-for="(item, idx) in openingBalances" :key="idx">
                <div class="flex flex-wrap gap-3 mb-2 items-end bg-gray-50 p-2 rounded">
                    <div class="flex-1 min-w-[150px]">
                        <label class="text-sm text-gray-600">المخزن</label>
                        <select x-model="item.warehouse_id" class="w-full border rounded px-2 py-1">
                            <option value="">اختر المخزن</option>
                            <?php
                            $db = getDB();
                            $warehouses = $db->query("SELECT id, name FROM warehouses ORDER BY name")->fetchAll();
                            foreach($warehouses as $wh): ?>
                            <option value="<?= $wh['id'] ?>"><?= htmlspecialchars($wh['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="w-28">
                        <label class="text-sm text-gray-600">الكمية</label>
                        <input type="number" step="0.01" x-model="item.quantity" @input="updateTotal(idx)" class="w-full border rounded px-2 py-1">
                    </div>
                    <div class="w-36">
                        <label class="text-sm text-gray-600">سعر الشراء</label>
                        <input type="number" step="0.01" x-model="item.price" @input="updateTotal(idx)" class="w-full border rounded px-2 py-1">
                    </div>
                    <div class="w-32">
                        <label class="text-sm text-gray-600">الإجمالي</label>
                        <span x-text="item.total.toFixed(2)" class="inline-block w-full px-2 py-1 bg-gray-100 rounded text-center"></span>
                    </div>
                    <button type="button" @click="removeOpening(idx)" class="text-red-600 mb-1"><i class="fas fa-trash"></i></button>
                </div>
            </template>
            <button type="button" @click="addOpening()" class="bg-gray-200 px-3 py-1 rounded text-sm mt-1"><i class="fas fa-plus"></i> إضافة مخزن</button>
        </div>
        
        <div class="flex justify-end mt-6 gap-2">
            <a href="<?= APP_URL ?>/products" class="bg-gray-300 px-4 py-2 rounded">إلغاء</a>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">حفظ</button>
        </div>
    </form>
</div>
<script>
function productForm() {
    return {
        openingBalances: <?php echo json_encode($product_opening_balances ?? []); ?>,
        addOpening() { this.openingBalances.push({warehouse_id: '', quantity: 0, price: 0, total: 0}); },
        removeOpening(idx) { this.openingBalances.splice(idx, 1); },
        updateTotal(idx) {
            let item = this.openingBalances[idx];
            item.total = (parseFloat(item.quantity) || 0) * (parseFloat(item.price) || 0);
        }
    }
}
</script>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
