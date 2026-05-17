<?php requireLogin(); $pageTitle = 'عهدة المندوبين'; ob_start(); ?>
<div class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-6">
        <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-user-tie text-blue-600"></i> عهدة المندوبين
        </h2>
        <div class="flex flex-wrap gap-2">
            <a href="<?= APP_URL ?>/salesrep/assign" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition flex items-center gap-2 text-sm">
                <i class="fas fa-plus"></i> إسناد بضاعة
            </a>
            <a href="<?= APP_URL ?>/salesrep/full-report" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition flex items-center gap-2 text-sm">
                <i class="fas fa-chart-line"></i> تقرير العهدة الكامل
            </a>
        </div>
    </div>

    <div class="bg-gray-50 p-4 rounded-xl border border-gray-100 mb-6">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs text-gray-500 mb-1">اختر المندوب</label>
                <select name="sales_rep_id" class="w-full border border-gray-300 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-400 outline-none">
                    <option value="">-- اختر المندوب --</option>
                    <?php foreach($users as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= (isset($_GET['sales_rep_id']) && $_GET['sales_rep_id'] == $u['id']) ? 'selected' : '' ?>><?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition font-bold text-sm">
                <i class="fas fa-search ml-1"></i> عرض العهدة
            </button>
        </form>
    </div>
    
    <?php if ($selectedRep): ?>
    <div class="mb-4 border-r-4 border-blue-600 pr-4">
        <h3 class="font-bold text-lg text-blue-900">عهدة المندوب: <?= htmlspecialchars($selectedRep['full_name']) ?></h3>
        <p class="text-sm text-gray-500">جرد اللحظة الحالية للمواد المسندة</p>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm text-right">
            <thead class="bg-gray-100 text-gray-600">
                <tr>
                    <th class="p-3">المادة</th>
                    <th class="p-3">الباركود</th>
                    <th class="p-3">إجمالي المسند</th>
                    <th class="p-3">المباع</th>
                    <th class="p-3">المسترد</th>
                    <th class="p-3">المتبقي (العهدة)</th>
                    <th class="p-3 text-center">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach($stock as $item): ?>
                <tr class="hover:bg-gray-50 transition">
                    <td class="p-3 font-bold text-gray-800"><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="p-3 text-gray-400 font-mono text-xs"><?= $item['barcode'] ?></td>
                    <td class="p-3 font-bold"><?= number_format($item['quantity'], 2) ?></td>
                    <td class="p-3 text-green-600"><?= number_format($item['sold'], 2) ?></td>
                    <td class="p-3 text-orange-600"><?= number_format($item['returned'], 2) ?></td>
                    <td class="p-3">
                        <span class="px-2 py-1 rounded font-bold <?= $item['current_stock'] > 0 ? 'bg-blue-100 text-blue-700' : 'bg-red-100 text-red-700' ?>">
                            <?= number_format($item['current_stock'], 2) ?>
                        </span>
                    </td>
                    <td class="p-3 text-center flex justify-center gap-2">
                        <button type="button" onclick="showSaleModal(<?= $item['id'] ?>)" 
                                class="bg-green-50 text-green-600 px-3 py-1 rounded hover:bg-green-100 transition text-xs flex items-center gap-1"
                                <?= $item['current_stock'] <= 0 ? 'disabled' : '' ?>>
                            <i class="fas fa-shopping-cart"></i> تسجيل بيع
                        </button>
                        <a href="<?= APP_URL ?>/salesrep/return-form/<?= $item['id'] ?>" 
                           class="bg-orange-50 text-orange-600 px-3 py-1 rounded hover:bg-orange-100 transition text-xs flex items-center gap-1">
                            <i class="fas fa-undo-alt"></i> استرداد
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($stock)): ?>
                <tr><td colspan="7" class="p-8 text-center text-gray-400 italic">لا توجد مواد مسندة لهذا المندوب حالياً</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200">
        <i class="fas fa-user-tie text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 font-bold">يرجى اختيار مندوب لعرض عهدته</p>
    </div>
    <?php endif; ?>
</div>

