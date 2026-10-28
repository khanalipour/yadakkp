<?php
/**
 * Database Connection & Initialization
 * یدک کی پی - اتصال به پایگاه داده با پشتیبانی از MySQL و فال‌بک خودکار
 */

require_once __DIR__ . '/config.php';

$pdo = null;
$dbDriver = 'mysql';
$dbNotice = '';

function getDB() {
    global $pdo, $dbDriver, $dbNotice;

    if ($pdo !== null) {
        return $pdo;
    }

    // تلاش برای اتصال به MySQL
    $mysqlError = '';
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $dbDriver = 'mysql';
    } catch (PDOException $e) {
        $mysqlError = $e->getMessage();

        // اگر دیتابیس وجود نداشت، سعی می‌کنیم بسازیمش (برای محیط لوکال)
        if (strpos($e->getMessage(), 'Unknown database') !== false) {
            try {
                $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=" . DB_CHARSET;
                $tempPdo = new PDO($rootDsn, DB_USER, DB_PASS);
                $tempPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci");
                
                // اتصال مجدد پس از ایجاد دیتابیس
                $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $dbDriver = 'mysql';
            } catch (Exception $ex) {
                // ادامه برای بررسی فال‌بک
            }
        }

        // اگر اتصال به MySQL ناموفق بود
        if ($pdo === null) {
            // تنها در صورتی از SQLite استفاده کن که اکستنشن pdo_sqlite روی سرور نصب و فعال باشد
            if (defined('DB_ALLOW_SQLITE_FALLBACK') && DB_ALLOW_SQLITE_FALLBACK && extension_loaded('pdo_sqlite')) {
                try {
                    $dir = dirname(SQLITE_FILE_PATH);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0777, true);
                    }
                    $pdo = new PDO("sqlite:" . SQLITE_FILE_PATH, null, null, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]);
                    $dbDriver = 'sqlite';
                    $dbNotice = 'در حال حاضر از پایگاه داده محلی موقت (SQLite) استفاده می‌شود. برای اتصال به MySQL، تنظیمات config/config.php را بررسی فرمایید.';
                } catch (Exception $sqle) {
                    $pdo = null;
                }
            }
            
            if ($pdo === null) {
                die("
                <div style='direction:rtl;text-align:right;font-family:tahoma,sans-serif;max-width:650px;margin:50px auto;padding:25px;background:#fff1f2;border:1px solid #fecdd3;border-radius:14px;color:#9f1239;box-shadow:0 10px 25px rgba(0,0,0,0.06);line-height:1.8;'>
                    <h3 style='margin-top:0;color:#e11d48;'>⚠️ خطا در اتصال به پایگاه داده MySQL هاست</h3>
                    <p>سیستم نتوانست با مشخصات فعلی به دیتابیس متصل شود. علت خطا:</p>
                    <div style='background:#fff;padding:12px;border-radius:8px;border:1px solid #fda4af;font-family:monospace;direction:ltr;text-align:left;color:#be123c;margin-bottom:15px;word-break:break-all;'>
                        " . htmlspecialchars($mysqlError) . "
                    </div>
                    <h4 style='margin-bottom:8px;color:#9f1239;'>چگونه مشکل را حل کنید؟</h4>
                    <ol style='padding-right:20px;margin-bottom:0;'>
                        <li>وارد فایل <code>config/config.php</code> در هاست شوید.</li>
                        <li>مقادیر <code>DB_NAME</code>، <code>DB_USER</code> و <code>DB_PASS</code> را دقیقاً طبق دیتابیسی که در کنترل‌پنل هاست (cPanel یا DirectAdmin) ساختید وارد کنید.</li>
                        <li><b>دقت فرمایید:</b> در هاست‌های اشتراکی، نام دیتابیس و نام کاربر دارای پیشوند نام کاربری هاست هستند (مثلاً: <code>yadakkpi_anbar</code>).</li>
                    </ol>
                </div>");
            }
        }
    }

    // مقداردهی و بررسی وجود جداول
    initDatabaseTables($pdo, $dbDriver);

    return $pdo;
}

