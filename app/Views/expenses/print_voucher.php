<?php
$pageTitle = 'طباعة سند صرف ' . htmlspecialchars($voucher['voucher_number']);
ob_start();
?>
<div class="max-w-4xl mx-auto bg-white rounded-xl shadow p-8 print:shadow-none print:p-0">
    <!-- Header -->
    <div class="flex justify-between items-start border-b-2 border-gray-800 pb-4 mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">سند صرف نقدي</h1>
            <h2 class="text-xl text-gray-600 mt-2"><?php echo APP_NAME; ?></h2>
        </div>
        <div class="text-left">
            <p class="font-bold text-lg">رقم السند: <span class="text-red-600 font-mono"><?php echo htmlspecialchars($voucher['voucher_number']); ?></span></p>
            <p class="text-gray-600 mt-1">التاريخ: <?php echo date('Y/m/d', strtotime($voucher['date'])); ?></p>
        </div>
    </div>

    <!-- Content -->
    <div class="space-y-6 text-lg border-2 border-gray-200 p-8 rounded-xl relative">
        <div class="absolute -top-4 -right-4 text-gray-200 opacity-20">
            <i class="fas fa-file-invoice-dollar" style="font-size: 15rem;"></i>
        </div>
        
        <div class="flex items-center gap-4 border-b border-dashed border-gray-300 pb-4">
            <div class="w-32 font-bold text-gray-700">دفعنا إلى السيد/ة:</div>
            <div class="flex-1 font-semibold text-xl border-b border-gray-400 pb-1 text-center">
                <?php echo htmlspecialchars($voucher['expense_name'] ?? 'مورد / أخرى'); ?>
            </div>
        </div>

        <div class="flex items-center gap-4 border-b border-dashed border-gray-300 pb-4">
            <div class="w-32 font-bold text-gray-700">مبلغاً وقدره:</div>
            <div class="flex-1 font-semibold text-xl border-b border-gray-400 pb-1 text-center">
                <?php echo number_format($voucher['amount'], 2); ?> د.ك
            </div>
        </div>

        <div class="flex items-center gap-4 border-b border-dashed border-gray-300 pb-4">
            <div class="w-32 font-bold text-gray-700">وذلك عن:</div>
            <div class="flex-1 font-semibold text-lg border-b border-gray-400 pb-1 min-h-[2rem]">
                <?php echo nl2br(htmlspecialchars($voucher['notes'])); ?>
            </div>
        </div>

        <div class="flex items-center gap-4 border-b border-dashed border-gray-300 pb-4">
            <div class="w-32 font-bold text-gray-700">طريقة الدفع:</div>
            <div class="flex-1 font-semibold text-lg">
                <?php 
                    $pt = $voucher['payment_type'] ?? 'cash';
                    if($pt == 'cash') echo 'نقداً';
                    elseif($pt == 'knet') echo 'كي نت';
                    elseif($pt == 'bank') echo 'تحويل بنكي';
                    else echo $pt;
                ?>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="mt-16 grid grid-cols-3 gap-8 text-center">
        <div>
            <p class="font-bold text-gray-700 border-b border-gray-300 pb-2 mb-8">توقيع المستلم</p>
            <p></p>
        </div>
        <div>
            <p class="font-bold text-gray-700 border-b border-gray-300 pb-2 mb-8">المحاسب</p>
            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($voucher['created_by_name'] ?? ''); ?></p>
        </div>
        <div>
            <p class="font-bold text-gray-700 border-b border-gray-300 pb-2 mb-8">المدير المالي</p>
            <p></p>
        </div>
    </div>

    <!-- Print Button -->
    <div class="mt-12 text-center no-print">
        <button onclick="window.print()" class="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-bold hover:bg-blue-700 transition shadow-lg flex items-center justify-center gap-2 mx-auto">
            <i class="fas fa-print"></i> طباعة السند
        </button>
        <a href="<?php echo APP_URL; ?>/expenses/vouchers" class="block mt-4 text-gray-500 hover:text-gray-800 underline text-sm">العودة للقائمة</a>
    </div>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
