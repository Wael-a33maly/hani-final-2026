<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جرد عهدة مندوب - <?= htmlspecialchars($salesRepName) ?></title>
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
        .num { direction: ltr; display: inline-block; }
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
    <div class="doc-title">📋 جرد عهدة المندوب</div>

    <div class="info-grid">
        <div>
            <strong>المندوب:</strong> <?= htmlspecialchars($salesRepName) ?><br>
            <?php if (!empty($productId)): ?>
                <?php
                $db = getDB();
                $pStmt = $db->prepare("SELECT name FROM products WHERE id = ?");
                $pStmt->execute([$productId]);
                $pName = $pStmt->fetchColumn();
                ?>
                <strong>المادة:</strong> <?= htmlspecialchars($pName) ?><br>
            <?php endif; ?>
        </div>
        <div style="text-align:left">
            <strong>من:</strong> <?= $fromDate ?><br>
            <strong>إلى:</strong> <?= $toDate ?><br>
            <strong>الطباعة:</strong> <?= date('Y-m-d H:i') ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:22%">المادة</th>
                <th style="width:8%">الوحدة</th>
                <th style="width:14%">المسند</th>
                <th style="width:14%">المباع</th>
                <th style="width:14%">المسترد</th>
                <th style="width:14%">المتبقي</th>
                <th style="width:14%">قيمة العهدة</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalAssigned = 0; $totalSold = 0; $totalReturned = 0; $totalRemaining = 0; $totalValue = 0;
            foreach($reportData as $row):
                $remainingValue = $row['current_quantity'] * $row['selling_price'];
                $totalAssigned += $row['assigned_quantity'];
                $totalSold += $row['sold_quantity'];
                $totalReturned += $row['returned_quantity'];
                $totalRemaining += $row['current_quantity'];
                $totalValue += $remainingValue;
            ?>
            <tr>
                <td style="text-align:right">
                    <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                    <br><span style="font-size:8pt;color:#94a3b8"><?= $row['barcode'] ?></span>
                </td>
                <td><?= $row['unit_name'] ?></td>
                <td><?= number_format($row['assigned_quantity'], 2) ?></td>
                <td style="color:#16a34a"><?= number_format($row['sold_quantity'], 2) ?></td>
                <td style="color:#d97706"><?= number_format($row['returned_quantity'], 2) ?></td>
                <td style="color:#1e40af;font-weight:bold"><?= number_format($row['current_quantity'], 2) ?></td>
                <td style="font-weight:bold"><?= number_format($remainingValue, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:left">الإجمالي</td>
                <td><?= number_format($totalAssigned, 2) ?></td>
                <td><?= number_format($totalSold, 2) ?></td>
                <td><?= number_format($totalReturned, 2) ?></td>
                <td><?= number_format($totalRemaining, 2) ?></td>
                <td style="font-size:12pt;color:#fbbf24"><?= number_format($totalValue, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="summary">
        <div class="summary-item">
            <div class="label">إجمالي المسند</div>
            <div class="value"><?= number_format($totalAssigned, 2) ?></div>
        </div>
        <div class="summary-item">
            <div class="label">إجمالي المباع</div>
            <div class="value" style="color:#16a34a"><?= number_format($totalSold, 2) ?></div>
        </div>
        <div class="summary-item">
            <div class="label">إجمالي المسترد</div>
            <div class="value" style="color:#d97706"><?= number_format($totalReturned, 2) ?></div>
        </div>
        <div class="summary-item">
            <div class="label">العهدة المتبقية</div>
            <div class="value" style="color:#1e40af"><?= number_format($totalRemaining, 2) ?></div>
        </div>
        <div class="summary-item">
            <div class="label">قيمة العهدة</div>
            <div class="value"><?= number_format($totalValue, 2) ?> ج.م</div>
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
