<?php
/**
 * Product Delete & Restore Handler
 * یدک کی پی - مدیریت حذف نرم (سطل زباله)، بازیابی و حذف دائمی
 */

require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $db->prepare("SELECT name FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    $name = $product ? $product['name'] : 'کالا';

    if ($action === 'trash') {
        // انتقال به سطل زباله (Soft Delete)
        $now = date('Y-m-d H:i:s');
        $delStmt = $db->prepare("UPDATE products SET is_deleted = 1, deleted_at = ? WHERE id = ?");
        $delStmt->execute([$now, $id]);
        setFlash('warning', "کالای «{$name}» به سطل زباله منتقل شد.");
    } elseif ($action === 'restore') {
        // بازیابی از سطل زباله
        $resStmt = $db->prepare("UPDATE products SET is_deleted = 0, deleted_at = NULL WHERE id = ?");
        $resStmt->execute([$id]);
        setFlash('success', "کالای «{$name}» با موفقیت بازیابی شد و به لیست محصولات فعال بازگشت.");
    } elseif ($action === 'permanent') {
        // حذف دائمی و کامل از دیتابیس
        $permStmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $permStmt->execute([$id]);
        setFlash('danger', "کالای «{$name}» به صورت کامل و دائمی از دیتابیس پاک شد.");
    }
}

// خالی کردن کامل سطل زباله
if ($action === 'empty_trash' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $emptyStmt = $db->prepare("DELETE FROM products WHERE is_deleted = 1");
    $emptyStmt->execute();
    setFlash('info', 'سطل زباله به طور کامل تخلیه گردید.');
}

// بازگشت به صفحه مناسب
$redirect = $_SERVER['HTTP_REFERER'] ?? 'products.php';
// اگر از سطل زباله آمده بود
if ($action === 'restore' || $action === 'permanent' || $action === 'empty_trash') {
    $redirect = 'trash.php';
}
header("Location: {$redirect}");
exit;
