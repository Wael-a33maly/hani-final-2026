<?php requireLogin(); $pageTitle = 'مدفوعات الموردين'; ob_start(); ?>
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-hand-holding-usd text-green-600"></i> مدفوعات الموردين
        </h2>
        <a href="<?= APP_URL ?>/supplier-payments/create" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-2">
            <i class="fas fa-plus"></i> دفعة جديدة
        </a>
    </div>
    
    <!-- نموذج الفلترة -->
    <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-6 bg-gray-50 p-4 rounded-lg border border-gray-100">
        <select name="supplier_id" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
            <option value="">كل الموردين</option>
            <?php foreach($suppliers as $s): ?>
            <option value="<?= $s['id'] ?>" <?= (isset($_GET['supplier_id']) && $_GET['supplier_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="date" name="from_date" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
        <input type="date" name="to_date" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
        <select name="payment_type" class="border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
            <option value="">كل طرق الدفع</option>
            <option value="cash" <?= (isset($_GET['payment_type']) && $_GET['payment_type'] == 'cash') ? 'selected' : '' ?>>نقدي</option>
            <option value="vodafone" <?= (isset($_GET['payment_type']) && $_GET['payment_type'] == 'vodafone') ? 'selected' : '' ?>>فودافون كاش</option>
            <option value="instapay" <?= (isset($_GET['payment_type']) && $_GET['payment_type'] == 'instapay') ? 'selected' : '' ?>>انستا باي</option>
            <option value="bank" <?= (isset($_GET['payment_type']) && $_GET['payment_type'] == 'bank') ? 'selected' : '' ?>>بنك</option>
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition font-bold text-sm">
            <i class="fas fa-filter ml-1"></i> فلترة
        </button>
    </form>
    
    <!-- جدول المدفوعات -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="p-3">#</th>
                    <th class="p-3">المورد</th>
                    <th class="p-3">المبلغ</th>
                    <th class="p-3">التاريخ</th>
                    <th class="p-3">طريقة الدفع</th>
                    <th class="p-3">ملاحظات</th>
                    <th class="p-3">بواسطة</th>
                    <th class="p-3 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($payments)): ?>
                <tr><td colspan="8" class="p-6 text-center text-gray-500 italic">لا توجد مدفوعات مسجلة</td></tr>
                <?php else: ?>
                <?php foreach($payments as $p): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-3 text-gray-400 font-mono text-xs"><?= $p['id'] ?></td>
                    <td class="p-3 font-bold text-gray-800"><?= htmlspecialchars($p['supplier_name']) ?></td>
                    <td class="p-3 font-bold text-red-600"><?= number_format($p['amount'], 2) ?></td>
                    <td class="p-3"><?= $p['payment_date'] ?></td>
                    <td class="p-3">
                        <span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">
                            <?= $p['payment_type'] ?>
                        </span>
                    </td>
                    <td class="p-3 text-gray-500 max-w-xs truncate"><?= htmlspecialchars($p['notes']) ?></td>
                    <td class="p-3 text-gray-400 text-xs"><?= htmlspecialchars($p['created_by_name']) ?></td>
                    <td class="p-3 text-center">
                        <?php if($_SESSION['user_role'] == 'admin'): ?>
                        <form method="POST" action="<?= APP_URL ?>/supplier-payments/delete/<?= $p['id'] ?>" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذه الدفعة؟')">
                            <?= csrf_field() ?>
                            <button type="submit" class="text-red-500 hover:text-red-700 p-1">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="bg-green-50 font-bold border-t-2 border-green-200">
                    <td colspan="2" class="p-4 text-left text-green-800">إجمالي البحث:</td>
                    <td class="p-4 text-green-800"><?= number_format($total, 2) ?> ج.م</td>
                    <td colspan="5"></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="mt-6">
        <?= $pagination['links'] ?? '' ?>
    </div>
</div>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
