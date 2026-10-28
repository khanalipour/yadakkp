<?php
/**
 * Application Configuration
 * یدک کی پی - سیستم مدیریت و انبارداری لوازم یدکی
 */

// جلوگیری از نمایش خطاهای ناخواسته در هاست یا محیط پروداکشن
error_reporting(E_ALL);
ini_set('display_errors', '1');

// تنظیم منطقه زمانی ایران
date_default_timezone_set('Asia/Tehran');

// اطلاعات عمومی سیستم
define('APP_NAME', 'یدک کی پی (YadakKp)');
define('APP_TITLE', 'سیستم انبارداری و مدیریت قطعات یدک کی پی');
define('APP_VERSION', '1.0.0');

// مسیرهای پایه
define('BASE_PATH', dirname(__DIR__));
define('ASSETS_PATH', 'assets/');

// اطلاعات ورود ثابت (مدیر سیستم)
// می‌توانید این اطلاعات را در صورت تمایل تغییر دهید
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'admin123'); // رمز عبور پیش‌فرض

// مدت اعتبار توکن ورود (۲ روز = ۴۸ ساعت)
define('TOKEN_EXPIRY_SECONDS', 2 * 24 * 60 * 60); // 172800 ثانیه
define('SESSION_COOKIE_NAME', 'yadakkp_auth_token');

// تنظیمات دیتابیس MySQL (مناسب برای ساب‌دامین و هاست)
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'yadakkp_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// در صورتی که دیتابیس MySQL در لوکال فعال نباشد، جهت تست فوری از SQLite استفاده می‌شود
define('DB_ALLOW_SQLITE_FALLBACK', true);
define('SQLITE_FILE_PATH', BASE_PATH . '/data/warehouse.sqlite');
