<?php
$pageTitle = 'تحويل بين المخازن';
ob_start();
?>
<div class="max-w-6xl mx-auto bg-white rounded-xl shadow p-6">
    <h2 class="text-xl font-bold text-gray-800 mb-6 border-b pb-4 flex items-center gap-2">
        <i class="fas fa-truck-loading text-yellow-500"></i> عملية تحويل بضاعة
    </h2>

    <form method="POST" action="<?php echo APP_URL; ?>/reports/transfer-store" id="transferForm">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <!-- المخازن -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-red-50 p-4 rounded-lg border border-red-100">
                <label class="block text-sm font-bold text-red-700 mb-2">من المخزن (المصدر) *</label>
                <select name="from_warehouse_id" id="fromWarehouse" required
                    class="w-full border border-red-300 rounded p-2.5 focus:ring-2 focus:ring-red-400 outline-none">
                    <option value="">-- اختر المخزن المصدر --</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bg-green-50 p-4 rounded-lg border border-green-100">
                <label class="block text-sm font-bold text-green-700 mb-2">إلى المخزن (الوجهة) *</label>
                <select name="to_warehouse_id" id="toWarehouse" required
                    class="w-full border border-green-300 rounded p-2.5 focus:ring-2 focus:ring-green-400 outline-none">
                    <option value="">-- اختر المخزن الوجهة --</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?php echo $w['id']; ?>"><?php echo htmlspecialchars($w['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- جدول المواد -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-700"><i class="fas fa-boxes mr-2"></i>المواد المراد تحويلها</h3>
                <button type="button" onclick="addTransferRow()"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm">
                    <i class="fas fa-plus ml-1"></i> إضافة مادة
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="transferTable">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 text-sm">
                            <th class="p-3 text-right border">المادة</th>
                            <th class="p-3 text-center border w-32">الكمية</th>
                            <th class="p-3 text-center border w-40">الرصيد المتاح</th>
                            <th class="p-3 text-center border w-20">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="transferTableBody">
                        <tr class="transfer-row">
                            <td class="p-2 border">
                                <select name="products[0][product_id]" required onchange="checkStock(this)"
                                    class="product-select w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-blue-400 outline-none">
                                    <option value="">-- اختر المادة --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?php echo $p['id']; ?>"
                                            data-barcode="<?php echo htmlspecialchars($p['barcode'] ?? ''); ?>">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                            <?php echo !empty($p['barcode']) ? ' | ' . $p['barcode'] : ''; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="p-2 border">
                                <input type="number" step="0.01" min="0.01" name="products[0][quantity]" required
                                    placeholder="0.00"
                                    class="quantity-input w-full border border-gray-300 rounded p-2 text-center font-bold"
                                    onchange="validateQuantity(this)">
                            </td>
                            <td class="p-2 border text-center">
                                <span class="available-stock text-gray-500">-</span>
                            </td>
                            <td class="p-2 border text-center">
                                <button type="button" onclick="removeTransferRow(this)"
                                    class="text-red-500 hover:text-red-700 p-2">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="noItemsError" class="hidden mt-2 text-red-600 text-sm">
                <i class="fas fa-exclamation-circle ml-1"></i> يجب إضافة مادة واحدة على الأقل
            </div>
        </div>

        <!-- ملاحظات -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">ملاحظات / سبب التحويل</label>
            <textarea name="notes" rows="2"
                class="w-full border border-gray-300 rounded-lg p-2.5 focus:ring-2 focus:ring-blue-400 outline-none"></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="<?php echo APP_URL; ?>/reports/transfers"
                class="bg-gray-200 text-gray-700 px-6 py-2.5 rounded hover:bg-gray-300 transition">إلغاء</a>
            <button type="submit"
                class="bg-blue-600 text-white px-8 py-2.5 rounded hover:bg-blue-700 transition font-bold">
                <i class="fas fa-check ml-1"></i> تنفيذ التحويل
            </button>
        </div>
    </form>
</div>

