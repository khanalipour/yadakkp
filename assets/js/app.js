/**
 * YadakKp Warehouse - Frontend Application Logic
 * Dark Mode, SweetAlert2 Popups, Price Calculator, Mobile Filters
 */

// ۱. مدیریت پوسته تاریک و روشن (Dark Mode) همراه با تعویض لوگو
function initTheme() {
    const savedTheme = localStorage.getItem('yadakkp_theme') || 'light';
    applyTheme(savedTheme, false);

    const toggleBtn = document.getElementById('themeToggleBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function () {
            const currentTheme = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            applyTheme(newTheme, true);
        });
    }
}

function applyTheme(theme, save = true) {
    document.documentElement.setAttribute('data-bs-theme', theme);
    if (theme === 'dark') {
        document.body.classList.add('theme-dark');
    } else {
        document.body.classList.remove('theme-dark');
    }

    // تعویض لوگوها در حالت تاریک و روشن
    const logos = document.querySelectorAll('.app-logo-img');
    logos.forEach(img => {
        if (theme === 'dark') {
            img.src = 'assets/images/logo-dark.png';
        } else {
            img.src = 'assets/images/logo.png';
        }
    });

    // تعویض آیکون دکمه
    const toggleIcon = document.getElementById('themeToggleIcon');
    if (toggleIcon) {
        if (theme === 'dark') {
            toggleIcon.className = 'bi bi-sun-fill text-warning';
        } else {
            toggleIcon.className = 'bi bi-moon-stars text-secondary';
        }
    }

    if (save) {
        localStorage.setItem('yadakkp_theme', theme);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    initTheme();

    // نمایش پاپ‌آپ زیبا برای پیام‌های سیستمی فلش (به جای آلرت‌های خام)
    if (window.YADAK_FLASH && window.YADAK_FLASH.message) {
        const flash = window.YADAK_FLASH;
        let iconType = 'info';
        let title = 'اطلاعیه سیستم';

        if (flash.type === 'success') {
            iconType = 'success';
            title = 'عملیات موفق';
        } else if (flash.type === 'danger') {
            iconType = 'error';
            title = 'خطا در عملیات';
        } else if (flash.type === 'warning') {
            iconType = 'warning';
            title = 'هشدار سیستم';
        }

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: iconType,
                title: title,
                html: flash.message,
                confirmButtonText: 'متوجه شدم',
                confirmButtonColor: '#0284c7',
                timer: 4000,
                timerProgressBar: true
            });
        }
    }

    // فرمت‌بندی ۳ رقم ۳ رقم فیلدهای قیمت هنگام تایپ
    document.querySelectorAll('.currency-input').forEach(input => {
        input.addEventListener('input', function () {
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

    const editSellPrice = document.getElementById('edit_sell_price');
    const editPercent = document.getElementById('price_increase_percent');
    const editAmount = document.getElementById('price_increase_amount');
    const editPreview = document.getElementById('new_price_preview');
    if (editSellPrice && editPreview) {
        initPriceCalculator(editSellPrice, editPercent, editAmount, editPreview);
    }
});

/**
 * پاپ‌آپ تایید حذف محصول (انتقال به سطل زباله)
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
 * پاپ‌آپ تایید حذف کامل و دائمی از سطل زباله
 */
function confirmPermanentDelete(productId, productName) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'حذف دائمی کالا از دیتابیس؟',
            html: `کالای <b>«${productName}»</b> به صورت کامل و غیرقابل بازگشت پاک خواهد شد. آیا مطمئن هستید؟`,
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
 * پاپ‌آپ تایید خاتمه دادن به نشست دستگاه
 */
function confirmTerminateSession(sessionId, deviceName) {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'خروج دستگاه انتخاب شده؟',
            html: `آیا مایلید نشست دستگاه <b>«${deviceName}»</b> را خاتمه دهید؟ این دستگاه فوراً از سیستم خارج می‌شود.`,
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
/**
 * پاپ‌آپ تایید خاتمه دادن به تمامی سایر دستگاه‌ها
 */
function confirmTerminateOtherSessions() {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'خروج از سایر دستگاه‌ها؟',
            html: 'تمامی نشست‌های فعال روی گوشی‌ها و رایانه‌های دیگر قطع خواهد شد و فقط این دستگاه متصل می‌ماند.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'بله، قطع اتصال همه',
            cancelButtonText: 'انصراف',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('terminateOthersForm');
                if (form) form.submit();
            }
        });
    } else {
        if (confirm('آیا از قطع دسترسی سایر دستگاه‌ها اطمینان دارید؟')) {
            const form = document.getElementById('terminateOthersForm');
            if (form) form.submit();
        }
    }
}

