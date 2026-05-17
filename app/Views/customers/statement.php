<?php requireLogin(); $pageTitle = 'كشف حساب عميل'; ob_start(); ?>
<style>
  .statement-header {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    border-radius: 12px;
    padding: 24px 28px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .statement-header h2 { font-size: 1.35rem; font-weight: 700; margin: 0; }
  .statement-header .actions { display: flex; gap: 8px; }
  .statement-header .actions a, .statement-header .actions button {
    background: rgba(255,255,255,0.15);
    color: #fff; padding: 8px 16px; border-radius: 8px;
    text-decoration: none; font-size: 0.85rem; border: none; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px; transition: 0.2s;
  }
  .statement-header .actions a:hover, .statement-header .actions button:hover {
    background: rgba(255,255,255,0.25);
  }

  .filter-card {
    background: #fff; border-radius: 12px; padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06); border: 1px solid #e5e7eb;
  }
  .filter-card .grid { display: grid; grid-template-columns: 1fr 1fr auto; gap: 12px; align-items: end; }

  .customer-card {
    display: flex; align-items: center; gap: 16px;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: 16px 20px;
  }
  .customer-card .avatar {
    width: 48px; height: 48px; border-radius: 50%;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.2rem; font-weight: 700; flex-shrink: 0;
  }
  .customer-card .info h4 { font-size: 1rem; font-weight: 700; margin: 0; color: #1e293b; }
  .customer-card .info p { font-size: 0.8rem; color: #64748b; margin: 2px 0 0; }
  .customer-card .stats { margin-right: auto; display: flex; gap: 24px; }
  .customer-card .stats .stat { text-align: center; }
  .customer-card .stats .stat .num { font-size: 1.2rem; font-weight: 700; color: #1e40af; }
  .customer-card .stats .stat .lbl { font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; }

  .summary-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; }
  .summary-box {
    background: #fff; border-radius: 8px; padding: 10px 8px;
    border: 1px solid #e5e7eb; text-align: center;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
  }
  .summary-box .s-icon { font-size: 1rem; margin-bottom: 2px; }
  .summary-box .s-label { font-size: 0.65rem; color: #6b7280; margin-bottom: 2px; }
  .summary-box .s-value { font-size: 1rem; font-weight: 700; }

  .table-wrap { overflow-x: auto; border-radius: 10px; border: 1px solid #e5e7eb; }
  .table-wrap table { width: 100%; border-collapse: collapse; font-size: 0.85rem; }
  .table-wrap thead { background: #f1f5f9; }
  .table-wrap thead th {
    padding: 10px 12px; text-align: center; font-weight: 600; color: #475569;
    border-bottom: 2px solid #e2e8f0; font-size: 0.8rem; white-space: nowrap;
  }
  .table-wrap tbody td {
    padding: 10px 12px; text-align: center; border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
  }
  .table-wrap tbody tr:hover { background: #f8fafc; }
  .table-wrap tbody tr:last-child td { border-bottom: none; }

  .row-opening { background: #fefce8 !important; }
  .row-opening td { border-bottom-color: #fde68a !important; font-weight: 600; }

  .row-opening-installment { background: #f0f9ff !important; }
  .row-opening-installment td { border-bottom-color: #bae6fd !important; }
  .opening-note {
    display: block; font-size: 0.7rem; color: #0369a1;
    margin-top: 2px; font-weight: 400;
  }
  .row-opening-installment .desc-cell { text-align: right; }

  .text-debit { color: #dc2626; font-weight: 600; }
  .text-credit { color: #16a34a; font-weight: 600; }
  .balance-debit { color: #dc2626; font-weight: 700; }
  .balance-credit { color: #16a34a; font-weight: 700; }

  .table-footer {
    background: #1e293b; color: #fff;
  }
  .table-footer td {
    padding: 12px; font-weight: 700; font-size: 0.95rem;
    border-bottom: none; text-align: center;
  }

  @media print {
    .no-print { display: none !important; }
    .statement-header { background: #1e40af !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .table-wrap { border: 1px solid #000; }
    .table-wrap thead { background: #1e40af !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .table-wrap thead th { color: #fff !important; }
    .row-opening { background: #fefce8 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .table-footer { background: #1e293b !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .table-footer td { color: #fff !important; }
    .customer-card { border: 1px solid #000; }
    .summary-box { border: 1px solid #000; }
  }

  @media (max-width: 768px) {
    .statement-header { flex-direction: column; gap: 12px; text-align: center; }
    .filter-card .grid { grid-template-columns: 1fr; }
    .customer-card { flex-wrap: wrap; }
    .customer-card .stats { margin-right: 0; width: 100%; justify-content: space-around; margin-top: 8px; }
    .summary-grid { grid-template-columns: repeat(2, 1fr); }
  }
</style>

<div class="space-y-5">
  <!-- HEADER -->
  <div class="statement-header no-print">
    <h2><i class="fas fa-file-invoice ml-2"></i> كشف حساب: <?= htmlspecialchars($customer['name']) ?></h2>
    <div class="actions">
      <a href="<?= APP_URL ?>/customers/statement/<?= $customer['id'] ?>?from=<?= $from ?>&to=<?= $to ?>&pdf=1" target="_blank">
        <i class="fas fa-file-pdf"></i> PDF
      </a>
      <button onclick="window.print()"><i class="fas fa-print"></i> طباعة</button>
    </div>
  </div>

  <!-- FILTERS -->
  <div class="filter-card no-print">
    <form method="GET" class="grid">
      <div>
        <label class="block text-xs text-gray-500 mb-1">من تاريخ</label>
        <input type="date" name="from" value="<?= $from ?>"
               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">إلى تاريخ</label>
        <input type="date" name="to" value="<?= $to ?>"
               class="w-full border border-gray-300 rounded-lg p-2 text-sm">
      </div>
      <button type="submit" class="bg-blue-600 text-white rounded-lg px-5 py-2 hover:bg-blue-700 transition text-sm">
        <i class="fas fa-search ml-1"></i> بحث
      </button>
    </form>
  </div>

  <?php
  $totalDebit = 0;
  $totalCredit = 0;
  $balance = 0;
  // Start from opening installments (pre-system debt)
  $running = $opening + $openingFromInst;
  $runningPaid = 0;
  $runningOwed = $openingFromInst + ($opening > 0 ? $opening : 0);
  // Then period transactions
  foreach ($transactions as $t):
    if ($t['type'] == 'invoice') { $running += $t['amount']; $runningOwed += $t['amount']; $totalDebit += $t['amount']; }
    else { $running -= $t['paid']; $runningPaid += $t['paid']; $totalCredit += $t['paid']; }
  endforeach;
  $totalDebit += $openingFromInst + ($opening > 0 ? $opening : 0);
  $totalCredit += ($opening < 0 ? abs($opening) : 0);
  $balance = $running;
  $totalOwed = $runningOwed;
  $grandTotalPaid = $runningPaid;
  ?>
  <!-- CUSTOMER INFO -->
  <div class="customer-card">
    <div class="avatar"><?= mb_substr($customer['name'], 0, 1) ?></div>
    <div class="info">
      <h4><?= htmlspecialchars($customer['name']) ?></h4>
      <p><i class="fas fa-phone ml-1 text-gray-400"></i> <?= htmlspecialchars($customer['phone'] ?? '-') ?>
      <?php if (!empty($customer['area'])): ?> &middot; <i class="fas fa-map-marker-alt ml-1 text-gray-400"></i> <?= htmlspecialchars($customer['area']) ?><?php endif; ?>
      <?php if (!empty($customer['code'])): ?> &middot; <i class="fas fa-hashtag ml-1 text-gray-400"></i> <?= htmlspecialchars($customer['code']) ?><?php endif; ?>
      </p>
    </div>
    <div class="stats">
      <div class="stat">
        <div class="num" style="color:#1e40af"><?= number_format($totalOwed, 0) ?></div>
        <div class="lbl">المطلوب</div>
      </div>
      <div class="stat">
        <div class="num" style="color:#16a34a"><?= number_format($grandTotalPaid, 0) ?></div>
        <div class="lbl">مدفوع</div>
      </div>
      <div class="stat">
        <div class="num" style="color:#dc2626"><?= number_format($balance, 0) ?></div>
        <div class="lbl">متبقي</div>
      </div>
    </div>
  </div>

  <div class="summary-grid">
    <div class="summary-box">
      <div class="s-icon" style="color:#3b82f6"><i class="fas fa-wallet"></i></div>
      <div class="s-label">الرصيد الافتتاحي</div>
      <div class="s-value" style="color:#3b82f6"><?= number_format($opening + $openingFromInst, 2) ?></div>
    </div>
    <div class="summary-box">
      <div class="s-icon" style="color:#dc2626"><i class="fas fa-arrow-up"></i></div>
      <div class="s-label">إجمالي الفواتير</div>
      <div class="s-value" style="color:#dc2626"><?= number_format($totalDebit, 2) ?></div>
    </div>
    <div class="summary-box">
      <div class="s-icon" style="color:#16a34a"><i class="fas fa-arrow-down"></i></div>
      <div class="s-label">إجمالي المدفوعات</div>
      <div class="s-value" style="color:#16a34a"><?= number_format($totalCredit, 2) ?></div>
    </div>
    <div class="summary-box">
      <div class="s-icon" style="color:#1e293b"><i class="fas fa-balance-scale"></i></div>
      <div class="s-label">الرصيد النهائي</div>
      <div class="s-value" style="color:<?= $balance > 0 ? '#dc2626' : '#16a34a' ?>"><?= number_format($balance, 2) ?></div>
    </div>
  </div>

  <!-- TRANSACTIONS TABLE -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:10%">التاريخ</th>
          <th style="width:28%">البيان</th>
          <th style="width:12%">المبلغ</th>
          <th style="width:12%">المدفوع</th>
          <th style="width:13%">الرصيد</th>
          <th style="width:12%">مدفوع تراكمي</th>
          <th style="width:13%">المتبقي</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $running = $opening;
        $runningPaid = $opening < 0 ? abs($opening) : 0;
        $runningOwed = $opening > 0 ? $opening : 0;
        ?>
        <?php foreach ($openingInstallments as $oi):
          $running += $oi['amount'];
          $runningOwed += $oi['amount'];
          $remaining = $runningOwed - $runningPaid;
        ?>
        <tr class="row-opening-installment">
          <td><?= htmlspecialchars($oi['installment_date']) ?></td>
          <td class="desc-cell">
            <i class="fas fa-history text-sky-600 ml-1"></i>
            <strong>قسط افتتاحي (من قبل التطبيق)</strong>
            <?php if (!empty($oi['notes'])): ?>
              <span class="opening-note"><i class="fas fa-comment ml-1"></i><?= htmlspecialchars($oi['notes']) ?></span>
            <?php endif; ?>
          </td>
          <td class="text-debit"><?= number_format($oi['amount'], 2) ?></td>
          <td class="text-credit">-</td>
          <td class="balance-debit"><?= number_format($running, 2) ?></td>
          <td class="text-credit"><?= number_format($runningPaid, 2) ?></td>
          <td class="balance-debit"><?= number_format($remaining, 2) ?></td>
        </tr>
        <?php endforeach; ?>

        <?php foreach ($transactions as $t):
          if ($t['type'] == 'invoice') {
            $running += $t['amount'];
            $runningOwed += $t['amount'];
          } else {
            $running -= $t['paid'];
            $runningPaid += $t['paid'];
          }
          $remaining = $runningOwed - $runningPaid;
        ?>
        <tr>
          <td><?= $t['date'] ?></td>
          <td style="text-align:right">
            <?php if ($t['type'] == 'invoice'): ?>
              <span class="inline-flex items-center gap-1">
                <i class="fas fa-file-invoice text-blue-600"></i>
                <strong>فاتورة مبيعات</strong> #<?= $t['ref'] ?>
              </span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1">
                <i class="fas fa-hand-holding-usd text-green-600"></i>
                تحصيل قسط &mdash; فاتورة #<?= $t['ref'] ?>
              </span>
            <?php endif; ?>
          </td>
          <td class="text-debit"><?= $t['amount'] > 0 ? number_format($t['amount'], 2) : '-' ?></td>
          <td class="text-credit"><?= $t['paid'] > 0 ? number_format($t['paid'], 2) : '-' ?></td>
          <td class="<?= $running > 0 ? 'balance-debit' : 'balance-credit' ?>">
            <?= number_format($running, 2) ?>
          </td>
          <td class="text-credit"><?= number_format($runningPaid, 2) ?></td>
          <td class="<?= $remaining > 0 ? 'balance-debit' : 'balance-credit' ?>">
            <?= number_format($remaining, 2) ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="table-footer">
          <td colspan="2" style="text-align:left">الإجمالي النهائي</td>
          <td><?= number_format($totalDebit, 2) ?></td>
          <td><?= number_format($totalCredit, 2) ?></td>
          <td style="font-size:1.05rem"><?= number_format($balance, 2) ?></td>
          <td><?= number_format($grandTotalPaid, 2) ?></td>
          <td style="font-size:1.05rem"><?= number_format($balance, 2) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
