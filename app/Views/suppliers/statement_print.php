<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كشف حساب مورد - <?= htmlspecialchars($supplier['name']) ?></title>
    <style>
        @page {
            margin: 15mm 12mm 18mm 12mm;
            size: A4 portrait;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Tahoma', 'Arial', sans-serif;
            font-size: 10pt;
            color: #1e293b;
            background: #fff;
            direction: rtl;
            line-height: 1.6;
        }

        /* ========== HEADER ========== */
        .report-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding-bottom: 14px;
            border-bottom: 3px solid #059669;
            margin-bottom: 18px;
        }
        .report-header .logo-area {
            flex-shrink: 0;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .report-header .logo-area img {
            max-width: 80px;
            max-height: 80px;
        }
        .report-header .logo-placeholder {
            width: 80px;
            height: 80px;
            background: #f1f5f9;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9pt;
            color: #94a3b8;
        }
        .report-header .company-area {
            flex: 1;
        }
        .report-header .company-area h1 {
            font-size: 18pt;
            font-weight: 700;
            color: #059669;
            letter-spacing: 0.5px;
        }
        .report-header .company-area .company-details {
            font-size: 8.5pt;
            color: #64748b;
            margin-top: 2px;
        }
        .report-header .company-area .company-details span {
            display: inline-block;
        }
        .report-header .company-area .company-details .sep {
            margin: 0 6px;
            color: #cbd5e1;
        }
        .report-header .doc-badge {
            flex-shrink: 0;
            text-align: center;
            background: #f0fdf4;
            border: 1px solid #a7f3d0;
            border-radius: 8px;
            padding: 8px 18px;
        }
        .report-header .doc-badge .doc-title {
            font-size: 13pt;
            font-weight: 700;
            color: #059669;
        }
        .report-header .doc-badge .doc-sub {
            font-size: 7.5pt;
            color: #64748b;
        }

        /* ========== INFO SECTION ========== */
        .info-panel {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 16px;
        }
        .info-panel .col p {
            font-size: 9.5pt;
            margin-bottom: 3px;
        }
        .info-panel .col p:last-child { margin-bottom: 0; }
        .info-panel .col strong {
            color: #059669;
            font-weight: 600;
        }
        .info-panel .col-left { text-align: left; }

        /* ========== TABLE ========== */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        thead th {
            background: #059669;
            color: #fff;
            padding: 9px 6px;
            text-align: center;
            font-weight: 600;
            font-size: 8.5pt;
            letter-spacing: 0.3px;
        }
        thead th:first-child { border-radius: 0 4px 0 0; }
        thead th:last-child { border-radius: 4px 0 0 0; }
        tbody td {
            border: 1px solid #e2e8f0;
            padding: 7px 6px;
            text-align: center;
            font-size: 9pt;
        }
        tbody tr:nth-child(even) { background: #fafbfc; }
        .row-opening {
            background: #fef9c3 !important;
            font-weight: 600;
        }
        .row-opening td { border-color: #fde68a; }

        .text-debit { color: #dc2626; font-weight: 600; }
        .text-credit { color: #16a34a; font-weight: 600; }
        .balance-debit { color: #dc2626; font-weight: 700; }
        .balance-credit { color: #16a34a; font-weight: 700; }

        /* Footer Row */
        tfoot .footer-row {
            background: #064e3b !important;
            color: #fff !important;
        }
        tfoot .footer-row td {
            border-color: #065f46;
            padding: 10px 6px;
            font-weight: 700;
            font-size: 10pt;
        }
        tfoot .footer-row .final-balance {
            font-size: 12pt;
        }

        /* ========== SUMMARY ========== */
        .summary-grid {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 16px;
        }
        .summary-card {
            flex: 1;
            text-align: center;
            background: #f0fdf4;
            border: 1px solid #a7f3d0;
            border-radius: 6px;
            padding: 10px 6px;
        }
        .summary-card .s-label {
            font-size: 7.5pt;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-card .s-value {
            font-size: 13pt;
            font-weight: 700;
            color: #059669;
            margin-top: 3px;
        }
        .summary-card .s-value.debit { color: #dc2626; }
        .summary-card .s-value.credit { color: #16a34a; }

        /* ========== FOOTER ========== */
        .report-footer {
            margin-top: 28px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 7.5pt;
            color: #94a3b8;
        }

        /* ========== SIGNATURE ========== */
        .signature-area {
            margin-top: 24px;
            display: flex;
            justify-content: space-between;
            font-size: 9pt;
        }
        .signature-area .sig-box {
            text-align: center;
            min-width: 160px;
        }
        .signature-area .sig-box .sig-line {
            margin-top: 32px;
            border-top: 1px solid #94a3b8;
            padding-top: 6px;
            font-size: 8pt;
            color: #64748b;
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

    <!-- ========== HEADER ========== -->
    <?php
        $logo = $company['logo_path'] ?? '';
        $logoUrl = $logo ? APP_URL . '/public/' . ltrim($logo, '/') : '';
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
            <div class="doc-title">كشف حساب مورد</div>
            <div class="doc-sub">SUPPLIER STATEMENT</div>
        </div>
    </div>

    <!-- ========== SUPPLIER INFO ========== -->
    <div class="info-panel">
        <div class="col">
            <p><strong>المورد / Supplier:</strong> <?= htmlspecialchars($supplier['name']) ?></p>
            <p><strong>الهاتف / Phone:</strong> <?= htmlspecialchars($supplier['phone'] ?? '-') ?></p>
            <?php if (!empty($supplier['area'])): ?>
                <p><strong>المنطقة / Area:</strong> <?= htmlspecialchars($supplier['area']) ?></p>
            <?php endif; ?>
            <?php if (!empty($supplier['address'])): ?>
                <p><strong>العنوان / Address:</strong> <?= htmlspecialchars($supplier['address']) ?></p>
            <?php endif; ?>
        </div>
        <div class="col col-left">
            <p><strong>من / From:</strong> <?= $from ?></p>
            <p><strong>إلى / To:</strong> <?= $to ?></p>
            <p><strong>تاريخ الطباعة / Printed:</strong> <?= date('Y-m-d H:i') ?></p>
        </div>
    </div>

    <!-- ========== TRANSACTIONS TABLE ========== -->
    <table>
        <thead>
            <tr>
                <th style="width:11%">التاريخ<br><span style="font-weight:400;font-size:7pt">DATE</span></th>
                <th style="width:34%">البيان<br><span style="font-weight:400;font-size:7pt">DESCRIPTION</span></th>
                <th style="width:16%">دائن (فواتير)<br><span style="font-weight:400;font-size:7pt">CREDIT</span></th>
                <th style="width:16%">مدين (مدفوعات)<br><span style="font-weight:400;font-size:7pt">DEBIT</span></th>
                <th style="width:18%">الرصيد<br><span style="font-weight:400;font-size:7pt">BALANCE</span></th>
            </tr>
        </thead>
        <tbody>
            <!-- Opening Balance -->
            <tr class="row-opening">
                <td><?= $from ?></td>
                <td style="text-align:right">رصيد افتتاحي (أول المدة) / Opening Balance</td>
                <td class="text-debit"><?= $opening < 0 ? number_format(abs($opening), 2) : '-' ?></td>
                <td class="text-credit"><?= $opening > 0 ? number_format($opening, 2) : '-' ?></td>
                <td class="<?= $opening < 0 ? 'balance-debit' : 'balance-credit' ?>">
                    <?= number_format($opening, 2) ?>
                </td>
            </tr>

            <?php
            $balance = $opening;
            $totalCredit = 0;
            $totalDebit = 0;
            foreach ($transactions as $t):
                if ($t['type'] == 'invoice') {
                    $balance -= $t['amount'];
                    $totalCredit += $t['amount'];
                } else {
                    $balance += $t['paid'];
                    $totalDebit += $t['paid'];
                }
            ?>
            <tr>
                <td><?= $t['date'] ?></td>
                <td style="text-align:right">
                    <?php if ($t['type'] == 'invoice'): ?>
                        <strong>فاتورة مشتريات</strong> #<?= $t['ref'] ?>
                    <?php else: ?>
                        <strong style="color:#16a34a">دفعة / Payment</strong>
                        <?= !empty($t['description']) ? '- ' . htmlspecialchars($t['description']) : '' ?>
                    <?php endif; ?>
                </td>
                <td class="text-debit"><?= $t['amount'] > 0 ? number_format($t['amount'], 2) : '-' ?></td>
                <td class="text-credit"><?= $t['paid'] > 0 ? number_format($t['paid'], 2) : '-' ?></td>
                <td class="<?= $balance < 0 ? 'balance-debit' : 'balance-credit' ?>">
                    <?= number_format($balance, 2) ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="footer-row">
                <td colspan="2" style="text-align:left">الرصيد النهائي / CLOSING BALANCE</td>
                <td><?= number_format($totalCredit, 2) ?></td>
                <td><?= number_format($totalDebit, 2) ?></td>
                <td class="final-balance"><?= number_format($balance, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- ========== SUMMARY ========== -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="s-label">إجمالي الفواتير</div>
            <div class="s-value debit"><?= number_format($totalCredit, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="s-label">إجمالي المدفوعات</div>
            <div class="s-value credit"><?= number_format($totalDebit, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="s-label">الرصيد النهائي</div>
            <div class="s-value"><?= number_format($balance, 2) ?></div>
        </div>
        <div class="summary-card">
            <div class="s-label">عدد العمليات</div>
            <div class="s-value"><?= count($transactions) ?></div>
        </div>
    </div>

    <!-- ========== SIGNATURES ========== -->
    <div class="signature-area">
        <div class="sig-box">
            <div class="sig-line">توقيع المورد / Supplier Signature</div>
        </div>
        <div class="sig-box">
            <div class="sig-line">توقيع المدير / Authorized Signature</div>
        </div>
        <div class="sig-box">
            <div class="sig-line">الخاتم / Company Stamp</div>
        </div>
    </div>

    <!-- ========== FOOTER ========== -->
    <div class="report-footer">
        <span><?= htmlspecialchars($company['company_name'] ?? '') ?> &mdash; كشف حساب مورد</span>
        <span>ص 1/1 &bull; <?= date('Y-m-d H:i:s') ?></span>
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 500);
        });
    </script>
</body>
</html>
