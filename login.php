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
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود به سیستم | <?= APP_NAME ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0b1f3a 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        .login-box {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.35);
        }
    </style>
</head>
<body>

<div class="login-box text-center">
    <!-- Logo -->
    <div class="mb-4">
        <img src="assets/images/logo.png" alt="<?= APP_NAME ?>" class="img-fluid" style="max-height: 70px;">
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
