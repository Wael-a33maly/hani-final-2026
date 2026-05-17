<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>طباعة إيصالات الأقساط</title>
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
            .receipt-wrapper { page-break-after: always; margin-bottom: 0 !important; height: 99mm; overflow: hidden; }
        }
        
        .receipt-wrapper { width: 210mm; height: 99mm; margin: 0 auto 5px auto; background: #fff; overflow: hidden; }
        .receipt { width: 210mm; height: 99mm; padding: 2mm 3mm; display: flex; flex-direction: column; box-sizing: border-box; background: #fff; overflow: hidden; border: 1px solid #000; }
        
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #000; padding-bottom: 1mm; min-height: 12mm; }
        .header-right { display: flex; align-items: center; gap: 2mm; }
        .header-logo { height: 10mm; width: auto; max-width: 25mm; }
        .inst-box { background: #cc0000; color: #ffffff; padding: 0.8mm 3mm; border-radius: 2px; text-align: center; }
        .inst-box-label { font-size: 5pt; font-weight: normal; }
        .inst-box-num { font-size: 12pt; font-weight: 900; display: block; line-height: 1.1; }
        .header-center { text-align: center; }
        .company-name { font-size: 14pt; font-weight: 900; color: #000000; line-height: 1.2; }
        .branch-name { font-size: 8pt; color: #444; margin-top: 0.5mm; }
        .header-left { text-align: right; }
        .company-phone { font-size: 8pt; color: #000; font-weight: bold; }
        .branch-phone { font-size: 7pt; color: #444; margin-top: 0.5mm; }
        
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
        
        .products-box { border: 1px solid #000; padding: 1.5mm; flex: 1; }
        .rep-bar { display: flex; justify-content: space-between; align-items: center; border: 1px solid #000; padding: 0.5mm 1mm; font-size: 8pt; margin-top: auto; }
        .rep-bar strong { font-weight: 900; }
        .bottom-section { margin-top: 1mm; flex: 1; display: flex; flex-direction: column; }
        .no-print-btn { position: fixed; bottom: 20px; left: 20px; background: #cc0000; color: #fff; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-size: 12px; z-index: 1000; cursor: pointer; border: none; }

        /* تحسينات سرعة المعالجة للطابعة */
        .header-logo { image-rendering: -webkit-optimize-contrast; backface-visibility: hidden; }
        .receipt-wrapper { page-break-inside: avoid; break-inside: avoid; transform: translateZ(0); }
    </style>
</head>
<body onload="window.print()">
    <a href="javascript:window.print()" class="no-print-btn no-print">طباعة الكل</a>

    <?php 
    foreach ($installments as $inst): ?>
    <div class="receipt-wrapper">
        <div class="receipt">
            <div class="header">
                <div class="header-right">
                    <div class="inst-box">
                        <div class="inst-box-label">رقم القسط</div>
                        <div class="inst-box-num"><?= $inst['installment_number'] ?> / <?= $inst['total_installments_count'] ?></div>
                    </div>
                </div>
                <div class="header-center">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 2mm;">
                        <?php 
                        $logo = $company['logo_path'] ?? '';
                        $logoPath = $logo ? APP_URL . '/public/' . ltrim($logo, '/') : '';
                        if($logoPath): ?><img src="<?= $logoPath ?>" class="header-logo"><?php endif; ?>
                        <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? 'شركة عياش للتجارة') ?></div>
                    </div>
                    <div class="branch-name"><?= htmlspecialchars($inst['branch_data']['name'] ?? '') ?></div>
                </div>
                <div class="header-left">
                    <div class="company-phone"><?= htmlspecialchars($company['company_phone'] ?? '') ?></div>
                    <div class="branch-phone"><?= htmlspecialchars($inst['branch_data']['phone'] ?? '') ?></div>
                </div>
            </div>
            
            <div class="content">
                <div class="col">
                    <div class="box">
                        <span class="box-title">بيانات العميل</span>
                        <div class="info-row"><span>كود العميل:</span><strong><?= $inst['customer_id'] ?></strong></div>
                        <div class="info-row"><span>اسم العميل:</span><strong><?= htmlspecialchars($inst['customer_name']) ?></strong></div>
                        <div class="info-row"><span>رقم التليفون:</span><strong><?= htmlspecialchars($inst['phone'] ?: '-') ?></strong></div>
                        <div class="info-row"><span>المنطقة:</span><strong><?= htmlspecialchars($inst['area'] ?: '-') ?></strong></div>
                        <div class="info-row"><span>العنوان:</span><strong><?= htmlspecialchars($inst['address'] ?: '-') ?></strong></div>
                    </div>
                    
                    <div class="dates-row">
                        <div class="date-box">
                            <span class="date-label">تاريخ:</span>
                            <span class="date-val"><?= htmlspecialchars($inst['due_date']) ?></span>
                        </div>
                        <div class="date-box">
                            <span class="date-label">القادم:</span>
                            <span class="date-val"><?= htmlspecialchars($inst['next_installment_date'] ?? 'الأخيرة') ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col">
                    <div class="box">
                        <span class="box-title">بيانات الفاتورة والأقساط</span>
                        <div class="info-row"><span>رقم الفاتورة:</span><strong><?= htmlspecialchars($inst['invoice_number']) ?></strong></div>
                        <div class="info-row"><span>إجمالي الفاتورة:</span><strong><?= number_format($inst['total']) ?></strong></div>
                        <div class="info-row"><span>تاريخ الفاتورة:</span><strong><?= $inst['invoice_date'] ?></strong></div>
                        <div class="info-row"><span>المقدم:</span><strong><?= number_format($inst['paid_upfront']) ?></strong></div>
                        <div class="info-row"><span>عدد الأقساط:</span><strong><?= $inst['total_installments_count'] ?></strong></div>
                    </div>
                    
                    <div class="amount-row">
                        <div class="amount-box">
                            <span class="amount-label">المبلغ:</span>
                            <span class="amount-value"><?= number_format($inst['amount']) ?></span>
                        </div>
                        <div class="remain-box">
                            <span class="remain-label">المتبقي:</span>
                            <span class="remain-value"><?= number_format($inst['remaining_after_this']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bottom-section">
                <div class="products-box">
                <div style="font-size: 9.5pt; line-height: 1.4; text-align: right; font-weight: bold;">
                    <strong>المنتجات:</strong> 
                    <?php 
                    $productStrings = [];
                    foreach($inst['items'] ?? [] as $item) {
                        $qty = (int)$item['quantity'];
                        $productStrings[] = "عدد " . $qty . " قطعة " . htmlspecialchars($item['product_name']) . " x " . number_format($item['unit_price']);
                    }
                    echo implode(' // ', $productStrings);
                    ?>
                </div>
            </div>
                
                <div class="rep-bar">
                    <span><strong>المندوب:</strong> <?= htmlspecialchars($inst['sales_rep_name'] ?? 'غير محدد') ?></span>
                    <span><strong>ت:</strong> <?= htmlspecialchars($inst['sales_rep_phone'] ?? '-') ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</body>
</html>
