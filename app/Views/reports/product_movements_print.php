<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كارت صنف - <?= htmlspecialchars($selectedProduct['name']) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Tahoma', 'Arial', sans-serif;
            font-size: 11pt;
            color: #1e293b;
            background: #fff;
            direction: rtl;
            padding: 15px;
        }
        .company-name {
            font-size: 20pt;
            font-weight: bold;
            color: #1e40af;
            text-align: center;
        }
        .company-info {
            text-align: center;
            font-size: 9pt;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-title {
            text-align: center;
            font-size: 15pt;
            font-weight: bold;
            color: #1e40af;
            margin: 12px 0;
            padding: 6px 0;
            border-bottom: 2px solid #1e40af;
        }
        .info-grid {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            margin-bottom: 14px;
            font-size: 10pt;
        }
        .info-grid strong { color: #1e40af; }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
        }
        thead th {
            background: #1e40af;
            color: #fff;
            padding: 9px 6px;
            text-align: center;
            font-weight: bold;
            font-size: 9pt;
        }
        tbody td {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: center;
        }
        tbody tr:nth-child(even) { background: #f8fafc; }
        .opening-row { background: #fef9c3 !important; font-weight: bold; }
        .text-in { color: #16a34a; font-weight: bold; }
        .text-out { color: #dc2626; font-weight: bold; }
        .balance-pos { color: #16a34a; font-weight: bold; }
        .balance-neg { color: #dc2626; font-weight: bold; }
        tfoot tr {
            background: #1e293b;
            color: #fff;
            font-weight: bold;
            font-size: 10pt;
        }
        tfoot td { padding: 10px 6px; border: 1px solid #334155; }
        .summary {
            display: flex;
            justify-content: space-around;
            margin-top: 14px;
            padding: 12px;
            background: #eff6ff;
            border-radius: 6px;
            border: 1px solid #bfdbfe;
        }
        .summary-item { text-align: center; }
        .summary-item .label { font-size: 8pt; color: #64748b; }
        .summary-item .value { font-size: 13pt; font-weight: bold; color: #1e40af; }
        .footer {
            margin-top: 25px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            font-size: 8pt;
            color: #94a3b8;
        }
        .actions { text-align: center; margin-bottom: 18px; }
        .actions button {
            padding: 8px 24px;
            font-size: 12px;
            cursor: pointer;
            border: none;
            border-radius: 6px;
            margin: 0 4px;
            font-family: inherit;
            transition: 0.2s;
        }
        .btn-print { background: #1e40af; color: #fff; }
        .btn-print:hover { background: #1e3a8a; }
        .btn-back { background: #6b7280; color: #fff; }
        .btn-back:hover { background: #4b5563; }
        @media print {
            body { padding: 8px; }
            .actions { display: none !important; }
            thead { display: table-header-group; }
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <button class="btn-print" onclick="window.print()">🖨️ طباعة / PDF</button>
        <button class="btn-back" onclick="history.back()">← رجوع</button>
    </div>

    <div class="company-name"><?= htmlspecialchars($company['company_name'] ?? APP_NAME) ?></div>
    <div class="company-info">
        <?php if (!empty($company['address'])): ?><?= htmlspecialchars($company['address']) ?><?php endif; ?>
        <?php if (!empty($company['phone'])): ?> | هاتف: <?= htmlspecialchars($company['phone']) ?><?php endif; ?>
    </div>
    <div class="doc-title">📦 كارت صنف</div>

    <div class="info-grid">
        <div>
            <strong>المادة:</strong> <?= htmlspecialchars($selectedProduct['name']) ?><br>
            <strong>الباركود:</strong> <?= htmlspecialchars($selectedProduct['barcode'] ?? '-') ?>
        </div>
        <div style="text-align:left">
            <strong>من:</strong> <?= $from ?><br>
            <strong>إلى:</strong> <?= $to ?><br>
            <strong>الطباعة:</strong> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:12%">التاريخ</th>
                <th style="width:16%">البيان</th>
                <th style="width:12%">وارد</th>
                <th style="width:12%">منصرف</th>
                <th style="width:14%">الرصيد</th>
                <th style="width:18%">المرجع</th>
                <th style="width:16%">المخزن</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $balance = $openingBalance;
            $totalIn = 0;
            $totalOut = 0;
            ?>
            <tr class="opening-row">
                <td><?= $openingDate ?></td>
                <td style="text-align:right">رصيد افتتاحي</td>
                <td class="text-in"><?= $openingBalance > 0 ? number_format($openingBalance, 2) : '-' ?></td>
                <td class="text-out">-</td>
                <td class="<?= $balance >= 0 ? 'balance-pos' : 'balance-neg' ?>"><?= number_format($balance, 2) ?></td>
                <td>-</td>
                <td>-</td>
            </tr>
            <?php foreach ($movements as $mov):
                $qty = (float)$mov['quantity'];
                if ($mov['type'] == 'in') {
                    $balance += $qty;
                    $totalIn += $qty;
                } else {
                    $balance -= $qty;
                    $totalOut += $qty;
                }
            ?>
            <tr>
                <td><?= date('Y/m/d', strtotime($mov['created_at'])) ?></td>
                <td style="text-align:right">
                    <?php if ($mov['type'] == 'in'): ?>
                        <span class="text-in">وارد</span>
                    <?php else: ?>
                        <span class="text-out">منصرف</span>
                    <?php endif; ?>
                </td>
                <td class="text-in"><?= $mov['type'] == 'in' ? number_format($qty, 2) : '-' ?></td>
                <td class="text-out"><?= $mov['type'] == 'out' ? number_format($qty, 2) : '-' ?></td>
                <td class="<?= $balance >= 0 ? 'balance-pos' : 'balance-neg' ?>"><?= number_format($balance, 2) ?></td>
                <td style="text-align:right">
                    <?= htmlspecialchars($mov['reference']) ?>
                    <?php if ($mov['reference_id']): ?> #<?= htmlspecialchars($mov['reference_id']) ?><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($mov['warehouse_name'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:left">الإجمالي</td>
                <td><?= number_format($totalIn, 2) ?></td>
                <td><?= number_format($totalOut, 2) ?></td>
                <td style="font-size:12pt"><?= number_format($balance, 2) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <div class="summary-item">
            <div class="label">إجمالي الوارد</div>
            <div class="value" style="color:#16a34a"><?= number_format($totalIn, 2) ?></div>
        </div>
        <div class="summary-item">
            <div class="label">إجمالي المنصرف</div>
            <div class="value" style="color:#dc2626"><?= number_format($totalOut, 2) ?></div>
        </div>
        <div class="summary-item">
            <div class="label">الرصيد الختامي</div>
            <div class="value"><?= number_format($balance, 2) ?></div>
        </div>
        <div class="summary-item">
            <div class="label">عدد الحركات</div>
            <div class="value"><?= count($movements) ?></div>
        </div>
    </div>

    <div class="footer">
        <span>تم الإنشاء بواسطة <?= htmlspecialchars($company['company_name'] ?? APP_NAME) ?></span>
        <span>صفحة 1 / 1</span>
    </div>

    <script>
        window.addEventListener('load', function() {
            setTimeout(function() { window.print(); }, 500);
        });
    </script>
</body>
</html>
