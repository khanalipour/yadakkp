<?php
/**
 * Edit Product
 * یدک کی پی - ویرایش محصول همراه با امکان افزایش قیمت درصدی و عددی
 */

$pageTitle = 'ویرایش کالا و تغییر قیمت';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND is_deleted = 0");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('danger', 'کالای مورد نظر یافت نشد یا در سطل زباله قرار دارد.');
    header('Location: products.php');
    exit;
}

$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$brands = $db->query("SELECT * FROM brands ORDER BY name ASC")->fetchAll();

$errors = [];

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

    // بررسی ابزار افزایش سریع قیمت در صورت وارد شدن مقادیر
    $increaseTarget = $_POST['increase_target'] ?? ''; // 'sell', 'buy', 'both'
    $incPercent = (float)($_POST['price_increase_percent'] ?? 0);
    $incAmount = (int)str_replace(',', '', $_POST['price_increase_amount'] ?? '0');

    if ($incPercent > 0 || $incAmount > 0) {
        if ($increaseTarget === 'sell' || $increaseTarget === 'both') {
            if ($incPercent > 0) {
                $sellPrice += (int)round($sellPrice * ($incPercent / 100));
            }
            if ($incAmount > 0) {
                $sellPrice += $incAmount;
            }
        }
        if ($increaseTarget === 'buy' || $increaseTarget === 'both') {
            if ($incPercent > 0) {
                $purchasePrice += (int)round($purchasePrice * ($incPercent / 100));
            }
            if ($incAmount > 0) {
                $purchasePrice += $incAmount;
            }
        }
    }

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
        $sql = "UPDATE products SET 
                name = ?, code = ?, category_id = ?, brand_id = ?, 
                purchase_price = ?, sell_price = ?, stock = ?, min_stock = ?, description = ?, 
                updated_at = " . (getDBDriver() === 'mysql' ? "NOW()" : "datetime('now', 'localtime')") . " 
                WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$name, $code, $categoryId, $brandId, $purchasePrice, $sellPrice, $stock, $minStock, $description, $id]);

        setFlash('success', "مشخصات کالای «{$name}» با موفقیت به‌روزرسانی شد.");
        header("Location: products.php");
        exit;
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="bi bi-pencil-square text-primary me-2"></i>ویرایش مشخصات کالا</h3>
                <p class="text-muted small mb-0">کالای در حال ویرایش: <strong class="text-dark">«<?= htmlspecialchars($product['name']) ?>»</strong></p>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="confirmDeleteProduct(<?= $product['id'] ?>, '<?= htmlspecialchars(addslashes($product['name'])) ?>')">
                    <i class="bi bi-trash me-1"></i> انتقال به زباله‌دان
                </button>
                <a href="products.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-right me-1"></i> بازگشت
                </a>
            </div>
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

        <form method="POST" action="product-edit.php?id=<?= $product['id'] ?>" autocomplete="off">
            <div class="row g-4">
                <!-- بخش مشخصات اصلی کالا -->
                <div class="col-12 col-lg-8">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <span class="fw-bold text-dark"><i class="bi bi-card-text text-primary me-2"></i>اطلاعات پایه قطعه</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-12 col-md-8">
                                    <label class="form-label fw-semibold">نام محصول / قطعه یدکی <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($_POST['name'] ?? $product['name']) ?>" required>
                                </div>

                                <div class="col-12 col-md-4">
                                    <label class="form-label fw-semibold">کد فنی / پارت نامبر</label>
                                    <input type="text" name="code" class="form-control font-monospace" value="<?= htmlspecialchars($_POST['code'] ?? $product['code'] ?? '') ?>">
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">دسته‌بندی</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">-- بدون دسته --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= ((int)($_POST['category_id'] ?? $product['category_id'])) === $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">برند / شرکت سازنده</label>
                                    <select name="brand_id" class="form-select">
                                        <option value="">-- بدون برند --</option>
                                        <?php foreach ($brands as $brand): ?>
                                            <option value="<?= $brand['id'] ?>" <?= ((int)($_POST['brand_id'] ?? $product['brand_id'])) === $brand['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($brand['name']) ?><?= !empty($brand['country']) ? " ({$brand['country']})" : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">موجودی فعلی انبار</label>
                                    <div class="input-group">
                                        <input type="number" min="0" name="stock" class="form-control" value="<?= htmlspecialchars($_POST['stock'] ?? $product['stock']) ?>" required>
                                        <span class="input-group-text">عدد</span>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold">حداقل هشدار موجودی</label>
                                    <div class="input-group">
                                        <input type="number" min="0" name="min_stock" class="form-control" value="<?= htmlspecialchars($_POST['min_stock'] ?? $product['min_stock']) ?>">
                                        <span class="input-group-text">عدد</span>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold">توضیحات و مشخصات</label>
                                    <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($_POST['description'] ?? $product['description'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- بخش قیمت‌گذاری و ابزار افزایش قیمت اختصاصی -->
                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3 border-bottom">
                            <span class="fw-bold text-dark"><i class="bi bi-cash-stack text-success me-2"></i>قیمت‌ها (تومان)</span>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary">قیمت خرید فعلی (تومان)</label>
                                <div class="input-group">
                                    <input type="text" name="purchase_price" id="edit_buy_price" class="form-control currency-input" value="<?= number_format($product['purchase_price']) ?>" required>
                                    <span class="input-group-text text-muted">تومان</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-success">قیمت فروش فعلی (تومان)</label>
                                <div class="input-group">
                                    <input type="text" name="sell_price" id="edit_sell_price" class="form-control currency-input fw-bold text-success fs-5" value="<?= number_format($product['sell_price']) ?>" required>
                                    <span class="input-group-text text-muted">تومان</span>
                                </div>
                            </div>

                            <hr>

                            <!-- ابزار سریع افزایش قیمت درصدی و عددی -->
                            <div class="bg-light p-3 rounded-3 border">
                                <h6 class="fw-bold text-primary mb-2 d-flex align-items-center">
                                    <i class="bi bi-graph-up me-1"></i> ابزار سریع افزایش قیمت
                                </h6>
                                <p class="text-muted small mb-3">
                                    می‌توانید همینجا درصد یا عدد دلخواه وارد کنید تا هنگام ذخیره به قیمت اضافه گردد:
                                </p>

                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">اعمال افزایش روی:</label>
                                    <select name="increase_target" id="increase_target" class="form-select form-select-sm">
                                        <option value="sell" selected>فقط قیمت فروش</option>
                                        <option value="both">هم قیمت فروش و هم خرید</option>
                                        <option value="buy">فقط قیمت خرید</option>
                                    </select>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label small fw-semibold">افزایش درصدی (%)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.5" min="0" name="price_increase_percent" id="price_increase_percent" class="form-control" placeholder="مثال: 10 یا 15">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">افزایش عددی مستقیم (تومان)</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" name="price_increase_amount" id="price_increase_amount" class="form-control currency-input" placeholder="مثال: 50,000">
                                        <span class="input-group-text">تومان</span>
                                    </div>
                                </div>

                                <button type="button" class="btn btn-outline-primary btn-sm w-100" id="apply_to_fields_btn">
                                    <i class="bi bi-arrow-repeat me-1"></i> محاسبه و درج در فیلد قیمت
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="d-flex justify-content-end gap-2 p-3 bg-white rounded-3 shadow-sm border">
                        <a href="products.php" class="btn btn-secondary px-4">انصراف</a>
                        <button type="submit" class="btn btn-primary px-5 fw-bold">
                            <i class="bi bi-check2-circle me-1"></i> ذخیره تغییرات کالا
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const applyBtn = document.getElementById('apply_to_fields_btn');
    const sellPriceInput = document.getElementById('edit_sell_price');
    const buyPriceInput = document.getElementById('edit_buy_price');
    const percentInput = document.getElementById('price_increase_percent');
    const amountInput = document.getElementById('price_increase_amount');
    const targetSelect = document.getElementById('increase_target');

    if (applyBtn) {
        applyBtn.addEventListener('click', function() {
            const percent = parseFloat(percentInput.value) || 0;
            const amount = parseFloat((amountInput.value || '').replace(/,/g, '')) || 0;
            const target = targetSelect.value;

            if (percent === 0 && amount === 0) {
                alert('لطفاً ابتدا مقدار درصد یا عدد مورد نظر برای افزایش را وارد کنید.');
                return;
            }

            if (target === 'sell' || target === 'both') {
                let currentSell = parseFloat(sellPriceInput.value.replace(/,/g, '')) || 0;
                if (percent > 0) currentSell += Math.round(currentSell * (percent / 100));
                if (amount > 0) currentSell += amount;
                sellPriceInput.value = Math.round(currentSell).toLocaleString('en-US');
            }

            if (target === 'buy' || target === 'both') {
                let currentBuy = parseFloat(buyPriceInput.value.replace(/,/g, '')) || 0;
                if (percent > 0) currentBuy += Math.round(currentBuy * (percent / 100));
                if (amount > 0) currentBuy += amount;
                buyPriceInput.value = Math.round(currentBuy).toLocaleString('en-US');
            }

            // پاک کردن مقادیر کمکی برای جلوگیری از اعمال مجدد دوبل
            percentInput.value = '';
            amountInput.value = '';

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'قیمت جدید در فیلد درج شد. جهت ثبت نهایی دکمه ذخیره را بزنید.',
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
