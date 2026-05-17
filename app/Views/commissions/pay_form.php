<?php requireRole('admin'); $pageTitle = 'صرف عمولة - ' . $agent['full_name']; ob_start(); ?>
<div class="max-w-2xl mx-auto" x-data="payForm()" x-init="init()">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-hand-holding-usd text-green-600 text-xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800">صرف عمولة مندوب</h2>
                <p class="text-gray-500 text-sm"><?= htmlspecialchars($agent['full_name']) ?> | <?= htmlspecialchars($agent['phone'] ?? '') ?></p>
            </div>
        </div>

        <form method="POST" action="<?= APP_URL ?>/commissions/pay/<?= $agent['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">

            <!-- الفترة -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">من تاريخ</label>
                    <input type="date" name="period_from" x-model="periodFrom" @change="calculate()"
                           class="w-full border rounded-lg px-3 py-2" required>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">إلى تاريخ</label>
                    <input type="date" name="period_to" x-model="periodTo" @change="calculate()"
                           class="w-full border rounded-lg px-3 py-2" required>
                </div>
            </div>

            <!-- ملخص المستحق -->
            <div class="bg-gray-50 rounded-lg p-4 mb-4" x-show="calculated" x-cloak>
                <h4 class="font-bold text-gray-700 mb-3">ملخص المستحق في الفترة</h4>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">عمولات مبيعات:</span>
                        <span class="font-bold text-orange-600" x-text="formatNumber(sales)"></span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-600">عمولات تحصيل:</span>
                        <span class="font-bold text-blue-600" x-text="formatNumber(collection)"></span>
                    </div>
                    <div class="flex justify-between font-bold text-base border-t pt-2">
                        <span class="text-gray-800">إجمالي المستحق:</span>
                        <span class="text-green-600" x-text="formatNumber(totalDue)"></span>
                    </div>
                </div>
            </div>

            <!-- مبلغ الصرف -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">المبلغ المصروف <span class="text-red-500">*</span></label>
                <input type="number" step="0.01" min="0" name="amount" x-model="amount"
                       class="w-full border rounded-lg px-3 py-2 text-lg font-bold"
                       placeholder="0.00" required>
                <p class="text-sm text-gray-500 mt-1">
                    يمكنك صرف جزء من المستحق (دفعة جزئية) أو أكثر من المستحق (مكافأة/سلفة)
                </p>
            </div>

            <!-- سبب الفرق إن وجد -->
            <div x-show="parseFloat(amount) != parseFloat(totalDue) && calculated" x-cloak
                 x-transition class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                <label class="block text-sm font-medium text-gray-700 mb-1">
                    <i class="fas fa-exclamation-triangle text-yellow-500"></i>
                    سبب الفرق (زيادة/نقص) عن المستحق
                </label>
                <textarea name="difference_reason" rows="2"
                          class="w-full border border-yellow-300 rounded-lg px-3 py-2 text-sm"
                          placeholder="اذكر سبب الزيادة (مكافأة, حافز, ...) أو النقص (خصم, سلفة, ...)"></textarea>
            </div>

            <!-- تاريخ الصرف -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">تاريخ الصرف</label>
                <input type="date" name="payment_date" value="<?= date('Y-m-d') ?>"
                       class="w-full border rounded-lg px-3 py-2" required>
            </div>

            <!-- طريقة الصرف -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">طريقة الصرف</label>
                <select name="payment_type" class="w-full border rounded-lg px-3 py-2">
                    <option value="cash">نقدي</option>
                    <option value="transfer">تحويل بنكي</option>
                    <option value="vodafone">فودافون كاش</option>
                    <option value="instapay">إنستا باي</option>
                </select>
            </div>

            <!-- ملاحظات -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-1">ملاحظات</label>
                <textarea name="notes" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm"
                          placeholder="أي ملاحظات إضافية..."></textarea>
            </div>

            <!-- أزرار -->
            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="<?= APP_URL ?>/commissions/agent/<?= $agent['id'] ?>"
                   class="bg-gray-200 text-gray-700 px-5 py-2 rounded-lg hover:bg-gray-300 transition text-sm">
                    إلغاء
                </a>
                <button type="submit"
                        class="bg-green-600 text-white px-5 py-2 rounded-lg hover:bg-green-700 transition text-sm flex items-center gap-2">
                    <i class="fas fa-check"></i> تأكيد الصرف
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function payForm() {
    return {
        periodFrom: '<?= date('Y-m-01') ?>',
        periodTo: '<?= date('Y-m-d') ?>',
        sales: 0,
        collection: 0,
        totalDue: 0,
        amount: 0,
        calculated: false,
        init() {
            this.calculate();
        },
        calculate() {
            if (!this.periodFrom || !this.periodTo) return;
            const self = this;
            fetch(`<?= APP_URL ?>/commissions/calculate/<?= $agent['id'] ?>?from=${this.periodFrom}&to=${this.periodTo}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        self.sales = data.sales;
                        self.collection = data.collection;
                        self.totalDue = data.total;
                        self.amount = data.total;
                        self.calculated = true;
                    }
                });
        },
        formatNumber(num) {
            return parseFloat(num || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + ' ج.م';
        }
    }
}
</script>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