function getDBDriver() {
    global $dbDriver;
    return $dbDriver;
}

function getDBNotice() {
    global $dbNotice;
    return $dbNotice;
}

function initDatabaseTables(PDO $db, string $driver) {
    if ($driver === 'mysql') {
        // ساخت جداول برای MySQL
        $db->exec("
            CREATE TABLE IF NOT EXISTS `categories` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(191) NOT NULL,
                `description` TEXT NULL,
                `color` VARCHAR(50) DEFAULT '#0d6efd',
                `icon` VARCHAR(50) DEFAULT 'folder',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `brands` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(191) NOT NULL,
                `country` VARCHAR(100) NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `products` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(255) NOT NULL,
                `code` VARCHAR(100) NULL,
                `category_id` INT NULL,
                `brand_id` INT NULL,
                `purchase_price` BIGINT DEFAULT 0,
                `sell_price` BIGINT DEFAULT 0,
                `stock` INT DEFAULT 0,
                `min_stock` INT DEFAULT 5,
                `description` TEXT NULL,
                `is_deleted` TINYINT(1) DEFAULT 0,
                `deleted_at` DATETIME NULL,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX (`category_id`),
                INDEX (`brand_id`),
                INDEX (`is_deleted`),
                INDEX (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `active_sessions` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `session_token` VARCHAR(128) NOT NULL UNIQUE,
                `username` VARCHAR(100) NOT NULL,
                `ip_address` VARCHAR(45) NOT NULL,
                `user_agent` TEXT NOT NULL,
                `device_name` VARCHAR(255) NULL,
                `login_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `last_activity` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `expires_at` DATETIME NOT NULL,
                INDEX (`session_token`),
                INDEX (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `settings` (
                `key` VARCHAR(100) PRIMARY KEY,
                `value` TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;
        ");
    } else {
        // ساخت جداول برای SQLite
        $db->exec("
            CREATE TABLE IF NOT EXISTS `categories` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` TEXT NOT NULL,
                `description` TEXT,
                `color` TEXT DEFAULT '#0d6efd',
                `icon` TEXT DEFAULT 'folder',
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `brands` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` TEXT NOT NULL,
                `country` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `products` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `name` TEXT NOT NULL,
                `code` TEXT,
                `category_id` INTEGER,
                `brand_id` INTEGER,
                `purchase_price` INTEGER DEFAULT 0,
                `sell_price` INTEGER DEFAULT 0,
                `stock` INTEGER DEFAULT 0,
                `min_stock` INTEGER DEFAULT 5,
                `description` TEXT,
                `is_deleted` INTEGER DEFAULT 0,
                `deleted_at` DATETIME,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `active_sessions` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `session_token` TEXT NOT NULL UNIQUE,
                `username` TEXT NOT NULL,
                `ip_address` TEXT NOT NULL,
                `user_agent` TEXT NOT NULL,
                `device_name` TEXT,
                `login_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `last_activity` DATETIME DEFAULT CURRENT_TIMESTAMP,
                `expires_at` DATETIME NOT NULL
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS `settings` (
                `key` TEXT PRIMARY KEY,
                `value` TEXT NOT NULL
            );
        ");
    }

    // ثبت مقادیر پیش‌فرض برای دسته‌ها و برندها در صورت خالی بودن
    seedDefaultData($db);
}

function seedDefaultData(PDO $db) {
    // بررسی دسته‌بندی‌ها
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM categories");
    $row = $stmt->fetch();
    if ($row && (int)$row['cnt'] === 0) {
        $defaultCategories = [
            ['قطعات موتوری', 'سرسیلندر، تسمه تایم، واشر، پیستون و سوپاپ', '#0284c7', 'cpu'],
            ['جلوبندی و سیستم تعلیق', 'کمک‌فنر، طبق، سیبک، جعبه‌فرمان و میل موج‌گیر', '#16a34a', 'disc'],
            ['برقی و روشنایی', 'شمع، وایر، کوئل، دینام، استارت و لامپ‌ها', '#eab308', 'zap'],
            ['ترمز و ایمنی', 'لنت ترمز، دیسک چرخ، بوستر و شیلنگ‌های ترمز', '#dc2626', 'shield-alert'],
            ['لوازم مصرفی و فیلترها', 'فیلتر روغن، فیلتر هوا، روغن موتور و ضدیخ', '#8b5cf6', 'droplet'],
            ['بدنه و تزئینات', 'سپر، آینه‌بغل، چراغ‌های عقب و جلو، جلوپنجره', '#06b6d4', 'layers'],
        ];
        $insertCat = $db->prepare("INSERT INTO categories (name, description, color, icon) VALUES (?, ?, ?, ?)");
        foreach ($defaultCategories as $cat) {
            $insertCat->execute($cat);
        }
    }

    // بررسی برندها
    $stmtB = $db->query("SELECT COUNT(*) as cnt FROM brands");
    $rowB = $stmtB->fetch();
    if ($rowB && (int)$rowB['cnt'] === 0) {
        $defaultBrands = [
            ['ایساکو (ISACO)', 'ایران'],
            ['سایپا یدک', 'ایران'],
            ['بوش (Bosch)', 'آلمان'],
            ['عظام (Ezam)', 'ایران'],
            ['کروز (Crouse)', 'ایران'],
            ['والئو (Valeo)', 'فرانسه'],
            ['رادیاتور ایران', 'ایران'],
            ['سرکان (Serkan)', 'ایران'],
            ['دنسو (Denso)', 'ژاپن'],
        ];
        $insertBrand = $db->prepare("INSERT INTO brands (name, country) VALUES (?, ?)");
        foreach ($defaultBrands as $brand) {
            $insertBrand->execute($brand);
        }
    }

    // ثبت چند محصول اولیه برای نمونه کاربری در صورت خالی بودن
    $stmtP = $db->query("SELECT COUNT(*) as cnt FROM products");
    $rowP = $stmtP->fetch();
    if ($rowP && (int)$rowP['cnt'] === 0) {
        $sampleProducts = [
            ['لنت ترمز جلو پژو ۲۰۶ تیپ ۵', 'LP-206-T5', 4, 3, 420000, 580000, 24, 6, 'لنت سرامیکی با گیرایی بالا بدون صدا و سوت'],
            ['تسمه تایم پژو ۴۰۵ و سمند', 'TB-405-114', 1, 1, 350000, 490000, 18, 5, 'تسمه تایم استاندارد شرکتی ایساکو ۱۱۴ دندانه'],
            ['شمع موتور سوزنی ایریدیوم پایه کوتاه', 'SP-DEN-IK20', 3, 9, 180000, 260000, 40, 10, 'جرقه زنی دقیق، کاهش مصرف سوخت'],
            ['کمک‌فنر جلو پراید چپ/راست', 'SH-PRD-FR', 2, 4, 750000, 980000, 12, 4, 'گازی و روغنی با ضمانت ۶ ماهه'],
            ['فیلتر روغن پژو ۲۰۶ و پارس TU5', 'OF-TU5-SER', 5, 8, 55000, 85000, 65, 15, 'کاغذ فیلتر استاندارد با فیلتراسیون ذرات معلق'],
            ['دیسک و صفحه کلاچ پژو ۴۰۵ والئو سبز', 'CL-405-VAL', 1, 6, 2800000, 3650000, 8, 3, 'کلاچ نرم و شتاب عالی اصلی پری دمپر'],
            ['کوئل دوبل زیمنس پراید و تیبا', 'IGN-PRD-CR', 3, 5, 620000, 840000, 15, 4, 'تولید شرکت کروز، تست شده و با دوام'],
            ['دیسک چرخ جلو سمند و پارس', 'DB-SMD-EZ', 4, 4, 520000, 710000, 14, 4, 'آلیاژ کربن بالا ضد لرزش و تاب'],
        ];

        $insertProd = $db->prepare("INSERT INTO products (name, code, category_id, brand_id, purchase_price, sell_price, stock, min_stock, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($sampleProducts as $p) {
            $insertProd->execute($p);
        }
    }
}
