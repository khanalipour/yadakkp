<?php
/**
 * Export Products to Excel (CSV) or JSON
 * یدک کی پی - خروجی گرفتن از تمام کالاها یا یک دسته مشخص
 */

require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

$format = $_GET['format'] ?? 'csv'; // 'csv' or 'json'
$catId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$isTemplate = isset($_GET['template']);

// اگر کاربر قالب خالی درخواست کرده باشد
if ($isTemplate) {
    $filename = "yadakkp_template_" . date('Y-m-d');
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
        $sample = [
            [
                'name' => 'لنت ترمز جلو نمونه',
                'code' => 'LP-SAMPLE',
                'category' => 'ترمز و ایمنی',
                'brand' => 'بوش (Bosch)',
                'purchase_price' => 350000,
                'sell_price' => 480000,
                'stock' => 10,
                'min_stock' => 3,
                'description' => 'توضیحات نمونه قطعه'
            ]
        ];
        echo json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
        echo "\xEF\xBB\xBF"; // UTF-8 BOM برای نمایش صحیح حروف فارسی در اکسل
        $out = fopen('php://output', 'w');
        fputcsv($out, ['نام کالا', 'کد فنی', 'دسته بندی', 'برند', 'قیمت خرید (تومان)', 'قیمت فروش (تومان)', 'موجودی', 'حداقل هشدار', 'توضیحات']);
        fputcsv($out, ['لنت ترمز جلو نمونه', 'LP-SAMPLE', 'ترمز و ایمنی', 'بوش (Bosch)', '350000', '480000', '10', '3', 'توضیحات نمونه قطعه']);
        fclose($out);
        exit;
    }
}

// خواندن اطلاعات محصولات
$where = ["p.is_deleted = 0"];
$params = [];
$catNameSlug = 'all';

if ($catId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $catId;

    $cStmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
    $cStmt->execute([$catId]);
    $catRow = $cStmt->fetch();
    if ($catRow) {
        $catNameSlug = preg_replace('/[^\w\x{0600}-\x{06FF}]/u', '_', $catRow['name']);
    }
}

$whereClause = implode(" AND ", $where);
$sql = "
    SELECT p.id, p.name, p.code, c.name as category_name, b.name as brand_name, 
           p.purchase_price, p.sell_price, p.stock, p.min_stock, p.description, p.created_at
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    LEFT JOIN brands b ON p.brand_id = b.id 
    WHERE {$whereClause} 
    ORDER BY p.id ASC
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$dateStr = date('Y-m-d_H-i');
$filename = "yadakkp_products_{$catNameSlug}_{$dateStr}";

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
    echo json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
} else {
    // خروجی اکسل / CSV با BOM
    header('Content-Type: text/csv; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
    
    // ارسال UTF-8 BOM جهت باز شدن بی‌نقص در Microsoft Excel بدون به هم ریختگی فارسی
    echo "\xEF\xBB\xBF";
    
    $out = fopen('php://output', 'w');
    
    // سرفصل‌های ستون‌ها
    fputcsv($out, [
        'شناسه',
        'نام کالا',
        'کد فنی / پارت نامبر',
        'دسته بندی',
        'برند سازنده',
        'قیمت خرید (تومان)',
        'قیمت فروش (تومان)',
        'تعداد موجودی',
        'حداقل هشدار موجودی',
        'توضیحات'
    ]);

    foreach ($products as $p) {
        fputcsv($out, [
            $p['id'],
            $p['name'],
            $p['code'] ?? '',
            $p['category_name'] ?? 'بدون دسته',
            $p['brand_name'] ?? 'بدون برند',
            $p['purchase_price'],
            $p['sell_price'],
            $p['stock'],
            $p['min_stock'],
            $p['description'] ?? ''
        ]);
    }

    fclose($out);
    exit;
}
