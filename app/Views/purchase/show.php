<?php
$pageTitle = 'عرض فاتورة مشتريات #' . htmlspecialchars($invoice['invoice_number']);
ob_start();
?>
<div class="max-w-4xl mx-auto bg-white rounded-xl shadow p-8 print:shadow-none print:p-0">
    <!-- Header -->
    <div class="flex justify-between items-start border-b-2 border-gray-800 pb-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">فاتورة مشتريات</h1>
            <h2 class="text-xl text-gray-600 mt-2"><?php echo APP_NAME; ?></h2>
        </div>
        <div class="text-left">
            <p class="font-bold text-lg">رقم الفاتورة: <span class="text-blue-600 font-mono"><?php echo htmlspecialchars($invoice['invoice_number']); ?></span></p>
            <p class="text-gray-600 mt-1">التاريخ: <?php echo date('Y/m/d', strtotime($invoice['date'])); ?></p>
        </div>
    </div>

    <!-- Invoice Details -->
    <div class="grid grid-cols-2 gap-8 mb-8 border border-gray-200 rounded-lg p-6 bg-gray-50">
        <div>
            <h3 class="text-sm font-bold text-gray-500 mb-1">بيانات المورد:</h3>
            <p class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($invoice['supplier_name'] ?? 'مورد محذوف'); ?></p>
        </div>
        <div>
            <h3 class="text-sm font-bold text-gray-500 mb-1">طريقة الدفع:</h3>
            <p class="text-lg font-bold text-gray-800">
                <?php 
                    $pt = $invoice['payment_type'] ?? 'cash';
                    if($pt == 'cash') echo 'نقداً';
                    elseif($pt == 'credit') echo 'آجل';
                    elseif($pt == 'bank') echo 'تحويل بنكي';
                    else echo $pt;
                ?>
            </p>
        </div>
        <?php if(!empty($invoice['notes'])): ?>
        <div class="col-span-2">
            <h3 class="text-sm font-bold text-gray-500 mb-1">ملاحظات:</h3>
            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Items Table -->
    <table class="w-full text-sm border-collapse border border-gray-200 mb-8">
        <thead class="bg-gray-800 text-white">
            <tr>
                <th class="border border-gray-700 px-4 py-3 text-right">م</th>
                <th class="border border-gray-700 px-4 py-3 text-right">المادة (المنتج)</th>
                <th class="border border-gray-700 px-4 py-3 text-center">الكمية</th>
                <th class="border border-gray-700 px-4 py-3 text-center">الوحدة</th>
                <th class="border border-gray-700 px-4 py-3 text-center">سعر الوحدة</th>
                <th class="border border-gray-700 px-4 py-3 text-center">الإجمالي</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php 
            $totalCalc = 0;
            foreach ($items as $index => $item): 
                $lineTotal = $item['quantity'] * $item['unit_price'];
                $totalCalc += $lineTotal;
            ?>
            <tr class="hover:bg-gray-50">
                <td class="border border-gray-200 px-4 py-3 text-gray-600"><?php echo $index + 1; ?></td>
                <td class="border border-gray-200 px-4 py-3 font-semibold text-gray-800">
                    <?php echo htmlspecialchars($item['product_name']); ?>
                    <?php if($item['barcode']): ?>
                        <br><span class="text-xs text-gray-500 font-mono"><?php echo htmlspecialchars($item['barcode']); ?></span>
                    <?php endif; ?>
                </td>
                <td class="border border-gray-200 px-4 py-3 text-center font-bold"><?php echo $item['quantity']; ?></td>
                <td class="border border-gray-200 px-4 py-3 text-center text-gray-600"><?php echo htmlspecialchars($item['unit_name'] ?? '-'); ?></td>
                <td class="border border-gray-200 px-4 py-3 text-center"><?php echo number_format($item['unit_price'], 3); ?></td>
                <td class="border border-gray-200 px-4 py-3 text-center font-bold text-gray-800"><?php echo number_format($lineTotal, 3); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot class="bg-gray-50 font-bold text-lg">
            <tr>
                <td colspan="5" class="border border-gray-200 px-4 py-4 text-left">إجمالي الفاتورة:</td>
                <td class="border border-gray-200 px-4 py-4 text-center text-green-700"><?php echo number_format($totalCalc, 3); ?> د.ك</td>
            </tr>
        </tfoot>
    </table>

    <!-- Signatures -->
    <div class="mt-16 grid grid-cols-2 gap-8 text-center print:block">
        <div>
            <p class="font-bold text-gray-700 border-b border-gray-300 pb-2 mb-8 mx-12">توقيع المستلم (المخازن)</p>
        </div>
        <div>
            <p class="font-bold text-gray-700 border-b border-gray-300 pb-2 mb-8 mx-12">توقيع المحاسب</p>
        </div>
    </div>

    <!-- Print Button -->
    <div class="mt-12 text-center no-print">
        <button onclick="window.print()" class="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-bold hover:bg-blue-700 transition shadow-lg inline-flex items-center gap-2">
            <i class="fas fa-print"></i> طباعة الفاتورة
        </button>
        <a href="<?php echo APP_URL; ?>/purchases" class="block mt-4 text-gray-500 hover:text-gray-800 underline text-sm">العودة لقائمة المشتريات</a>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
