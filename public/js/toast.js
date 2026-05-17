// نظام Toast موحد
class Toast {
    constructor() {
        this.container = null;
        this.toasts = [];
        this.init();
    }
    
    init() {
        if (document.querySelector('.toast-container')) return;
        this.container = document.createElement('div');
        this.container.className = 'toast-container fixed top-4 left-4 z-50 space-y-2';
        this.container.style.direction = 'rtl';
        document.body.appendChild(this.container);
    }
    
    show(message, type = 'info', duration = 4000) {
        const toastId = Date.now();
        const toast = document.createElement('div');
        toast.className = `toast-message animate-slide-in-right bg-white rounded-xl shadow-xl border-r-4 overflow-hidden w-80 transition-all`;
        
        // لون الحدود حسب النوع
        const borderColors = {
            success: '#10b981',
            error: '#ef4444',
            warning: '#f59e0b',
            info: '#3b82f6'
        };
        toast.style.borderRightColor = borderColors[type] || borderColors.info;
        
        // أيقونة
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };
        
        toast.innerHTML = `
            <div class="flex items-start p-3 gap-3">
                <div class="flex-shrink-0">
                    <i class="fas ${icons[type]} text-xl" style="color: ${borderColors[type]}"></i>
                </div>
                <div class="flex-1">
                    <p class="text-sm text-gray-800">${message}</p>
                    <div class="progress-bar mt-2 h-1 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-${type === 'error' ? 'red' : (type === 'warning' ? 'yellow' : 'blue')}-500" style="width: 100%; animation: shrink ${duration}ms linear forwards;"></div>
                    </div>
                </div>
                <button class="flex-shrink-0 text-gray-400 hover:text-gray-600" onclick="this.closest('.toast-message').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        this.container.appendChild(toast);
        
        // إزالة بعد المدة
        setTimeout(() => {
            toast.classList.add('animate-fade-out');
            setTimeout(() => toast.remove(), 300);
        }, duration);
        
        return toast;
    }
    
    success(message, duration = 4000) {
        return this.show(message, 'success', duration);
    }
    
    error(message, duration = 5000) {
        return this.show(message, 'error', duration);
    }
    
    warning(message, duration = 4000) {
        return this.show(message, 'warning', duration);
    }
    
    info(message, duration = 3000) {
        return this.show(message, 'info', duration);
    }
}

// استدعاء عالمي
window.Toast = new Toast();

// إضافة الأنيميشن المطلوبة
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(20px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes fadeOut {
        to { opacity: 0; transform: translateX(20px); }
    }
    @keyframes shrink {
        from { width: 100%; }
        to { width: 0%; }
    }
    .animate-slide-in-right {
        animation: slideInRight 0.3s ease forwards;
    }
    .animate-fade-out {
        animation: fadeOut 0.3s ease forwards;
    }
`;
document.head.appendChild(style);
