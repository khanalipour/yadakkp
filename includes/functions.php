<?php
/**
 * Helper Functions
 * یدک کی پی - توابع کمکی سیستم انبارداری
 */

require_once __DIR__ . '/jdf.php';

/**
 * فرمت‌بندی مبالغ به تومان همراه با جداکننده سه رقمی
 */
function formatToman($amount, $showUnit = true, $persianDigits = true) {
    $amount = (int) $amount;
    $formatted = number_format($amount);
    if ($persianDigits) {
        $formatted = tr_num($formatted, 'fa');
    }
    return $showUnit ? $formatted . ' تومان' : $formatted;
}

/**
 * پاکسازی ورودی‌های کاربر برای امنیت
 */
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
}

/**
 * دریافت آی‌پی کلاینت
 */
function getClientIp() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            return trim($ips[0]);
        }
    }
    return '127.0.0.1';
}

/**
 * تشخیص نام دستگاه و مرورگر از User-Agent با دقت بالا
 */
function parseUserAgent($userAgent = null) {
    if ($userAgent === null) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    if (empty($userAgent)) {
        return 'دستگاه محلی (مستقیم)';
    }

    $platform = 'کامپیوتر / لپ‌تاپ';
    $deviceType = '';

    // تشخیص سیستم عامل و نوع دستگاه
    if (preg_match('/iphone/i', $userAgent)) {
        $platform = 'گوشی اپل (iPhone)';
        $deviceType = 'موبایل';
    } elseif (preg_match('/ipad/i', $userAgent)) {
        $platform = 'تبلت اپل (iPad)';
        $deviceType = 'تبلت';
    } elseif (preg_match('/android/i', $userAgent)) {
        if (preg_match('/mobile/i', $userAgent)) {
            $platform = 'گوشی اندروید (Android)';
            $deviceType = 'موبایل';
        } else {
            $platform = 'تبلت اندروید (Android Tab)';
            $deviceType = 'تبلت';
        }
    } elseif (preg_match('/windows nt 10/i', $userAgent)) {
        $platform = 'ویندوز (Windows 10/11)';
        $deviceType = 'رایانه';
    } elseif (preg_match('/windows nt 6\.3/i', $userAgent) || preg_match('/windows nt 6\.2/i', $userAgent)) {
        $platform = 'ویندوز (Windows 8)';
        $deviceType = 'رایانه';
    } elseif (preg_match('/windows nt 6\.1/i', $userAgent)) {
        $platform = 'ویندوز (Windows 7)';
        $deviceType = 'رایانه';
    } elseif (preg_match('/macintosh|mac os x/i', $userAgent)) {
        $platform = 'مک‌بوک (macOS)';
        $deviceType = 'رایانه';
    } elseif (preg_match('/linux/i', $userAgent)) {
        $platform = 'لینوکس (Linux)';
        $deviceType = 'رایانه';
    }

    // تشخیص مرورگر
    $browser = 'مرورگر وب';
    if (preg_match('/edg\/([0-9\.]+)/i', $userAgent, $m)) {
        $browser = 'مایکروسافت اج';
    } elseif (preg_match('/opr\/([0-9\.]+)|opera/i', $userAgent, $m)) {
        $browser = 'اوپرا (Opera)';
    } elseif (preg_match('/samsungbrowser\/([0-9\.]+)/i', $userAgent, $m)) {
        $browser = 'مرورگر سامسونگ';
    } elseif (preg_match('/chrome\/([0-9]+)/i', $userAgent, $m)) {
        $browser = 'گوگل کروم (نسخه ' . tr_num($m[1]) . ')';
    } elseif (preg_match('/firefox\/([0-9]+)/i', $userAgent, $m)) {
        $browser = 'فایرفاکس (نسخه ' . tr_num($m[1]) . ')';
    } elseif (preg_match('/version\/([0-9]+).*safari/i', $userAgent, $m)) {
        $browser = 'سافاری اپل (نسخه ' . tr_num($m[1]) . ')';
    }

    return "{$platform} • {$browser}";
}

/**
 * تنظیم پیام فلش (Toast/Alert)
 */
function setFlash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message,
    ];
}

/**
 * دریافت و پاک کردن پیام فلش
 */
function getFlash() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * تبدیل تاریخ میلادی دیتابیس به شمسی خوانا
 */
function toShamsiDate($datetimeStr, $withTime = true) {
    if (empty($datetimeStr)) {
        return '-';
    }
    $timestamp = strtotime($datetimeStr);
    if (!$timestamp) {
        return '-';
    }
    $format = $withTime ? 'Y/m/d ساعت H:i' : 'Y/m/d';
    return jdate($format, $timestamp, '', 'Asia/Tehran', 'fa');
}

/**
 * محاسبه زمان گذشته به صورت خوانا (مثلاً چند دقیقه پیش)
 */
function timeElapsedString($datetime) {
    $now = new DateTime('now', new DateTimeZone('Asia/Tehran'));
    $ago = new DateTime($datetime, new DateTimeZone('Asia/Tehran'));
    $diff = $now->diff($ago);

    if ($diff->d > 0) {
        return tr_num($diff->d) . ' روز پیش';
    }
    if ($diff->h > 0) {
        return tr_num($diff->h) . ' ساعت پیش';
    }
    if ($diff->i > 0) {
        return tr_num($diff->i) . ' دقیقه پیش';
    }
    return 'لحظاتی پیش';
}

/**
 * دریافت یک مقدار از جدول تنظیمات
 */
function getSetting($key, $default = null) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT value FROM settings WHERE key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['value'] : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * ذخیره یا به‌روزرسانی مقدار در جدول تنظیمات
 */
function setSetting($key, $value) {
    try {
        $db = getDB();
        $driver = getDBDriver();
        if ($driver === 'mysql') {
            $stmt = $db->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
            return $stmt->execute([$key, $value, $value]);
        } else {
            $stmt = $db->prepare("INSERT INTO settings (key, value) VALUES (?, ?) ON CONFLICT(key) DO UPDATE SET value = ?");
            return $stmt->execute([$key, $value, $value]);
        }
    } catch (Exception $e) {
        return false;
    }
}

