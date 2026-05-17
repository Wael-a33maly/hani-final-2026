<?php
$pageTitle = 'حركة مادة (كارت صنف)';
ob_start();
?>
<div class="bg-white rounded-xl shadow p-6">
    <form method="GET" action="<?php echo APP_URL; ?>/reports/product-movements" class="mb-8 bg-gray-50 p-4 rounded-lg border">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            <div class="md:col-span-2">
                <label class="block text-gray-700 text-sm font-bold mb-2">اختر المادة *</label>
                <select name="product_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:border-indigo-500" required>
                    <option value="">-- اختر مادة --</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo (isset($_GET['product_id']) && $_GET['product_id'] == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">من تاريخ</label>
                <input type="date" name="from" value="<?php echo $_GET['from'] ?? date('Y-m-01'); ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">إلى تاريخ</label>
                <input type="date" name="to" value="<?php echo $_GET['to'] ?? date('Y-m-d'); ?>" class="w-full border rounded px-3 py-2">
            </div>
        </div>
        <div class="mt-4 text-left">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded text-sm hover:bg-indigo-700 transition">
                <i class="fas fa-search"></i> عرض حركة المادة
            </button>
        </div>
    </form>

    <?php if ($selectedProduct): ?>
        <div class="flex justify-between items-center mb-4 border-b pb-2">
            <h3 class="text-lg font-bold text-gray-800">
                حركة المادة: <span class="text-indigo-600"><?php echo htmlspecialchars($selectedProduct['name']); ?></span>
            </h3>
            <div class="flex gap-2">
                <a href="<?php echo APP_URL; ?>/reports/product-movements?product_id=<?php echo $selectedProduct['id']; ?>&from=<?php echo $_GET['from'] ?? date('Y-m-01'); ?>&to=<?php echo $_GET['to'] ?? date('Y-m-d'); ?>&print=1" target="_blank" class="no-print bg-gray-100 text-gray-700 px-4 py-2 rounded text-sm hover:bg-gray-200">
                    <i class="fas fa-print"></i> طباعة كارت الصنف
                </a>
            </div>
        </div>

        <?php if ($openingBalance == 0 && empty($movements)): ?>
            <div class="text-center text-gray-500 py-8">لا توجد حركات لهذه المادة في الفترة المحددة.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-sm border-collapse border border-gray-200">
                    <thead class="bg-gray-50 text-gray-700 text-xs">
                        <tr>
                            <th class="border border-gray-200 px-3 py-2 text-right">التاريخ</th>
                            <th class="border border-gray-200 px-3 py-2 text-center">البيان</th>
                            <th class="border border-gray-200 px-3 py-2 text-center">وارد</th>
                            <th class="border border-gray-200 px-3 py-2 text-center">منصرف</th>
                            <th class="border border-gray-200 px-3 py-2 text-center">الرصيد</th>
                            <th class="border border-gray-200 px-3 py-2 text-right">المرجع</th>
                            <th class="border border-gray-200 px-3 py-2 text-right">المخزن</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $balance = $openingBalance;
                        ?>
                        <tr class="bg-yellow-50 font-semibold">
                            <td class="border border-gray-200 px-3 py-2 text-gray-600"><?php echo $openingDate; ?></td>
                            <td class="border border-gray-200 px-3 py-2 text-center text-amber-700">رصيد افتتاحي</td>
                            <td class="border border-gray-200 px-3 py-2 text-center text-green-600 font-bold"><?php echo $openingBalance > 0 ? number_format($openingBalance, 2) : '-'; ?></td>
                            <td class="border border-gray-200 px-3 py-2 text-center text-red-600 font-bold">-</td>
                            <td class="border border-gray-200 px-3 py-2 text-center font-bold text-lg"><?php echo number_format($balance, 2); ?></td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-500">-</td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-500">-</td>
                        </tr>
                        <?php foreach ($movements as $mov):
                            $qty = (float)$mov['quantity'];
                            if ($mov['type'] == 'in') {
                                $balance += $qty;
                            } else {
                                $balance -= $qty;
                            }
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-200 px-3 py-2 text-gray-600"><?php echo date('Y/m/d', strtotime($mov['created_at'])); ?></td>
                            <td class="border border-gray-200 px-3 py-2 text-center font-bold <?php echo $mov['type'] == 'in' ? 'text-green-600' : 'text-red-500'; ?>">
                                <?php echo $mov['type'] == 'in' ? 'وارد' : 'منصرف'; ?>
                            </td>
                            <td class="border border-gray-200 px-3 py-2 text-center text-green-600 font-bold">
                                <?php echo $mov['type'] == 'in' ? number_format($qty, 2) : '-'; ?>
                            </td>
                            <td class="border border-gray-200 px-3 py-2 text-center text-red-600 font-bold">
                                <?php echo $mov['type'] == 'out' ? number_format($qty, 2) : '-'; ?>
                            </td>
                            <td class="border border-gray-200 px-3 py-2 text-center font-bold text-lg <?php echo $balance > 0 ? 'text-blue-600' : ($balance < 0 ? 'text-red-600' : ''); ?>">
                                <?php echo number_format($balance, 2); ?>
                            </td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-500">
                                <?php
                                    echo htmlspecialchars($mov['reference']);
                                    if ($mov['reference_id']) echo ' #' . htmlspecialchars($mov['reference_id']);
                                ?>
                            </td>
                            <td class="border border-gray-200 px-3 py-2 text-gray-500"><?php echo htmlspecialchars($mov['warehouse_name'] ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-left text-sm text-gray-500">
                الرصيد الختامي في الفترة: <strong class="<?php echo $balance > 0 ? 'text-green-600' : ($balance < 0 ? 'text-red-600' : ''); ?>"><?php echo number_format($balance, 2); ?></strong>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
