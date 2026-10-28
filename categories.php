<?php
/**
 * Categories & Folder View Management
 * یدک کی پی - نمایش پوشه‌ای دسته‌ها و افزایش قیمت گروهی
 */

$pageTitle = 'محصولات بر اساس دسته‌بندی';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

// ایجاد دسته جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color = trim($_POST['color'] ?? '#0284c7');
    $icon = trim($_POST['icon'] ?? 'folder');

    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO categories (name, description, color, icon) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $description, $color, $icon]);
        setFlash('success', "دسته‌بندی «{$name}» با موفقیت افزوده شد.");
        header('Location: categories.php');
        exit;
    } else {
        setFlash('danger', 'لطفاً نام دسته‌بندی را وارد نمایید.');
    }
}

// ویرایش دسته
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $catId = (int)$_POST['category_id'];
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $color = trim($_POST['color'] ?? '#0284c7');

    if ($catId > 0 && !empty($name)) {
        $stmt = $db->prepare("UPDATE categories SET name = ?, description = ?, color = ? WHERE id = ?");
        $stmt->execute([$name, $description, $color, $catId]);
        setFlash('success', "مشخصات دسته «{$name}» به‌روزرسانی گردید.");
        header("Location: categories.php?cat_id={$catId}");
        exit;
    }
}

// حذف دسته
if (isset($_GET['delete_cat'])) {
    $deleteId = (int)$_GET['delete_cat'];
    if ($deleteId > 0) {
        $pStmt = $db->prepare("SELECT COUNT(*) as cnt FROM products WHERE category_id = ? AND is_deleted = 0");
        $pStmt->execute([$deleteId]);
        $prodCount = (int)$pStmt->fetch()['cnt'];

        if ($prodCount > 0) {
            setFlash('danger', "امکان حذف این دسته‌بندی وجود ندارد، زیرا دارای {$prodCount} کالای فعال است.");
            header("Location: categories.php?cat_id={$deleteId}");
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$deleteId]);
            setFlash('success', 'دسته‌بندی مورد نظر با موفقیت حذف گردید.');
            header('Location: categories.php');
        }
        exit;
    }
}

// عملیات افزایش قیمت گروهی بر روی محصولات این دسته‌بندی
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_price_increase'])) {
    $catId = (int)$_POST['target_category_id'];
    $targetPriceType = $_POST['price_type'] ?? 'sell'; // 'sell', 'buy', 'both'
    $increaseMode = $_POST['increase_mode'] ?? 'percent'; // 'percent', 'amount'
    $percentValue = (float)($_POST['percent_value'] ?? 0);
    $amountValue = (int)str_replace(',', '', $_POST['amount_value'] ?? '0');

    if ($catId > 0 && ($percentValue > 0 || $amountValue > 0)) {
        // خواندن محصولات فعال این دسته
        $stmt = $db->prepare("SELECT id, purchase_price, sell_price FROM products WHERE category_id = ? AND is_deleted = 0");
        $stmt->execute([$catId]);
        $prods = $stmt->fetchAll();

        $updateCount = 0;
        $upStmt = $db->prepare("UPDATE products SET purchase_price = ?, sell_price = ?, updated_at = " . (getDBDriver() === 'mysql' ? "NOW()" : "datetime('now', 'localtime')") . " WHERE id = ?");

        foreach ($prods as $p) {
            $newBuy = (int)$p['purchase_price'];
            $newSell = (int)$p['sell_price'];

            // افزایش بر روی قیمت فروش
            if ($targetPriceType === 'sell' || $targetPriceType === 'both') {
                if ($increaseMode === 'percent' && $percentValue > 0) {
                    $newSell += (int)round($newSell * ($percentValue / 100));
                } elseif ($increaseMode === 'amount' && $amountValue > 0) {
                    $newSell += $amountValue;
                }
            }

            // افزایش بر روی قیمت خرید
            if ($targetPriceType === 'buy' || $targetPriceType === 'both') {
                if ($increaseMode === 'percent' && $percentValue > 0) {
                    $newBuy += (int)round($newBuy * ($percentValue / 100));
                } elseif ($increaseMode === 'amount' && $amountValue > 0) {
                    $newBuy += $amountValue;
                }
            }

            $upStmt->execute([$newBuy, $newSell, $p['id']]);
            $updateCount++;
        }

        setFlash('success', "قیمت تعداد {$updateCount} کالای فعال این دسته‌بندی با موفقیت افزایش یافت.");
        header("Location: categories.php?cat_id={$catId}");
        exit;
    } else {
        setFlash('warning', 'لطفاً درصد یا مقدار معتبر جهت افزایش قیمت وارد فرمایید.');
    }
}

