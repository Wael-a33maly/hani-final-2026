<?php requireLogin(); $pageTitle = 'المشتريات'; ob_start(); ?>
<div class="bg-white rounded-xl shadow">
    <div class="p-5 border-b flex justify-between items-center">
        <h2 class="text-lg font-bold text-gray-700">
            <i class="fas fa-shopping-cart ml-2 text-green-500"></i>فواتير المشتريات
        </h2>
        <a href="<?php echo APP_URL; ?>/purchases/create"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm flex items-center gap-2">
            <i class="fas fa-plus"></i> فاتورة شراء جديدة
        </a>
    </div>
    
    <!-- نموذج الفلترة -->
    <div class="p-4 border-b bg-gray-50">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <select name="supplier_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
                <option value="">كل الموردين</option>
                <?php foreach($suppliers as $s): ?>
                <option value="<?= $s['id'] ?>" <?= (isset($supplier_id) && $supplier_id == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="from_date" value="<?= htmlspecialchars($from_date ?? '') ?>" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
            <input type="date" name="to_date" value="<?= htmlspecialchars($to_date ?? '') ?>" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
            <select name="payment_type" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
                <option value="">طريقة الدفع (الكل)</option>
                <option value="cash" <?= (isset($payment_type) && $payment_type == 'cash') ? 'selected' : '' ?>>نقدي</option>
                <option value="credit" <?= (isset($payment_type) && $payment_type == 'credit') ? 'selected' : '' ?>>آجل</option>
            </select>
            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition font-bold text-sm flex items-center justify-center gap-2">
                <i class="fas fa-search"></i> فلترة
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-right">رقم الفاتورة</th>
                    <th class="px-4 py-3 text-right">المورد</th>
                    <th class="px-4 py-3 text-right hidden sm:table-cell">التاريخ</th>
                    <th class="px-4 py-3 text-right hidden md:table-cell">طريقة الدفع</th>
                    <th class="px-4 py-3 text-right">الإجمالي</th>
                    <th class="px-4 py-3 text-right">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($invoices as $inv): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-mono text-xs font-bold whitespace-nowrap"><?php echo htmlspecialchars($inv['invoice_number']); ?></td>
                    <td class="px-4 py-3 font-medium text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($inv['supplier_name']); ?></td>
                    <td class="px-4 py-3 text-gray-600 hidden sm:table-cell whitespace-nowrap"><?php echo htmlspecialchars($inv['date']); ?></td>
                    <td class="px-4 py-3 hidden md:table-cell whitespace-nowrap">
                        <span class="px-2 py-1 rounded text-xs <?php echo $inv['payment_type'] == 'cash' ? 'bg-green-100 text-green-700' : 'bg-orange-100 text-orange-700'; ?>">
                            <?php echo $inv['payment_type'] == 'cash' ? 'نقدي' : 'آجل'; ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 font-bold text-blue-600 whitespace-nowrap"><?php echo number_format($inv['total'], 2); ?></td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <div class="flex items-center gap-3">
                            <a href="<?php echo APP_URL; ?>/purchases/show/<?php echo $inv['id']; ?>"
                               class="text-indigo-500 hover:text-indigo-700 transition" title="عرض التفاصيل">
                                <i class="fas fa-eye"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($invoices)): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">لا يوجد فواتير مشتريات</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if (!empty($pagination['links'])): ?>
        <div class="p-4 border-t">
            <?= $pagination['links'] ?>
        </div>
    <?php endif; ?>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
