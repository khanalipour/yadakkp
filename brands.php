<?php
/**
 * Brands Management
 * یدک کی پی - مدیریت برندها و شرکت‌های تولیدکننده قطعات
 */

$pageTitle = 'مدیریت برندها';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

// افزودن برند جدید
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_brand'])) {
    $name = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if (!empty($name)) {
        $stmt = $db->prepare("INSERT INTO brands (name, country) VALUES (?, ?)");
        $stmt->execute([$name, $country]);
        setFlash('success', "برند «{$name}» با موفقیت افزوده شد.");
        header('Location: brands.php');
        exit;
    } else {
        setFlash('danger', 'لطفاً نام برند را وارد نمایید.');
    }
}

// ویرایش برند
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_brand'])) {
    $brandId = (int)$_POST['brand_id'];
    $name = trim($_POST['name'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if ($brandId > 0 && !empty($name)) {
        $stmt = $db->prepare("UPDATE brands SET name = ?, country = ? WHERE id = ?");
        $stmt->execute([$name, $country, $brandId]);
        setFlash('success', "مشخصات برند «{$name}» به‌روزرسانی شد.");
        header('Location: brands.php');
        exit;
    }
}

// حذف برند
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId > 0) {
        // بررسی وابستگی محصولات
        $pStmt = $db->prepare("SELECT COUNT(*) as cnt FROM products WHERE brand_id = ? AND is_deleted = 0");
        $pStmt->execute([$deleteId]);
        $prodCount = (int)$pStmt->fetch()['cnt'];

        if ($prodCount > 0) {
            setFlash('danger', "امکان حذف این برند وجود ندارد، زیرا {$prodCount} کالای فعال به آن متصل هستند.");
        } else {
            $stmt = $db->prepare("DELETE FROM brands WHERE id = ?");
            $stmt->execute([$deleteId]);
            setFlash('success', 'برند مورد نظر با موفقیت حذف شد.');
        }
        header('Location: brands.php');
        exit;
    }
}

// دریافت لیست برندها همراه با تعداد کالاها
$brands = $db->query("
    SELECT b.*, COUNT(p.id) as product_count 
    FROM brands b 
    LEFT JOIN products p ON b.id = p.brand_id AND p.is_deleted = 0 
    GROUP BY b.id 
    ORDER BY b.name ASC
")->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">
            <i class="bi bi-tags text-primary me-2"></i>مدیریت برندهای لوازم یدکی
        </h3>
        <p class="text-muted small mb-0">
            برندها و شرکت‌های تولیدکننده قطعات را تعریف کنید تا در هنگام ثبت یا فیلتر کالا قابل انتخاب باشند.
        </p>
    </div>
    <div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBrandModal">
            <i class="bi bi-plus-lg me-1"></i> افزودن برند جدید
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- جدول برندها -->
    <div class="col-12">
        <div class="table-container mb-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th style="width: 70px;">ردیف</th>
                            <th>نام برند / تولیدکننده</th>
                            <th>کشور / مبدأ ساخت</th>
                            <th>تعداد محصولات فعال</th>
                            <th>تاریخ ثبت</th>
                            <th class="text-center" style="width: 110px;">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($brands)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    هیچ برندی تاکنون ثبت نشده است. از دکمه بالا برند جدید اضافه کنید.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $index = 1; foreach ($brands as $brand): ?>
                                <tr>
                                    <td class="text-muted fw-bold"><?= tr_num($index++) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary-subtle text-primary p-2 rounded-3">
                                                <i class="bi bi-tag-fill"></i>
                                            </span>
                                            <span class="fw-bold fs-6"><?= htmlspecialchars($brand['name']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?= !empty($brand['country']) ? htmlspecialchars($brand['country']) : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3 py-1">
                                            <?= tr_num($brand['product_count']) ?> کالا
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= toShamsiDate($brand['created_at'], false) ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            <button type="button" class="table-action-btn table-action-edit" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editBrandModal" 
                                                data-id="<?= $brand['id'] ?>" 
                                                data-name="<?= htmlspecialchars($brand['name']) ?>" 
                                                data-country="<?= htmlspecialchars($brand['country'] ?? '') ?>"
                                                title="ویرایش">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                            <a href="brands.php?delete=<?= $brand['id'] ?>" 
                                                class="table-action-btn table-action-delete" 
                                                onclick="return confirm('آیا از حذف برند «<?= htmlspecialchars($brand['name']) ?>» مطمئن هستید؟');"
                                                title="حذف">
                                                <i class="bi bi-trash"></i>
                                            </a>
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

<!-- مدال افزودن برند -->
<div class="modal fade" id="addBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="brands.php">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle text-primary me-2"></i>افزودن برند جدید</h5>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">نام برند / شرکت <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: ایساکو، بوش، کروز..." required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">کشور سازنده یا توضیحات</label>
                        <input type="text" name="country" class="form-control" placeholder="مثال: ایران، آلمان، ژاپن...">
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="add_brand" class="btn btn-primary fw-bold px-4">ثبت برند</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مدال ویرایش برند -->
<div class="modal fade" id="editBrandModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="brands.php">
                <input type="hidden" name="brand_id" id="edit_modal_brand_id">
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>ویرایش برند</h5>
                    <button type="button" class="btn-close ms-0" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">نام برند <span class="text-danger">*</span></label>
                        <input type="text" name="name" id="edit_modal_brand_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">کشور سازنده یا توضیحات</label>
                        <input type="text" name="country" id="edit_modal_brand_country" class="form-control">
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="edit_brand" class="btn btn-primary fw-bold px-4">ذخیره تغییرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editBrandModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const country = button.getAttribute('data-country');

            document.getElementById('edit_modal_brand_id').value = id;
            document.getElementById('edit_modal_brand_name').value = name;
            document.getElementById('edit_modal_brand_country').value = country;
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
