<?php
/**
 * Export Products to Professional Excel (.xls), CSV, or JSON
 * یدک کی پی - خروجی اکسل با فرمت‌بندی کامل، راست‌چین و عناوین استاندارد
 */

require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

$format = $_GET['format'] ?? 'excel'; // 'excel' (default .xls), 'csv', 'json'
$catId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$isTemplate = isset($_GET['template']);

// اگر کاربر قالب نمونه جهت ورود اطلاعات درخواست کرده باشد
if ($isTemplate) {
    $filename = "yadakkp_template_" . date('Y-m-d');
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
        $sample = [
            [
                'name' => 'لنت ترمز جلو پژو ۲۰۶',
                'code' => 'LP-206',
                'category' => 'ترمز و ایمنی',
                'brand' => 'بوش (Bosch)',
                'purchase_price' => 350000,
                'sell_price' => 480000,
                'stock' => 15,
                'min_stock' => 5,
                'description' => 'لنت سرامیکی با دوام بالا'
            ]
        ];
        echo json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        // قالب اکسل استاندارد
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
        ?>
        <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            <!--[if gte mso 9]>
            <xml>
             <x:ExcelWorkbook>
              <x:ExcelWorksheets>
               <x:ExcelWorksheet>
                <x:Name>قالب ورود کالا</x:Name>
                <x:WorksheetOptions>
                 <x:DisplayRightToLeft/>
                </x:WorksheetOptions>
               </x:ExcelWorksheet>
              </x:ExcelWorksheets>
             </x:ExcelWorkbook>
            </xml>
            <![endif]-->
            <style>
                th { background-color: #0b1f3a; color: #ffffff; font-weight: bold; padding: 10px; border: 1px solid #000; text-align: center; }
                td { padding: 8px; border: 1px solid #cccccc; text-align: center; }
            </style>
        </head>
        <body dir="rtl">
            <table border="1">
                <thead>
                    <tr>
                        <th>نام کالا</th>
                        <th>کد فنی</th>
                        <th>دسته بندی</th>
                        <th>برند</th>
                        <th>قیمت خرید (تومان)</th>
                        <th>قیمت فروش (تومان)</th>
                        <th>موجودی</th>
                        <th>حداقل هشدار</th>
                        <th>توضیحات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>لنت ترمز جلو پژو ۲۰۶</td>
                        <td>LP-206</td>
                        <td>ترمز و ایمنی</td>
                        <td>بوش (Bosch)</td>
                        <td>350000</td>
                        <td>480000</td>
                        <td>15</td>
                        <td>5</td>
                        <td>لنت سرامیکی با کیفیت عالی</td>
                    </tr>
                </tbody>
            </table>
        </body>
        </html>
        <?php
        exit;
    }
}

// واکشی داده‌های محصولات
$where = ["p.is_deleted = 0"];
$params = [];
$catNameSlug = 'all';
$catTitleText = 'تمامی دسته‌بندی‌ها';

if ($catId > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $catId;

    $cStmt = $db->prepare("SELECT name FROM categories WHERE id = ?");
    $cStmt->execute([$catId]);
    $catRow = $cStmt->fetch();
    if ($catRow) {
        $catTitleText = $catRow['name'];
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

$dateStr = date('Y-m-d');
$filename = "yadakkp_products_{$catNameSlug}_{$dateStr}";

// ۱. خروجی به صورت JSON
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.json\"");
    echo json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// ۲. خروجی استاندارد و شکیل اکسل (.xls) با سرفصل و ردیف‌های منظم راست‌چین
if ($format === 'excel' || $format === 'xls') {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=\"{$filename}.xls\"");
    header("Pragma: no-cache");
    header("Expires: 0");
    ?>
    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <!--[if gte mso 9]>
        <xml>
         <x:ExcelWorkbook>
          <x:ExcelWorksheets>
           <x:ExcelWorksheet>
            <x:Name>اقلام انبار یدک کی پی</x:Name>
            <x:WorksheetOptions>
             <x:DisplayRightToLeft/>
            </x:WorksheetOptions>
           </x:ExcelWorksheet>
          </x:ExcelWorksheets>
         </x:ExcelWorkbook>
        </xml>
        <![endif]-->
        <style>
            body { font-family: Tahoma, Arial, sans-serif; }
            .title-row { background-color: #0b1f3a; color: #ffffff; font-size: 16px; font-weight: bold; text-align: center; height: 40px; }
            .subtitle-row { background-color: #f1f5f9; color: #334155; font-size: 12px; text-align: center; height: 25px; }
            th { background-color: #0284c7; color: #ffffff; font-size: 13px; font-weight: bold; text-align: center; height: 35px; border: 1px solid #0369a1; }
            td { font-size: 12px; text-align: center; height: 30px; border: 1px solid #cbd5e1; }
            .td-name { text-align: right; padding-right: 10px; font-weight: bold; }
            .price { text-align: left; padding-left: 10px; mso-number-format:"\#\,\#\#0"; }
            .stock-ok { background-color: #f0fdf4; color: #166534; font-weight: bold; }
            .stock-low { background-color: #fefce8; color: #854d0e; font-weight: bold; }
            .stock-zero { background-color: #fef2f2; color: #991b1b; font-weight: bold; }
            .even { background-color: #f8fafc; }
        </style>
    </head>
    <body dir="rtl">
        <table border="1" cellpadding="5" cellspacing="0">
            <!-- سربرگ گزارش بالای برگه -->
            <tr>
                <td colspan="10" class="title-row">
                    فروشگاه لوازم یدکی یدک کی پی (YadakKp) - گزارش موجودی و قیمت قطعات
                </td>
            </tr>
            <tr>
                <td colspan="10" class="subtitle-row">
                    دسته‌بندی: <b><?= htmlspecialchars($catTitleText) ?></b> | تاریخ استخراج: <b><?= jdate('Y/m/d ساعت H:i') ?></b> | تعداد اقلام: <b><?= count($products) ?> قلم کالا</b>
                </td>
            </tr>
            <tr><td colspan="10" style="height: 10px; background-color: #ffffff; border: none;"></td></tr>

            <!-- عناوین ستون‌ها در بالای جدول -->
            <thead>
                <tr>
                    <th style="width: 50px;">ردیف</th>
                    <th style="width: 250px;">نام محصول / قطعه یدکی</th>
                    <th style="width: 130px;">کد فنی / پارت نامبر</th>
                    <th style="width: 140px;">دسته‌بندی</th>
                    <th style="width: 120px;">برند سازنده</th>
                    <th style="width: 140px;">قیمت خرید (تومان)</th>
                    <th style="width: 140px;">قیمت فروش (تومان)</th>
                    <th style="width: 90px;">موجودی</th>
                    <th style="width: 90px;">حداقل هشدار</th>
                    <th style="width: 250px;">توضیحات و مشخصات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; height: 50px;">هیچ محصولی در این دسته‌بندی یافت نشد.</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $rowNum = 1;
                    foreach ($products as $p): 
                        $stockClass = 'stock-ok';
                        if ($p['stock'] <= 0) $stockClass = 'stock-zero';
                        elseif ($p['stock'] <= $p['min_stock']) $stockClass = 'stock-low';
                        $evenClass = ($rowNum % 2 === 0) ? 'even' : '';
                    ?>
                        <tr class="<?= $evenClass ?>">
                            <td><?= $rowNum++ ?></td>
                            <td class="td-name"><?= htmlspecialchars($p['name']) ?></td>
                            <td style="mso-number-format:'\@';"><?= htmlspecialchars($p['code'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['category_name'] ?? 'بدون دسته') ?></td>
                            <td><?= htmlspecialchars($p['brand_name'] ?? 'بدون برند') ?></td>
                            <td class="price"><?= number_format($p['purchase_price']) ?></td>
                            <td class="price" style="color: #059669; font-weight: bold;"><?= number_format($p['sell_price']) ?></td>
                            <td class="<?= $stockClass ?>"><?= (int)$p['stock'] ?></td>
                            <td><?= (int)$p['min_stock'] ?></td>
                            <td style="text-align: right;"><?= htmlspecialchars($p['description'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit;
}

// ۳. خروجی استاندارد CSV
header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}.csv\"");
echo "\xEF\xBB\xBF"; // BOM
$out = fopen('php://output', 'w');
fputcsv($out, ['ردیف', 'نام کالا', 'کد فنی', 'دسته‌بندی', 'برند', 'قیمت خرید (تومان)', 'قیمت فروش (تومان)', 'موجودی', 'حداقل هشدار', 'توضیحات']);
$idx = 1;
foreach ($products as $p) {
    fputcsv($out, [
        $idx++,
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
