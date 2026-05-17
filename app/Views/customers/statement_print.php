<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب - <?= htmlspecialchars($customer['name']) ?></title>
    <style>
        @page {
            margin: 12mm 10mm 16mm 10mm;
            size: A4 portrait;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Tahoma', 'Arial', sans-serif;
            font-size: 9.5pt;
            color: #1e293b;
            background: #fff;
            direction: rtl;
            line-height: 1.5;
        }

        .report-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding-bottom: 12px;
            border-bottom: 3px solid #1e40af;
            margin-bottom: 12px;
        }
        .report-header .logo-area {
            flex-shrink: 0;
            width: 75px;
            height: 75px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .report-header .logo-area img {
            max-width: 75px;
            max-height: 75px;
        }
        .report-header .logo-placeholder {
            width: 75px;
            height: 75px;
            background: #f1f5f9;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8pt;
            color: #94a3b8;
        }
        .report-header .company-area { flex: 1; }
        .report-header .company-area h1 {
            font-size: 16pt;
            font-weight: 700;
            color: #1e40af;
        }
        .report-header .company-area .company-details {
            font-size: 8pt;
            color: #64748b;
            margin-top: 2px;
        }
        .report-header .company-area .company-details .sep {
            margin: 0 5px;
            color: #cbd5e1;
        }
        .report-header .doc-badge {
            flex-shrink: 0;
            text-align: center;
            background: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 6px;
            padding: 6px 14px;
        }
        .report-header .doc-badge .doc-title {
            font-size: 12pt;
            font-weight: 700;
            color: #1e40af;
        }
        .report-header .doc-badge .doc-sub {
            font-size: 7pt;
            color: #64748b;
        }

        .info-panel {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            padding: 10px 14px;
            margin-bottom: 12px;
        }
        .info-panel .col p {
            font-size: 9pt;
            margin-bottom: 2px;
        }
        .info-panel .col p:last-child { margin-bottom: 0; }
        .info-panel .col strong { color: #1e40af; font-weight: 600; }
        .info-panel .col-left { text-align: left; }

        .client-totals {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }
        .client-totals .ct-box {
            flex: 1;
            text-align: center;
            padding: 6px 4px;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            background: #fff;
        }
        .client-totals .ct-box .ct-label {
            font-size: 7pt;
            color: #64748b;
        }
        .client-totals .ct-box .ct-value {
            font-size: 11pt;
            font-weight: 700;
            margin-top: 1px;
        }
        .ct-owed .ct-value { color: #1e40af; }
        .ct-paid .ct-value { color: #16a34a; }
        .ct-remaining .ct-value { color: #dc2626; }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        thead th {
            background: #1e40af;
            color: #fff;
            padding: 7px 4px;
            text-align: center;
            font-weight: 600;
            font-size: 8pt;
        }
        thead th:first-child { border-radius: 0 3px 0 0; }
        thead th:last-child { border-radius: 3px 0 0 0; }
        tbody td {
            border: 1px solid #e2e8f0;
            padding: 5px 4px;
            text-align: center;
            font-size: 8.5pt;
        }
        tbody tr:nth-child(even) { background: #fafbfc; }
        .row-opening {
            background: #fef9c3 !important;
            font-weight: 600;
        }
        .row-opening td { border-color: #fde68a; }
        .row-opening-installment {
            background: #f0f9ff !important;
        }
        .row-opening-installment td { border-color: #bae6fd; }
        .opening-note {
            display: block; font-size: 7pt; color: #0369a1;
            margin-top: 1px; font-weight: 400;
        }
        .text-debit { color: #dc2626; font-weight: 600; }
        .text-credit { color: #16a34a; font-weight: 600; }
        .balance-debit { color: #dc2626; font-weight: 700; }
        .balance-credit { color: #16a34a; font-weight: 700; }

        tfoot .footer-row {
            background: #1e293b !important;
            color: #fff !important;
        }
        tfoot .footer-row td {
            border-color: #334155;
            padding: 7px 4px;
            font-weight: 700;
            font-size: 9pt;
        }
        tfoot .footer-row .final-balance { font-size: 11pt; }

        .summary-grid {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            margin-top: 12px;
        }
        .summary-card {
            flex: 1;
            text-align: center;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 7px 4px;
        }
        .summary-card .s-label {
            font-size: 7pt;
            color: #64748b;
        }
        .summary-card .s-value {
            font-size: 11pt;
            font-weight: 700;
            color: #1e40af;
            margin-top: 2px;
        }
        .summary-card .s-value.debit { color: #dc2626; }
        .summary-card .s-value.credit { color: #16a34a; }

        .signature-area {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 8.5pt;
        }
        .signature-area .sig-box {
            text-align: center;
            min-width: 140px;
        }
        .signature-area .sig-box .sig-line {
            margin-top: 28px;
            border-top: 1px solid #94a3b8;
            padding-top: 5px;
            font-size: 7.5pt;
            color: #64748b;
        }

        .report-footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 7pt;
            color: #94a3b8;
        }

        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <?php
    $logo = $company['logo_path'] ?? '';
    $logoUrl = $logo ? APP_URL . '/public/' . ltrim($logo, '/') : '';
    $rPaidP = $opening < 0 ? abs($opening) : 0;
    $rOwedP = $opening > 0 ? $opening : 0;
    $runningP = $opening;
    // Add opening installments
    foreach ($openingInstallments as $oi):
        $runningP += $oi['amount'];
        $rOwedP += $oi['amount'];
    endforeach;
    // Period transactions
    foreach ($transactions as $t):
        if ($t['type'] == 'invoice') { $runningP += $t['amount']; $rOwedP += $t['amount']; }
        else { $runningP -= $t['paid']; $rPaidP += $t['paid']; }
    endforeach;
    $balanceP = $runningP;
    $totalDebitP = $rOwedP;
    $totalCreditP = $rPaidP;
    $totalOwedP = $rOwedP;
    $grandPaidP = $rPaidP;
    ?>

    <div class="report-header">
        <div class="logo-area">
            <?php if ($logoUrl && @getimagesize($logoUrl)): ?>
                <img src="<?= $logoUrl ?>" alt="Logo">
            <?php else: ?>
                <div class="logo-placeholder">الشعار</div>
            <?php endif; ?>
        </div>
        <div class="company-area">
            <h1><?= htmlspecialchars($company['company_name'] ?? 'الشركة') ?></h1>
            <div class="company-details">
                <?php if (!empty($company['address'])): ?>
                    <span><?= htmlspecialchars($company['address']) ?></span>
                    <span class="sep">|</span>
                <?php endif; ?>
                <?php if (!empty($company['phone'])): ?>
                    <span>هاتف: <?= htmlspecialchars($company['phone']) ?></span>
                    <span class="sep">|</span>
                <?php endif; ?>
                <?php if (!empty($company['email'])): ?>
                    <span><?= htmlspecialchars($company['email']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="doc-badge">
            <div class="doc-title">كشف حساب</div>
            <div class="doc-sub">STATEMENT OF ACCOUNT</div>
        </div>
    </div>

    <div class="info-panel">
        <div class="col">
            <p><strong>العميل / Client:</strong> <?= htmlspecialchars($customer['name']) ?></p>
            <p><strong>الهاتف / Phone:</strong> <?= htmlspecialchars($customer['phone'] ?? '-') ?></p>
            <?php if (!empty($customer['code'])): ?>
                <p><strong>الكود / Code:</strong> <?= htmlspecialchars($customer['code']) ?></p>
            <?php endif; ?>
            <?php if (!empty($customer['area'])): ?>
                <p><strong>المنطقة / Area:</strong> <?= htmlspecialchars($customer['area']) ?></p>
            <?php endif; ?>
            <?php if (!empty($customer['address'])): ?>
                <p><strong>العنوان / Address:</strong> <?= htmlspecialchars($customer['address']) ?></p>
            <?php endif; ?>
        </div>
        <div class="col col-left">
            <p><strong>من / From:</strong> <?= $from ?></p>
            <p><strong>إلى / To:</strong> <?= $to ?></p>
            <p><strong>طباعة / Printed:</strong> <?= date('Y-m-d H:i') ?></p>
        </div>
    </div>

    <div class="client-totals">
        <div class="ct-box ct-owed">
            <div class="ct-label">إجمالي المطلوب</div>
            <div class="ct-value"><?= number_format($totalOwedP, 2) ?></div>
        </div>
        <div class="ct-box ct-paid">
            <div class="ct-label">إجمالي المدفوع</div>
            <div class="ct-value"><?= number_format($grandPaidP, 2) ?></div>
        </div>
        <div class="ct-box ct-remaining">
            <div class="ct-label">المتبقي</div>
            <div class="ct-value"><?= number_format($balanceP, 2) ?></div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:9%">التاريخ<br><span style="font-weight:400;font-size:6.5pt">DATE</span></th>
                <th style="width:26%">البيان<br><span style="font-weight:400;font-size:6.5pt">DESCRIPTION</span></th>
                <th style="width:12%">المبلغ<br><span style="font-weight:400;font-size:6.5pt">AMOUNT</span></th>
                <th style="width:12%">المدفوع<br><span style="font-weight:400;font-size:6.5pt">PAID</span></th>
                <th style="width:13%">الرصيد<br><span style="font-weight:400;font-size:6.5pt">BALANCE</span></th>
                <th style="width:13%">مدفوع تراكمي<br><span style="font-weight:400;font-size:6.5pt">TOTAL PAID</span></th>
                <th style="width:15%">المتبقي<br><span style="font-weight:400;font-size:6.5pt">REMAINING</span></th>
            </tr>
        </thead>
        <tbody>
            <?php
            $rBalance = $opening;
            $rPaid = $opening < 0 ? abs($opening) : 0;
            $rOwed = $opening > 0 ? $opening : 0;
            ?>
            <?php foreach ($openingInstallments as $oi):
                $rBalance += $oi['amount'];
                $rOwed += $oi['amount'];
                $rRemaining = $rOwed - $rPaid;
            ?>
            <tr class="row-opening-installment">
                <td><?= htmlspecialchars($oi['installment_date']) ?></td>
                <td style="text-align:right">
                    <strong>قسط افتتاحي (من قبل التطبيق)</strong>
                    <?php if (!empty($oi['notes'])): ?>
                        <span class="opening-note">&#9656; <?= htmlspecialchars($oi['notes']) ?></span>
                    <?php endif; ?>
                </td>
                <td class="text-debit"><?= number_format($oi['amount'], 2) ?></td>
                <td class="text-credit">-</td>
                <td class="balance-debit"><?= number_format($rBalance, 2) ?></td>
                <td class="text-credit"><?= number_format($rPaid, 2) ?></td>
                <td class="balance-debit"><?= number_format($rRemaining, 2) ?></td>
            </tr>
            <?php endforeach; ?>

            <?php foreach ($transactions as $t):
                if ($t['type'] == 'invoice') {
                    $rBalance += $t['amount'];
                    $rOwed += $t['amount'];
                } else {
                    $rBalance -= $t['paid'];
                    $rPaid += $t['paid'];
                }
                $rRemaining = $rOwed - $rPaid;
            ?>
            <tr>
                <td><?= $t['date'] ?></td>
                <td style="text-align:right">
                    <?php if ($t['type'] == 'invoice'): ?>
                        <strong>فاتورة مبيعات</strong> #<?= $t['ref'] ?>
                    <?php else: ?>
                        تحصيل قسط - فاتورة #<?= $t['ref'] ?>
                    <?php endif; ?>
                </td>
                <td class="text-debit"><?= $t['amount'] > 0 ? number_format($t['amount'], 2) : '-' ?></td>
                <td class="text-credit"><?= $t['paid'] > 0 ? number_format($t['paid'], 2) : '-' ?></td>
                <td class="<?= $rBalance > 0 ? 'balance-debit' : 'balance-credit' ?>"><?= number_format($rBalance, 2) ?></td>
                <td class="text-credit"><?= number_format($rPaid, 2) ?></td>
                <td class="<?= $rRemaining > 0 ? 'balance-debit' : 'balance-credit' ?>"><?= number_format($rRemaining, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="footer-row">
                <td colspan="2" style="text-align:left">الإجمالي / TOTAL</td>
                <td><?= number_format($totalDebitP, 2) ?></td>
                <td><?= number_format($totalCreditP, 2) ?></td>
                <td class="final-balance"><?= number_format($balanceP, 2) ?></td>
                <td><?= number_format($grandPaidP, 2) ?></td>
                <td style="font-size:11pt"><?= number_format($balanceP, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="s-label">إجمالي المدين (فواتير)</div>
            <div class="s-value debit"><?= number_format($totalDebitP, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="s-label">إجمالي الدائن (مدفوعات)</div>
            <div class="s-value credit"><?= number_format($totalCreditP, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="s-label">الرصيد النهائي</div>
            <div class="s-value"><?= number_format($balanceP, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="s-label">عدد العمليات</div>
            <div class="s-value"><?= count($transactions) ?></div>
        </div>
    </div>

    <div class="signature-area">
        <div class="sig-box">
            <div class="sig-line">توقيع العميل / Client Signature</div>
        </div>
        <div class="sig-box">
            <div class="sig-line">توقيع المدير / Authorized Signature</div>
        </div>
        <div class="sig-box">
            <div class="sig-line">الخاتم / Company Stamp</div>
        </div>
    </div>

    <div class="report-footer">
        <span><?= htmlspecialchars($company['company_name'] ?? '') ?> &mdash; كشف حساب عميل</span>
        <span>ص 1/1 &bull; <?= date('Y-m-d H:i:s') ?></span>
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 500);
        });
    </script>
</body>
</html>
