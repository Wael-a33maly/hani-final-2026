<?php requireLogin(); 
$pageTitle = 'إدارة الأقساط';
$breadcrumb = ['المالية', 'الأقساط'];
ob_start();
?>

<div x-data="installmentsManager()" x-init="init()" class="space-y-6">
    <!-- كروت إحصائية قابلة للضغط -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <button @click="setFilter('all')" 
                :class="{'ring-2 ring-blue-500 bg-blue-50': currentFilter === 'all', 'bg-white hover:bg-gray-50': currentFilter !== 'all'}"
                class="rounded-xl shadow-sm p-4 text-right transition-all border">
            <p class="text-sm text-gray-500">الإجمالي</p>
            <p class="text-2xl font-bold text-gray-800 mt-1"><?= $statsTotal ?></p>
            <p class="text-xs text-gray-400 mt-1">جميع الأقساط</p>
        </button>
        <button @click="setFilter('pending')"
                :class="{'ring-2 ring-yellow-500 bg-yellow-50': currentFilter === 'pending', 'bg-white hover:bg-gray-50': currentFilter !== 'pending'}"
                class="rounded-xl shadow-sm p-4 text-right transition-all border">
            <p class="text-sm text-gray-500">معلقة</p>
            <p class="text-2xl font-bold text-yellow-600 mt-1"><?= $statsPending ?></p>
            <p class="text-xs text-gray-400 mt-1">غير مدفوعة بعد</p>
        </button>
        <button @click="setFilter('paid')"
                :class="{'ring-2 ring-green-500 bg-green-50': currentFilter === 'paid', 'bg-white hover:bg-gray-50': currentFilter !== 'paid'}"
                class="rounded-xl shadow-sm p-4 text-right transition-all border">
            <p class="text-sm text-gray-500">مدفوعة</p>
            <p class="text-2xl font-bold text-green-600 mt-1"><?= $statsPaid ?></p>
            <p class="text-xs text-gray-400 mt-1">تم السداد كاملاً</p>
        </button>
        <button @click="setFilter('overdue')"
                :class="{'ring-2 ring-red-500 bg-red-50': currentFilter === 'overdue', 'bg-white hover:bg-gray-50': currentFilter !== 'overdue'}"
                class="rounded-xl shadow-sm p-4 text-right transition-all border">
            <p class="text-sm text-gray-500">متأخرة</p>
            <p class="text-2xl font-bold text-red-600 mt-1"><?= $statsOverdue ?></p>
            <p class="text-xs text-gray-400 mt-1">تجاوزت تاريخ الاستحقاق</p>
        </button>
    </div>

    <!-- فلاتر إضافية -->
    <div class="bg-white rounded-xl shadow-sm p-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <select name="branch_id" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">كل الفروع</option>
                <?php foreach($branches as $b): ?>
                <option value="<?= $b['id'] ?>" <?= ($_GET['branch_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="sales_rep_id" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">كل المندوبين</option>
                <?php foreach($reps as $r): ?>
                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['full_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="from_date" value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>" class="border rounded-lg px-3 py-2 text-sm" placeholder="من تاريخ">
            <input type="date" name="to_date" value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>" class="border rounded-lg px-3 py-2 text-sm" placeholder="إلى تاريخ">
            <div class="md:col-span-4 flex justify-end">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">تطبيق الفلاتر</button>
            </div>
        </form>
    </div>

    <!-- عرض الأقساط: جدول للديسكتوب - كروت للموبايل -->
    <!-- الجدول (يظهر على الشاشات الكبيرة) -->
    <div class="hidden md:block bg-white rounded-xl shadow-sm overflow-hidden">
        <form method="POST" action="<?= APP_URL ?>/installments/pay-multiple" id="bulkPayForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="p-3 text-right w-12"><input type="checkbox" :checked="selectAll" @click="toggleSelectAll"></th>
                            <th class="p-3 text-right">العميل</th>
                            <th class="p-3 text-right">الفاتورة</th>
                            <th class="p-3 text-right">رقم القسط</th>
                            <th class="p-3 text-right">تاريخ الاستحقاق</th>
                            <th class="p-3 text-right">المبلغ</th>
                            <th class="p-3 text-right">المدفوع</th>
                            <th class="p-3 text-right">الحالة</th>
                            <th class="p-3 text-right">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($installments as $inst): 
                            $isOpening = !empty($inst['is_opening']);
                            $isOverdue = ($inst['due_date'] < date('Y-m-d') && $inst['status'] != 'paid');
                            $statusClass = $inst['status'] == 'paid' ? 'success' : ($isOverdue ? 'danger' : 'warning');
                            $statusText = $inst['status'] == 'paid' ? 'مدفوع' : ($isOverdue ? 'متأخر' : 'معلق');
                            if ($isOpening) { $statusText = 'افتتاحي'; $statusClass = 'info'; }
                        ?>
                        <tr class="border-b hover:bg-gray-50 transition <?= $isOpening ? 'bg-sky-50' : '' ?>">
                            <td class="p-3 text-center">
                                <?php if ($inst['status'] != 'paid' && !$isOpening): ?>
                                <input type="checkbox" name="installment_ids[]" value="<?= $inst['id'] ?>" class="installment-checkbox" x-model="selectedIds" @change="updateSelectedCount">
                                <?php elseif ($isOpening && $inst['status'] != 'paid'): ?>
                                <input type="checkbox" name="opening_ids[]" value="<?= $inst['id'] ?>" class="installment-checkbox" x-model="selectedIds" @change="updateSelectedCount">
                                <?php endif; ?>
                            </td>
                            <td class="p-3"><?= htmlspecialchars($inst['customer_name']) ?></td>
                            <td class="p-3"><?= $isOpening ? '<span class="text-sky-700 text-xs">قبل التطبيق</span>' : $inst['invoice_number'] ?></td>
                            <td class="p-3"><?= $isOpening ? '<span class="text-sky-700"><i class="fas fa-history ml-1"></i>قسط افتتاحي</span>' : $inst['installment_number'] ?></td>
                            <td class="p-3 <?= $isOverdue ? 'text-red-600 font-semibold' : '' ?>"><?= $inst['due_date'] ?></td>
                            <td class="p-3"><?= number_format($inst['amount'], 2) ?></td>
                            <td class="p-3"><?= number_format($inst['paid_amount'], 2) ?></td>
                            <td class="p-3">
                                <span class="badge badge-<?= $statusClass ?>"><?= $statusText ?></span>
                                <?php if (!empty($inst['notes'])): ?>
                                <span class="block text-xs text-sky-600 mt-1"><i class="fas fa-comment ml-1"></i><?= htmlspecialchars($inst['notes']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                <?php if ($isOpening): ?>
                                <a href="<?= BASE_URL ?>print/opening-receipt/<?= $inst['id'] ?>" target="_blank" class="text-orange-600 hover:text-orange-800 p-1" title="طباعة إيصال"><i class="fas fa-print"></i></a>
                                <?php if ($inst['status'] != 'paid'): ?>
                                <button type="button" onclick="showPaymentModal(<?= $inst['id'] ?>, '<?= addslashes($inst['customer_name']) ?>', <?= $inst['amount'] ?>, <?= $inst['paid_amount'] ?>)" class="text-green-600 hover:text-green-800 p-1" title="دفع"><i class="fas fa-money-bill-wave"></i></button>
                                <?php endif; ?>
                                <?php else: ?>
                                <button type="button" onclick="viewInvoice(<?= $inst['id'] ?>)" class="text-blue-600 hover:text-blue-800 p-1" title="معاينة الفاتورة"><i class="fas fa-eye"></i></button>
                                <a href="<?= BASE_URL ?>print/receipt/<?= $inst['id'] ?>" target="_blank" class="text-orange-600 hover:text-orange-800 p-1" title="طباعة إيصال"><i class="fas fa-print"></i></a>
                                <?php endif; ?>
                                <?php if ($inst['status'] != 'paid'): ?>
                                <button type="button" onclick="showPaymentModal(<?= $inst['id'] ?>, '<?= addslashes($inst['customer_name']) ?>', <?= $inst['amount'] ?>, <?= $inst['paid_amount'] ?>)" class="text-green-600 hover:text-green-800 p-1" title="دفع"><i class="fas fa-money-bill-wave"></i></button>
                                <button type="button" onclick="showNoteModal(<?= $inst['id'] ?>)" class="text-gray-600 hover:text-gray-800 p-1" title="إضافة ملاحظة"><i class="fas fa-comment"></i></button>
                                <form method="POST" action="<?= APP_URL ?>/installments/delete/<?= $inst['id'] ?>" class="inline" onsubmit="event.preventDefault(); confirmDelete(this)">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <button type="submit" class="text-red-600 hover:text-red-800 p-1" title="حذف"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($installments)): ?>
                        <tr><td colspan="9" class="p-8 text-center text-gray-500">لا توجد أقساط مطابقة للبحث</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>

    <!-- عرض الكروت للموبايل -->
    <div class="md:hidden space-y-3">
        <?php foreach($installments as $inst): 
            $isOpening = !empty($inst['is_opening']);
            $isOverdue = ($inst['due_date'] < date('Y-m-d') && $inst['status'] != 'paid');
            $statusClass = $inst['status'] == 'paid' ? 'success' : ($isOverdue ? 'danger' : 'warning');
            $statusText = $inst['status'] == 'paid' ? 'مدفوع' : ($isOverdue ? 'متأخر' : 'معلق');
            if ($isOpening) { $statusText = 'افتتاحي'; $statusClass = 'info'; }
            $borderColor = $isOpening ? 'sky' : ($statusClass === 'danger' ? 'red' : ($statusClass === 'success' ? 'green' : 'yellow'));
        ?>
        <div class="bg-white rounded-xl shadow-sm p-4 border-r-4 border-<?= $borderColor ?>-500 <?= $isOpening ? 'bg-sky-50' : '' ?>">
            <div class="flex justify-between items-start mb-2">
                <div class="font-semibold text-gray-800"><?= htmlspecialchars($inst['customer_name']) ?></div>
                <span class="badge badge-<?= $statusClass ?>"><?= $statusText ?></span>
            </div>
            <div class="text-sm text-gray-600 space-y-1">
                <?php if ($isOpening): ?>
                <div><i class="fas fa-history w-5 text-sky-600"></i> <span class="text-sky-700 font-semibold">قسط افتتاحي (من قبل التطبيق)</span></div>
                <?php else: ?>
                <div><i class="fas fa-file-invoice w-5"></i> فاتورة: <?= $inst['invoice_number'] ?> - قسط <?= $inst['installment_number'] ?></div>
                <?php endif; ?>
                <div><i class="fas fa-calendar w-5"></i> تاريخ الاستحقاق: <?= $inst['due_date'] ?></div>
                <div><i class="fas fa-money-bill w-5"></i> المبلغ: <?= number_format($inst['amount'], 2) ?> ج.م</div>
                <div><i class="fas fa-hand-holding-usd w-5"></i> المدفوع: <?= number_format($inst['paid_amount'], 2) ?> ج.م</div>
                <?php if (!empty($inst['notes'])): ?>
                <div class="text-xs text-sky-600 mt-1"><i class="fas fa-comment ml-1"></i><?= htmlspecialchars($inst['notes']) ?></div>
                <?php endif; ?>
            </div>
            <div class="flex justify-end gap-3 mt-3 pt-2 border-t">
                <?php if ($isOpening): ?>
                <a href="<?= BASE_URL ?>print/opening-receipt/<?= $inst['id'] ?>" target="_blank" class="text-orange-600"><i class="fas fa-print"></i> طباعة</a>
                <?php if ($inst['status'] != 'paid'): ?>
                <button type="button" onclick="showPaymentModal(<?= $inst['id'] ?>, '<?= addslashes($inst['customer_name']) ?>', <?= $inst['amount'] ?>, <?= $inst['paid_amount'] ?>)" class="text-green-600"><i class="fas fa-money-bill-wave"></i> دفع</button>
                <?php endif; ?>
                <?php else: ?>
                <button type="button" onclick="viewInvoice(<?= $inst['id'] ?>)" class="text-blue-600"><i class="fas fa-eye"></i> معاينة</button>
                <a href="<?= BASE_URL ?>print/receipt/<?= $inst['id'] ?>" target="_blank" class="text-orange-600"><i class="fas fa-print"></i> طباعة</a>
                <?php if ($inst['status'] != 'paid'): ?>
                <button type="button" onclick="showPaymentModal(<?= $inst['id'] ?>, '<?= addslashes($inst['customer_name']) ?>', <?= $inst['amount'] ?>, <?= $inst['paid_amount'] ?>)" class="text-green-600"><i class="fas fa-money-bill-wave"></i> دفع</button>
                <button type="button" onclick="showNoteModal(<?= $inst['id'] ?>)" class="text-gray-600"><i class="fas fa-comment"></i> ملاحظة</button>
                <button type="button" onclick="confirmDeleteCard(<?= $inst['id'] ?>)" class="text-red-600"><i class="fas fa-trash"></i> حذف</button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- شريط الاختيار المتعدد -->
    <div x-show="selectedCount > 0" 
         x-transition
         class="fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white rounded-full shadow-xl px-6 py-3 flex items-center gap-4 z-30">
        <span>تم اختيار <span x-text="selectedCount"></span> قسط</span>
        <button @click="paySelected()" class="bg-green-600 px-4 py-1 rounded-full text-sm">دفع المحدد</button>
        <button @click="clearSelected()" class="text-gray-300 text-sm">إلغاء</button>
    </div>

    <!-- Modal الدفع -->
    <div id="paymentModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md">
            <h3 class="text-lg font-bold mb-4">دفع قسط</h3>
            <form id="paymentForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <p class="mb-2">العميل: <span id="paymentCustomer"></span></p>
                <p class="mb-2">المبلغ الكلي: <span id="paymentTotal"></span> ج.م</p>
                <p class="mb-2">المدفوع سابقاً: <span id="paymentPaid"></span> ج.م</p>
                <div class="mb-4">
                    <label class="block text-sm mb-1">المبلغ المراد دفعه</label>
                    <input type="number" name="amount" id="paymentAmount" step="0.01" required class="w-full border rounded-lg px-3 py-2">
                </div>
                <div class="mb-4">
                    <label class="block text-sm mb-1">ملاحظات</label>
                    <input type="text" name="notes" class="w-full border rounded-lg px-3 py-2">
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="closePaymentModal()" class="px-4 py-2 bg-gray-200 rounded-lg">إلغاء</button>
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">تأكيد الدفع</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function installmentsManager() {
    return {
        selectedIds: [],
        selectAll: false,
        currentFilter: 'all',
        selectedCount: 0,
        init() {
            this.updateSelectedCount();
        },
        toggleSelectAll() {
            this.selectAll = !this.selectAll;
            const checkboxes = document.querySelectorAll('.installment-checkbox');
            if (this.selectAll) {
                checkboxes.forEach(cb => cb.checked = true);
                this.selectedIds = Array.from(checkboxes).map(cb => cb.value);
            } else {
                checkboxes.forEach(cb => cb.checked = false);
                this.selectedIds = [];
            }
            this.updateSelectedCount();
        },
        updateSelectedCount() {
            this.selectedCount = this.selectedIds.length;
        },
        clearSelected() {
            this.selectedIds = [];
            document.querySelectorAll('.installment-checkbox').forEach(cb => cb.checked = false);
            this.updateSelectedCount();
        },
        paySelected() {
            if (this.selectedCount === 0) return;
            document.getElementById('bulkPayForm').submit();
        },
        setFilter(filter) {
            this.currentFilter = filter;
            // يمكن إعادة توجيه أو تحديث الجدول حسب الفلتر
            let url = new URL(window.location.href);
            url.searchParams.set('status', filter === 'all' ? '' : filter);
            if (filter === 'overdue') url.searchParams.set('overdue', '1');
            window.location.href = url.toString();
        }
    }
}

let activeInstallmentId = null;

function showPaymentModal(id, customerName, total, paid) {
    activeInstallmentId = id;
    document.getElementById('paymentCustomer').innerText = customerName;
    document.getElementById('paymentTotal').innerText = total;
    document.getElementById('paymentPaid').innerText = paid;
    document.getElementById('paymentAmount').value = (total - paid).toFixed(2);
    document.getElementById('paymentForm').action = '<?= APP_URL ?>/installments/pay/' + id;
    document.getElementById('paymentModal').classList.remove('hidden');
    document.getElementById('paymentModal').classList.add('flex');
}

function closePaymentModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

function viewInvoice(id) {
    window.open('<?= APP_URL ?>/installments/view-invoice/' + id, '_blank');
}

function confirmDelete(formElement) {
    if (confirm('هل أنت متأكد من حذف هذا القسط؟')) {
        formElement.submit();
    }
}

function confirmDeleteCard(id) {
    if (confirm('هل أنت متأكد من حذف هذا القسط؟')) {
        let form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= APP_URL ?>/installments/delete/' + id;
        let csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = '<?= generateCSRFToken() ?>';
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
