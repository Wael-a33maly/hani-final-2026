<?php requireLogin(); $pageTitle = 'طباعة الأقساط'; ob_start(); ?>
<div class="bg-white rounded-xl shadow-sm p-6" x-data="installmentPrinter()">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-print text-gray-600"></i> طباعة إيصالات الأقساط
        </h2>
    </div>

    <!-- فلاتر البحث -->
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-8 bg-gray-50 p-4 rounded-lg border border-gray-100">
        <select name="customer_id" class="border border-gray-300 rounded-lg p-2 text-sm">
            <option value="">كل العملاء</option>
            <?php foreach($customers as $c): ?>
                <option value="<?=$c['id']?>" <?=($filters['customer_id'] == $c['id'] ? 'selected' : '')?>><?=$c['name']?></option>
            <?php endforeach; ?>
        </select>
        <select name="sales_rep_id" class="border border-gray-300 rounded-lg p-2 text-sm">
            <option value="">كل المناديب</option>
            <?php foreach($reps as $r): ?>
                <option value="<?=$r['id']?>" <?=($filters['sales_rep_id'] == $r['id'] ? 'selected' : '')?>><?=$r['full_name']?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="border border-gray-300 rounded-lg p-2 text-sm">
            <option value="">كل الحالات</option>
            <option value="pending" <?=($filters['status'] == 'pending' ? 'selected' : '')?>>معلق</option>
            <option value="paid" <?=($filters['status'] == 'paid' ? 'selected' : '')?>>مدفوع</option>
        </select>
        <input type="date" name="from_date" value="<?=$filters['from_date']?>" class="border border-gray-300 rounded-lg p-2 text-sm">
        <input type="date" name="to_date" value="<?=$filters['to_date']?>" class="border border-gray-300 rounded-lg p-2 text-sm">
        <button type="submit" class="bg-blue-600 text-white rounded-lg px-4 py-2 hover:bg-blue-700 transition font-bold text-sm">
            <i class="fas fa-search ml-1"></i> بحث
        </button>
    </form>

    <?php if (!empty($installments)): ?>
    <div class="flex justify-between items-center mb-4 sticky top-0 bg-white z-10 py-2 border-b">
        <div class="flex items-center gap-4">
            <span class="text-sm text-gray-500 font-bold">تم العثور على (<?=count($installments)?>) قسط</span>
            <div x-show="selectedIds.length > 0" class="flex items-center gap-2 animate-bounce">
                <span class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-xs font-bold">تم تحديد (<span x-text="selectedIds.length"></span>) قسط</span>
                <button @click="printSelected()" class="bg-orange-600 text-white px-4 py-1.5 rounded-lg hover:bg-orange-700 transition text-sm font-bold flex items-center gap-2 shadow-lg shadow-orange-100">
                    <i class="fas fa-print"></i> طباعة المحدد
                </button>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="<?= APP_URL ?>/print/bulk-receipts?ids=<?= implode(',', array_column($installments, 'id')) ?>" 
               target="_blank" 
               class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-900 transition text-sm flex items-center gap-2">
                <i class="fas fa-print"></i> طباعة الكل المعروض
            </a>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th class="px-4 py-3 w-10">
                        <input type="checkbox" @change="toggleAll($event)" class="w-4 h-4 rounded border-gray-300 focus:ring-orange-500">
                    </th>
                    <th class="px-4 py-3">العميل</th>
                    <th class="px-4 py-3">رقم الفاتورة</th>
                    <th class="px-4 py-3">رقم القسط</th>
                    <th class="px-4 py-3">تاريخ الاستحقاق</th>
                    <th class="px-4 py-3">المبلغ</th>
                    <th class="px-4 py-3">الحالة</th>
                    <th class="px-4 py-3 text-center">إجراء</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach($installments as $i): ?>
                <tr class="hover:bg-blue-50 transition-colors" :class="selectedIds.includes('<?=$i['id']?>') ? 'bg-blue-50' : ''">
                    <td class="px-4 py-3">
                        <input type="checkbox" value="<?=$i['id']?>" x-model="selectedIds" class="w-4 h-4 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    </td>
                    <td class="px-4 py-3 font-medium text-gray-800"><?=$i['customer_name']?></td>
                    <td class="px-4 py-3 font-mono text-xs font-bold"><?=$i['invoice_number']?></td>
                    <td class="px-4 py-3 font-bold text-blue-600">
                        <span class="bg-blue-50 px-2 py-1 rounded text-xs">
                            <?= $i['installment_number'] ?> / <?= $i['total_installments_count'] ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-red-600 font-bold"><?=$i['due_date']?></td>
                    <td class="px-4 py-3 font-black text-gray-900"><?=number_format($i['amount'], 2)?></td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase <?=($i['status'] == 'paid' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700')?>">
                            <?=($i['status'] == 'paid' ? 'مدفوع' : 'معلق')?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="<?=APP_URL?>/print/receipt/<?=$i['id']?>" target="_blank" class="text-blue-500 hover:text-blue-700 bg-blue-50 p-2 rounded-lg transition-all" title="طباعة فردية">
                            <i class="fas fa-print"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <div class="text-center py-12 text-gray-400 border-2 border-dashed rounded-2xl">
            <i class="fas fa-search fa-3x mb-4 text-gray-200"></i>
            <p class="text-lg font-bold">استخدم الفلاتر أعلاه للبحث عن الأقساط المطلوب طباعتها</p>
            <p class="text-sm">يمكنك البحث بالعميل، المندوب، التاريخ أو الحالة</p>
        </div>
    <?php endif; ?>
</div>

<script>
function installmentPrinter() {
    return {
        selectedIds: [],
        allIds: [<?= implode(',', array_column($installments ?? [], 'id')) ?>],
        
        toggleAll(e) {
            if (e.target.checked) {
                this.selectedIds = this.allIds.map(String);
            } else {
                this.selectedIds = [];
            }
        },
        
        printSelected() {
            if (this.selectedIds.length === 0) return;
            const url = '<?= APP_URL ?>/print/bulk-receipts?ids=' + this.selectedIds.join(',');
            window.open(url, '_blank');
        }
    }
}
</script>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
