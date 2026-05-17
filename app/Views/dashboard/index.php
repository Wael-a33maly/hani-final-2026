<?php ob_start();
requireLogin();
$pageTitle = 'لوحة التحكم';
$chartData = $chartDataJson;
?>
<div class="space-y-6" dir="rtl" x-data="dashboardApp(<?= htmlspecialchars($chartData, ENT_QUOTES, 'UTF-8') ?>)">

    <!-- الهيدر -->
    <div class="bg-gradient-to-l from-blue-600 to-indigo-700 rounded-2xl p-6 text-white shadow-lg">
        <div class="flex justify-between items-center flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold">مرحباً، <?= htmlspecialchars($_SESSION['user_name'] ?? '') ?></h1>
                <p class="text-blue-200 text-sm mt-1">نظام إدارة المبيعات والأقساط المتكامل</p>
            </div>
            <div class="text-left">
                <div class="text-3xl font-bold" id="liveTime"><?= date('h:i:s A') ?></div>
                <div class="text-blue-200 text-sm"><?= $dayName ?> — <?= date('Y/m/d') ?></div>
            </div>
        </div>
    </div>

    <?php if (empty($allBranches)): ?>
    <div class="text-center py-20 bg-white rounded-2xl shadow-sm border-2 border-dashed border-gray-200">
        <i class="fas fa-building text-5xl text-gray-300 mb-4"></i>
        <p class="text-gray-500 font-bold text-lg">لا توجد فروع متاحة لعرض الإحصائيات</p>
        <p class="text-gray-400 text-sm mt-2">يرجى التواصل مع مدير النظام</p>
    </div>
    <?php else: ?>

    <!-- كروت الفروع -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($allBranches as $br):
            $s = $branchStats[$br['id']] ?? [];
        ?>
        <div @click="selectBranch(<?= $br['id'] ?>)"
             :class="selectedBranch === <?= $br['id'] ?> ? 'ring-2 ring-blue-500 shadow-lg scale-[1.02]' : 'hover:shadow-md'"
             class="bg-white rounded-xl p-4 border border-gray-100 cursor-pointer transition-all duration-200">

            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <i class="fas fa-store text-indigo-600 text-sm"></i>
                    </div>
                    <span class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($br['name']) ?></span>
                </div>
                <i class="fas fa-chevron-left text-gray-300 text-xs"
                   :class="{'rotate-90 text-blue-500': selectedBranch === <?= $br['id'] ?>}"></i>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs">
                <div class="bg-blue-50 rounded-lg p-2 text-center">
                    <span class="text-blue-600 font-bold block"><?= number_format($s['sales_total'] ?? 0) ?></span>
                    <span class="text-gray-500">مبيعات</span>
                </div>
                <div class="bg-green-50 rounded-lg p-2 text-center">
                    <span class="text-green-600 font-bold block"><?= number_format($s['collected_total'] ?? 0) ?></span>
                    <span class="text-gray-500">تحصيلات</span>
                </div>
                <div class="bg-orange-50 rounded-lg p-2 text-center">
                    <span class="text-orange-600 font-bold block"><?= number_format($s['remaining_to_collect'] ?? 0) ?></span>
                    <span class="text-gray-500">متبقي</span>
                </div>
                <div class="bg-red-50 rounded-lg p-2 text-center">
                    <span class="text-red-600 font-bold block"><?= $s['overdue_count'] ?? 0 ?></span>
                    <span class="text-gray-500">متأخر</span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- تفاصيل الفرع المختار -->
    <div x-show="selectedBranch" x-cloak x-transition>
        <?php foreach ($allBranches as $br):
            $s = $branchStats[$br['id']] ?? [];
        ?>
        <div x-show="selectedBranch === <?= $br['id'] ?>">
            <!-- رأس الفرع -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="bg-gradient-to-l from-gray-50 to-white px-6 py-4 border-b flex justify-between items-center">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                            <i class="fas fa-store text-indigo-600"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($br['name']) ?></h2>
                            <span class="text-xs text-gray-400"><?= date('Y/m/d') ?></span>
                        </div>
                    </div>
                    <span class="bg-green-50 text-green-700 px-3 py-1 rounded-full text-xs font-bold">نشط</span>
                </div>

                <!-- كروت الإحصائيات -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3 p-5">
                    <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl p-4 border border-blue-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-blue-600 text-xs font-bold">إجمالي المبيعات</span>
                            <i class="fas fa-chart-line text-blue-400"></i>
                        </div>
                        <div class="text-xl font-bold text-gray-800"><?= number_format($s['sales_total'] ?? 0, 2) ?></div>
                        <div class="text-xs text-gray-500 mt-1"><?= $s['sales_count'] ?? 0 ?> فاتورة</div>
                    </div>
                    <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-4 border border-green-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-green-600 text-xs font-bold">إجمالي المشتريات</span>
                            <i class="fas fa-shopping-cart text-green-400"></i>
                        </div>
                        <div class="text-xl font-bold text-gray-800"><?= number_format($s['purchases_total'] ?? 0, 2) ?></div>
                        <div class="text-xs text-gray-500 mt-1"><?= $s['purchases_count'] ?? 0 ?> فاتورة</div>
                    </div>
                    <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl p-4 border border-purple-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-purple-600 text-xs font-bold">إجمالي الأقساط</span>
                            <i class="fas fa-calendar-alt text-purple-400"></i>
                        </div>
                        <div class="text-xl font-bold text-gray-800"><?= number_format($s['installments_total'] ?? 0, 2) ?></div>
                        <div class="text-xs text-gray-500 mt-1"><?= $s['installments_count'] ?? 0 ?> قسط</div>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-50 to-white rounded-xl p-4 border border-emerald-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-emerald-600 text-xs font-bold">إجمالي التحصيلات</span>
                            <i class="fas fa-hand-holding-usd text-emerald-400"></i>
                        </div>
                        <div class="text-xl font-bold text-emerald-700"><?= number_format($s['collected_total'] ?? 0, 2) ?></div>
                        <div class="text-xs text-gray-500 mt-1">مدفوعة</div>
                    </div>
                    <div class="bg-gradient-to-br from-orange-50 to-white rounded-xl p-4 border border-orange-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-orange-600 text-xs font-bold">المتبقي للتحصيل</span>
                            <i class="fas fa-clock text-orange-400"></i>
                        </div>
                        <div class="text-xl font-bold text-orange-700"><?= number_format($s['remaining_to_collect'] ?? 0, 2) ?></div>
                        <div class="text-xs text-gray-500 mt-1">غير محصلة</div>
                    </div>
                    <div class="bg-gradient-to-br from-red-50 to-white rounded-xl p-4 border border-red-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-red-600 text-xs font-bold">متأخرات</span>
                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                        </div>
                        <div class="text-xl font-bold text-red-700"><?= number_format($s['overdue_total'] ?? 0, 2) ?></div>
                        <div class="text-xs text-red-500 mt-1"><?= $s['overdue_count'] ?? 0 ?> قسط متأخر</div>
                    </div>
                    <div class="bg-gradient-to-br from-cyan-50 to-white rounded-xl p-4 border border-cyan-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-cyan-600 text-xs font-bold">العملاء</span>
                            <i class="fas fa-users text-cyan-400"></i>
                        </div>
                        <div class="text-xl font-bold text-gray-800"><?= $s['customers_count'] ?? 0 ?></div>
                        <div class="text-xs text-gray-500 mt-1">عميل</div>
                    </div>
                    <div class="bg-gradient-to-br from-gray-50 to-white rounded-xl p-4 border border-gray-200">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-gray-600 text-xs font-bold">المستخدمون</span>
                            <i class="fas fa-user-cog text-gray-400"></i>
                        </div>
                        <div class="text-xl font-bold text-gray-800"><?= $s['users_count'] ?? 0 ?></div>
                        <div class="text-xs text-gray-500 mt-1">موظف</div>
                    </div>
                    <div class="bg-gradient-to-br from-rose-50 to-white rounded-xl p-4 border border-rose-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-rose-600 text-xs font-bold">المتبقي للموردين</span>
                            <i class="fas fa-truck text-rose-400"></i>
                        </div>
                        <div class="text-xl font-bold text-rose-700"><?= number_format($s['remaining_to_suppliers'] ?? 0, 2) ?></div>
                        <div class="text-xs text-gray-500 mt-1">غير مدفوعة</div>
                    </div>
                    <div class="bg-gradient-to-br from-teal-50 to-white rounded-xl p-4 border border-teal-100">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-teal-600 text-xs font-bold">الأقساط المدفوعة</span>
                            <i class="fas fa-check-circle text-teal-400"></i>
                        </div>
                        <div class="text-xl font-bold text-teal-700"><?= $s['paid_installments'] ?? 0 ?></div>
                        <div class="text-xs text-gray-500 mt-1">قسط مدفوع</div>
                    </div>
                </div>

                <!-- الرسوم البيانية -->
                <div class="border-t border-gray-100 p-5">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- رسم المبيعات vs المشتريات -->
                        <div class="bg-white rounded-xl border border-gray-100 p-4">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <i class="fas fa-chart-line text-blue-500"></i> مقارنة شهرية: المبيعات والمشتريات
                            </h3>
                            <canvas :id="'sp-' + <?= $br['id'] ?>" class="max-h-64"></canvas>
                        </div>
                        <!-- رسم التحصيلات -->
                        <div class="bg-white rounded-xl border border-gray-100 p-4">
                            <h3 class="text-sm font-bold text-gray-700 mb-4 flex items-center gap-2">
                                <i class="fas fa-chart-bar text-emerald-500"></i> التحصيلات الشهرية
                            </h3>
                            <canvas :id="'col-' + <?= $br['id'] ?>" class="max-h-64"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ملخص عام (للأدمن فقط) -->
    <?php if ($roleName === 'admin' && count($allBranches) > 1): ?>
    <div class="bg-gradient-to-br from-indigo-900 to-indigo-800 rounded-2xl p-6 shadow-lg text-white">
        <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
            <i class="fas fa-chart-pie"></i> ملخص عام — جميع الفروع
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div><div class="text-indigo-300 text-xs">إجمالي المبيعات</div><div class="text-xl font-bold"><?= number_format($grandTotal['sales_total'], 2) ?></div></div>
            <div><div class="text-indigo-300 text-xs">إجمالي المشتريات</div><div class="text-xl font-bold"><?= number_format($grandTotal['purchases_total'], 2) ?></div></div>
            <div><div class="text-indigo-300 text-xs">إجمالي الأقساط</div><div class="text-xl font-bold"><?= number_format($grandTotal['installments_total'], 2) ?></div></div>
            <div><div class="text-indigo-300 text-xs">إجمالي التحصيلات</div><div class="text-xl font-bold text-green-300"><?= number_format($grandTotal['collected_total'], 2) ?></div></div>
            <div><div class="text-indigo-300 text-xs">المتبقي للتحصيل</div><div class="text-xl font-bold text-yellow-300"><?= number_format($grandTotal['remaining_to_collect'], 2) ?></div></div>
            <div><div class="text-indigo-300 text-xs">المتأخرات</div><div class="text-xl font-bold text-red-300"><?= number_format($grandTotal['overdue_total'], 2) ?> (<?= $grandTotal['overdue_count'] ?>)</div></div>
            <div><div class="text-indigo-300 text-xs">العملاء</div><div class="text-xl font-bold"><?= $grandTotal['customers_count'] ?></div></div>
            <div><div class="text-indigo-300 text-xs">المستخدمون</div><div class="text-xl font-bold"><?= $grandTotal['users_count'] ?></div></div>
            <div><div class="text-indigo-300 text-xs">المواد</div><div class="text-xl font-bold"><?= $productsCount ?></div></div>
            <div><div class="text-indigo-300 text-xs">الفروع</div><div class="text-xl font-bold"><?= count($allBranches) ?></div></div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script>