<!-- Modal تسجيل بيع -->
<div id="saleModal" class="fixed inset-0 bg-gray-900 bg-opacity-60 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden transform transition-all scale-95" id="modalContainer">
        <div class="bg-green-600 p-4 text-white flex justify-between items-center">
            <h3 class="text-lg font-bold flex items-center gap-2"><i class="fas fa-cart-plus"></i> تسجيل بيع من العهدة</h3>
            <button onclick="closeModal()" class="text-white hover:bg-green-700 p-1 rounded-full"><i class="fas fa-times"></i></button>
        </div>
        
        <form id="saleForm" method="POST" action="<?= APP_URL ?>/salesrep/record-sale" class="p-6 space-y-4">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            <input type="hidden" name="rep_stock_id" id="repStockId">
            
            <div>
                <label class="block text-xs text-gray-400 mb-1">المادة</label>
                <input type="text" id="productName" disabled class="w-full border-b border-gray-200 py-1 font-bold text-gray-800 bg-transparent outline-none">
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs text-gray-400 mb-1 font-bold">الكمية المتاحة</label>
                    <input type="text" id="availableQty" disabled class="w-full border-b border-gray-200 py-1 text-blue-600 font-bold bg-transparent outline-none">
                </div>
                <div>
                    <label class="block text-xs text-gray-600 mb-1 font-bold text-green-600">الكمية المباعة *</label>
                    <input type="number" name="quantity" id="saleQty" step="0.01" required class="w-full border-b-2 border-green-500 py-1 font-bold focus:bg-green-50 transition outline-none">
                </div>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">العميل (اختياري)</label>
                <select name="customer_id" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
                    <option value="">-- بيع مباشر بدون عميل --</option>
                    <?php $db = getDB(); $customers = $db->query("SELECT id, name FROM customers ORDER BY name")->fetchAll(); ?>
                    <?php foreach($customers as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">نوع السعر</label>
                    <select name="price_type" id="priceType" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 outline-none">
                        <option value="selling">بيع قطاعي</option>
                        <option value="wholesale">جملة</option>
                        <option value="special">خاص</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">سعر البيع</label>
                    <input type="text" id="price" disabled class="w-full border-b border-gray-200 py-2 font-bold text-gray-800 bg-transparent outline-none">
                </div>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="button" onclick="closeModal()" class="flex-1 bg-gray-100 text-gray-700 py-2.5 rounded-xl hover:bg-gray-200 transition font-bold">إلغاء</button>
                <button type="submit" class="flex-1 bg-green-600 text-white py-2.5 rounded-xl hover:bg-green-700 shadow-lg shadow-green-200 transition font-bold">تسجيل البيع</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentPrices = {};

function showSaleModal(stockId) {
    fetch('<?= APP_URL ?>/salesrep/product-details/' + stockId)
        .then(res => res.json())
        .then(data => {
            document.getElementById('repStockId').value = data.id;
            document.getElementById('productName').value = data.product_name;
            document.getElementById('availableQty').value = data.quantity; // Note: this is total assigned, we need current stock
            // Calculate current stock client side or fetch it
            // For now, let's just use what's returned.
            
            currentPrices = {
                selling: parseFloat(data.selling_price) || 0,
                wholesale: parseFloat(data.wholesale_price) || 0,
                special: parseFloat(data.special_price) || 0
            };
            updatePrice();
            
            const modal = document.getElementById('saleModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            setTimeout(() => {
                document.getElementById('modalContainer').classList.remove('scale-95');
                document.getElementById('modalContainer').classList.add('scale-100');
            }, 10);
        });
}

function updatePrice() {
    let type = document.getElementById('priceType').value;
    let price = currentPrices[type] || 0;
    document.getElementById('price').value = price.toLocaleString(undefined, {minimumFractionDigits: 2}) + ' ج.م';
}

document.getElementById('priceType').addEventListener('change', updatePrice);

function closeModal() {
    document.getElementById('modalContainer').classList.remove('scale-100');
    document.getElementById('modalContainer').classList.add('scale-95');
    setTimeout(() => {
        document.getElementById('saleModal').classList.remove('flex');
        document.getElementById('saleModal').classList.add('hidden');
    }, 200);
}

document.getElementById('saleForm').onsubmit = function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message);
        }
    });
};
</script>
<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
