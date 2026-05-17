<?php
$pageTitle = 'جرد مادة';
ob_start();
?>
<div class="bg-white rounded-xl shadow p-6">
    <form method="GET" action="<?php echo APP_URL; ?>/reports/product-stock" class="mb-8 flex gap-4 items-end">
        <div class="flex-1 max-w-md">
            <label class="block text-gray-700 text-sm font-bold mb-2">اختر المادة</label>
            <select name="product_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500" required>
                <option value="">-- اختر مادة --</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?php echo $p['id']; ?>" <?php echo (isset($_GET['product_id']) && $_GET['product_id'] == $p['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded text-sm hover:bg-blue-700 transition">
            <i class="fas fa-search"></i> عرض الجرد
        </button>
    </form>

    <?php if ($selectedProduct): ?>
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
            رصيد المادة: <span class="text-blue-600"><?php echo htmlspecialchars($selectedProduct['name']); ?></span>
        </h3>
        
        <?php if (empty($stockData)): ?>
            <div class="text-center text-gray-500 py-8">لا يوجد رصيد لهذه المادة في أي مخزن حالياً.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs">
                        <tr>
                            <th class="px-4 py-3 text-right border-b">المخزن</th>
                            <th class="px-4 py-3 text-right border-b">الفرع التابع له</th>
                            <th class="px-4 py-3 text-center border-b">الرصيد المتاح</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php 
                        $total = 0;
                        foreach ($stockData as $stock): 
                            $total += $stock['quantity'];
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-semibold text-gray-800"><?php echo htmlspecialchars($stock['warehouse_name'] ?? 'غير معروف'); ?></td>
                            <td class="px-4 py-3 text-gray-600"><?php echo htmlspecialchars($stock['branch_name'] ?? 'بدون فرع'); ?></td>
                            <td class="px-4 py-3 text-center font-bold <?php echo $stock['quantity'] > 0 ? 'text-green-600' : 'text-red-500'; ?>">
                                <?php echo number_format($stock['quantity'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-gray-50 font-bold">
                        <tr>
                            <td colspan="2" class="px-4 py-3 text-left">إجمالي الرصيد:</td>
                            <td class="px-4 py-3 text-center text-lg text-blue-700"><?php echo number_format($total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
