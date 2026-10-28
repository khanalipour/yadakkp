-- ==========================================================
-- دیتابیس سیستم انبارداری فروشگاه لوازم یدکی «یدک کی پی» (YadakKp)
-- سازگار با MySQL 5.7+ و MySQL 8.0+ و MariaDB
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ساخت دیتابیس (در صورت تمایل)
-- CREATE DATABASE IF NOT EXISTS `yadakkp_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_persian_ci;
-- USE `yadakkp_db`;

-- --------------------------------------------------------
-- جدول دسته‌بندی‌ها (پوشه‌ها)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `description` text DEFAULT NULL,
  `color` varchar(50) DEFAULT '#0284c7',
  `icon` varchar(50) DEFAULT 'folder',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- مقادیر اولیه دسته‌بندی قطعات خودرو
INSERT INTO `categories` (`id`, `name`, `description`, `color`, `icon`, `created_at`) VALUES
(1, 'قطعات موتوری', 'سرسیلندر، تسمه تایم، واشر، پیستون و سوپاپ', '#0284c7', 'cpu', NOW()),
(2, 'جلوبندی و سیستم تعلیق', 'کمک‌فنر، طبق، سیبک، جعبه‌فرمان و میل موج‌گیر', '#16a34a', 'disc', NOW()),
(3, 'برقی و روشنایی', 'شمع، وایر، کوئل، دینام، استارت و لامپ‌ها', '#eab308', 'zap', NOW()),
(4, 'ترمز و ایمنی', 'لنت ترمز، دیسک چرخ، بوستر و شیلنگ‌های ترمز', '#dc2626', 'shield-alert', NOW()),
(5, 'لوازم مصرفی و فیلترها', 'فیلتر روغن، فیلتر هوا، روغن موتور و ضدیخ', '#8b5cf6', 'droplet', NOW()),
(6, 'بدنه و تزئینات', 'سپر، آینه‌بغل، چراغ‌های عقب و جلو، جلوپنجره', '#06b6d4', 'layers', NOW());

-- --------------------------------------------------------
-- جدول برندهای قطعات
-- --------------------------------------------------------
DROP TABLE IF EXISTS `brands`;
CREATE TABLE `brands` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- مقادیر اولیه برندها
INSERT INTO `brands` (`id`, `name`, `country`, `created_at`) VALUES
(1, 'ایساکو (ISACO)', 'ایران', NOW()),
(2, 'سایپا یدک', 'ایران', NOW()),
(3, 'بوش (Bosch)', 'آلمان', NOW()),
(4, 'عظام (Ezam)', 'ایران', NOW()),
(5, 'کروز (Crouse)', 'ایران', NOW()),
(6, 'والئو (Valeo)', 'فرانسه', NOW()),
(7, 'رادیاتور ایران', 'ایران', NOW()),
(8, 'سرکان (Serkan)', 'ایران', NOW()),
(9, 'دنسو (Denso)', 'ژاپن', NOW());

-- --------------------------------------------------------
-- جدول محصولات و قطعات انبار
-- --------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `brand_id` int(11) DEFAULT NULL,
  `purchase_price` bigint(20) DEFAULT 0,
  `sell_price` bigint(20) DEFAULT 0,
  `stock` int(11) DEFAULT 0,
  `min_stock` int(11) DEFAULT 5,
  `description` text DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cat` (`category_id`),
  KEY `idx_brand` (`brand_id`),
  KEY `idx_del` (`is_deleted`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- محصولات نمونه قطعات خودرو
INSERT INTO `products` (`name`, `code`, `category_id`, `brand_id`, `purchase_price`, `sell_price`, `stock`, `min_stock`, `description`) VALUES
('لنت ترمز جلو پژو ۲۰۶ تیپ ۵', 'LP-206-T5', 4, 3, 420000, 580000, 24, 6, 'لنت سرامیکی با گیرایی بالا بدون صدا و سوت'),
('تسمه تایم پژو ۴۰۵ و سمند', 'TB-405-114', 1, 1, 350000, 490000, 18, 5, 'تسمه تایم استاندارد شرکتی ایساکو ۱۱۴ دندانه'),
('شمع موتور سوزنی ایریدیوم پایه کوتاه', 'SP-DEN-IK20', 3, 9, 180000, 260000, 40, 10, 'جرقه زنی دقیق، کاهش مصرف سوخت'),
('کمک‌فنر جلو پراید چپ/راست', 'SH-PRD-FR', 2, 4, 750000, 980000, 12, 4, 'گازی و روغنی با ضمانت ۶ ماهه'),
('فیلتر روغن پژو ۲۰۶ و پارس TU5', 'OF-TU5-SER', 5, 8, 55000, 85000, 65, 15, 'کاغذ فیلتر استاندارد با فیلتراسیون ذرات معلق'),
('دیسک و صفحه کلاچ پژو ۴۰۵ والئو سبز', 'CL-405-VAL', 1, 6, 2800000, 3650000, 8, 3, 'کلاچ نرم و شتاب عالی اصلی پری دمپر'),
('کوئل دوبل زیمنس پراید و تیبا', 'IGN-PRD-CR', 3, 5, 620000, 840000, 15, 4, 'تولید شرکت کروز، تست شده و با دوام'),
('دیسک چرخ جلو سمند و پارس', 'DB-SMD-EZ', 4, 4, 520000, 710000, 14, 4, 'آلیاژ کربن بالا ضد لرزش و تاب');

-- --------------------------------------------------------
-- جدول نشست‌ها و دستگاه‌های فعال
-- --------------------------------------------------------
DROP TABLE IF EXISTS `active_sessions`;
CREATE TABLE `active_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_token` varchar(128) NOT NULL,
  `username` varchar(100) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text NOT NULL,
  `device_name` varchar(255) DEFAULT NULL,
  `login_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_activity` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_session_token` (`session_token`),
  KEY `idx_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

-- --------------------------------------------------------
-- جدول تنظیمات سیستم (نام کاربری و کلمه عبور)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key` varchar(100) NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_persian_ci;

SET FOREIGN_KEY_CHECKS = 1;
