<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إيصال رصيد افتتاحي - <?= htmlspecialchars($opening['customer_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        @page { size: 210mm 99mm; margin: 0; }
        html, body { width: 210mm; margin: 0; padding: 0; font-family: 'Cairo', 'Tahoma', sans-serif; background: #eee; font-size: 10pt; }
        @media print {
            @page { size: 210mm 99mm; margin: 0; }
            html, body { width: 210mm; margin: 0; padding: 0; background: #fff; }
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            .no-print { display: none !important; }
            .receipt-wrapper { margin-bottom: 0 !important; height: 99mm; overflow: hidden; }
        }
        .receipt-wrapper { width: 210mm; height: 99mm; margin: 0 auto; background: #fff; overflow: hidden; }
        .receipt { width: 210mm; height: 99mm; padding: 2mm 3mm; display: flex; flex-direction: column; box-sizing: border-box; background: #fff; overflow: hidden; border: 1px solid #000; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #000; padding-bottom: 1mm; min-height: 12mm; }
        .header-right { display: flex; align-items: center; gap: 2mm; }
        .header-logo { height: 10mm; width: auto; max-width: 25mm; }
        .opening-box { background: #0284c7; color: #ffffff; padding: 0.8mm 3mm; border-radius: 2px; text-align: center; }
        .opening-box-label { font-size: 5pt; font-weight: normal; }
        .opening-box-num { font-size: 10pt; font-weight: 900; display: block; line-height: 1.1; }
        .header-center { text-align: center; }
        .company-name { font-size: 14pt; font-weight: 900; color: #000000; line-height: 1.2; }
        .header-left { text-align: right; }
        .company-phone { font-size: 8pt; color: #000; font-weight: bold; }
        .content { display: flex; gap: 3mm; flex: 1; margin-top: 0.5mm; }
        .col { flex: 1; display: flex; flex-direction: column; gap: 1mm; }
        .box { border: 1px solid #000; padding: 2mm; border-radius: 2px; }
        .box-title { font-size: 9pt; font-weight: 900; color: #000; border-bottom: 1px solid #ccc; padding-bottom: 0.8mm; margin-bottom: 1mm; display: block; }
        .info-row { display: flex; justify-content: space-between; font-size: 9pt; margin-bottom: 0.8mm; line-height: 1.3; }
        .info-row span { color: #555; }
        .info-row strong { font-weight: 700; color: #000; }
        .dates-row { display: flex; gap: 1.5mm; }
        .amount-row { display: flex; gap: 1.5mm; }
        .amount-box, .remain-box, .date-box {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2mm;
            border: 1px solid #000;
            padding: 1.2mm;
            background: #eee;
            height: 8mm;
        }
        .date-box { background: #fff; }
        .amount-label, .remain-label, .date-label { font-size: 7pt; color: #444; font-weight: bold; }
        .amount-value, .remain-value, .date-val { font-weight: 900; font-size: 11pt; color: #000; }
        .date-val { font-size: 9pt; }
        .notes-box { border: 1px solid #000; padding: 1.5mm; flex: 1; }
        .rep-bar { display: flex; justify-content: space-between; align-items: center; border: 1px solid #000; padding: 0.5mm 1mm; font-size: 8pt; margin-top: auto; }
        .rep-bar strong { font-weight: 900; }
        .bottom-section { margin-top: 1mm; flex: 1; display: flex; flex-direction: column; }
        .no-print-btn { position: fixed; bottom: 20px; left: 20px; background: #cc0000; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 12px; z-index: 1000; cursor: pointer; border: none; }
        .header-logo { image-rendering: -webkit-optimize-contrast; backface-visibility: hidden; }
        .receipt-wrapper { page-break-inside: avoid; break-inside: avoid; transform: translateZ(0); }
    </style>
</head>
<body onload="window.print()">
    <button onclick="window.print()" class="no-print-btn no-print">طباعة الإيصال</button>

    <div class="receipt-wrapper">
        <div class="receipt">
            <div class="header">
                <div class="header-right">
                    <div class="opening-box">
                        <div class="opening-box-label">رصيد افتتاحي</div>
                        <div class="opening-box-num">إيصال</div>
                    </div>
                </div>
                <div class="header-center">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 2mm;">
                        <?php
                        $logo = $company['logo_path'] ?? '';
                        $logoPath = $logo ? APP_URL . '/public/' . ltrim($logo, '/') : '';
                        if($logoPath): ?><img src="<?= $logoPath ?>" class="header-logo"><?php endif; ?>
                        <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? 'شركة') ?></div>
                    </div>
                </div>
                <div class="header-left">
                    <div class="company-phone"><?= htmlspecialchars($company['company_phone'] ?? '') ?></div>
                </div>
            </div>

            <div class="content">
                <div class="col">
                    <div class="box">
                        <span class="box-title">بيانات العميل</span>
                        <div class="info-row"><span>كود العميل:</span><strong><?= $opening['customer_id'] ?></strong></div>
                        <div class="info-row"><span>اسم العميل:</span><strong><?= htmlspecialchars($opening['customer_name']) ?></strong></div>
                        <div class="info-row"><span>رقم التليفون:</span><strong><?= htmlspecialchars($opening['phone'] ?: '-') ?></strong></div>
                        <div class="info-row"><span>المنطقة:</span><strong><?= htmlspecialchars($opening['area'] ?: '-') ?></strong></div>
                        <div class="info-row"><span>العنوان:</span><strong><?= htmlspecialchars($opening['address'] ?: '-') ?></strong></div>
                    </div>

                    <div class="dates-row">
                        <div class="date-box">
                            <span class="date-label">تاريخ الإضافة:</span>
                            <span class="date-val"><?= htmlspecialchars($opening['installment_date']) ?></span>
                        </div>
                        <div class="date-box">
                            <span class="date-label">الحالة:</span>
                            <span class="date-val" style="color:<?= $opening['status'] === 'paid' ? '#16a34a' : ($opening['status'] === 'partial' ? '#ca8a04' : '#dc2626') ?>">
                                <?= $opening['status'] === 'paid' ? 'مدفوع' : ($opening['status'] === 'partial' ? 'جزئي' : 'معلق') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col">
                    <div class="box">
                        <span class="box-title">الرصيد الافتتاحي</span>
                        <div class="info-row"><span>البيان:</span><strong><?= htmlspecialchars($opening['notes'] ?: 'رصيد أول المدة') ?></strong></div>
                        <div class="info-row"><span>إجمالي الرصيد:</span><strong><?= number_format($opening['amount'], 2) ?></strong></div>
                        <div class="info-row"><span>المدفوع:</span><strong><?= number_format($totalPaid, 2) ?></strong></div>
                    </div>

                    <div class="amount-row">
                        <div class="amount-box">
                            <span class="amount-label">المبلغ:</span>
                            <span class="amount-value"><?= number_format($opening['amount'], 2) ?></span>
                        </div>
                        <div class="remain-box">
                            <span class="remain-label">المتبقي:</span>
                            <span class="remain-value"><?= number_format(max(0, $opening['amount'] - $totalPaid), 2) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bottom-section">
                <div class="notes-box">
                    <span style="font-size: 9pt; font-weight: bold; display: block; margin-bottom: 1mm;">ملاحظات:</span>
                    <span style="font-size: 9pt;"><?= htmlspecialchars($opening['notes'] ?: 'رصيد افتتاحي من قبل التطبيق') ?></span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>