<script>
    let rowIndex = 1;
    const products = <?php echo json_encode($products); ?>;
    const warehouses = <?php echo json_encode(array_column($warehouses, 'id')); ?>;

    function addTransferRow() {
        const tbody = document.getElementById('transferTableBody');
        const newRow = document.createElement('tr');
        newRow.className = 'transfer-row';
        newRow.innerHTML = `
        <td class="p-2 border">
            <select name="products[${rowIndex}][product_id]" required 
                    onchange="checkStock(this)"
                    class="product-select w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-blue-400 outline-none">
                <option value="">-- اختر المادة --</option>
                <?php foreach ($products as $p): ?>
                <option value="<?php echo $p['id']; ?>" data-barcode="<?php echo htmlspecialchars($p['barcode'] ?? ''); ?>">
                    <?php echo htmlspecialchars($p['name']); ?>
                    <?php echo !empty($p['barcode']) ? ' | ' . $p['barcode'] : ''; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="p-2 border">
            <input type="number" step="0.01" min="0.01" name="products[${rowIndex}][quantity]" required
                   placeholder="0.00"
                   class="quantity-input w-full border border-gray-300 rounded p-2 text-center font-bold"
                   onchange="validateQuantity(this)">
        </td>
        <td class="p-2 border text-center">
            <span class="available-stock text-gray-500">-</span>
        </td>
        <td class="p-2 border text-center">
            <button type="button" onclick="removeTransferRow(this)" 
                    class="text-red-500 hover:text-red-700 p-2">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
        tbody.appendChild(newRow);
        rowIndex++;
    }

    function removeTransferRow(btn) {
        const tbody = document.getElementById('transferTableBody');
        if (tbody.rows.length > 1) {
            btn.closest('tr').remove();
        } else {
            // مسح القيم بدلاً من حذف الصف الأخير
            const row = btn.closest('tr');
            row.querySelector('.product-select').value = '';
            row.querySelector('.quantity-input').value = '';
            row.querySelector('.available-stock').textContent = '-';
            row.querySelector('.available-stock').className = 'available-stock text-gray-500';
        }
    }

    function checkStock(select) {
        const row = select.closest('tr');
        const productId = select.value;
        const warehouseId = document.getElementById('fromWarehouse').value;
        const stockSpan = row.querySelector('.available-stock');

        if (!productId) {
            stockSpan.textContent = '-';
            stockSpan.className = 'available-stock text-gray-500';
            return;
        }

        if (!warehouseId) {
            stockSpan.textContent = 'اختر المخزن';
            stockSpan.className = 'available-stock text-yellow-600';
            return;
        }

        // جلب الرصيد عبر AJAX
        fetch(`<?php echo APP_URL; ?>/products/stock?product_id=${productId}&warehouse_id=${warehouseId}`)
            .then(response => response.json())
            .then(data => {
                stockSpan.textContent = data.stock || 0;
                stockSpan.className = 'available-stock font-bold ' + (data.stock > 0 ? 'text-green-600' : 'text-red-600');
            })
            .catch(() => {
                stockSpan.textContent = 'خطأ';
                stockSpan.className = 'available-stock text-red-600';
            });
    }

    function validateQuantity(input) {
        const row = input.closest('tr');
        const stockSpan = row.querySelector('.available-stock');
        const stock = parseFloat(stockSpan.textContent) || 0;
        const quantity = parseFloat(input.value) || 0;

        if (quantity > stock && stockSpan.textContent !== 'اختر المخزن' && stockSpan.textContent !== 'خطأ' && stockSpan.textContent !== '-') {
            input.classList.add('border-red-500', 'bg-red-50');
            input.classList.remove('border-green-500');
        } else if (quantity > 0) {
            input.classList.remove('border-red-500', 'bg-red-50');
            input.classList.add('border-green-500');
        }
    }

    // التحقق قبل الإرسال
    document.getElementById('transferForm').addEventListener('submit', function (e) {
        const rows = document.querySelectorAll('.transfer-row');
        let hasValidRow = false;

        rows.forEach(row => {
            const product = row.querySelector('.product-select').value;
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            if (product && quantity > 0) {
                hasValidRow = true;
            }
        });

        if (!hasValidRow) {
            e.preventDefault();
            document.getElementById('noItemsError').classList.remove('hidden');
            return false;
        }

        document.getElementById('noItemsError').classList.add('hidden');

        // التحقق من المخازن
        const fromWarehouse = document.getElementById('fromWarehouse').value;
        const toWarehouse = document.getElementById('toWarehouse').value;

        if (fromWarehouse === toWarehouse) {
            e.preventDefault();
            alert('لا يمكن التحويل إلى نفس المخزن!');
            return false;
        }

        return confirm('تأكيد تحويل المواد المحددة؟');
    });
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>