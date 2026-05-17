<?php
/**
 * Modal Component موحد
 * 
 * الاستخدام:
 * <?php include __DIR__ . '/components/modal.php'; ?>
 *
 * المتغيرات:
 * $modalId: معرف فريد (مثال: 'deleteModal')
 * $modalTitle: عنوان المودال
 * $modalContent: محتوى HTML للمودال (سيكون داخل body)
 * $modalFooter: محتوى أزرار المودال (اختياري)
 * $modalSize: 'sm', 'md', 'lg', 'xl' (افتراضي md)
 * $showCloseButton: إظهار زر إغلاق في الهيدر (افتراضي true)
 */
$modalId = $modalId ?? 'globalModal';
$modalTitle = $modalTitle ?? 'نافذة';
$modalContent = $modalContent ?? '';
$modalSize = $modalSize ?? 'md';
$showCloseButton = $showCloseButton ?? true;

$sizeClasses = [
    'sm' => 'max-w-md',
    'md' => 'max-w-lg',
    'lg' => 'max-w-2xl',
    'xl' => 'max-w-4xl',
];
?>

<div x-data="modal('<?= $modalId ?>')" 
     x-show="open" 
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
     style="display: none;">
    
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="scale-95 opacity-0"
         x-transition:enter-end="scale-100 opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="scale-100 opacity-100"
         x-transition:leave-end="scale-95 opacity-0"
         @click.away="close()"
         class="bg-white rounded-2xl shadow-xl w-full <?= $sizeClasses[$modalSize] ?? 'max-w-lg' ?> max-h-[90vh] overflow-hidden">
        
        <!-- Header -->
        <div class="flex justify-between items-center p-5 border-b border-gray-100">
            <h3 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($modalTitle) ?></h3>
            <?php if ($showCloseButton): ?>
            <button @click="close()" class="text-gray-400 hover:text-gray-600 transition w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100">
                <i class="fas fa-times"></i>
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Body -->
        <div class="p-5 overflow-y-auto max-h-[60vh]">
            <?= $modalContent ?>
        </div>
        
        <!-- Footer (if any) -->
        <?php if (!empty($modalFooter)): ?>
        <div class="flex justify-end gap-3 p-5 border-t border-gray-100 bg-gray-50">
            <?= $modalFooter ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function modal(modalId) {
    return {
        open: false,
        openModal() {
            this.open = true;
            document.body.style.overflow = 'hidden';
        },
        close() {
            this.open = false;
            document.body.style.overflow = '';
        },
        init() {
            // تسجيل المودال في نطاق عام ليتم فتحه من أي مكان
            window[`openModal_${modalId}`] = () => this.openModal();
            window[`closeModal_${modalId}`] = () => this.close();
        }
    }
}
</script>
