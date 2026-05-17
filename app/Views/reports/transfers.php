<?php
$pageTitle = 'التحويلات بين المخازن';
ob_start();
?>
<div class="bg-white rounded-xl shadow p-6">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
        <h2 class="text-xl font-bold text-gray-800">سجل التحويلات</h2>
        <a href="<?php echo APP_URL; ?>/reports/transfer-form" class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition">
            <i class="fas fa-plus"></i> تحويل جديد
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse border border-gray-200">
            <thead class="bg-gray-50 text-gray-700 text-xs">
                <tr>
                    <th class="border border-gray-200 px-3 py-2 text-right">رقم</th>
                    <th class="border border-gray-200 px-3 py-2 text-right">التاريخ</th>
                    <th class="border border-gray-200 px-3 py-2 text-right">من مخزن</th>
                    <th class="border border-gray-200 px-3 py-2 text-right">إلى مخزن</th>
                    <th class="border border-gray-200 px-3 py-2 text-right">المادة</th>
                    <th class="border border-gray-200 px-3 py-2 text-center">الكمية</th>
                    <th class="border border-gray-200 px-3 py-2 text-right">ملاحظات</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transfers as $t): ?>
                <tr class="hover:bg-gray-50">
                    <td class="border border-gray-200 px-3 py-2 font-mono text-gray-500 text-xs"><?php echo $t['id']; ?></td>
                    <td class="border border-gray-200 px-3 py-2 text-gray-600 text-xs"><?php echo $t['transfer_date']; ?></td>
                    <td class="border border-gray-200 px-3 py-2 font-semibold text-red-600"><?php echo htmlspecialchars($t['from_warehouse'] ?? ''); ?></td>
                    <td class="border border-gray-200 px-3 py-2 font-semibold text-green-600"><?php echo htmlspecialchars($t['to_warehouse'] ?? ''); ?></td>
                    <td class="border border-gray-200 px-3 py-2 font-bold text-gray-800"><?php echo htmlspecialchars($t['product_name'] ?? ''); ?></td>
                    <td class="border border-gray-200 px-3 py-2 text-center font-bold"><?php echo number_format($t['quantity'], 2); ?></td>
                    <td class="border border-gray-200 px-3 py-2 text-gray-500 text-xs"><?php echo htmlspecialchars($t['notes']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($transfers)): ?>
                <tr>
                    <td colspan="7" class="border border-gray-200 px-4 py-8 text-center text-gray-500">لا توجد عمليات تحويل مسجلة.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
