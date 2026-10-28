<?php
/**
 * Main Dashboard
 * یدک کی پی - پیشخوان و نمای کلی انبارداری لوازم یدکی
 */

$pageTitle = 'داشبورد مدیریت انبار';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

// محاسبات آمار کل انبار
$totalProducts = 0;
$totalStock = 0;
$totalBuyValue = 0;
$totalSellValue = 0;
$emptyCount = 0;
$lowStockCount = 0;

$pStats = $db->query("
    SELECT 
        COUNT(*) as total_items,
        COALESCE(SUM(stock), 0) as total_stock,
        COALESCE(SUM(purchase_price * stock), 0) as total_buy_value,
        COALESCE(SUM(sell_price * stock), 0) as total_sell_value,
        COUNT(CASE WHEN stock <= 0 THEN 1 END) as empty_items,
        COUNT(CASE WHEN stock > 0 AND stock <= min_stock THEN 1 END) as low_items
    FROM products 
    WHERE is_deleted = 0
")->fetch();

if ($pStats) {
    $totalProducts = (int)$pStats['total_items'];
    $totalStock = (int)$pStats['total_stock'];
    $totalBuyValue = (int)$pStats['total_buy_value'];
    $totalSellValue = (int)$pStats['total_sell_value'];
    $emptyCount = (int)$pStats['empty_items'];
    $lowStockCount = (int)$pStats['low_items'];
}

$categoryCount = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$brandCount = (int)$db->query("SELECT COUNT(*) FROM brands")->fetchColumn();

// آخرین محصولات ثبت یا ویرایش شده
$recentProducts = $db->query("
    SELECT p.*, c.name as category_name, b.name as brand_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.is_deleted = 0 
    ORDER BY p.updated_at DESC, p.id DESC 
    LIMIT 6
")->fetchAll();

// محصولاتی که موجودی آن‌ها کم است یا تمام شده
$alertProducts = $db->query("
    SELECT p.*, c.name as category_name, b.name as brand_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.is_deleted = 0 AND (p.stock <= p.min_stock) 
    ORDER BY p.stock ASC 
    LIMIT 5
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- بنر خوش‌آمدگویی و مشخصات فروشگاه -->
<div class="card shadow-sm border-0 mb-4 overflow-hidden" style="background: linear-gradient(135deg, #0b1f3a 0%, #0369a1 100%);">
    <div class="card-body p-4 text-white d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white p-2 rounded-3 shadow-sm d-none d-sm-block">
                <img src="assets/images/logo.png" alt="<?= APP_NAME ?>" height="45">
            </div>
            <div>
                <h3 class="fw-bold mb-1">سیستم انبارداری فروشگاه لوازم یدکی یدک کی پی (YadakKp)</h3>
                <p class="text-white-50 small mb-0">امروز: <?= jdate('l j F Y') ?> | مدیریت موجودی، قطعات یدکی، دسته‌بندی پوشه‌ای و افزایش سریع قیمت‌ها</p>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="product-add.php" class="btn btn-warning fw-bold px-3 shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> ثبت قطعه جدید
            </a>
            <a href="categories.php" class="btn btn-outline-light px-3">
                <i class="bi bi-folder2-open me-1"></i> پوشه‌های قطعات
            </a>
        </div>
    </div>
</div>

<!-- کارت‌های آمار و KPI ها -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">تعداد کل اقلام قطعات</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= tr_num($totalProducts) ?> <small class="text-muted fs-6">قلم کالا</small></h3>
                    <span class="text-secondary small">در <?= tr_num($categoryCount) ?> دسته و <?= tr_num($brandCount) ?> برند</span>
                </div>
                <div class="stat-icon bg-primary-subtle text-primary">
                    <i class="bi bi-boxes"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">مجموع کل موجودی انبار</span>
                    <h3 class="fw-bold text-dark mt-1 mb-0"><?= tr_num($totalStock) ?> <small class="text-muted fs-6">عدد / دست</small></h3>
                    <span class="text-success small"><i class="bi bi-check-circle me-1"></i>آماده فروش</span>
                </div>
                <div class="stat-icon bg-success-subtle text-success">
                    <i class="bi bi-stack"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">ارزش ریالی انبار (قیمت فروش)</span>
                    <h4 class="fw-bold text-success mt-1 mb-0"><?= formatToman($totalSellValue) ?></h4>
                    <span class="text-muted small">ارزش خرید: <?= formatToman($totalBuyValue) ?></span>
                </div>
                <div class="stat-icon bg-info-subtle text-info">
                    <i class="bi bi-cash-coin"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card stat-card shadow-sm border-0 h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-muted small fw-semibold">هشدار موجودی قطعات</span>
                    <h3 class="fw-bold text-danger mt-1 mb-0"><?= tr_num($emptyCount + $lowStockCount) ?> <small class="text-muted fs-6">قلم</small></h3>
                    <span class="text-danger small"><?= tr_num($emptyCount) ?> ناموجود | <?= tr_num($lowStockCount) ?> کمبود</span>
                </div>
                <div class="stat-icon bg-danger-subtle text-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- جدول آخرین قطعات ثبت یا ویرایش شده -->
    <div class="col-12 col-xl-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-dark"><i class="bi bi-clock-history text-primary me-2"></i>آخرین قطعات به‌روزرسانی شده</span>
                <a href="products.php" class="btn btn-outline-primary btn-sm">مشاهده همه قطعات</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>نام قطعه</th>
                                <th>دسته‌بندی</th>
                                <th>قیمت فروش</th>
                                <th>موجودی</th>
                                <th class="text-center" style="width: 100px;">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentProducts)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">هنوز کالایی ثبت نشده است.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentProducts as $p): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                            <div class="d-flex align-items-center gap-2 mt-1">
                                                <?php if (!empty($p['code'])): ?>
                                                    <span class="badge bg-light text-secondary border font-monospace"><?= htmlspecialchars($p['code']) ?></span>
                                                <?php endif; ?>
                                                <span class="badge bg-secondary-subtle text-secondary"><?= htmlspecialchars($p['brand_name'] ?: 'بدون برند') ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary border">
                                                <?= htmlspecialchars($p['category_name'] ?: 'بدون دسته') ?>
                                            </span>
                                        </td>
                                        <td class="price-sell fw-bold">
                                            <?= formatToman($p['sell_price']) ?>
                                        </td>
                                        <td>
                                            <?php if ($p['stock'] <= 0): ?>
                                                <span class="badge stock-badge-empty rounded-pill px-2 py-1">ناموجود</span>
                                            <?php elseif ($p['stock'] <= $p['min_stock']): ?>
                                                <span class="badge stock-badge-medium rounded-pill px-2 py-1"><?= tr_num($p['stock']) ?> عدد</span>
                                            <?php else: ?>
                                                <span class="badge stock-badge-high rounded-pill px-2 py-1"><?= tr_num($p['stock']) ?> عدد</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm">
                                                <a href="product-edit.php?id=<?= $p['id'] ?>" class="btn btn-outline-primary" title="ویرایش و افزایش قیمت">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger" onclick="confirmDeleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')" title="انتقال به سطل زباله">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ستون کناری: قطعات نیازمند سفارش و دسترسی سریع -->
    <div class="col-12 col-xl-4">
        <!-- هشدار کسری انبار -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold text-danger"><i class="bi bi-bell-fill text-danger me-2"></i>کسری موجودی و نقطه سفارش</span>
                <span class="badge bg-danger rounded-pill"><?= tr_num(count($alertProducts)) ?> کالا</span>
            </div>
            <div class="card-body p-3">
                <?php if (empty($alertProducts)): ?>
                    <div class="text-center py-4 text-success">
                        <i class="bi bi-check-circle fs-2 d-block mb-2"></i>
                        موجودی تمامی قطعات بالاتر از حد هشدار است.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($alertProducts as $ap): ?>
                            <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold text-dark small"><?= htmlspecialchars($ap['name']) ?></div>
                                    <div class="text-muted" style="font-size: 0.75rem;">
                                        حداقل نیاز: <?= tr_num($ap['min_stock']) ?> عدد
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="badge <?= $ap['stock'] <= 0 ? 'bg-danger' : 'bg-warning text-dark' ?> rounded-pill mb-1">
                                        موجودی: <?= tr_num($ap['stock']) ?> عدد
                                    </span>
                                    <div>
                                        <a href="product-edit.php?id=<?= $ap['id'] ?>" class="text-primary text-decoration-none" style="font-size: 0.75rem;">
                                            شارژ موجودی <i class="bi bi-arrow-left"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- کارت‌های دسترسی سریع -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <span class="fw-bold text-dark"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>دسترسی‌های سریع</span>
            </div>
            <div class="card-body p-3">
                <div class="d-grid gap-2">
                    <a href="categories.php" class="btn btn-outline-primary text-start d-flex justify-content-between align-items-center py-2">
                        <span><i class="bi bi-folder2-open me-2"></i>محصولات بر اساس دسته‌بندی (پوشه‌ای)</span>
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <a href="brands.php" class="btn btn-outline-secondary text-start d-flex justify-content-between align-items-center py-2">
                        <span><i class="bi bi-tags me-2"></i>مدیریت برندها و سازندگان قطعات</span>
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <a href="devices.php" class="btn btn-outline-info text-start d-flex justify-content-between align-items-center py-2">
                        <span><i class="bi bi-laptop me-2"></i>مشاهده دستگاه‌ها و نشست‌های فعال</span>
                        <i class="bi bi-chevron-left"></i>
                    </a>
                    <a href="trash.php" class="btn btn-outline-danger text-start d-flex justify-content-between align-items-center py-2">
                        <span><i class="bi bi-trash3 me-2"></i>سطل زباله و اقلام حذف شده</span>
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
