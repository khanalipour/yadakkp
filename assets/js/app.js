/**
 * YadakKp Warehouse - Frontend Scripts
 */

document.addEventListener('DOMContentLoaded', function () {
    // فرمت‌بندی ۳ رقم ۳ رقم فیلدهای قیمت هنگام تایپ
    document.querySelectorAll('.currency-input').forEach(input => {
        input.addEventListener('input', function (e) {
            let value = this.value.replace(/[^0-9]/g, '');
            if (value) {
                this.value = parseInt(value, 10).toLocaleString('en-US');
            } else {
                this.value = '';
            }
        });
    });

    // محاسبه قیمت جدید در فرم‌های افزایش قیمت
    const initPriceCalculator = (basePriceEl, percentEl, amountEl, resultEl) => {
        if (!basePriceEl || !resultEl) return;

        const calculate = () => {
            const rawBase = basePriceEl.value.replace(/,/g, '');
            const base = parseFloat(rawBase) || 0;
            const percent = parseFloat(percentEl?.value) || 0;
            const amount = parseFloat(amountEl?.value.replace(/,/g, '')) || 0;

            let finalPrice = base;
            if (percent > 0) {
                finalPrice += Math.round(base * (percent / 100));
            }
            if (amount > 0) {
                finalPrice += amount;
            }

            resultEl.textContent = Math.round(finalPrice).toLocaleString('fa-IR') + ' تومان';
        };

        if (percentEl) percentEl.addEventListener('input', calculate);
        if (amountEl) amountEl.addEventListener('input', calculate);
        if (basePriceEl) basePriceEl.addEventListener('input', calculate);
    };

    // متغیرهای فرم ویرایش محصول
    const editSellPrice = document.getElementById('edit_sell_price');
    const editPercent = document.getElementById('price_increase_percent');
    const editAmount = document.getElementById('price_increase_amount');
    const editPreview = document.getElementById('new_price_preview');
    if (editSellPrice && editPreview) {
        initPriceCalculator(editSellPrice, editPercent, editAmount, editPreview);
    }
});

/**
 * نمایش مدال تایید حذف محصول (انتقال به سطل زباله)
 */
function confirmDeleteProduct(productId, productName) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'آیا از حذف این کالا مطمئن هستید؟',
            html: `کالای <b>«${productName}»</b> به سطل زباله منتقل خواهد شد و هر زمان بخواهید می‌توانید آن را بازیابی کنید.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'بله، حذف و انتقال به زباله‌دان',
            cancelButtonText: 'انصراف',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `product-delete.php?action=trash&id=${productId}`;
            }
        });
    } else {
        if (confirm(`آیا از انتقال کالای "${productName}" به سطل زباله اطمینان دارید؟`)) {
            window.location.href = `product-delete.php?action=trash&id=${productId}`;
        }
    }
}

/**
 * تایید حذف کامل و همیشگی از سطل زباله
 */
function confirmPermanentDelete(productId, productName) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'حذف دائمی کالا از دیتابیس؟',
            html: `کالای <b>«${productName}»</b> به صورت غیرقابل بازگشت از کل سیستم حذف خواهد شد. آیا مطمئن هستید؟`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'بله، حذف همیشگی',
            cancelButtonText: 'انصراف',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `product-delete.php?action=permanent&id=${productId}`;
            }
        });
    } else {
        if (confirm(`هشدار: آیا از حذف همیشگی کالای "${productName}" مطمئن هستید؟ این عملیات قابل بازگشت نیست.`)) {
            window.location.href = `product-delete.php?action=permanent&id=${productId}`;
        }
    }
}

/**
 * تایید خاتمه دادن به نشست دستگاه
 */
function confirmTerminateSession(sessionId, deviceName) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'خروج دستگاه انتخاب شده؟',
            html: `آیا مایلید نشست دستگاه <b>«${deviceName}»</b> را خاتمه دهید؟ این دستگاه فوراً از حساب خارج می‌شود.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f59e0b',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'بله، قطع اتصال',
            cancelButtonText: 'انصراف',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `devices.php?terminate=${sessionId}`;
            }
        });
    } else {
        if (confirm(`آیا از قطع دسترسی دستگاه "${deviceName}" اطمینان دارید؟`)) {
            window.location.href = `devices.php?terminate=${sessionId}`;
        }
    }
}
