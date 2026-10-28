<?php
/**
 * Import Products from Excel (CSV) or JSON
 * یدک کی پی - ورود گروهی محصولات به انبار
 */

require_once __DIR__ . '/includes/auth.php';
requireAuth();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $file = $_FILES['import_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        setFlash('danger', 'خطا در آپلود فایل. لطفاً مجدداً تلاش کنید.');
        header('Location: products.php');
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $importedCount = 0;
    $errors = [];

    // نگاشت دسته‌ها و برندهای موجود جهت بهینه‌سازی
    $catMap = [];
    $allCats = $db->query("SELECT id, name FROM categories")->fetchAll();
    foreach ($allCats as $c) {
        $catMap[trim(mb_strtolower($c['name']))] = $c['id'];
    }

    $brandMap = [];
    $allBrands = $db->query("SELECT id, name FROM brands")->fetchAll();
    foreach ($allBrands as $b) {
        $brandMap[trim(mb_strtolower($b['name']))] = $b['id'];
    }

    $insertCatStmt = $db->prepare("INSERT INTO categories (name, description, color, icon) VALUES (?, ?, ?, ?)");
    $insertBrandStmt = $db->prepare("INSERT INTO brands (name, country) VALUES (?, ?)");
    $insertProdStmt = $db->prepare("
        INSERT INTO products (name, code, category_id, brand_id, purchase_price, sell_price, stock, min_stock, description) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $getOrCreateCategory = function($catName) use (&$catMap, $db, $insertCatStmt) {
        $catName = trim($catName);
        if (empty($catName) || $catName === 'بدون دسته') return null;
        $key = mb_strtolower($catName);
        if (isset($catMap[$key])) return $catMap[$key];

        $colors = ['#0284c7', '#16a34a', '#eab308', '#dc2626', '#8b5cf6', '#06b6d4'];
        $rndColor = $colors[array_rand($colors)];
        $insertCatStmt->execute([$catName, '', $rndColor, 'folder']);
        $newId = (int)$db->lastInsertId();
        $catMap[$key] = $newId;
        return $newId;
    };

    $getOrCreateBrand = function($brandName) use (&$brandMap, $db, $insertBrandStmt) {
        $brandName = trim($brandName);
        if (empty($brandName) || $brandName === 'بدون برند') return null;
        $key = mb_strtolower($brandName);
        if (isset($brandMap[$key])) return $brandMap[$key];

        $insertBrandStmt->execute([$brandName, '']);
        $newId = (int)$db->lastInsertId();
        $brandMap[$key] = $newId;
        return $newId;
    };

    if ($ext === 'json') {
        $content = file_get_contents($file['tmp_name']);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            setFlash('danger', 'فرمت ساختار فایل JSON نامعتبر است.');
            header('Location: products.php');
            exit;
        }

        foreach ($data as $item) {
            $name = trim($item['name'] ?? $item['نام کالا'] ?? '');
            if (empty($name)) continue;

            $code = trim($item['code'] ?? $item['کد فنی'] ?? $item['کد فنی / پارت نامبر'] ?? '');
            $catName = trim($item['category_name'] ?? $item['category'] ?? $item['دسته بندی'] ?? '');
            $brandName = trim($item['brand_name'] ?? $item['brand'] ?? $item['برند'] ?? $item['برند سازنده'] ?? '');
            $buyPrice = (int)str_replace(',', '', (string)($item['purchase_price'] ?? $item['قیمت خرید (تومان)'] ?? 0));
            $sellPrice = (int)str_replace(',', '', (string)($item['sell_price'] ?? $item['قیمت فروش (تومان)'] ?? 0));
            $stock = (int)($item['stock'] ?? $item['تعداد موجودی'] ?? $item['موجودی'] ?? 0);
            $minStock = (int)($item['min_stock'] ?? $item['حداقل هشدار موجودی'] ?? $item['حداقل هشدار'] ?? 5);
            $desc = trim($item['description'] ?? $item['توضیحات'] ?? '');

            $catId = $getOrCreateCategory($catName);
            $brandId = $getOrCreateBrand($brandName);

            $insertProdStmt->execute([$name, $code, $catId, $brandId, $buyPrice, $sellPrice, $stock, $minStock, $desc]);
            $importedCount++;
        }
    } elseif ($ext === 'csv') {
        $handle = fopen($file['tmp_name'], 'r');
        if ($handle !== false) {
            // حذف BOM در صورت وجود
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $headers = fgetcsv($handle);
            if ($headers) {
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) < 2 || empty(trim($row[0] ?? ''))) {
                        // ممکن است ستون اول شناسه باشد، بررسی ستون دوم برای نام
                        if (isset($row[1]) && !empty(trim($row[1]))) {
                            $name = trim($row[1]);
                            $code = trim($row[2] ?? '');
                            $catName = trim($row[3] ?? '');
                            $brandName = trim($row[4] ?? '');
                            $buyPrice = (int)str_replace(',', '', $row[5] ?? '0');
                            $sellPrice = (int)str_replace(',', '', $row[6] ?? '0');
                            $stock = (int)($row[7] ?? 0);
                            $minStock = (int)($row[8] ?? 5);
                            $desc = trim($row[9] ?? '');
                        } else {
                            continue;
                        }
                    } else {
                        // اگر ستون اول نام کالا بود
                        $name = trim($row[0]);
                        $code = trim($row[1] ?? '');
                        $catName = trim($row[2] ?? '');
                        $brandName = trim($row[3] ?? '');
                        $buyPrice = (int)str_replace(',', '', $row[4] ?? '0');
                        $sellPrice = (int)str_replace(',', '', $row[5] ?? '0');
                        $stock = (int)($row[6] ?? 0);
                        $minStock = (int)($row[7] ?? 5);
                        $desc = trim($row[8] ?? '');
                    }

                    if (empty($name) || $name === 'نام کالا' || $name === 'شناسه' || $name === 'ردیف') continue;

                    $catId = $getOrCreateCategory($catName);
                    $brandId = $getOrCreateBrand($brandName);

                    $insertProdStmt->execute([$name, $code, $catId, $brandId, $buyPrice, $sellPrice, $stock, $minStock, $desc]);
                    $importedCount++;
                }
            }
            fclose($handle);
        }
    } elseif ($ext === 'xls') {
        $html = file_get_contents($file['tmp_name']);
        if (preg_match_all('/<tr[^>]*>(.*?)<\/tr>/si', $html, $trMatches)) {
            foreach ($trMatches[1] as $tr) {
                if (preg_match_all('/<td[^>]*>(.*?)<\/td>/si', $tr, $tdMatches)) {
                    $cols = array_map(function($val) {
                        return trim(html_entity_decode(strip_tags($val), ENT_QUOTES, 'UTF-8'));
                    }, $tdMatches[1]);

                    if (count($cols) >= 5) {
                        $firstCol = $cols[0];
                        if (is_numeric($firstCol)) {
                            // ستون اول شماره ردیف است
                            $name = $cols[1] ?? '';
                            $code = $cols[2] ?? '';
                            $catName = $cols[3] ?? '';
                            $brandName = $cols[4] ?? '';
                            $buyPrice = (int)str_replace(',', '', $cols[5] ?? '0');
                            $sellPrice = (int)str_replace(',', '', $cols[6] ?? '0');
                            $stock = (int)($cols[7] ?? 0);
                            $minStock = (int)($cols[8] ?? 5);
                            $desc = $cols[9] ?? '';
                        } else {
                            $name = $cols[0];
                            if ($name === 'نام کالا' || $name === 'ردیف' || strpos($name, 'فروشگاه') !== false) continue;
                            $code = $cols[1] ?? '';
                            $catName = $cols[2] ?? '';
                            $brandName = $cols[3] ?? '';
                            $buyPrice = (int)str_replace(',', '', $cols[4] ?? '0');
                            $sellPrice = (int)str_replace(',', '', $cols[5] ?? '0');
                            $stock = (int)($cols[6] ?? 0);
                            $minStock = (int)($cols[7] ?? 5);
                            $desc = $cols[8] ?? '';
                        }

                        if (empty($name)) continue;

                        $catId = $getOrCreateCategory($catName);
                        $brandId = $getOrCreateBrand($brandName);

                        $insertProdStmt->execute([$name, $code, $catId, $brandId, $buyPrice, $sellPrice, $stock, $minStock, $desc]);
                        $importedCount++;
                    }
                }
            }
        }
    } else {
        setFlash('danger', 'پسوند فایل مجاز نیست. لطفاً فایل اکسل (.xls / .csv) یا .json انتخاب فرمایید.');
        header('Location: products.php');
        exit;
    }

    if ($importedCount > 0) {
        setFlash('success', "تعداد {$importedCount} قلم کالای جدید با موفقیت از فایل وارد انبار شد.");
    } else {
        setFlash('warning', 'هیچ کالایی از فایل وارد نشد. لطفاً از قالب استاندارد اکسل استفاده فرمایید.');
    }

    header('Location: products.php');
    exit;
}

header('Location: products.php');
exit;
