// تهيئة التطبيق
document.addEventListener('DOMContentLoaded', function() {
    // إضافة زر Scroll to Top
    addScrollTopButton();
    
    // إضافة Ripple Effect للأزرار
    addRippleEffect();
    
    // حفظ حالة السايدبار تلقائياً (تم في sidebar.js)
    
    // Page Transition (لن نخفي المحتوى بل نضيف تأثير fade بسيط على body)
    document.body.classList.add('page-fade-enter');
    setTimeout(() => document.body.classList.remove('page-fade-enter'), 300);
});

function addScrollTopButton() {
    let btn = document.querySelector('.scroll-top-btn');
    if (!btn) {
        btn = document.createElement('div');
        btn.className = 'scroll-top-btn';
        btn.innerHTML = '<i class="fas fa-arrow-up"></i>';
        btn.onclick = () => window.scrollTo({ top: 0, behavior: 'smooth' });
        document.body.appendChild(btn);
    }
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) btn.classList.add('visible');
        else btn.classList.remove('visible');
    });
}

function addRippleEffect() {
    const buttons = document.querySelectorAll('.btn, button[type="submit"], .ripple-btn');
    buttons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const rect = this.getBoundingClientRect();
            const ripple = document.createElement('span');
            ripple.className = 'ripple';
            ripple.style.left = (e.clientX - rect.left) + 'px';
            ripple.style.top = (e.clientY - rect.top) + 'px';
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            setTimeout(() => ripple.remove(), 600);
        });
    });
}

// دالة مساعدة للـ Toast
window.showSuccess = (msg) => Toast.success(msg);
window.showError = (msg) => Toast.error(msg);
window.showWarning = (msg) => Toast.warning(msg);
