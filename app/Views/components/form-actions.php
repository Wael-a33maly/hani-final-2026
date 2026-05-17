<?php
/**
 * أزرار النموذج
 */
?>
<div class="flex flex-wrap justify-end gap-3 mt-6">
    <button type="button" onclick="window.history.back()" 
            class="px-6 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition shadow-sm">
        <i class="fas fa-times ml-1"></i> إلغاء
    </button>
    <button type="submit" 
            x-data="{ loading: false }"
            @click="loading = true"
            :disabled="loading"
            class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-blue-700 rounded-xl hover:from-blue-700 hover:to-blue-800 transition shadow-md flex items-center gap-2">
        <i class="fas fa-save"></i>
        <span x-show="!loading">حفظ</span>
        <span x-show="loading" class="flex items-center gap-1">
            <i class="fas fa-spinner fa-spin"></i> جاري الحفظ...
        </span>
    </button>
</div>
