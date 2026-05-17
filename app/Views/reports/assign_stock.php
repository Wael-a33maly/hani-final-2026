<?php
$pageTitle = 'تسليم عهدة بضاعة لمندوب';
ob_start();
?>
<div class="max-w-2xl mx-auto bg-white rounded-xl shadow p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-4 flex items-center gap-2">
        <i class="fas fa-box-open text-red-500"></i> إسناد بضاعة لسيارة المندوب
    </h2>

    <form method="POST" action="<?php echo APP_URL; ?>/reports/assign-stock" class="space-y-6">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">اسم المندوب المستلم *</label>
            <select name="sales_rep_id" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-red-400 outline-none">
                <option value="">-- اختر المندوب --</option>
                <?php foreach($salesReps as $rep): ?>
                <option value="<?php echo $rep['id']; ?>"><?php echo htmlspecialchars($rep['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">المادة المسلمة *</label>
                <select name="product_id" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-red-400 outline-none">
                    <option value="">-- اختر المادة --</option>
                    <?php foreach($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">صرف من المخزن *</label>
                <select name="warehouse_id" required class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-red-400 outline-none">
                    <option value="">-- اختر --</option>
                    <?php foreach($warehouses as $w): ?>
                    <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">الكمية المسلمة *</label>
            <input type="number" step="0.01" min="0.01" name="quantity" required placeholder="0.00"
                   class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-red-400 outline-none font-bold text-lg">
        </div>
        
        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="<?php echo APP_URL; ?>/reports/sales-rep-stock" class="bg-gray-200 text-gray-700 px-6 py-2 rounded hover:bg-gray-300 transition">إلغاء</a>
            <button type="submit" class="bg-red-600 text-white px-8 py-2 rounded hover:bg-red-700 transition font-bold" onclick="return confirm('تأكيد خصم البضاعة من المخزن وإضافتها لعهدة المندوب؟');">
                تسليم العهدة
            </button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
