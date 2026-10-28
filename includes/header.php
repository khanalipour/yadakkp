<?php
/**
 * Header Template
 * یدک کی پی - نوار ناوبری و سربرگ صفحه
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// شمارش اقلام زباله‌دان و نشست‌های فعال جهت نمایش در نشانگرهای منو
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
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
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
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark custom-navbar sticky-top">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
            <img src="assets/images/logo.png" alt="<?= APP_NAME ?>" height="42" class="d-inline-block">
            <div class="d-none d-sm-block text-white">
                <span class="fw-bold fs-5">یدک کی پی</span>
                <span class="d-block text-white-50" style="font-size: 0.72rem; letter-spacing: 0.5px;">سیستم انبارداری و فروش قطعات</span>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 align-items-lg-center">
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
                        <i class="bi bi-plus-circle"></i> ثبت محصول جدید
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
                            <span class="badge bg-success badge-counter ms-1"><?= tr_num($activeSessionsCount) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'trash.php' ? 'active' : '' ?>" href="trash.php">
                        <i class="bi bi-trash3"></i> زباله‌دان
                        <?php if ($trashCount > 0): ?>
                            <span class="badge bg-danger badge-counter ms-1"><?= tr_num($trashCount) ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-check fs-6 text-success"></i>
                        <span><?= htmlspecialchars($_SESSION['auth_user'] ?? 'مدیر سیستم') ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li>
                            <div class="px-3 py-1 text-muted small">
                                <i class="bi bi-shield-lock me-1"></i> نشست ۲ روزه فعال
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="devices.php">
                                <i class="bi bi-devices me-2 text-primary"></i> مشاهده دستگاه‌ها (<?= tr_num($activeSessionsCount) ?>)
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i> خروج از حساب
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Main Wrapper -->
<main class="container-fluid px-lg-4 py-4 flex-grow-1">

    <?php if ($dbNotice): ?>
        <div class="alert alert-info alert-dismissible fade show d-flex align-items-center shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-info-circle-fill fs-5 me-2"></i>
            <div><?= htmlspecialchars($dbNotice) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show d-flex align-items-center shadow-sm rounded-3 mb-4" role="alert">
            <i class="bi bi-<?= $flash['type'] === 'success' ? 'check-circle-fill' : ($flash['type'] === 'danger' ? 'exclamation-octagon-fill' : 'info-circle-fill') ?> fs-5 me-2"></i>
            <div><?= htmlspecialchars($flash['message']) ?></div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
