/**
 * 餐廳註冊表單JavaScript功能 - 單頁版本
 * 只保留必要的表單驗證和條件欄位顯示
 */

document.addEventListener('DOMContentLoaded', function() {
    // 初始化表單功能
    initFormValidation();
    initConditionalFields();
});

/**
 * 初始化表單驗證
 */
function initFormValidation() {
    const form = document.getElementById('restaurant-registration-form');
    if (!form) return;
    
    const requiredFields = form.querySelectorAll('[required]');
    
    requiredFields.forEach(field => {
        // 失去焦點時驗證
        field.addEventListener('blur', function() {
            validateField(this);
        });
        
        // 輸入時清除錯誤
        field.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                clearFieldError(this);
            }
        });
    });
}

/**
 * 驗證單個欄位
 */
function validateField(field) {
    const value = field.value.trim();
    
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, getFieldErrorMessage(field));
        return false;
    }
    
    // 特定欄位驗證
    if (field.type === 'email' && value) {
        if (!isValidEmail(value)) {
            showFieldError(field, '請輸入有效的Email地址');
            return false;
        }
    }
    
    if (field.type === 'tel' && value) {
        if (!isValidPhone(value)) {
            showFieldError(field, '請輸入有效的電話號碼');
            return false;
        }
    }
    
    // 驗證通過
    clearFieldError(field);
    field.classList.add('valid');
    return true;
}

/**
 * 驗證Email格式
 */
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

/**
 * 驗證電話號碼
 */
function isValidPhone(phone) {
    const phoneRegex = /^[\d\s\-\+\(\)]{8,}$/;
    return phoneRegex.test(phone);
}

/**
 * 顯示欄位錯誤
 */
function showFieldError(field, message) {
    clearFieldError(field);
    field.classList.add('error');
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    errorDiv.style.color = '#dc3545';
    errorDiv.style.fontSize = '14px';
    errorDiv.style.marginTop = '5px';
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * 清除欄位錯誤
 */
function clearFieldError(field) {
    field.classList.remove('error');
    const errorDiv = field.parentNode.querySelector('.field-error');
    if (errorDiv) {
        errorDiv.remove();
    }
}

/**
 * 獲取欄位錯誤訊息
 */
function getFieldErrorMessage(field) {
    const name = field.name;
    
    if (name.includes('restaurant_name')) return '請輸入餐廳名稱';
    if (name.includes('contact_person')) return '請輸入聯絡人姓名';
    if (name.includes('email')) return '請輸入Email地址';
    if (name.includes('phone')) return '請輸入聯絡電話';
    if (name.includes('restaurant_type')) return '請選擇餐廳類型';
    if (name.includes('district')) return '請選擇行政區';
    if (name.includes('address')) return '請輸入餐廳地址';
    if (name.includes('is_charged')) return '請選擇是否酌收開瓶費';
    
    return '此欄位為必填';
}

/**
 * 初始化條件欄位
 */
function initConditionalFields() {
    // 開瓶費欄位顯示/隱藏
    const isChargedSelect = document.getElementById('is_charged');
    const corkageFeeGroup = document.getElementById('corkage_fee_group');
    
    if (isChargedSelect && corkageFeeGroup) {
        isChargedSelect.addEventListener('change', function() {
            if (this.value === '酌收') {
                corkageFeeGroup.style.display = 'block';
                const feeInput = corkageFeeGroup.querySelector('input');
                if (feeInput) {
                    feeInput.setAttribute('required', 'required');
                }
            } else {
                corkageFeeGroup.style.display = 'none';
                const feeInput = corkageFeeGroup.querySelector('input');
                if (feeInput) {
                    feeInput.removeAttribute('required');
                    feeInput.value = '';
                }
            }
        });
    }
    
    // 餐廳類型其他說明
    const restaurantTypeSelect = document.getElementById('restaurant_type');
    if (restaurantTypeSelect) {
        restaurantTypeSelect.addEventListener('change', function() {
            // 這裡可以添加餐廳類型「其他」的處理邏輯
            // 如果需要額外的文字輸入框
        });
    }
}

/**
 * 表單提交前驗證
 */
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('restaurant-registration-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!validateField(field)) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            // 滾動到第一個錯誤欄位
            const firstError = form.querySelector('.error');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    });
});
