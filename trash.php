<?php
/**
 * Trash Bin Management
 * یدک کی پی - مدیریت سطل زباله، بازیابی کالاها یا حذف دائمی
 */

$pageTitle = 'سطل زباله محصولات';
require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

$stmt = $db->query("
    SELECT p.*, c.name as category_name, b.name as brand_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE p.is_deleted = 1 
    ORDER BY p.deleted_at DESC
");
$deletedProducts = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold mb-1">
            <i class="bi bi-trash3 text-danger me-2"></i>سطل زباله محصولات
        </h3>
        <p class="text-muted small mb-0">
            کالاهای حذف شده در اینجا نگهداری می‌شوند. می‌توانید آن‌ها را مجدداً بازیابی کنید یا برای همیشه از سیستم پاک نمایید.
        </p>
    </div>
    <div>
        <?php if (!empty($deletedProducts)): ?>
            <form method="POST" action="product-delete.php?action=empty_trash" onsubmit="return confirm('آیا از تخلیه کامل سطل زباله مطمئن هستید؟ تمامی این کالاها برای همیشه پاک خواهند شد.');" class="d-inline">
                <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-radioactive me-1"></i> تخلیه کامل سطل زباله
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 60px;">ردیف</th>
                        <th>نام کالا و شناسه</th>
                        <th>دسته‌بندی</th>
                        <th>برند</th>
                        <th>قیمت فروش</th>
                        <th>زمان حذف</th>
                        <th class="text-center" style="width: 170px;">عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($deletedProducts)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-trash fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                سطل زباله خالی است! هیچ کالایی حذف نشده است.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $idx = 1; foreach ($deletedProducts as $p): ?>
                            <tr>
                                <td class="text-muted fw-bold"><?= tr_num($idx++) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($p['name']) ?></div>
                                    <?php if (!empty($p['code'])): ?>
                                        <span class="badge bg-light text-secondary border font-monospace mt-1"><?= htmlspecialchars($p['code']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border">
                                        <?= htmlspecialchars($p['category_name'] ?: 'بدون دسته') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary border">
                                        <?= htmlspecialchars($p['brand_name'] ?: 'بدون برند') ?>
                                    </span>
                                </td>
                                <td class="fw-bold text-dark">
                                    <?= formatToman($p['sell_price']) ?>
                                </td>
                                <td class="text-muted small">
                                    <?= toShamsiDate($p['deleted_at']) ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="product-delete.php?action=restore&id=<?= $p['id'] ?>" class="btn btn-outline-success" title="بازیابی به انبار">
                                            <i class="bi bi-arrow-counterclockwise me-1"></i> بازیابی
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" onclick="confirmPermanentDelete(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['name'])) ?>')" title="حذف دائمی از دیتابیس">
                                            <i class="bi bi-x-octagon me-1"></i> حذف کامل
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
