<?php
/**
 * Component موحد للجداول
 * 
 * الاستخدام:
 * <?php include __DIR__ . '/components/table.php'; ?>
 *
 * المتغيرات المطلوبة:
 * $columns: array ['field' => 'الاسم', ...]
 * $rows: array البيانات
 * $actions: array من الإجراءات لكل صف (اختياري)
 * $emptyMessage: نص عند عدم وجود بيانات
 */
?>
<div class="overflow-x-auto rounded-xl shadow-sm border border-gray-200 bg-white">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <?php foreach ($columns as $field => $label): ?>
                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                    <?= htmlspecialchars($label) ?>
                </th>
                <?php endforeach; ?>
                <?php if (!empty($actions)): ?>
                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">الإجراءات</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-100">
            <?php if (empty($rows)): ?>
            <tr>
                <td colspan="<?= count($columns) + (empty($actions) ? 0 : 1) ?>" class="px-6 py-12 text-center text-gray-500">
                    <i class="fas fa-inbox text-3xl mb-2 block"></i>
                    <?= htmlspecialchars($emptyMessage ?? 'لا توجد بيانات') ?>
                </td>
            </tr>
            <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr class="hover:bg-blue-50/30 transition-colors duration-150">
                    <?php foreach ($columns as $field => $label): ?>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" data-label="<?= htmlspecialchars($label) ?>">
                        <?php 
                        $value = $row[$field] ?? '';
                        if (is_numeric($value) && strpos($field, 'price') !== false) {
                            echo number_format($value, 2) . ' ج.م';
                        } elseif (strpos($field, 'status') !== false) {
                            $statusClass = '';
                            if ($value == 'paid' || $value == 'مدفوع') $statusClass = 'badge-success';
                            elseif ($value == 'pending' || $value == 'معلق') $statusClass = 'badge-warning';
                            elseif ($value == 'overdue' || $value == 'متأخر') $statusClass = 'badge-danger';
                            else $statusClass = 'badge-info';
                            echo "<span class='badge $statusClass'>" . htmlspecialchars($value) . "</span>";
                        } else {
                            echo htmlspecialchars($value);
                        }
                        ?>
                    </td>
                    <?php endforeach; ?>
                    <?php if (!empty($actions)): ?>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex items-center gap-2">
                            <?php foreach ($actions as $action): ?>
                                <?php if ($action['type'] == 'edit'): ?>
                                <a href="<?= str_replace('{id}', $row['id'], $action['url']) ?>" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="تعديل">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php elseif ($action['type'] == 'view'): ?>
                                <a href="<?= str_replace('{id}', $row['id'], $action['url']) ?>" class="p-2 text-green-600 hover:bg-green-50 rounded-lg transition" title="عرض">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php elseif ($action['type'] == 'delete'): ?>
                                <button type="button" onclick="confirmDelete('<?= str_replace('{id}', $row['id'], $action['url']) ?>', '<?= htmlspecialchars($row['name'] ?? $row['id']) ?>')" 
                                        class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition" title="حذف">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <?php elseif ($action['type'] == 'custom'): ?>
                                <a href="<?= str_replace('{id}', $row['id'], $action['url']) ?>" class="p-2 text-<?= $action['color'] ?? 'gray' ?>-600 hover:bg-<?= $action['color'] ?? 'gray' ?>-50 rounded-lg transition">
                                    <i class="fas fa-<?= $action['icon'] ?? 'link' ?>"></i>
                                </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function confirmDelete(url, name) {
    if (confirm(`هل أنت متأكد من حذف "${name}"؟ لا يمكن التراجع عن هذا الإجراء.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = url;
        const csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        csrf.value = '<?= generateCSRFToken() ?>';
        form.appendChild(csrf);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
