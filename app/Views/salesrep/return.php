<?php requireLogin(); $pageTitle = 'استرداد بضاعة من المندوب'; ob_start(); ?>
<div class="max-w-xl mx-auto bg-white rounded-2xl shadow-sm p-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-8 flex items-center gap-3 border-b pb-4">
        <i class="fas fa-undo-alt text-orange-600"></i> استرداد بضاعة للمخزن
    </h2>

    <form method="POST" action="<?= APP_URL ?>/salesrep/return-stock" class="space-y-6">
        <?= csrf_field() ?>
        <input type="hidden" name="rep_stock_id" value="<?= $stock['id'] ?>">
        
        <div class="bg-orange-50 p-4 rounded-xl border border-orange-100 mb-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs text-orange-600 font-bold uppercase tracking-wider">المادة</span>
                <span class="font-bold text-gray-800"><?= htmlspecialchars($stock['product_name']) ?></span>
            </div>
            <div class="flex justify-between items-center">
                <span class="text-xs text-orange-600 font-bold uppercase tracking-wider">الكمية الحالية بعهدة المندوب</span>
                <span class="font-bold text-blue-600 text-lg"><?= number_format($stock['quantity'], 2) ?></span>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2 font-bold text-orange-700">الكمية المستردة *</label>
            <input type="number" step="0.01" name="quantity" max="<?= $stock['quantity'] ?>" required 
                   class="w-full border border-orange-300 rounded-xl p-3 focus:ring-2 focus:ring-orange-400 outline-none font-bold text-xl" placeholder="0.00">
            <p class="text-xs text-gray-400 mt-2">لا يمكن استرداد كمية أكبر من المتاح بعهدة المندوب.</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">إيداع في المخزن *</label>
            <select name="to_warehouse_id" class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-orange-400 outline-none" required>
                <option value="">-- اختر مخزن الوجهة --</option>
                <?php foreach($warehouses as $wh): ?>
                <option value="<?= $wh['id'] ?>" <?= ($stock['assigned_from_warehouse_id'] == $wh['id']) ? 'selected' : '' ?>><?= htmlspecialchars($wh['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="pt-6 border-t flex flex-col md:flex-row gap-3">
            <a href="<?= APP_URL ?>/salesrep" class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl hover:bg-gray-200 transition font-bold text-center">إلغاء</a>
            <button type="submit" class="flex-1 bg-orange-600 text-white py-3 rounded-xl hover:bg-orange-700 shadow-lg shadow-orange-100 transition font-bold">تأكيد الاسترداد</button>
        </div>
    </form>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
