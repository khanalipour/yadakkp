<?php
/**
 * One-Click Installer & Database Setup
 * یدک کی پی - راه‌انداز خودکار پایگاه داده برای ساب‌دامین و هاست
 */

require_once __DIR__ . '/config/config.php';

$message = '';
$messageType = '';
$installed = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_db'])) {
    try {
        // ۱. تلاش برای اتصال به سرور MySQL
        $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
        $pdo = new PDO($rootDsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // ۲. ایجاد دیتابیس
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci");
        $pdo->exec("USE `" . DB_NAME . "`");

        // ۳. اجرای اسکریپت SQL
        $sqlScript = file_get_contents(__DIR__ . '/database.sql');
        
        // تقسیم دستورات
        $queries = explode(';', $sqlScript);
        foreach ($queries as $query) {
            $q = trim($query);
            if (!empty($q)) {
                $pdo->exec($q);
            }
        }

        $message = 'پایگاه داده MySQL با موفقیت ایجاد و تمامی جداول و داده‌های اولیه نصب گردیدند!';
        $messageType = 'success';
        $installed = true;
    } catch (Exception $e) {
        $message = 'خطا در نصب دیتابیس: ' . $e->getMessage() . '<br><small>لطفاً تنظیمات فایل config/config.php را بررسی فرمایید.</small>';
        $messageType = 'danger';
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نصب و راه‌اندازی دیتابیس | <?= APP_NAME ?></title>
    <!-- Bootstrap 5 RTL (Local) -->
    <link rel="stylesheet" href="assets/vendor/bootstrap/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons (Local) -->
    <link rel="stylesheet" href="assets/vendor/bootstrap-icons/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <style>
        body {
            background-color: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1.5rem;
        }
        .setup-card {
            max-width: 600px;
            width: 100%;
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0,0,0,0.06);
        }
    </style>
</head>
<body>

<div class="setup-card">
    <div class="text-center mb-4">
        <img src="assets/images/logo.png" alt="<?= APP_NAME ?>" height="55" class="mb-3">
        <h4 class="fw-bold">راه‌انداز خودکار پایگاه داده یدک کی پی</h4>
        <p class="text-muted small">این ابزار جداول مورد نیاز سیستم انبارداری را روی MySQL ایجاد و آماده‌سازی می‌کند.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> d-flex align-items-center mb-4">
            <i class="bi bi-<?= $messageType === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> fs-4 me-2"></i>
            <div><?= $message ?></div>
        </div>
    <?php endif; ?>

    <div class="card bg-light border-0 p-3 mb-4 rounded-3 small">
        <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-sliders me-1 text-primary"></i>تنظیمات فعلی در config/config.php:</h6>
        <div class="d-flex justify-content-between py-1 border-bottom">
            <span>میزبان (Host):</span>
            <span class="font-monospace fw-bold text-dark"><?= DB_HOST ?>:<?= DB_PORT ?></span>
        </div>
        <div class="d-flex justify-content-between py-1 border-bottom">
            <span>نام دیتابیس (DB Name):</span>
            <span class="font-monospace fw-bold text-dark"><?= DB_NAME ?></span>
        </div>
        <div class="d-flex justify-content-between py-1">
            <span>نام کاربری (DB User):</span>
            <span class="font-monospace fw-bold text-dark"><?= DB_USER ?></span>
        </div>
    </div>

    <?php if ($installed): ?>
        <a href="login.php" class="btn btn-success w-100 py-2 fs-6 fw-bold">
            <i class="bi bi-box-arrow-in-left me-1"></i> ورود به صفحه لاگین
        </a>
    <?php else: ?>
        <form method="POST">
            <button type="submit" name="install_db" class="btn btn-primary w-100 py-2 fs-6 fw-bold">
                <i class="bi bi-database-gear me-1"></i> ساخت دیتابیس و نصب جداول
            </button>
        </form>
        <div class="text-center mt-3">
            <a href="login.php" class="text-decoration-none small text-muted">
                یا رفتن مستقیم به پنل ورود <i class="bi bi-arrow-left"></i>
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
