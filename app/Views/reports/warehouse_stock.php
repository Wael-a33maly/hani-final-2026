<?php
$pageTitle = 'جرد مخزن';
ob_start();
?>
<div class="bg-white rounded-xl shadow p-6">
    <form method="GET" action="<?php echo APP_URL; ?>/reports/warehouse-stock" class="mb-8 flex gap-4 items-end">
        <div class="flex-1 max-w-md">
            <label class="block text-gray-700 text-sm font-bold mb-2">اختر المخزن</label>
            <select name="warehouse_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500" required>
                <option value="">-- اختر مخزناً --</option>
                <?php foreach ($warehouses as $w): ?>
                    <option value="<?php echo $w['id']; ?>" <?php echo (isset($_GET['warehouse_id']) && $_GET['warehouse_id'] == $w['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($w['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded text-sm hover:bg-blue-700 transition">
            <i class="fas fa-search"></i> عرض الجرد
        </button>
    </form>

    <?php if ($selectedWarehouse): ?>
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-lg font-bold text-gray-800">
                أرصدة مخزن: <span class="text-blue-600"><?php echo htmlspecialchars($selectedWarehouse['name']); ?></span>
            </h3>
            <button onclick="window.print()" class="no-print bg-gray-100 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-200">
                <i class="fas fa-print"></i> طباعة
            </button>
        </div>
        
        <?php if (empty($stock)): ?>
            <div class="text-center text-gray-500 py-8">المخزن فارغ حالياً.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700 text-xs">
                        <tr>
                            <th class="border border-gray-200 px-4 py-2 text-right">رقم المادة</th>
                            <th class="border border-gray-200 px-4 py-2 text-right">اسم المادة</th>
                            <th class="border border-gray-200 px-4 py-2 text-center">الرصيد المتاح</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock as $item): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-200 px-4 py-2 font-mono text-gray-500"><?php echo $item['product_id']; ?></td>
                            <td class="border border-gray-200 px-4 py-2 font-semibold text-gray-800"><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="border border-gray-200 px-4 py-2 text-center font-bold <?php echo $item['stock'] > 0 ? 'text-green-600' : 'text-red-500'; ?>">
                                <?php echo number_format($item['stock'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
