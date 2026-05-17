<?php requireLogin(); $pageTitle = 'كشف حساب مورد'; ob_start(); ?>
<style>
  .statement-header {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
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

  .supplier-card {
    display: flex; align-items: center; gap: 16px;
    background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px;
    padding: 16px 20px;
  }
  .supplier-card .avatar {
    width: 48px; height: 48px; border-radius: 50%;
    background: linear-gradient(135deg, #059669, #047857);
    display: flex; align-items: center; justify-content: center;
    color: #fff; font-size: 1.2rem; font-weight: 700; flex-shrink: 0;
  }
  .supplier-card .info h4 { font-size: 1rem; font-weight: 700; margin: 0; color: #1e293b; }
  .supplier-card .info p { font-size: 0.8rem; color: #64748b; margin: 2px 0 0; }
  .supplier-card .stats { margin-right: auto; display: flex; gap: 24px; }
  .supplier-card .stats .stat { text-align: center; }
  .supplier-card .stats .stat .num { font-size: 1.2rem; font-weight: 700; color: #059669; }
  .supplier-card .stats .stat .lbl { font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; }

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

  .text-debit { color: #dc2626; font-weight: 600; }
  .text-credit { color: #16a34a; font-weight: 600; }
  .balance-debit { color: #dc2626; font-weight: 700; }
  .balance-credit { color: #16a34a; font-weight: 700; }

  .table-footer {
    background: #064e3b; color: #fff;
  }
  .table-footer td {
    padding: 12px; font-weight: 700; font-size: 0.95rem;
    border-bottom: none; text-align: center;
  }

  @media print {
    .no-print { display: none !important; }
    .statement-header { background: #059669 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .table-wrap { border: 1px solid #000; }
    .table-wrap thead { background: #059669 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .table-wrap thead th { color: #fff !important; }
    .row-opening { background: #fefce8 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .table-footer { background: #064e3b !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .table-footer td { color: #fff !important; }
    .supplier-card { border: 1px solid #000; }
    .summary-box { border: 1px solid #000; }
  }

  @media (max-width: 768px) {
    .statement-header { flex-direction: column; gap: 12px; text-align: center; }
    .filter-card .grid { grid-template-columns: 1fr; }
    .supplier-card { flex-wrap: wrap; }
    .supplier-card .stats { margin-right: 0; width: 100%; justify-content: space-around; margin-top: 8px; }
    .summary-grid { grid-template-columns: repeat(2, 1fr); }
  }
</style>

<div class="space-y-5">
  <!-- HEADER -->
  <div class="statement-header no-print">
    <h2><i class="fas fa-file-contract ml-2"></i> كشف حساب: <?= htmlspecialchars($supplier['name']) ?></h2>
    <div class="actions">
      <a href="<?= APP_URL ?>/suppliers/statement/<?= $supplier['id'] ?>?from=<?= $from ?>&to=<?= $to ?>&pdf=1" target="_blank">
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
      <button type="submit" class="bg-emerald-600 text-white rounded-lg px-5 py-2 hover:bg-emerald-700 transition text-sm">
        <i class="fas fa-search ml-1"></i> بحث
      </button>
    </form>
  </div>

  <!-- SUPPLIER INFO -->
  <div class="supplier-card">
    <div class="avatar"><?= mb_substr($supplier['name'], 0, 1) ?></div>
    <div class="info">
      <h4><?= htmlspecialchars($supplier['name']) ?></h4>
      <p><i class="fas fa-phone ml-1 text-gray-400"></i> <?= htmlspecialchars($supplier['phone'] ?? '-') ?>
      <?php if (!empty($supplier['area'])): ?> &middot; <i class="fas fa-map-marker-alt ml-1 text-gray-400"></i> <?= htmlspecialchars($supplier['area']) ?><?php endif; ?>
      <?php if (!empty($supplier['code'])): ?> &middot; <i class="fas fa-hashtag ml-1 text-gray-400"></i> <?= htmlspecialchars($supplier['code']) ?><?php endif; ?>
      </p>
    </div>
    <div class="stats">
      <div class="stat">
        <div class="num"><?= $from ?></div>
        <div class="lbl">من</div>
      </div>
      <div class="stat">
        <div class="num"><?= $to ?></div>
        <div class="lbl">إلى</div>
      </div>
    </div>
  </div>

  <!-- SUMMARY CARDS -->
  <?php
  if (isset($transactions) && is_array($transactions)):
    $balance = $opening ?? 0;
    $totalCredit = 0;
    $totalDebit = 0;
    foreach ($transactions as $t):
      if ($t['type'] == 'invoice') { $balance -= $t['amount']; $totalCredit += $t['amount']; }
      else { $balance += $t['paid']; $totalDebit += $t['paid']; }
    endforeach;
  else:
    $balance = $opening ?? 0;
    $totalCredit = 0;
    $totalDebit = 0;
  endif;
  ?>
  <div class="summary-grid">
    <div class="summary-box">
      <div class="s-icon" style="color:#059669"><i class="fas fa-wallet"></i></div>
      <div class="s-label">الرصيد الافتتاحي</div>
      <div class="s-value" style="color:#059669"><?= number_format($opening ?? 0, 2) ?></div>
    </div>
    <div class="summary-box">
      <div class="s-icon" style="color:#dc2626"><i class="fas fa-arrow-up"></i></div>
      <div class="s-label">إجمالي المشتريات</div>
      <div class="s-value" style="color:#dc2626"><?= number_format($totalCredit, 2) ?></div>
    </div>
    <div class="summary-box">
      <div class="s-icon" style="color:#16a34a"><i class="fas fa-arrow-down"></i></div>
      <div class="s-label">إجمالي المدفوعات</div>
      <div class="s-value" style="color:#16a34a"><?= number_format($totalDebit, 2) ?></div>
    </div>
    <div class="summary-box">
      <div class="s-icon" style="color:#1e293b"><i class="fas fa-balance-scale"></i></div>
      <div class="s-label">الرصيد النهائي</div>
      <div class="s-value" style="color:<?= $balance < 0 ? '#dc2626' : '#16a34a' ?>"><?= number_format($balance, 2) ?></div>
    </div>
  </div>

  <!-- TRANSACTIONS TABLE -->
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th style="width:12%">التاريخ</th>
          <th style="width:38%">البيان</th>
          <th style="width:15%">دائن (فواتير)</th>
          <th style="width:15%">مدين (مدفوعات)</th>
          <th style="width:20%">الرصيد</th>
        </tr>
      </thead>
      <tbody>
        <tr class="row-opening">
          <td><?= $from ?></td>
          <td style="text-align:right"><i class="fas fa-clock ml-1 text-yellow-600"></i> رصيد أول المدة / Opening</td>
          <td class="text-debit"><?= ($opening ?? 0) < 0 ? number_format(abs($opening ?? 0), 2) : '-' ?></td>
          <td class="text-credit"><?= ($opening ?? 0) > 0 ? number_format($opening ?? 0, 2) : '-' ?></td>
          <td class="<?= ($opening ?? 0) < 0 ? 'balance-debit' : 'balance-credit' ?>">
            <?= number_format($opening ?? 0, 2) ?>
          </td>
        </tr>

        <?php if (isset($transactions) && is_array($transactions)): ?>
        <?php $running = $opening ?? 0; ?>
        <?php foreach ($transactions as $t):
          if ($t['type'] == 'invoice') $running -= $t['amount'];
          else $running += $t['paid'];
        ?>
        <tr>
          <td><?= $t['date'] ?></td>
          <td style="text-align:right">
            <?php if ($t['type'] == 'invoice'): ?>
              <span class="inline-flex items-center gap-1">
                <i class="fas fa-file-invoice text-red-600"></i>
                <strong>فاتورة مشتريات</strong> #<?= $t['ref'] ?>
              </span>
            <?php else: ?>
              <span class="inline-flex items-center gap-1">
                <i class="fas fa-hand-holding-usd text-green-600"></i>
                دفعة / سداد
                <?php if (!empty($t['description'])): ?>
                  &mdash; <?= htmlspecialchars($t['description']) ?>
                <?php endif; ?>
              </span>
            <?php endif; ?>
          </td>
          <td class="text-debit"><?= ($t['amount'] ?? 0) > 0 ? number_format($t['amount'], 2) : '-' ?></td>
          <td class="text-credit"><?= ($t['paid'] ?? 0) > 0 ? number_format($t['paid'], 2) : '-' ?></td>
          <td class="<?= $running < 0 ? 'balance-debit' : 'balance-credit' ?>">
            <?= number_format($running, 2) ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
      <tfoot>
        <tr class="table-footer">
          <td colspan="2" style="text-align:left">الرصيد النهائي / CLOSING BALANCE</td>
          <td><?= number_format($totalCredit, 2) ?></td>
          <td><?= number_format($totalDebit, 2) ?></td>
          <td style="font-size:1.05rem"><?= number_format($balance, 2) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
