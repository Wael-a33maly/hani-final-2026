<?php
$pageTitle = 'إنشاء سند صرف جديد';
ob_start();
?>
<div class="max-w-3xl mx-auto bg-white rounded-xl shadow p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-2">
        <i class="fas fa-file-invoice-dollar text-blue-500"></i> <?php echo $pageTitle; ?>
    </h2>

    <form action="<?php echo APP_URL; ?>/expenses/vouchers/store" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <div class="md:col-span-2">
                <label class="block text-gray-700 text-sm font-bold mb-2">بند المصروف <span class="text-red-500">*</span></label>
                <select name="expense_id" required class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500">
                    <option value="">-- اختر البند --</option>
                    <?php foreach ($expenses as $exp): ?>
                        <option value="<?php echo $exp['id']; ?>"><?php echo htmlspecialchars($exp['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">المبلغ <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="number" step="0.01" min="0" name="amount" required
                           class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500 text-left" dir="ltr">
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500 sm:text-sm">د.ك</span>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">التاريخ <span class="text-red-500">*</span></label>
                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required
                       class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500">
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">طريقة الدفع <span class="text-red-500">*</span></label>
                <select name="payment_type" required class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500">
                    <option value="cash">كاش</option>
                    <option value="knet">كي نت</option>
                    <option value="bank">تحويل بنكي</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2">الفرع <span class="text-red-500">*</span></label>
                <select name="branch_id" required class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500">
                    <?php foreach ($branches as $branch): ?>
                        <option value="<?php echo $branch['id']; ?>" <?php echo ($_SESSION['branch_id'] == $branch['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($branch['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-gray-700 text-sm font-bold mb-2">البيان / ملاحظات</label>
                <textarea name="notes" rows="3" class="w-full border rounded px-3 py-2 focus:outline-none focus:border-blue-500"></textarea>
            </div>
            
        </div>

        <div class="pt-4 border-t mt-6 flex justify-end gap-2">
            <a href="<?php echo APP_URL; ?>/expenses/vouchers" class="bg-gray-100 text-gray-700 px-6 py-2 rounded text-sm hover:bg-gray-200 transition">إلغاء</a>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded text-sm hover:bg-blue-700 transition">
                <i class="fas fa-save"></i> حفظ وإصدار السند
            </button>
        </div>
    </form>
</div>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>
