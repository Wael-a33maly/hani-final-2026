<?php
$pageTitle = 'فاتورة مبيعات #' . htmlspecialchars($invoice['invoice_number']);
ob_start();

$companyName = $settings['company_name'] ?? APP_NAME;
$companyPhone = $settings['company_phone'] ?? '';
$companyLogo = $settings['company_logo'] ?? '';
$companyAddress = $settings['company_address'] ?? '';
?>
<div class="max-w-4xl mx-auto p-2 no-print">
    <div class="flex justify-between items-center bg-white p-3 rounded-xl shadow-sm border mb-4">
        <a href="<?= APP_URL ?>/sales" class="flex items-center gap-2 text-gray-500 hover:text-gray-800 transition text-sm">
            <i class="fas fa-arrow-right"></i> العودة
        </a>
        <button onclick="window.print()" class="bg-gray-800 text-white px-5 py-1.5 rounded-lg font-bold hover:bg-black transition flex items-center gap-2 shadow-lg text-sm">
            <i class="fas fa-print"></i> طباعة الفاتورة
        </button>
    </div>
</div>

<!-- Compressed Invoice Document -->
<div class="invoice-document bg-white mx-auto print:m-0 print:p-0" id="printable-invoice">
    <!-- Header -->
    <table class="w-full mb-4 pb-2 border-b border-gray-300">
        <tr>
            <td class="w-1/2 text-right align-top">
                <?php if($companyLogo): ?>
                    <img src="<?= APP_URL ?>/public/uploads/logo/<?= $companyLogo ?>" alt="Logo" class="h-14 w-auto object-contain mb-1">
                <?php endif; ?>
                <h1 class="text-lg font-bold text-gray-900 leading-tight"><?= htmlspecialchars($companyName) ?></h1>
                <p class="text-gray-600 text-[10px]"><?= htmlspecialchars($companyAddress) ?></p>
                <p class="text-gray-600 text-[10px] font-mono"><?= htmlspecialchars($companyPhone) ?></p>
            </td>
            <td class="w-1/2 text-left align-top">
                <h2 class="text-xl font-bold text-gray-300 uppercase mb-2">فاتورة مبيعات</h2>
                <table class="mr-auto text-[11px] text-gray-700">
                    <tr>
                        <td class="pl-3 py-0.5 text-gray-400 font-bold text-left">الرقم:</td>
                        <td class="py-0.5 font-bold">#<?= htmlspecialchars($invoice['invoice_number']) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3 py-0.5 text-gray-400 font-bold text-left">التاريخ:</td>
                        <td class="py-0.5"><?= date('Y-m-d', strtotime($invoice['date'])) ?></td>
                    </tr>
                    <tr>
                        <td class="pl-3 py-0.5 text-gray-400 font-bold text-left">الدفع:</td>
                        <td class="py-0.5">
                            <?php 
                                $pt = $invoice['payment_type'];
                                if($pt == 'cash') echo 'نقدي';
                                elseif($pt == 'installment') echo 'تقسيط';
                                elseif($pt == 'credit') echo 'آجل';
                                else echo $pt;
                            ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Billing Info -->
    <table class="w-full mb-4 text-[11px]">
        <tr>
            <td class="w-1/2 align-top">
                <h3 class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1 border-b w-20">بيانات العميل</h3>
                <p class="text-base font-bold text-gray-800"><?= htmlspecialchars($invoice['customer_name']) ?></p>
                <p class="text-gray-600"><?= htmlspecialchars($invoice['customer_phone'] ?? '-') ?></p>
                <p class="text-gray-600"><?= htmlspecialchars($invoice['address'] ?? ($invoice['area'] ?? '-')) ?></p>
            </td>
            <td class="w-1/2 align-top text-left">
                <h3 class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1 border-b w-20 mr-auto">المسؤول</h3>
                <p class="text-gray-600">المندوب: <span class="text-gray-900 font-bold"><?= htmlspecialchars($invoice['sales_rep_name'] ?? 'غير محدد') ?></span></p>
                <p class="text-gray-600">المخزن: <span class="text-gray-900 font-bold"><?= htmlspecialchars($invoice['warehouse_name'] ?? 'الرئيسي') ?></span></p>
            </td>
        </tr>
    </table>

    <!-- Items Table -->
    <table class="w-full text-right border-collapse mb-4 text-[11px]">
        <thead>
            <tr class="border-b border-gray-800 text-gray-800 font-bold bg-gray-50">
                <th class="py-1 px-1 w-8 text-center">#</th>
                <th class="py-1 px-1">البيان / الصنف</th>
                <th class="py-1 px-1 text-center w-16">الكمية</th>
                <th class="py-1 px-1 text-center w-20">السعر</th>
                <th class="py-1 px-1 text-left w-20">الإجمالي</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach($items as $idx => $item): ?>
            <tr>
                <td class="py-1.5 px-1 text-center text-gray-400"><?= $idx + 1 ?></td>
                <td class="py-1.5 px-1 text-gray-800"><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="py-1.5 px-1 text-center font-bold"><?= (float)$item['quantity'] ?></td>
                <td class="py-1.5 px-1 text-center text-gray-600 font-mono"><?= number_format($item['unit_price'], 2) ?></td>
                <td class="py-1.5 px-1 text-left font-bold text-gray-900 font-mono"><?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="border-t border-gray-400">
                <td colspan="4" class="py-2 px-1 text-left text-gray-500 font-bold">الإجمالي الكلي:</td>
                <td class="py-2 px-1 text-left font-black text-gray-900 font-mono text-sm"><?= number_format($invoice['total'], 2) ?></td>
            </tr>
            <?php if($invoice['payment_type'] == 'installment'): ?>
            <tr>
                <td colspan="4" class="py-0.5 px-1 text-left text-gray-400 text-[10px]">المقدم:</td>
                <td class="py-0.5 px-1 text-left text-gray-700 font-mono text-[10px]"><?= number_format($invoice['paid_upfront'], 2) ?></td>
            </tr>
            <tr class="border-t border-gray-100">
                <td colspan="4" class="py-0.5 px-1 text-left text-gray-800 font-bold text-[10px]">المتبقي للتقسيط:</td>
                <td class="py-0.5 px-1 text-left font-bold text-gray-900 font-mono text-[10px]"><?= number_format($invoice['total'] - $invoice['paid_upfront'], 2) ?></td>
            </tr>
            <?php endif; ?>
        </tfoot>
    </table>

    <!-- Installments Schedule -->
    <?php if($invoice['payment_type'] == 'installment' && !empty($installments)): ?>
    <div class="mb-4">
        <h3 class="text-[9px] font-bold text-gray-400 uppercase tracking-widest mb-1 border-b w-24">جدول الأقساط</h3>
        <table class="w-full text-right border-collapse text-[10px] border border-gray-200">
            <thead>
                <tr class="bg-gray-50 text-gray-500">
                    <th class="p-1 border border-gray-200 text-center">م</th>
                    <th class="p-1 border border-gray-200 text-center">المبلغ</th>
                    <th class="p-1 border border-gray-200 text-center">الاستحقاق</th>
                    <th class="p-1 border border-gray-200 text-center">الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($installments as $inst): ?>
                <tr>
                    <td class="p-1 border border-gray-200 text-center text-gray-400"><?= $inst['installment_number'] ?></td>
                    <td class="p-1 border border-gray-200 text-center font-bold font-mono"><?= number_format($inst['amount'], 2) ?></td>
                    <td class="p-1 border border-gray-200 text-center font-mono"><?= $inst['due_date'] ?></td>
                    <td class="p-1 border border-gray-200 text-center">
                        <span class="text-[8px]"><?= ($inst['status'] == 'paid' ? 'مسدد' : 'معلق') ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <!-- Signature Section -->
    <div class="mt-8 grid grid-cols-2 gap-10 text-center text-[10px]">
        <div class="space-y-4">
            <div class="border-b border-gray-300 h-6"></div>
            <p class="font-bold text-gray-400 uppercase tracking-widest">توقيع العميل</p>
        </div>
        <div class="space-y-4">
            <div class="border-b border-gray-300 h-6"></div>
            <p class="font-bold text-gray-400 uppercase tracking-widest">توقيع واعتماد الشركة</p>
        </div>
    </div>

    <!-- Legal Notes -->
    <?php if(!empty($invoice['notes'])): ?>
    <div class="mt-4 pt-2 border-t border-gray-50 text-[9px] text-gray-400 italic">
        ملاحظات: <?= htmlspecialchars($invoice['notes']) ?>
    </div>
    <?php endif; ?>
</div>

<style>
@import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap');

.invoice-document {
    width: 210mm;
    min-height: 297mm;
    padding: 10mm;
    font-family: 'Cairo', sans-serif;
    margin-bottom: 1rem;
    box-shadow: 0 0 10px rgba(0,0,0,0.02);
}

@media print {
    .no-print { display: none !important; }
    .invoice-document {
        width: 100% !important;
        box-shadow: none !important;
        margin: 0 !important;
        padding: 5mm !important;
        min-height: auto !important;
    }
    @page { size: A4; margin: 0; }
    body { background: white !important; }
}
</style>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/main.php';
?>
