<?php
/**
 * Add New Product
 * یدک کی پی - ثبت محصول و قطعه یدکی جدید در انبار
 */

$pageTitle = 'ثبت محصول جدید';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$brands = $db->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();

$errors = [];
$preselectedCatId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $categoryId = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $brandId = !empty($_POST['brand_id']) ? (int)$_POST['brand_id'] : null;
    $purchasePrice = (int)str_replace(',', '', $_POST['purchase_price'] ?? '0');
    $sellPrice = (int)str_replace(',', '', $_POST['sell_price'] ?? '0');
    $stock = (int)($_POST['stock'] ?? 0);
    $minStock = !empty($_POST['min_stock']) ? (int)$_POST['min_stock'] : 5;
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $errors[] = 'وارد کردن نام محصول الزامی است.';
    }
    if ($purchasePrice < 0 || $sellPrice < 0) {
        $errors[] = 'قیمت خرید یا فروش نمی‌تواند منفی باشد.';
    }
    if ($stock < 0) {
        $errors[] = 'تعداد موجودی نمی‌تواند منفی باشد.';
    }

    if (empty($errors)) {
        $sql = "INSERT INTO products (name, code, category_id, brand_id, purchase_price, sell_price, stock, min_stock, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([$name, $code, $categoryId, $brandId, $purchasePrice, $sellPrice, $stock, $minStock, $description]);

        setFlash('success', "محصول «{$name}» با موفقیت در انبار ثبت گردید.");
        if ($categoryId) {
            header("Location: categories.php?cat_id={$categoryId}");
        } else {
            header("Location: products.php");
        }
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-9 col-xl-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-plus-circle text-primary me-2"></i>ثبت محصول جدید در انبار</h3>
                <p class="text-muted small mb-0">مشخصات قطعه یدکی را با دقت تکمیل کنید. قیمت‌ها به تومان محاسبه می‌شوند.</p>
            </div>
            <a href="products.php" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-right me-1"></i> انصراف و بازگشت
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm rounded-3 mb-4">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="product-add.php" autocomplete="off">
                    <div class="row g-3">
                        <!-- نام قطعه -->
                        <div class="col-12 col-md-8">
                            <label class="form-label fw-semibold">نام محصول / قطعه یدکی <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control form-control-lg" placeholder="مثال: لنت ترمز جلو پژو ۲۰۶ تیپ ۵" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required autofocus>
                        </div>

                        <!-- کد فنی یا شناسه -->
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">کد فنی / پارت نامبر (اختیاری)</label>
                            <input type="text" name="code" class="form-control form-control-lg font-monospace" placeholder="مثال: LP-206-T5" value="<?= htmlspecialchars($_POST['code'] ?? '') ?>">
                        </div>

                        <!-- دسته‌بندی -->
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">دسته‌بندی قطعه</label>
                                <a href="categories.php" class="text-decoration-none small text-primary"><i class="bi bi-folder-plus me-1"></i>تعریف دسته جدید</a>
                            </div>
                            <select name="category_id" class="form-select form-select-lg">
                                <option value="">-- انتخاب دسته‌بندی --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= (isset($_POST['category_id']) && (int)$_POST['category_id'] === $cat['id']) || ($preselectedCatId === $cat['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- برند قطعه -->
                        <div class="col-12 col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold mb-0">برند / شرکت تولیدکننده</label>
                                <a href="brands.php" class="text-decoration-none small text-primary"><i class="bi bi-tag me-1"></i>تعریف برند جدید</a>
                            </div>
                            <select name="brand_id" class="form-select form-select-lg">
                                <option value="">-- انتخاب برند از لیست --</option>
                                <?php foreach ($brands as $brand): ?>
                                    <option value="<?= $brand['id'] ?>" <?= (isset($_POST['brand_id']) && (int)$_POST['brand_id'] === $brand['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($brand['name']) ?><?= !empty($brand['country']) ? " ({$brand['country']})" : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- قیمت خرید -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold text-secondary">قیمت خرید (تومان) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <input type="text" name="purchase_price" class="form-control currency-input text-start" placeholder="مثال: 350,000" value="<?= htmlspecialchars($_POST['purchase_price'] ?? '') ?>" required>
                                <span class="input-group-text text-muted">تومان</span>
                            </div>
                        </div>

                        <!-- قیمت فروش -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold text-success">قیمت فروش (تومان) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <input type="text" name="sell_price" class="form-control currency-input text-start fw-bold text-success" placeholder="مثال: 480,000" value="<?= htmlspecialchars($_POST['sell_price'] ?? '') ?>" required>
                                <span class="input-group-text text-muted">تومان</span>
                            </div>
                        </div>

                        <!-- تعداد موجودی انبار -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">تعداد موجودی انبار <span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <input type="number" min="0" name="stock" class="form-control" placeholder="مثال: 20" value="<?= htmlspecialchars($_POST['stock'] ?? '1') ?>" required>
                                <span class="input-group-text text-muted">عدد / دست</span>
                            </div>
                        </div>

                        <!-- حداقل هشدار موجودی -->
                        <div class="col-12 col-md-6">
                            <label class="form-label fw-semibold">حداقل هشدار موجودی (نقطه سفارش)</label>
                            <div class="input-group input-group-lg">
                                <input type="number" min="0" name="min_stock" class="form-control" placeholder="پیش‌فرض: 5" value="<?= htmlspecialchars($_POST['min_stock'] ?? '5') ?>">
                                <span class="input-group-text text-muted">عدد</span>
                            </div>
                        </div>

                        <!-- توضیحات -->
                        <div class="col-12">
                            <label class="form-label fw-semibold">توضیحات و مشخصات تکمیلی</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="توضیحات فنی، کاربرد خودرو، گارانتی یا یادداشت‌های انبار..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="pt-4 mt-4 border-top d-flex justify-content-end gap-2">
                        <a href="products.php" class="btn btn-secondary px-4">انصراف</a>
                        <button type="submit" class="btn btn-primary px-5 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> ثبت و ذخیره کالا در انبار
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
