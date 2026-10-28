<?php
/**
 * All Products List & Advanced Filtering
 * یدک کی پی - نمایش تمامی محصولات همراه با سیستم فیلتر حرفه‌ای
 */

$pageTitle = 'همه محصولات انبار';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

// فیلترها از پارامترهای GET
$search = trim($_GET['search'] ?? '');
$categoryId = !empty($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$brandId = !empty($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$minPrice = !empty($_GET['min_price']) ? (int)str_replace(',', '', $_GET['min_price']) : 0;
$maxPrice = !empty($_GET['max_price']) ? (int)str_replace(',', '', $_GET['max_price']) : 0;
$stockStatus = $_GET['stock_status'] ?? 'all'; // 'all', 'available', 'empty', 'low'
$sortBy = $_GET['sort_by'] ?? 'newest';

// ساخت کوئری داینامیک
$where = ["p.is_deleted = 0"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.name LIKE ? OR p.code LIKE ? OR p.description LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
}

if ($categoryId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $categoryId;
}

if ($brandId > 0) {
    $where[] = "p.brand_id = ?";
    $params[] = $brandId;
}

if ($minPrice > 0) {
    $where[] = "p.sell_price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice > 0) {
    $where[] = "p.sell_price <= ?";
    $params[] = $maxPrice;
}

if ($stockStatus === 'available') {
    $where[] = "p.stock > 0";
} elseif ($stockStatus === 'empty') {
    $where[] = "p.stock <= 0";
} elseif ($stockStatus === 'low') {
    $where[] = "p.stock > 0 AND p.stock <= p.min_stock";
}

// مرتب‌سازی
$orderBy = "p.id DESC";
switch ($sortBy) {
    case 'oldest':
        $orderBy = "p.id ASC";
        break;
    case 'price_desc':
        $orderBy = "p.sell_price DESC";
        break;
    case 'price_asc':
        $orderBy = "p.sell_price ASC";
        break;
    case 'stock_desc':
        $orderBy = "p.stock DESC";
        break;
    case 'stock_asc':
        $orderBy = "p.stock ASC";
        break;
    case 'name_asc':
        $orderBy = "p.name ASC";
        break;
    default:
        $orderBy = "p.id DESC";
        break;
}

$whereClause = implode(" AND ", $where);

$sql = "
    SELECT p.*, c.name as category_name, c.color as category_color, b.name as brand_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE {$whereClause} 
    ORDER BY {$orderBy}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// آمار کلی فیلتر شده
$totalFilteredCount = count($products);
$totalFilteredStock = 0;
$totalFilteredValue = 0;
foreach ($products as $p) {
    $totalFilteredStock += (int)$p['stock'];
    $totalFilteredValue += ((int)$p['sell_price'] * (int)$p['stock']);
}

// لیست دسته‌ها و برندها برای فرم فیلتر
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$brands = $db->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- هدر صفحه -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">
            <i class="bi bi-boxes text-primary me-2"></i>لیست تمام کالاهای موجود در انبار
        </h3>
        <p class="text-muted small mb-0">
            جستجو، فیلتر پیشرفته، مدیریت قیمت و ویرایش کلیه قطعات یدکی ثبت شده در سیستم.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="product-add.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-plus-circle me-1"></i> ثبت محصول جدید
        </a>
    </div>
</div>

<!-- بخش فیلتر پیشرفته (Advanced Filters Card) -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <span class="fw-bold text-dark"><i class="bi bi-funnel-fill text-primary me-2"></i>فیلترهای پیشرفته و جستجو</span>
        <?php if (!empty($search) || $categoryId > 0 || $brandId > 0 || $minPrice > 0 || $maxPrice > 0 || $stockStatus !== 'all' || $sortBy !== 'newest'): ?>
            <a href="products.php" class="btn btn-outline-danger btn-sm">
                <i class="bi bi-x-circle me-1"></i> پاکسازی فیلترها
            </a>
        <?php endif; ?>
    </div>
    <div class="card-body p-4">
        <form method="GET" action="products.php" id="filterForm">
            <div class="row g-3">
                <!-- جستجوی متنی -->
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">جستجو (نام کالا، پارت نامبر یا کد فنی)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-search"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="مثال: لنت، پژو، شمع..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <!-- فیلتر دسته‌بندی -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">دسته‌بندی</label>
                    <select name="category_id" class="form-select">
                        <option value="">-- همه دسته‌ها --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $categoryId === $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- فیلتر برند -->
                <div class="col-12 col-sm-6 col-md-4">
                    <label class="form-label small fw-semibold text-secondary">برند / شرکت سازنده</label>
                    <select name="brand_id" class="form-select">
                        <option value="">-- همه برندها --</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= $brand['id'] ?>" <?= $brandId === $brand['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($brand['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- حداقل قیمت فروش -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">حداقل قیمت فروش (تومان)</label>
                    <input type="text" name="min_price" class="form-control currency-input" placeholder="از مبلغ..." value="<?= $minPrice > 0 ? number_format($minPrice) : '' ?>">
                </div>

                <!-- حداکثر قیمت فروش -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">حداکثر قیمت فروش (تومان)</label>
                    <input type="text" name="max_price" class="form-control currency-input" placeholder="تا مبلغ..." value="<?= $maxPrice > 0 ? number_format($maxPrice) : '' ?>">
                </div>

                <!-- وضعیت موجودی -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">وضعیت موجودی انبار</label>
                    <select name="stock_status" class="form-select">
                        <option value="all" <?= $stockStatus === 'all' ? 'selected' : '' ?>>همه وضعیت‌ها</option>
                        <option value="available" <?= $stockStatus === 'available' ? 'selected' : '' ?>>کالاهای موجود (> ۰)</option>
                        <option value="low" <?= $stockStatus === 'low' ? 'selected' : '' ?>>کمبود موجودی (نیاز به سفارش)</option>
                        <option value="empty" <?= $stockStatus === 'empty' ? 'selected' : '' ?>>کالاهای اتمام موجودی (۰)</option>
                    </select>
                </div>

                <!-- مرتب‌سازی بر اساس -->
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small fw-semibold text-secondary">ترتیب نمایش</label>
                    <select name="sort_by" class="form-select">
                        <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>جدیدترین ثبت شده</option>
                        <option value="oldest" <?= $sortBy === 'oldest' ? 'selected' : '' ?>>قدیمی‌ترین</option>
                        <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>گران‌ترین قیمت فروش</option>
                        <option value="price_asc" <?= $sortBy === 'price_asc' ? 'selected' : '' ?>>ارزان‌ترین قیمت فروش</option>
                        <option value="stock_desc" <?= $sortBy === 'stock_desc' ? 'selected' : '' ?>>بیشترین موجودی</option>
                        <option value="stock_asc" <?= $sortBy === 'stock_asc' ? 'selected' : '' ?>>کمترین موجودی</option>
                        <option value="name_asc" <?= $sortBy === 'name_asc' ? 'selected' : '' ?>>نام کالا (الفبا)</option>
                    </select>
                </div>

                <div class="col-12 d-flex justify-content-end gap-2 pt-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-funnel me-1"></i> اعمال فیلترها
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- نوار خلاصه آمار فیلتر -->
<div class="d-flex flex-wrap justify-content-between align-items-center bg-light p-3 rounded-3 border mb-3 gap-2">
    <div class="d-flex align-items-center gap-3">
        <span><i class="bi bi-check2-all text-success me-1"></i>تعداد یافت شده: <strong><?= tr_num($totalFilteredCount) ?></strong> کالا</span>
        <span class="text-secondary">|</span>
        <span><i class="bi bi-layers text-primary me-1"></i>مجموع موجودی: <strong><?= tr_num($totalFilteredStock) ?></strong> عدد</span>
    </div>
    <div>
        <span>ارزش کل فروش اقلام نمایش داده شده: <strong class="text-success fs-6"><?= formatToman($totalFilteredValue) ?></strong></span>
    </div>
</div>

<!-- جدول محصولات -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>نام قطعه و شناسه</th>
                        <th>دسته‌بندی</th>
                        <th>برند سازنده</th>
                        <th>قیمت خرید</th>
                        <th>قیمت فروش</th>
                        <th>موجودی</th>
                        <th class="text-center" style="width: 140px;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-search fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                هیچ کالایی مطابق با فیلترهای انتخابی یافت نشد.
                                <div class="mt-2">
                                    <a href="products.php" class="btn btn-sm btn-outline-primary">نمایش همه کالاها</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $idx = 1; foreach ($products as $p): ?>
                            <tr>
                                <td class="text-muted fw-bold"><?= tr_num($idx++) ?></td>
                                <td>
                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($p['name']) ?></div>
                                    <?php if (!empty($p['code'])): ?>
                                        <span class="badge bg-light text-secondary border font-monospace mt-1">کد: <?= htmlspecialchars($p['code']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($p['category_name'])): ?>
                                        <a href="categories.php?cat_id=<?= $p['category_id'] ?>" class="text-decoration-none">
                                            <span class="badge rounded-pill text-white px-2 py-1" style="background-color: <?= htmlspecialchars($p['category_color'] ?: '#0284c7') ?>;">
                                                <i class="bi bi-folder-fill me-1"></i><?= htmlspecialchars($p['category_name']) ?>
                                            </span>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">بدون دسته</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-dark border px-2 py-1">
                                        <?= htmlspecialchars($p['brand_name'] ?: 'نامشخص') ?>
                                    </span>
                                </td>
                                <td class="price-buy">
                                    <?= formatToman($p['purchase_price']) ?>
                                </td>
                                <td class="price-sell fw-bold">
                                    <?= formatToman($p['sell_price']) ?>
                                </td>
                                <td>
                                    <?php if ($p['stock'] <= 0): ?>
                                        <span class="badge stock-badge-empty rounded-pill px-3 py-1">
                                            <i class="bi bi-x-circle me-1"></i>ناموجود (۰)
                                        </span>
                                    <?php elseif ($p['stock'] <= $p['min_stock']): ?>
                                        <span class="badge stock-badge-medium rounded-pill px-3 py-1">
                                            <i class="bi bi-exclamation-triangle me-1"></i><?= tr_num($p['stock']) ?> عدد (کمبود)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge stock-badge-high rounded-pill px-3 py-1">
                                            <i class="bi bi-check2 me-1"></i><?= tr_num($p['stock']) ?> عدد
                                        </span>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
