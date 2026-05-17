<?php requireRole('admin');
$pageTitle = 'إسناد بضاعة لمندوب';
ob_start();
?>
<div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-sm p-6">
    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3 border-b pb-4">
        <i class="fas fa-dolly text-green-600"></i> إسناد بضاعة لعهدة المندوب
    </h2>

    <form method="POST" action="<?= APP_URL ?>/salesrep/assign" id="assignForm">
        <?= csrf_field() ?>

        <!-- المندوب والمخزن -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">المندوب المستلم *</label>
                <select name="sales_rep_id" id="salesRepId" required
                    class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-green-400 outline-none">
                    <option value="">-- اختر المندوب من القائمة --</option>
                    <?php foreach ($salesReps as $rep): ?>
                        <option value="<?= $rep['id'] ?>"><?= htmlspecialchars($rep['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">من المخزن *</label>
                <select name="warehouse_id" id="warehouseId" required
                    class="w-full border border-gray-300 rounded-xl p-3 focus:ring-2 focus:ring-green-400 outline-none">
                    <option value="">-- اختر مخزن المصدر --</option>
                    <?php foreach ($warehouses as $wh): ?>
                        <option value="<?= $wh['id'] ?>"><?= htmlspecialchars($wh['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- جدول المواد -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-700"><i class="fas fa-boxes mr-2"></i>المواد المراد إسنادها</h3>
                <button type="button" onclick="addAssignRow()"
                    class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition text-sm">
                    <i class="fas fa-plus ml-1"></i> إضافة مادة
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse" id="assignTable">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700 text-sm">
                            <th class="p-3 text-right border">المادة</th>
                            <th class="p-3 text-center border w-32">الكمية</th>
                            <th class="p-3 text-center border w-40">الرصيد المتاح</th>
                            <th class="p-3 text-center border w-20">حذف</th>
                        </tr>
                    </thead>
                    <tbody id="assignTableBody">
                        <tr class="assign-row">
                            <td class="p-2 border">
                                <select name="products[0][product_id]" required onchange="checkAssignStock(this)"
                                    class="product-select w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-green-400 outline-none">
                                    <option value="">-- اختر المادة --</option>
                                    <?php foreach ($products as $prod): ?>
                                        <option value="<?= $prod['id'] ?>"
                                            data-barcode="<?= htmlspecialchars($prod['barcode'] ?? '') ?>">
                                            <?= htmlspecialchars($prod['name']) ?>
                                            <?= !empty($prod['barcode']) ? ' | ' . $prod['barcode'] : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="p-2 border">
                                <input type="number" step="0.01" min="0.01" name="products[0][quantity]" required
                                    placeholder="0.00"
                                    class="quantity-input w-full border border-gray-300 rounded p-2 text-center font-bold"
                                    onchange="validateAssignQuantity(this)">
                            </td>
                            <td class="p-2 border text-center">
                                <span class="available-stock text-gray-500">-</span>
                            </td>
                            <td class="p-2 border text-center">
                                <button type="button" onclick="removeAssignRow(this)"
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

        <div class="pt-6 border-t flex flex-col md:flex-row gap-3">
            <a href="<?= APP_URL ?>/salesrep"
                class="flex-1 bg-gray-100 text-gray-700 py-3 rounded-xl hover:bg-gray-200 transition font-bold text-center">إلغاء</a>
            <button type="submit"
                class="flex-1 bg-green-600 text-white py-3 rounded-xl hover:bg-green-700 shadow-lg shadow-green-100 transition font-bold">
                <i class="fas fa-check ml-1"></i> تأكيد الإسناد
            </button>
        </div>
    </form>
</div>

<script>
    let rowIndex = 1;

    function addAssignRow() {
        const tbody = document.getElementById('assignTableBody');
        const newRow = document.createElement('tr');
        newRow.className = 'assign-row';
        newRow.innerHTML = `
            <td class="p-2 border">
                <select name="products[${rowIndex}][product_id]" required onchange="checkAssignStock(this)"
                    class="product-select w-full border border-gray-300 rounded p-2 focus:ring-2 focus:ring-green-400 outline-none">
                    <option value="">-- اختر المادة --</option>
                    <?php foreach ($products as $prod): ?>
                        <option value="<?= $prod['id'] ?>" data-barcode="<?= htmlspecialchars($prod['barcode'] ?? '') ?>">
                            <?= htmlspecialchars($prod['name']) ?>
                            <?= !empty($prod['barcode']) ? ' | ' . $prod['barcode'] : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td class="p-2 border">
                <input type="number" step="0.01" min="0.01" name="products[${rowIndex}][quantity]" required
                    placeholder="0.00"
                    class="quantity-input w-full border border-gray-300 rounded p-2 text-center font-bold"
                    onchange="validateAssignQuantity(this)">
            </td>
            <td class="p-2 border text-center">
                <span class="available-stock text-gray-500">-</span>
            </td>
            <td class="p-2 border text-center">
                <button type="button" onclick="removeAssignRow(this)" class="text-red-500 hover:text-red-700 p-2">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(newRow);
        rowIndex++;
    }

    function removeAssignRow(btn) {
        const tbody = document.getElementById('assignTableBody');
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

    function checkAssignStock(select) {
        const row = select.closest('tr');
        const productId = select.value;
        const warehouseId = document.getElementById('warehouseId').value;
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
        fetch(`<?= APP_URL ?>/products/stock?product_id=${productId}&warehouse_id=${warehouseId}`)
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

    function validateAssignQuantity(input) {
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
    document.getElementById('assignForm').addEventListener('submit', function (e) {
        const rows = document.querySelectorAll('.assign-row');
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

        // التحقق من اختيار المندوب والمخزن
        const salesRepId = document.getElementById('salesRepId').value;
        const warehouseId = document.getElementById('warehouseId').value;

        if (!salesRepId) {
            e.preventDefault();
            alert('يرجى اختيار المندوب');
            return false;
        }

        if (!warehouseId) {
            e.preventDefault();
            alert('يرجى اختيار المخزن');
            return false;
        }

        return confirm('تأكيد إسناد المواد المحددة للمندوب؟');
    });
</script>
<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/main.php';
?>