(function() {
    const el = document.getElementById('liveTime');
    if (el) { function u() { const n=new Date(); el.textContent=String(n.getHours()).padStart(2,'0')+':'+String(n.getMinutes()).padStart(2,'0')+':'+String(n.getSeconds()).padStart(2,'0'); } u(); setInterval(u,1000); }
})();

function dashboardApp(chartData) {
    return {
        selectedBranch: null,
        charts: {},
        selectBranch(id) {
            this.selectedBranch = id;
            this.$nextTick(() => {
                this.initChart('salesPurchases', id);
                this.initChart('collections', id);
            });
        },
        initChart(type, branchId) {
            const data = chartData[branchId];
            if (!data) return;
            const canvasId = type === 'salesPurchases' ? 'sp-' + branchId : 'col-' + branchId;
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            const key = type + '-' + branchId;
            if (this.charts[key]) { this.charts[key].destroy(); }
            const ctx = canvas.getContext('2d');
            if (type === 'salesPurchases') {
                this.charts[key] = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels.map(m => { const p=m.split('-'); const months=['يناير','فبراير','مارس','إبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']; return months[parseInt(p[1])-1]; }),
                        datasets: [
                            { label: 'المبيعات', data: data.salesData, borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)', fill: true, tension: 0.3, pointRadius: 3 },
                            { label: 'المشتريات', data: data.purchasesData, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.1)', fill: true, tension: 0.3, pointRadius: 3 }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'top', labels: { font: { family: 'Tahoma' } } } }, scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } } } }
                });
            } else {
                this.charts[key] = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels.map(m => { const p=m.split('-'); const months=['يناير','فبراير','مارس','إبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']; return months[parseInt(p[1])-1]; }),
                        datasets: [{
                            label: 'التحصيلات',
                            data: data.collectionsData,
                            backgroundColor: data.collectionsData.map((_,i) => ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316','#6366f1','#84cc16','#06b6d4','#d946ef'][i % 12]),
                            borderRadius: 4
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString() } } } }
                });
            }
        }
    }
}
</script>

<?php $content = ob_get_clean(); require_once __DIR__ . '/../layouts/main.php'; ?>
