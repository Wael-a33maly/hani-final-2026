// تحسين النماذج: إخفاء رسائل الخطأ بعد 4 ثوانٍ
document.addEventListener('DOMContentLoaded', function() {
    const errorMessages = document.querySelectorAll('.form-error');
    errorMessages.forEach(msg => {
        setTimeout(() => {
            msg.style.opacity = '0';
            setTimeout(() => msg.remove(), 300);
        }, 4000);
    });
});

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('error');
            showFieldError(field, 'هذا الحقل مطلوب');
            isValid = false;
        } else {
            field.classList.remove('error');
        }
    });
    return isValid;
}

function showFieldError(field, message) {
    let errorDiv = field.parentElement.querySelector('.form-error');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'form-error';
        field.parentElement.appendChild(errorDiv);
    }
    errorDiv.innerHTML = `<i class="fas fa-exclamation-circle text-xs"></i> ${message}`;
    setTimeout(() => {
        if (errorDiv) errorDiv.style.opacity = '0';
        setTimeout(() => errorDiv?.remove(), 300);
    }, 4000);
}
