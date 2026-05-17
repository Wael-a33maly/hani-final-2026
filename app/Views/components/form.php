<?php
/**
 * Component موحد لنماذج الإدخال
 * 
 * الاستخدام:
 * <div class="form-group">
 *   <?php include __DIR__ . '/components/form-field.php'; ?>
 * </div>
 *
 * المتغيرات المتوقعة:
 * $fields: array من الحقول
 *   - name: اسم الحقل
 *   - label: التسمية
 *   - type: text, email, number, date, password, select, textarea, checkbox
 *   - required: true/false
 *   - value: القيمة الحالية
 *   - options: array للـ select
 *   - placeholder: نص تلميحي
 *   - help: نص مساعد
 *   - icon: أيقونة (اختياري)
 *   - rows: عدد الأسطر للـ textarea
 */
?>

<style>
.form-group {
    margin-bottom: 1rem;
    position: relative;
}
.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    transition: all 0.2s;
}
.form-label.required::after {
    content: " *";
    color: #ef4444;
}
.form-field {
    width: 100%;
    padding: 0.625rem 0.875rem;
    font-size: 0.875rem;
    border: 1.5px solid #e5e7eb;
    border-radius: 0.75rem;
    background: white;
    transition: all 0.2s;
    outline: none;
}
.form-field:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
.form-field.error {
    border-color: #ef4444;
}
.form-field.error:focus {
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}
.form-field.success {
    border-color: #10b981;
}
.form-error {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}
.form-help {
    color: #6b7280;
    font-size: 0.7rem;
    margin-top: 0.25rem;
}
/* تحسين الـ Select */
select.form-field {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: left 0.75rem center;
    background-repeat: no-repeat;
    background-size: 1.25rem;
}
/* Checkbox مخصص */
.checkbox-wrapper {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.checkbox-wrapper input[type="checkbox"] {
    width: 1.2rem;
    height: 1.2rem;
    border-radius: 0.25rem;
    border: 1.5px solid #e5e7eb;
    cursor: pointer;
}
/* تحريك التسمية عند التركيز (اختياري) */
.form-field:not(:placeholder-shown) ~ .form-label,
.form-field:focus ~ .form-label {
    transform: translateY(-1.4rem) scale(0.85);
    background: white;
    padding: 0 0.25rem;
}
</style>

<?php foreach ($fields as $field): 
    $name = $field['name'];
    $label = $field['label'];
    $type = $field['type'] ?? 'text';
    $required = $field['required'] ?? false;
    $value = $field['value'] ?? '';
    $options = $field['options'] ?? [];
    $placeholder = $field['placeholder'] ?? '';
    $help = $field['help'] ?? '';
    $icon = $field['icon'] ?? null;
    $rows = $field['rows'] ?? 3;
    $error = $field['error'] ?? '';
    $success = $field['success'] ?? false;
?>
<div class="form-group">
    <label class="form-label <?= $required ? 'required' : '' ?>"><?= htmlspecialchars($label) ?></label>
    <div class="relative">
        <?php if ($icon): ?>
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <i class="<?= htmlspecialchars($icon) ?> text-gray-400"></i>
        </div>
        <?php endif; ?>
        
        <?php if ($type === 'textarea'): ?>
            <textarea name="<?= $name ?>" 
                      class="form-field <?= $error ? 'error' : ($success ? 'success' : '') ?>"
                      placeholder="<?= htmlspecialchars($placeholder) ?>"
                      rows="<?= $rows ?>"
                      <?= $required ? 'required' : '' ?>><?= htmlspecialchars($value) ?></textarea>
                      
        <?php elseif ($type === 'select'): ?>
            <select name="<?= $name ?>" 
                    class="form-field <?= $error ? 'error' : ($success ? 'success' : '') ?>"
                    <?= $required ? 'required' : '' ?>>
                <option value="">اختر <?= htmlspecialchars($label) ?></option>
                <?php foreach ($options as $optValue => $optLabel): ?>
                <option value="<?= htmlspecialchars($optValue) ?>" <?= $value == $optValue ? 'selected' : '' ?>><?= htmlspecialchars($optLabel) ?></option>
                <?php endforeach; ?>
            </select>
            
        <?php elseif ($type === 'checkbox'): ?>
            <div class="checkbox-wrapper">
                <input type="checkbox" name="<?= $name ?>" value="1" <?= $value ? 'checked' : '' ?> class="form-checkbox">
                <span class="text-sm text-gray-700"><?= htmlspecialchars($label) ?></span>
            </div>
            
        <?php else: ?>
            <input type="<?= $type ?>" 
                   name="<?= $name ?>" 
                   value="<?= htmlspecialchars($value) ?>"
                   class="form-field <?= $error ? 'error' : ($success ? 'success' : '') ?>"
                   placeholder="<?= htmlspecialchars($placeholder) ?>"
                   <?= $required ? 'required' : '' ?>>
        <?php endif; ?>
        
        <?php if ($success && !$error): ?>
        <div class="absolute left-3 top-1/2 -translate-y-1/2">
            <i class="fas fa-check-circle text-green-500"></i>
        </div>
        <?php endif; ?>
    </div>
    
    <?php if ($error): ?>
    <div class="form-error">
        <i class="fas fa-exclamation-circle text-xs"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($help && !$error): ?>
    <div class="form-help">
        <i class="fas fa-info-circle text-xs"></i>
        <?= htmlspecialchars($help) ?>
    </div>
    <?php endif; ?>
</div>
<?php endforeach; ?>