// بررسی اینکه آیا دسته خاصی باز شده است یا خیر
$activeCatId = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$activeCategory = null;
$categoryProducts = [];

if ($activeCatId > 0) {
    $cStmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $cStmt->execute([$activeCatId]);
    $activeCategory = $cStmt->fetch();

    if ($activeCategory) {
        $pStmt = $db->prepare("
            SELECT p.*, b.name as brand_name 
            FROM products p 
            LEFT JOIN brands b ON p.brand_id = b.id 
            WHERE p.category_id = ? AND p.is_deleted = 0 
            ORDER BY p.name ASC
        ");
        $pStmt->execute([$activeCatId]);
        $categoryProducts = $pStmt->fetchAll();
    }
}

// دریافت لیست تمام دسته‌ها برای نمایش پوشه‌ای
$categories = $db->query("
    SELECT c.*, 
           COUNT(p.id) as product_count, 
           COALESCE(SUM(p.stock), 0) as total_stock,
           COALESCE(SUM(p.sell_price * p.stock), 0) as total_value
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.is_deleted = 0 
    GROUP BY c.id 
    ORDER BY c.name ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($activeCategory): ?>
    <!-- نمایش محصولات داخل یک پوشه / دسته مشخص -->
    <div class="mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-light p-2 px-3 rounded-3 border">
                <li class="breadcrumb-item"><a href="categories.php" class="text-decoration-none"><i class="bi bi-folder me-1"></i>همه پوشه‌ها</a></li>
                <li class="breadcrumb-item active fw-bold text-dark" aria-current="page"><?= htmlspecialchars($activeCategory['name']) ?></li>
            </ol>
        </nav>

        <div class="card shadow-sm border-0 mb-4" style="border-right: 5px solid <?= htmlspecialchars($activeCategory['color']) ?> !important;">
            <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <span class="p-2 rounded-3 text-white" style="background-color: <?= htmlspecialchars($activeCategory['color']) ?>;">
                            <i class="bi bi-folder2-open fs-4"></i>
                        </span>
                        <div>
                            <h3 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($activeCategory['name']) ?></h3>
                            <p class="text-muted small mb-0"><?= htmlspecialchars($activeCategory['description'] ?: 'بدون توضیحات') ?></p>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- دکمه خروجی اکسل و JSON این دسته -->
                    <div class="dropdown">
                        <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-file-earmark-arrow-down me-1"></i> خروجی این دسته
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li>
                                <a class="dropdown-item py-2" href="export.php?format=excel&category_id=<?= $activeCategory['id'] ?>">
                                    <i class="bi bi-file-earmark-excel text-success me-2"></i>فایل حرفه‌ای اکسل (.xls)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2" href="export.php?format=json&category_id=<?= $activeCategory['id'] ?>">
                                    <i class="bi bi-filetype-json text-primary me-2"></i>فایل JSON این دسته
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item py-2 text-secondary" href="export.php?format=csv&category_id=<?= $activeCategory['id'] ?>">
                                    <i class="bi bi-filetype-csv me-2"></i>فایل CSV این دسته
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- دکمه افزایش قیمت محصولات این دسته -->
                    <button type="button" class="btn btn-warning fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#bulkPriceModal">
                        <i class="bi bi-graph-up-arrow me-1"></i> افزایش قیمت محصولات این دسته
                    </button>

                    <a href="product-add.php?category_id=<?= $activeCategory['id'] ?>" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-1"></i> افزودن کالا به این دسته
                    </a>

                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li>
                                <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editCatModal">
                                    <i class="bi bi-pencil-square me-2 text-primary"></i>ویرایش مشخصات دسته
                                </button>
                            </li>
                            <li>
                                <a class="dropdown-item text-danger" href="categories.php?delete_cat=<?= $activeCategory['id'] ?>" onclick="return confirm('آیا از حذف این دسته‌بندی مطمئن هستید؟');">
                                    <i class="bi bi-trash me-2"></i>حذف دسته
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- جدول محصولات این دسته -->
        <div class="table-container mb-4">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-bold">
                    <i class="bi bi-box-seam text-primary me-2"></i>لیست کالاهای موجود در این پوشه (<?= tr_num(count($categoryProducts)) ?> کالا)
                </span>
                <a href="categories.php" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-right me-1"></i> بازگشت به لیست پوشه‌ها
                </a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ردیف</th>
                            <th>نام قطعه و شناسه</th>
                            <th>برند</th>
                            <th>قیمت خرید</th>
                            <th>قیمت فروش</th>
                            <th>موجودی</th>
                            <th class="text-center" style="width: 110px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categoryProducts)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                    هیچ محصولی در این پوشه ثبت نشده است.
                                    <div class="mt-2">
                                        <a href="product-add.php?category_id=<?= $activeCategory['id'] ?>" class="btn btn-sm btn-primary">
                                            ثبت اولین کالا در این دسته
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $idx = 1; foreach ($categoryProducts as $p): ?>
                                <tr>
                                    <td class="text-muted fw-bold"><?= tr_num($idx++) ?></td>
                                    <td>
                                        <div class="fw-bold fs-6"><?= htmlspecialchars($p['name']) ?></div>
                                        <?php if (!empty($p['code'])): ?>
                                            <span class="badge bg-light text-secondary border font-monospace mt-1"><?= htmlspecialchars($p['code']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary-subtle text-body border">
                                            <?= htmlspecialchars($p['brand_name'] ?: 'متفرقه') ?>
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
                                            <span class="stock-pill stock-pill-empty">
                                                <span class="stock-dot"></span>ناموجود (۰)
                                            </span>
                                        <?php elseif ($p['stock'] <= $p['min_stock']): ?>
                                            <span class="stock-pill stock-pill-low">
                                                <span class="stock-dot"></span><?= tr_num($p['stock']) ?> عدد (کمبود)
                                            </span>
                                        <?php else: ?>
                                            <span class="stock-pill stock-pill-high">
                                                <span class="stock-dot"></span><?= tr_num($p['stock']) ?> عدد
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <a href="product-edit.php?id=<?= $p['id'] ?>" class="table-action-btn table-action-edit" title="ویرایش و تغییر قیمت">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <button type="button" class="table-action-btn table-action-delete" onclick="confirmDeleteProduct(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')" title="انتقال به زباله‌دان">
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

    <!-- مدال افزایش قیمت دسته‌ای محصولات این دسته -->
    <div class="modal fade" id="bulkPriceModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="categories.php?cat_id=<?= $activeCategory['id'] ?>">
                    <input type="hidden" name="target_category_id" value="<?= $activeCategory['id'] ?>">
                    <div class="modal-header border-bottom bg-warning bg-opacity-10">
                        <h5 class="modal-title fw-bold text-dark">
                            <i class="bi bi-graph-up-arrow text-warning me-2"></i>افزایش قیمت دسته‌ای محصولات
                        </h5>
                        <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="alert alert-info py-2 small mb-3">
                            این تغییر روی تمامی <strong><?= tr_num(count($categoryProducts)) ?></strong> محصول فعال دسته <strong>«<?= htmlspecialchars($activeCategory['name']) ?>»</strong> اعمال خواهد شد.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">کدام قیمت افزایش یابد؟</label>
                            <select name="price_type" class="form-select">
                                <option value="sell" selected>فقط قیمت فروش (پیش‌نهادی)</option>
                                <option value="buy">فقط قیمت خرید</option>
                                <option value="both">هر دو (هم قیمت خرید و هم قیمت فروش)</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">نوع افزایش قیمت</label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="increase_mode" id="mode_percent" value="percent" checked onchange="toggleIncreaseInputs()">
                                    <label class="form-check-label fw-medium" for="mode_percent">
                                        بر اساس درصد (%)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="increase_mode" id="mode_amount" value="amount" onchange="toggleIncreaseInputs()">
                                    <label class="form-check-label fw-medium" for="mode_amount">
                                        بر اساس مبلغ ثابت (تومان)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3" id="wrapper_percent">
                            <label class="form-label fw-semibold">درصد افزایش قیمت</label>
                            <div class="input-group">
                                <input type="number" step="0.1" name="percent_value" id="bulk_percent_input" class="form-control" placeholder="مثال: 15 یا 20">
                                <span class="input-group-text">درصد %</span>
                            </div>
                            <div class="form-text">مثال: با وارد کردن ۲۰، قیمت تمام کالاها ۲۰٪ بالا می‌رود.</div>
                        </div>

                        <div class="mb-3 d-none" id="wrapper_amount">
                            <label class="form-label fw-semibold">مبلغ افزایش به ازای هر کالا (تومان)</label>
                            <div class="input-group">
                                <input type="text" name="amount_value" id="bulk_amount_input" class="form-control currency-input" placeholder="مثال: 50,000">
                                <span class="input-group-text">تومان</span>
                            </div>
                            <div class="form-text">این مبلغ دقیقاً به قیمت فعلی تمام کالاهای این دسته اضافه خواهد شد.</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" name="bulk_price_increase" class="btn btn-warning fw-bold px-4" onclick="return confirm('آیا از اعمال افزایش قیمت روی تمامی محصولات این دسته مطمئن هستید؟');">
                            <i class="bi bi-check2-circle me-1"></i> اعمال افزایش قیمت
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- مدال ویرایش دسته -->
    <div class="modal fade" id="editCatModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="categories.php">
                    <input type="hidden" name="category_id" value="<?= $activeCategory['id'] ?>">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold">ویرایش دسته‌بندی</h5>
                        <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">نام دسته‌بندی</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($activeCategory['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">توضیحات</label>
                            <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($activeCategory['description'] ?? '') ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">رنگ تم پوشه</label>
                            <input type="color" name="color" class="form-control form-control-color w-100" value="<?= htmlspecialchars($activeCategory['color']) ?>">
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" name="edit_category" class="btn btn-primary fw-bold">ذخیره تغییرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function toggleIncreaseInputs() {
        const isPercent = document.getElementById('mode_percent').checked;
        const pWrapper = document.getElementById('wrapper_percent');
        const aWrapper = document.getElementById('wrapper_amount');
        if (isPercent) {
            pWrapper.classList.remove('d-none');
            aWrapper.classList.add('d-none');
        } else {
            pWrapper.classList.add('d-none');
            aWrapper.classList.remove('d-none');
        }
    }
    </script>

<?php else: ?>
    <!-- نمایش پوشه‌ای همه دسته‌بندی‌ها (حالت پیش‌فرض) -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="bi bi-folder2-open text-primary me-2"></i>محصولات بر اساس دسته‌بندی (پوشه‌ای)
            </h3>
            <p class="text-muted small mb-0">
                بر روی هر پوشه کلیک کنید تا تمام محصولات موجود در آن دسته، قطعات مرتبط و امکان افزایش قیمت دسته‌ای باز شود.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-folder-plus me-1"></i> ایجاد دسته‌بندی جدید
            </button>
        </div>
    </div>

    <div class="row g-2 g-md-4">
        <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-md-4 col-xl-3">
                <div class="folder-card p-3 p-md-4 h-100 d-flex flex-column" onclick="window.location.href='categories.php?cat_id=<?= $cat['id'] ?>'">
                    <div class="folder-tab" style="background-color: <?= htmlspecialchars($cat['color']) ?>;"></div>
                    
                    <div class="folder-icon-wrapper" style="background-color: <?= htmlspecialchars($cat['color']) ?>15; color: <?= htmlspecialchars($cat['color']) ?>;">
                        <i class="bi bi-folder-fill"></i>
                    </div>

                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($cat['name']) ?></h5>
                    <p class="text-muted small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        <?= htmlspecialchars($cat['description'] ?: 'شامل اقلام و قطعات یدکی این گروه') ?>
                    </p>

                    <div class="pt-3 border-top mt-auto">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">تعداد اقلام:</span>
                            <span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1">
                                <?= tr_num($cat['product_count']) ?> کالا
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">موجودی کل انبار:</span>
                            <span class="fw-bold text-dark small"><?= tr_num($cat['total_stock']) ?> عدد</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- مدال ایجاد دسته‌بندی جدید -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="categories.php">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title fw-bold"><i class="bi bi-folder-plus text-primary me-2"></i>ایجاد دسته‌بندی جدید</h5>
                        <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">نام دسته‌بندی <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="مثال: سیستم ترمز، تسمه‌جات، سنسورها..." required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">توضیحات کوتاه</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="توضیح در رابطه با قطعات این دسته..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">رنگ پوشه</label>
                            <input type="color" name="color" class="form-control form-control-color w-100" value="#0284c7">
                        </div>
                    </div>
                    <div class="modal-footer border-top bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" name="add_category" class="btn btn-primary fw-bold px-4">ایجاد دسته‌بندی</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
