<?php
/**
 * Login Page
 * یدک کی پی - ورود با نام کاربری و کلمه عبور ثابت و توکن ۲ روزه
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// اگر از قبل وارد شده، مستقیماً به داشبورد برود
if (checkAuth()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'لطفاً نام کاربری و کلمه عبور را وارد فرمایید.';
    } else {
        if (loginUser($username, $password)) {
            setFlash('success', 'خوش آمدید! ورود موفقیت‌آمیز بود. نشست شما تا ۲ روز فعال خواهد بود.');
            header('Location: index.php');
            exit;
        } else {
            $error = 'نام کاربری یا رمز عبور اشتباه است.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم | <?= APP_NAME ?></title>
    <!-- Bootstrap 5 RTL (Local) -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons (Local) -->
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0b1f3a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
            position: relative;
        }
        .login-box {
            width: 100%;
            max-width: 440px;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
            position: relative;
        }
    </style>
    <script>
        function applyTheme(theme, save) {
            if (save === undefined) save = true;
            document.documentElement.setAttribute('data-bs-theme', theme);
            if (theme === 'dark') {
                document.documentElement.classList.add('theme-dark');
                if (document.body) document.body.classList.add('theme-dark');
            } else {
                document.documentElement.classList.remove('theme-dark');
                if (document.body) document.body.classList.remove('theme-dark');
            }

            // تعویض لوگو
            var logos = document.querySelectorAll('.app-logo-img');
            logos.forEach(function(img) {
                img.src = (theme === 'dark') ? 'assets/images/logo-dark.png' : 'assets/images/logo.png';
            });

            // تعویض آیکون
            var icon = document.getElementById('themeToggleIcon');
            if (icon) {
                icon.className = (theme === 'dark') ? 'bi bi-sun-fill text-warning fs-5' : 'bi bi-moon-stars text-secondary fs-5';
            }

            if (save) {
                try { localStorage.setItem('yadakkp_theme', theme); } catch(e){}
            }
        }

        function toggleDarkMode() {
            var current = document.documentElement.getAttribute('data-bs-theme') || 'light';
            var next = (current === 'dark') ? 'light' : 'dark';
            applyTheme(next, true);
        }

        window.toggleDarkMode = toggleDarkMode;

        // مقداردهی اولیه سریع قبل از رندر
        (function() {
            var saved = 'light';
            try { saved = localStorage.getItem('yadakkp_theme') || 'light'; } catch(e){}
            document.documentElement.setAttribute('data-bs-theme', saved);
            if (saved === 'dark') {
                document.documentElement.classList.add('theme-dark');
            }
        })();

        document.addEventListener('DOMContentLoaded', function() {
            var saved = 'light';
            try { saved = localStorage.getItem('yadakkp_theme') || 'light'; } catch(e){}
            applyTheme(saved, false);
        });
    </script>
</head>
<body>

<div class="login-box text-center">
    <!-- دکمه دارک مود در صفحه لاگین -->
    <div class="position-absolute top-0 start-0 m-3">
        <button type="button" class="theme-toggle-btn shadow-none" id="themeToggleBtn" onclick="toggleDarkMode()" title="تغییر حالت شب و روز">
            <i class="bi bi-moon-stars" id="themeToggleIcon"></i>
        </button>
    </div>

    <!-- Logo -->
    <div class="mb-4 mt-2">
        <img src="assets/images/logo.png" alt="<?= APP_NAME ?>" class="app-logo-img img-fluid" style="max-height: 70px;">
    </div>
    
    <h4 class="fw-bold text-dark mb-1">سیستم انبارداری یدک کی پی</h4>
    <p class="text-muted small mb-4">جهت ورود به پنل مدیریت انبار، مشخصات خود را وارد کنید</p>

    <?php if ($error): ?>
        <div class="alert alert-danger py-2 small d-flex align-items-center justify-content-center gap-2 mb-3">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" autocomplete="off">
        <div class="mb-3 text-start">
            <label class="form-label small fw-semibold text-secondary">نام کاربری</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control border-start-0" placeholder="مثال: admin" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-4 text-start">
            <label class="form-label small fw-semibold text-secondary">کلمه عبور</label>
            <div class="input-group">
                <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-key"></i></span>
                <input type="password" name="password" class="form-control border-start-0" placeholder="رمز عبور" required>
            </div>
        </div>

        <div class="p-2 mb-3 bg-light rounded-3 text-secondary small text-start d-flex align-items-center gap-2">
            <i class="bi bi-shield-check text-success fs-5"></i>
            <div>
                <strong>اعتبار ورود:</strong> پس از ورود، توکن شما دقیقاً به مدت <b>۲ روز</b> فعال بوده و سپس به صورت خودکار خارج خواهید شد.
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 py-2 fs-6 fw-bold">
            <i class="bi bi-box-arrow-in-left me-1"></i> ورود به پنل انبارداری
        </button>
    </form>
</div>

</body>
</html>
