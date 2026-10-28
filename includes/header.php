<?php
/**
 * Header Template
 * یدک کی پی - نوار ناوبری سفید حرفه‌ای، منوی مدرن موبایل و سوئیچر دارک مود
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

$db = getDB();
$trashCount = 0;
$activeSessionsCount = 0;

try {
    $tStmt = $db->query("SELECT COUNT(*) as cnt FROM products WHERE is_deleted = 1");
    $trashCount = (int)$tStmt->fetch()['cnt'];

    $sStmt = $db->query("SELECT COUNT(*) as cnt FROM active_sessions WHERE expires_at > " . (getDBDriver() === 'mysql' ? "NOW()" : "datetime('now', 'localtime')"));
    $activeSessionsCount = (int)$sStmt->fetch()['cnt'];
} catch (Exception $e) {}

$currentPage = basename($_SERVER['PHP_SELF']);
$flash = getFlash();
$dbNotice = getDBNotice();
$currentUsername = getSetting('admin_username', ADMIN_USERNAME);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | ' : '' ?><?= APP_TITLE ?></title>
    
    <!-- Bootstrap 5 RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= time() ?>">
    <script>
        // اعمال فوری پوسته ذخیره شده پیش از لود صفحه جهت جلوگیری از پرش نور
        (function() {
            var theme = localStorage.getItem('yadakkp_theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', theme);
            if (theme === 'dark') {
                document.documentElement.classList.add('theme-dark');
            }
        })();
    </script>
</head>
<body>

<!-- Header Navbar (سفید حرفه‌ای با دیزاین مدرن) -->
<header class="navbar navbar-expand-lg custom-header sticky-top py-2">
    <div class="container-fluid px-lg-4">
        
        <!-- Logo & Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2 py-1" href="index.php">
            <img src="assets/images/logo.png" alt="<?= APP_NAME ?>" class="app-logo-img d-inline-block">
            <div class="d-none d-sm-block">
                <span class="fw-bold fs-5 text-primary">یدک کی پی</span>
                <span class="d-block text-muted" style="font-size: 0.72rem;">انبارداری و فروش قطعات</span>
            </div>
        </a>

        <!-- دکمه‌های سمت چپ هدر (دارک مود، پروفایل و منوی مدرن موبایل) -->
        <div class="d-flex align-items-center gap-2 order-lg-3">
            <!-- سوئیچ دارک مود -->
            <button type="button" class="theme-toggle-btn shadow-none" id="themeToggleBtn" title="تغییر حالت شب و روز">
                <i class="bi bi-moon-stars" id="themeToggleIcon"></i>
            </button>

            <!-- منوی کاربری و تنظیمات -->
            <div class="dropdown">
                <button class="btn btn-light border btn-sm dropdown-toggle d-flex align-items-center gap-2 rounded-3 px-3 py-2" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle fs-6 text-primary"></i>
                    <span class="d-none d-md-inline fw-semibold"><?= htmlspecialchars($_SESSION['auth_user'] ?? $currentUsername) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2">
                    <li class="px-3 py-2 border-bottom mb-1">
                        <div class="fw-bold text-dark"><?= htmlspecialchars($_SESSION['auth_user'] ?? $currentUsername) ?></div>
                        <span class="badge bg-success-subtle text-success small">نشست فعال (۲ روزه)</span>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2" href="settings.php">
                            <i class="bi bi-shield-lock me-2 text-primary"></i>تغییر رمز و نام کاربری
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2" href="devices.php">
                            <i class="bi bi-laptop me-2 text-info"></i>دستگاه‌های متصل (<?= tr_num($activeSessionsCount) ?>)
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item rounded-2 py-2 text-danger" href="logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i>خروج از حساب
                        </a>
                    </li>
                </ul>
            </div>

            <!-- دکمه منوی کشویی موبایل (Offcanvas Toggle) -->
            <button class="btn btn-light border d-lg-none p-2 rounded-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar" aria-controls="offcanvasNavbar">
                <i class="bi bi-list fs-4"></i>
            </button>
        </div>

        <!-- منوی دسکتاپ -->
        <div class="collapse navbar-collapse order-lg-2">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-center">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-speedometer2"></i> داشبورد
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>" href="products.php">
                        <i class="bi bi-boxes"></i> همه محصولات
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>" href="categories.php">
                        <i class="bi bi-folder2-open"></i> محصولات بر اساس دسته‌بندی
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'product-add.php' ? 'active' : '' ?>" href="product-add.php">
                        <i class="bi bi-plus-circle"></i> ثبت کالا
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'brands.php' ? 'active' : '' ?>" href="brands.php">
                        <i class="bi bi-tags"></i> برندها
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'devices.php' ? 'active' : '' ?>" href="devices.php">
                        <i class="bi bi-laptop"></i> دستگاه‌های فعال
                        <?php if ($activeSessionsCount > 0): ?>
                            <span class="badge bg-success rounded-pill ms-1"><?= tr_num($activeSessionsCount) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'trash.php' ? 'active' : '' ?>" href="trash.php">
                        <i class="bi bi-trash3"></i> زباله‌دان
                        <?php if ($trashCount > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= tr_num($trashCount) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</header>

<!-- منوی آف‌کانواس کشویی حرفه‌ای موبایل (Mobile Offcanvas Drawer) -->
<div class="offcanvas offcanvas-start offcanvas-custom" tabindex="-1" id="offcanvasNavbar" aria-labelledby="offcanvasNavbarLabel">
    <div class="offcanvas-header border-bottom py-3">
        <div class="d-flex align-items-center gap-2">
            <img src="assets/images/logo.png" alt="<?= APP_NAME ?>" height="36" class="app-logo-img">
            <span class="fw-bold fs-6">یدک کی پی</span>
        </div>
        <button type="button" class="btn-close ms-0" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <div class="p-3 bg-light rounded-4 mb-3 d-flex align-items-center gap-3">
            <div class="bg-primary text-white p-2 rounded-3">
                <i class="bi bi-person-fill fs-5"></i>
            </div>
            <div>
                <div class="fw-bold small"><?= htmlspecialchars($_SESSION['auth_user'] ?? $currentUsername) ?></div>
                <div class="text-muted" style="font-size: 0.72rem;">مدیر سیستم انبارداری</div>
            </div>
        </div>

        <ul class="nav nav-pills flex-column flex-grow-1">
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">
                    <span><i class="bi bi-speedometer2 me-2"></i> داشبورد انبار</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'products.php' ? 'active' : '' ?>" href="products.php">
                    <span><i class="bi bi-boxes me-2"></i> همه محصولات</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>" href="categories.php">
                    <span><i class="bi bi-folder2-open me-2"></i> دسته‌بندی پوشه‌ای</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'product-add.php' ? 'active' : '' ?>" href="product-add.php">
                    <span><i class="bi bi-plus-circle me-2"></i> ثبت محصول جدید</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'brands.php' ? 'active' : '' ?>" href="brands.php">
                    <span><i class="bi bi-tags me-2"></i> مدیریت برندها</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'devices.php' ? 'active' : '' ?>" href="devices.php">
                    <span><i class="bi bi-laptop me-2"></i> دستگاه‌های فعال</span>
                    <?php if ($activeSessionsCount > 0): ?>
                        <span class="badge bg-success rounded-pill"><?= tr_num($activeSessionsCount) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $currentPage === 'trash.php' ? 'active' : '' ?>" href="trash.php">
                    <span><i class="bi bi-trash3 me-2"></i> سطل زباله</span>
                    <?php if ($trashCount > 0): ?>
                        <span class="badge bg-danger rounded-pill"><?= tr_num($trashCount) ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item mt-3 pt-3 border-top">
                <a class="nav-link <?= $currentPage === 'settings.php' ? 'active' : '' ?>" href="settings.php">
                    <span><i class="bi bi-shield-lock me-2 text-primary"></i> تغییر نام کاربری و رمز</span>
                </a>
            </li>
        </ul>

        <div class="pt-3 border-top mt-auto">
            <a href="logout.php" class="btn btn-outline-danger w-100 rounded-3 py-2">
                <i class="bi bi-box-arrow-right me-1"></i> خروج از حساب
            </a>
        </div>
    </div>
</div>

<!-- نوار ناوبری پایین صفحه مخصوص موبایل (Mobile Bottom App Bar) -->
<div class="mobile-bottom-bar d-lg-none">
    <a href="index.php" class="mobile-bottom-link <?= $currentPage === 'index.php' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i>
        <span>داشبورد</span>
    </a>
    <a href="products.php" class="mobile-bottom-link <?= $currentPage === 'products.php' ? 'active' : '' ?>">
        <i class="bi bi-boxes"></i>
        <span>کالاها</span>
    </a>
    <a href="product-add.php" class="mobile-bottom-link <?= $currentPage === 'product-add.php' ? 'active' : '' ?>">
        <i class="bi bi-plus-circle text-primary fs-4"></i>
        <span>ثبت کالا</span>
    </a>
    <a href="categories.php" class="mobile-bottom-link <?= $currentPage === 'categories.php' ? 'active' : '' ?>">
        <i class="bi bi-folder2-open"></i>
        <span>پوشه‌ها</span>
    </a>
    <a href="javascript:void(0)" class="mobile-bottom-link" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
        <i class="bi bi-grid"></i>
        <span>منو</span>
    </a>
</div>

<!-- Main Content Wrapper -->
<main class="container-fluid px-lg-4 py-4 flex-grow-1">

    <?php if ($flash): ?>
        <script>
            window.YADAK_FLASH = <?= json_encode($flash, JSON_UNESCAPED_UNICODE) ?>;
        </script>
    <?php endif; ?>
