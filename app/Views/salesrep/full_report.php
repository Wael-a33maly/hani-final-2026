<?php requireLogin(); $pageTitle = 'تقرير جرد المندوبين'; ob_start(); ?>
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-8 border-b pb-4">
        <h2 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-clipboard-list text-indigo-600"></i> تقرير جرد عهدة المندوبين
        </h2>
        <a href="<?= APP_URL ?>/salesrep/full-report?<?= http_build_query(array_merge($_GET, ['print' => 1])) ?>" target="_blank" class="no-print bg-gray-100 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-200 transition flex items-center gap-2 text-sm font-bold">
            <i class="fas fa-print"></i> طباعة التقرير
        </a>
    </div>

    <form method="GET" class="no-print grid grid-cols-1 md:grid-cols-5 gap-4 mb-8 bg-indigo-50 p-6 rounded-2xl border border-indigo-100">
        <div class="md:col-span-1">
            <label class="block text-xs text-indigo-600 font-bold mb-1 uppercase">المندوب</label>
            <select name="sales_rep_id" class="w-full border border-indigo-200 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
                <option value="">-- كل المندوبين --</option>
                <?php foreach($users as $u): ?>
                <option value="<?= $u['id'] ?>" <?= ($salesRepId == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-1">
            <label class="block text-xs text-indigo-600 font-bold mb-1 uppercase">المادة</label>
            <select name="product_id" class="w-full border border-indigo-200 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-400 outline-none select2">
                <option value="">-- كل المواد --</option>
                <?php foreach($products as $p): ?>
                <option value="<?= $p['id'] ?>" <?= ($productId == $p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-indigo-600 font-bold mb-1 uppercase">من تاريخ</label>
            <input type="date" name="from_date" value="<?= $fromDate ?>" class="w-full border border-indigo-200 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
        </div>
        <div>
            <label class="block text-xs text-indigo-600 font-bold mb-1 uppercase">إلى تاريخ</label>
            <input type="date" name="to_date" value="<?= $toDate ?>" class="w-full border border-indigo-200 rounded-xl p-2.5 text-sm focus:ring-2 focus:ring-indigo-400 outline-none">
        </div>
        <div class="flex items-end">
            <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-100 transition font-bold">
                تحديث التقرير
            </button>
        </div>
    </form>
    
    <?php if (!empty($salesRepId) && !empty($reportData)): ?>
    <div class="overflow-x-auto border rounded-xl overflow-hidden shadow-sm">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                <tr>
                    <th class="p-4">المادة</th>
                    <th class="p-4">الوحدة</th>
                    <th class="p-4 text-center">المسند كلياً</th>
                    <th class="p-4 text-center text-green-600">المباع</th>
                    <th class="p-4 text-center text-orange-600">المسترد</th>
                    <th class="p-4 text-center text-indigo-600">الرصيد المتبقي</th>
                    <th class="p-4 text-left">قيمة العهدة</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php 
                $totalAssigned = 0; $totalSold = 0; $totalReturned = 0; $totalRemaining = 0; $totalValue = 0;
                foreach($reportData as $row): 
                    $remainingValue = $row['current_quantity'] * $row['selling_price'];
                    $totalAssigned += $row['assigned_quantity'];
                    $totalSold += $row['sold_quantity'];
                    $totalReturned += $row['returned_quantity'];
                    $totalRemaining += $row['current_quantity'];
                    $totalValue += $remainingValue;
                ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-4">
                        <div class="font-bold text-gray-800"><?= htmlspecialchars($row['product_name']) ?></div>
                        <div class="text-xs text-gray-400 font-mono"><?= $row['barcode'] ?></div>
                    </td>
                    <td class="p-4 text-gray-500"><?= $row['unit_name'] ?></td>
                    <td class="p-4 text-center font-bold"><?= number_format($row['assigned_quantity'], 2) ?></td>
                    <td class="p-4 text-center text-green-600 font-bold"><?= number_format($row['sold_quantity'], 2) ?></td>
                    <td class="p-4 text-center text-orange-600 font-bold"><?= number_format($row['returned_quantity'], 2) ?></td>
                    <td class="p-4 text-center">
                        <span class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded-full font-bold">
                            <?= number_format($row['current_quantity'], 2) ?>
                        </span>
                    </td>
                    <td class="p-4 text-left font-bold text-indigo-900"><?= number_format($remainingValue, 2) ?> ج.م</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-indigo-900 text-white font-bold">
                <tr>
                    <td colspan="2" class="p-4 text-left">إجمالي الجرد</td>
                    <td class="p-4 text-center"><?= number_format($totalAssigned, 2) ?></td>
                    <td class="p-4 text-center"><?= number_format($totalSold, 2) ?></td>
                    <td class="p-4 text-center"><?= number_format($totalReturned, 2) ?></td>
                    <td class="p-4 text-center"><?= number_format($totalRemaining, 2) ?></td>
                    <td class="p-4 text-left text-lg text-yellow-400"><?= number_format($totalValue, 2) ?> ج.م</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php elseif(!empty($salesRepId)): ?>
    <div class="text-center py-20 bg-gray-50 rounded-2xl border-2 border-dashed">
        <i class="fas fa-folder-open text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 font-bold italic">لا توجد حركات عهدة مسجلة لهذا المندوب خلال الفترة المختارة</p>
    </div>
    <?php else: ?>
    <div class="text-center py-20 bg-indigo-50 rounded-2xl border-2 border-dashed border-indigo-100">
        <i class="fas fa-chart-pie text-4xl text-indigo-200 mb-4"></i>
        <p class="text-indigo-600 font-bold">يرجى تحديد المندوب وفترة التقرير لعرض نتائج الجرد</p>
    </div>
    <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
