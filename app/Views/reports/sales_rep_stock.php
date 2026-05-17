<?php
$pageTitle = 'تقرير عهدة المندوبين';
ob_start();
?>
<div class="bg-white rounded-xl shadow p-6">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-xl font-bold text-gray-800">البضاعة في عهدة المناديب</h2>
        <a href="<?php echo APP_URL; ?>/reports/assign-stock-form" class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700 transition">
            <i class="fas fa-box-open"></i> تسليم عهدة جديدة
        </a>
    </div>

    <form method="GET" action="<?php echo APP_URL; ?>/reports/sales-rep-stock" class="mb-8 flex gap-4 items-end">
        <div class="flex-1 max-w-md">
            <label class="block text-gray-700 text-sm font-bold mb-2">اختر المندوب</label>
            <select name="sales_rep_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:border-red-500" required>
                <option value="">-- جميع المناديب --</option>
                <?php foreach ($salesReps as $rep): ?>
                    <option value="<?php echo $rep['id']; ?>" <?php echo (isset($_GET['sales_rep_id']) && $_GET['sales_rep_id'] == $rep['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($rep['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-red-600 text-white px-6 py-2 rounded text-sm hover:bg-red-700 transition">
            <i class="fas fa-search"></i> عرض العهدة
        </button>
    </form>

    <?php if ($selectedRep): ?>
        <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">
            عهدة المندوب: <span class="text-red-600"><?php echo htmlspecialchars($selectedRep['full_name']); ?></span>
        </h3>
        
        <?php if (empty($stock)): ?>
            <div class="text-center text-gray-500 py-8">لا يوجد عهدة مسجلة على هذا المندوب.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700 text-xs">
                        <tr>
                            <th class="border border-gray-200 px-4 py-2 text-right">المادة</th>
                            <th class="border border-gray-200 px-4 py-2 text-center">الكمية المستلمة</th>
                            <th class="border border-gray-200 px-4 py-2 text-center">الكمية المباعة</th>
                            <th class="border border-gray-200 px-4 py-2 text-center">الكمية المرتجعة</th>
                            <th class="border border-gray-200 px-4 py-2 text-center">الرصيد المتبقي (بالسيارة)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stock as $item): 
                            $remaining = $item['quantity'] - $item['sold_qty'] - $item['returned_qty'];
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-200 px-4 py-2 font-bold text-gray-800"><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="border border-gray-200 px-4 py-2 text-center text-gray-600"><?php echo number_format($item['quantity'], 2); ?></td>
                            <td class="border border-gray-200 px-4 py-2 text-center text-blue-600"><?php echo number_format($item['sold_qty'], 2); ?></td>
                            <td class="border border-gray-200 px-4 py-2 text-center text-orange-500"><?php echo number_format($item['returned_qty'], 2); ?></td>
                            <td class="border border-gray-200 px-4 py-2 text-center font-bold <?php echo $remaining > 0 ? 'text-green-600' : 'text-gray-400'; ?>">
                                <?php echo number_format($remaining, 2); ?>
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
